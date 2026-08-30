# ARCHITECTURE.md — AturAja.com

Dokumen arsitektur teknis. Pasangan dari `PRD.md` (kebutuhan produk) dan `AGENT.md` (prompt eksekusi build).

---

## 1. Tech Stack

| Layer | Pilihan | Catatan |
|---|---|---|
| Backend Framework | **Laravel 12** (PHP 8.3+) | REST-ish + server-rendered Blade |
| Database | **MySQL 8** via **Laragon** (local dev) | `DECIMAL(15,2)` untuk semua nominal uang |
| Frontend Styling | **Tailwind CSS 3** | Utility-first, custom plugin untuk token Neomorphism |
| Interaktivitas | **Alpine.js** (ringan, cocok Blade) + **Livewire 3** untuk komponen data-heavy (tabel transaksi, form budget inline-edit) | Hindari full SPA/API terpisah di v1 — kurangi kompleksitas |
| Charting | **Chart.js** (bar, line, doughnut) dengan plugin animasi custom | Dipakai untuk Cash Flow Trend, Expense Breakdown, Net Worth trend |
| Auth | **Laravel Fortify/Breeze** (base) + kustomisasi **Teams/Workspace** ala Jetstream untuk multi-tenant | Lihat §3 |
| Export | `barryvdh/laravel-dompdf` (PDF), `maatwebsite/excel` (Excel/CSV) | |
| Queue/Jobs | Laravel Queue (database driver cukup untuk v1) | Untuk generate recurring transaction & snapshot bulanan |
| Testing | Pest / PHPUnit | Unit test untuk kalkulasi (cash flow, DTI, savings rate, net worth) wajib |

---

## 2. High-Level Architecture

```
┌───────────────────────────────────────────────────────────┐
│                        Browser (Client)                     │
│  Blade Views + Tailwind (Neomorphism) + Alpine.js + Chart.js│
│  Livewire components (transactions table, budget matrix)    │
└───────────────────────────┬─────────────────────────────────┘
                              │ HTTP (session-based, CSRF)
┌───────────────────────────▼─────────────────────────────────┐
│                     Laravel Application                      │
│                                                               │
│  Middleware: auth, EnsureTenantSelected (workspace context)  │
│                                                               │
│  Modules (app/Domain/*):                                     │
│   ├─ Auth & Workspace   (register, login, switch workspace)  │
│   ├─ Accounts           (wallets, credit cards)               │
│   ├─ Transactions       (income/expense/transfer, recurring)  │
│   ├─ Categories & Tags                                        │
│   ├─ Budgets            (limits, alerts)                      │
│   ├─ Assets & Liabilities (net worth)                        │
│   ├─ Debts              (payoff tracker, receivables)         │
│   ├─ Reports            (cash flow, breakdown, health score)  │
│   └─ Exports            (PDF/Excel jobs)                      │
│                                                               │
│  Scheduled Jobs: GenerateRecurringTransactions,               │
│                   SnapshotMonthlyNetWorth,                    │
│                   EvaluateBudgetAlerts                        │
└───────────────────────────┬─────────────────────────────────┘
                              │ Eloquent ORM
┌───────────────────────────▼─────────────────────────────────┐
│                       MySQL 8 (Laragon)                      │
│      Semua tabel tenant-scoped via `workspace_id`             │
└───────────────────────────────────────────────────────────────┘
```

---

## 3. Strategi Multi-Tenancy (SaaS Multi-Akun)

**Pendekatan: Single Database, Shared Schema, `workspace_id` sebagai discriminator** (bukan database-per-tenant) — dipilih karena lebih sederhana dioperasikan di skala awal dan cukup aman selama isolasi ditegakkan konsisten di level query.

- Tabel `users` — akun login individu.
- Tabel `workspaces` — "Ruang Keuangan" (tenant). Satu user bisa punya banyak workspace.
- Tabel pivot `workspace_user` — relasi user ↔ workspace + role (`owner`, `member`, `viewer` — hanya `owner` aktif di v1).
- **Semua tabel domain** (accounts, transactions, categories, budgets, assets, liabilities, debts, tags) wajib punya kolom `workspace_id`.
- **Global Scope Laravel** (`WorkspaceScope`) otomatis menambahkan `WHERE workspace_id = ?` ke setiap query model domain — dipasang via trait `BelongsToWorkspace` di setiap model, sehingga tidak mungkin lupa filter tenant secara manual.
- Workspace aktif disimpan di session (`current_workspace_id`) setelah login/switch, divalidasi ulang di middleware `EnsureTenantSelected` di setiap request.
- Setiap request memvalidasi bahwa `current_workspace_id` benar-benar dimiliki user yang login (cegah tenant-hopping via manipulasi session).

```php
// app/Models/Concerns/BelongsToWorkspace.php
trait BelongsToWorkspace
{
    protected static function bootBelongsToWorkspace(): void
    {
        static::addGlobalScope(new WorkspaceScope);
        static::creating(function ($model) {
            $model->workspace_id ??= session('current_workspace_id');
        });
    }
}
```

---

## 4. Skema Database (Inti)

Konvensi: `id` BIGINT UNSIGNED PK, timestamps standar, semua nominal `DECIMAL(15,2)`, `soft_deletes` untuk data finansial (audit trail).

### 4.1 Identity & Tenancy
```
users            (id, name, email, password, ...)
workspaces       (id, name, owner_user_id, currency default 'IDR', created_at)
workspace_user   (id, workspace_id, user_id, role enum[owner,member,viewer])
```

### 4.2 Accounts & Wallets
```
accounts (
  id, workspace_id, name, type enum[cash,bank,ewallet,credit_card],
  balance DECIMAL(15,2) default 0,       -- cache, direkonsiliasi dari transactions
  credit_limit DECIMAL(15,2) null,        -- khusus type=credit_card
  billing_date TINYINT null,              -- tanggal cetak tagihan
  due_date TINYINT null,                  -- tanggal jatuh tempo
  is_archived boolean default false,
  icon, color
)
```

### 4.3 Categories & Tags
```
categories (
  id, workspace_id, parent_id nullable,   -- 2 level (sub-kategori)
  name, type enum[income,expense], icon, color,
  is_default boolean, is_archived boolean
)
tags (id, workspace_id, name, color)
transaction_tag (transaction_id, tag_id)   -- many-to-many
```

### 4.4 Transactions
```
transactions (
  id, workspace_id, account_id, category_id nullable, -- null utk transfer
  type enum[income,expense,transfer],
  amount DECIMAL(15,2),
  transfer_to_account_id nullable,         -- khusus type=transfer
  transaction_date DATE,
  note TEXT nullable,
  attachment_path nullable,
  is_reconciled boolean default false,
  recurring_rule_id nullable FK -> recurring_rules,
  created_at, updated_at, deleted_at
)

recurring_rules (
  id, workspace_id, template_json,         -- account, category, amount, note
  frequency enum[weekly,monthly,yearly],
  interval_count, start_date, end_date nullable,
  next_run_date, is_active
)
```

### 4.5 Budgeting
```
budgets (
  id, workspace_id, period_month DATE,     -- disimpan sbg tanggal 1 bulan tsb (mis. 2026-09-01)
  category_id, limit_amount DECIMAL(15,2),
  alert_threshold_percent TINYINT default 80
)
```
Realisasi dihitung on-the-fly (SUM transactions per category per bulan), bukan disimpan redundan, agar selalu akurat.

### 4.6 Assets & Liabilities (Net Worth)
```
assets (
  id, workspace_id, name,
  category enum[cash_bank,investment_stock,investment_mutual_fund,investment_gold,investment_crypto,real_estate,vehicle,other],
  current_value DECIMAL(15,2),
  linked_account_id nullable,              -- jika category=cash_bank & auto dari accounts
  last_updated_at
)

liabilities (
  id, workspace_id, name,
  category enum[bank_loan,mortgage,vehicle_loan,credit_card,paylater,other],
  linked_account_id nullable,              -- jika category=credit_card & auto dari accounts
  principal_remaining DECIMAL(15,2),
  monthly_installment DECIMAL(15,2) nullable,
  interest_rate DECIMAL(5,2) nullable,
  due_date DATE nullable
)

net_worth_snapshots (
  id, workspace_id, snapshot_date DATE,
  total_assets DECIMAL(15,2), total_liabilities DECIMAL(15,2),
  net_worth DECIMAL(15,2)
)   -- diisi oleh scheduled job bulanan, dasar grafik tren
```

### 4.7 Debt / Receivable Tracker
```
debts (
  id, workspace_id,
  direction enum[payable,receivable],      -- payable = utang saya, receivable = piutang ke orang lain
  counterparty_name,                       -- pemberi/penerima pinjaman
  principal_amount DECIMAL(15,2),
  remaining_amount DECIMAL(15,2),
  due_date DATE nullable,
  status enum[ongoing,partially_paid,paid],
  note TEXT nullable
)

debt_payments (
  id, debt_id, amount DECIMAL(15,2), paid_at DATE, note nullable
)
```

### 4.8 Reports Support
```
-- Tidak ada tabel fisik untuk Cash Flow / Expense Breakdown / Financial Health Score:
-- semua dihitung via query aggregat (lihat §5) agar selalu real-time & konsisten.
```

### 4.9 Export
```
export_jobs (
  id, workspace_id, user_id, type enum[pdf,excel,csv],
  report enum[cash_flow,net_worth,budget_vs_actual,expense_breakdown,transactions],
  period_start, period_end,
  status enum[queued,processing,done,failed],
  file_path nullable, created_at
)
```

---

## 5. Logika Kalkulasi Kunci

### 5.1 Net Cash Flow
```
Total Income   = SUM(transactions.amount WHERE type='income' AND date BETWEEN ...)
Total Expense  = SUM(transactions.amount WHERE type='expense' AND date BETWEEN ...)
Net Cash Flow  = Total Income - Total Expense
```

### 5.2 Net Worth
```
Total Assets      = SUM(assets.current_value) + SUM(accounts.balance WHERE type IN [cash,bank,ewallet])
Total Liabilities = SUM(liabilities.principal_remaining) + SUM(accounts.balance WHERE type='credit_card')
Net Worth         = Total Assets - Total Liabilities
```

### 5.3 Budget vs Actual (per kategori per bulan)
```
Actual = SUM(transactions.amount WHERE category_id=X AND type='expense' AND MONTH(date)=budget.period_month)
Usage% = Actual / budget.limit_amount * 100
Status = green (<70%) | yellow (70-99%) | red (>=100%)
```

### 5.4 Financial Health Score
```
Savings Rate      = (SUM(transfer ke akun/aset investasi + selisih surplus ditabung) / Total Income Bulanan) * 100
DTI               = (SUM(liabilities.monthly_installment) / Total Income Bulanan) * 100
Emergency Fund    = SUM(saldo akun kategori "dana darurat" atau tag khusus) / AVG(pengeluaran 3 bulan terakhir)

Skor komposit = rata-rata tertimbang dari 3 rasio dinormalisasi ke skala 0-100,
                dengan threshold ideal sbg acuan (lihat PRD §3.3).
```
> Catatan implementasi: "dana darurat" diidentifikasi lewat akun/tag yang ditandai user sebagai emergency fund (checkbox di form akun), bukan tebakan otomatis.

---

## 6. Struktur Folder Laravel (ringkas)

```
app/
 ├─ Domain/
 │   ├─ Workspace/{Models,Actions,Policies}
 │   ├─ Accounts/{Models,Actions}
 │   ├─ Transactions/{Models,Actions,Jobs}
 │   ├─ Categories/{Models}
 │   ├─ Budgets/{Models,Actions}
 │   ├─ NetWorth/{Models,Actions,Jobs}
 │   ├─ Debts/{Models,Actions}
 │   ├─ Reports/{Actions}          -- CashFlowReport, ExpenseBreakdownReport, HealthScoreCalculator
 │   └─ Exports/{Jobs,Exports}     -- Maatwebsite Export classes + Dompdf views
 ├─ Http/
 │   ├─ Controllers/{Web}
 │   ├─ Livewire/{TransactionTable, BudgetMatrix, DashboardCharts, SidebarNav}
 │   └─ Middleware/EnsureTenantSelected.php
 └─ Models/Concerns/BelongsToWorkspace.php

resources/
 ├─ views/
 │   ├─ layouts/app.blade.php        -- shell: sidebar + topbar
 │   ├─ dashboard/index.blade.php
 │   ├─ transactions/*
 │   ├─ reports/*
 │   └─ components/                  -- neomorphic-card, kpi-card, progress-bar, sidebar-item
 └─ css/app.css                      -- Tailwind + design tokens neomorphism

database/
 ├─ migrations/
 └─ seeders/DemoDataSeeder.php       -- data contoh utk preview
```

---

## 7. Desain Sistem UI (Design Tokens)

```css
:root {
  --bg:            #EAF3EC;   /* base neomorphic background, off-white kehijauan */
  --surface:       #F2F8F4;
  --shadow-light:  #FFFFFF;
  --shadow-dark:   #C6D6CB;
  --primary:       #16A34A;   /* hijau utama */
  --primary-dark:  #15803D;
  --primary-soft:  #DCFCE7;
  --text:          #1C2B22;
  --text-muted:    #6B8074;
  --danger:        #DC2626;
  --warning:       #D97706;
  --radius:        18px;
}

/* Neomorphic extruded (default card) */
.neo-card {
  background: var(--surface);
  border-radius: var(--radius);
  box-shadow: 8px 8px 16px var(--shadow-dark), -8px -8px 16px var(--shadow-light);
}

/* Neomorphic inset (active/pressed, input fields) */
.neo-inset {
  box-shadow: inset 4px 4px 8px var(--shadow-dark), inset -4px -4px 8px var(--shadow-light);
}
```
Detail lengkap token, komponen, dan aturan animasi ada di `AGENT.md` §Design System (untuk dikonsumsi langsung oleh AI coding agent).

---

## 8. Keamanan

- Isolasi tenant via global scope (§3) + validasi ulang `workspace_id` di setiap Policy (`TransactionPolicy`, `AccountPolicy`, dst).
- CSRF token di semua form (default Laravel).
- Rate limiting login (`throttle:login`, max 5 percobaan/menit).
- File upload (lampiran bukti transaksi) divalidasi tipe & ukuran, disimpan di disk privat (`storage/app/private/{workspace_id}/...`), diakses via signed URL sementara.
- Password hashing bcrypt (default Laravel), 2FA opsional fase lanjutan.
- Audit log ringan (siapa mengubah transaksi apa, kapan) — kolom `updated_by` cukup untuk v1.

---

## 9. Environment & Deployment (Dev Lokal)

- **Laragon** sebagai local dev server (Apache/Nginx + MySQL + PHP 8.3).
- `.env`: `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_DATABASE=aturaja`.
- `php artisan migrate --seed` untuk data demo.
- `npm run dev` (Vite) untuk compile Tailwind + Alpine + Chart.js.
- Queue worker lokal: `php artisan queue:work` (untuk recurring transaction & snapshot job).
- Scheduler: `php artisan schedule:work` (dev) — production nanti via cron.

---

## 10. Keputusan Arsitektur & Alasan (ADR Ringkas)

| Keputusan | Alasan |
|---|---|
| Single DB + `workspace_id` (bukan DB-per-tenant) | Lebih murah dioperasikan & di-maintain di tahap awal SaaS; cukup aman dengan global scope + policy berlapis |
| Livewire untuk komponen data-heavy, bukan full SPA API | Selaras dengan stack yang diminta (Laravel+Tailwind), kurangi kompleksitas build/deploy dua aplikasi terpisah |
| Realisasi budget dihitung on-the-fly, bukan disimpan redundan | Menjamin akurasi—tidak ada risiko data "actual" basi jika transaksi diedit/dihapus setelah fakta |
| Net Worth pakai snapshot bulanan + kalkulasi real-time saat ini | Snapshot untuk grafik tren historis, kalkulasi live untuk angka saat ini yang selalu akurat |
| Emergency fund by tag/flag manual, bukan heuristik otomatis | Menghindari asumsi salah yang bisa menyesatkan skor kesehatan finansial |
