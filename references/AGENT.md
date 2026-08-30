# AGENT.md — Prompt Eksekusi untuk AI Coding Agent (Opencode)
**Project: AturAja.com**

Dokumen ini adalah instruksi kerja untuk AI coding agent (Opencode atau sejenisnya) yang akan mengeksekusi pembangunan aplikasi. Baca `PRD.md` (kebutuhan produk) dan `ARCHITECTURE.md` (desain teknis) sebelum mulai — dokumen ini adalah lapisan **eksekusi**, bukan pengganti keduanya.

---

## 0. Peran & Aturan Main Agent

Kamu adalah senior full-stack engineer yang membangun **AturAja.com**, aplikasi SaaS manajemen keuangan pribadi multi-akun.

**Aturan wajib:**
1. Selalu kerjakan **satu fase per iterasi** (lihat §3). Jangan lompat fase sebelum fase sebelumnya lulus checklist QA-nya.
2. Setiap membuat migration/model baru untuk tabel domain (bukan tabel `users`/`workspaces`/pivot), **wajib** pakai trait `BelongsToWorkspace` — tidak boleh ada tabel finansial tanpa isolasi tenant.
3. Semua nominal uang: kolom `DECIMAL(15,2)`, tidak pernah `float`/`double`.
4. Tulis unit test untuk setiap logika kalkulasi (cash flow, net worth, budget usage%, DTI, savings rate, emergency fund) sebelum menandai fase report selesai.
5. Ikuti Design System di §5 secara konsisten — jangan improvisasi warna/shadow di luar token yang didefinisikan.
6. Setelah setiap fase, jalankan `php artisan test` dan pastikan hijau sebelum lanjut.
7. Jika ada ambiguitas requirement, ambil keputusan paling sederhana yang konsisten dengan `ARCHITECTURE.md`, catat asumsi di commit message — jangan berhenti menunggu klarifikasi kecuali benar-benar blocking.
8. Commit granular per fitur kecil (bukan satu commit raksasa per fase), pesan commit dalam Bahasa Indonesia, format: `feat(transactions): tambah recurring rule generator`.

---

## 1. Tech Stack (wajib diikuti persis)

- Laravel 12 (PHP 8.3+)
- MySQL 8 (Laragon lokal)
- Tailwind CSS 3
- Alpine.js + Livewire 3
- Chart.js untuk semua grafik
- `barryvdh/laravel-dompdf` + `maatwebsite/excel` untuk export

Jangan mengganti stack ini dengan alternatif lain (misal Vue/React penuh, atau database lain) tanpa instruksi eksplisit dari user.

---

## 2. Setup Awal (Fase 0)

```bash
composer create-project laravel/laravel aturaja
cd aturaja
composer require livewire/livewire barryvdh/laravel-dompdf maatwebsite/excel laravel/fortify
npm install -D tailwindcss postcss autoprefixer
npm install alpinejs chart.js
```

Checklist Fase 0:
- [ ] `.env` terkoneksi ke MySQL Laragon (`aturaja` database dibuat)
- [ ] Migration `users`, `workspaces`, `workspace_user`
- [ ] Auth (Fortify) berjalan: register → auto-create workspace pertama → login
- [ ] Middleware `EnsureTenantSelected` aktif di route group `web` (auth)
- [ ] Layout shell `layouts/app.blade.php`: sidebar (collapsible) + topbar sesuai referensi dashboard
- [ ] Trait `BelongsToWorkspace` + `WorkspaceScope` sudah ada dan diuji dengan 1 model dummy

---

## 3. Fase Pengembangan (urutan wajib)

### Fase 1 — Accounts, Transactions, Categories & Tags
- Migration + model: `accounts`, `categories`, `tags`, `transaction_tag`, `transactions`, `recurring_rules`
- CRUD Accounts (4 tipe: cash, bank, ewallet, credit_card) — form kartu kredit punya field `credit_limit`, `billing_date`, `due_date`
- CRUD Transactions (income/expense/transfer) via Livewire component `TransactionForm` + `TransactionTable` (searchable, filter kategori/tag/tanggal/akun)
- Recurring: job `GenerateRecurringTransactions` (scheduled daily) generate transaksi dari `recurring_rules` yang jatuh tempo
- Update saldo `accounts.balance` otomatis via Model Observer setiap transaksi/transfer dibuat/diubah/dihapus (jangan hitung ulang dari nol tiap request — pakai increment/decrement + reconciliation command sebagai fallback)
- Kategori default di-seed saat workspace baru dibuat (`DemoDataSeeder` sebagai referensi daftar kategori)

**QA Fase 1:** transaksi income/expense/transfer akurat mengubah saldo akun; hapus transaksi mengembalikan saldo; filter & search bekerja.

### Fase 2 — Cash Flow Statement & Expense Breakdown
- `Domain/Reports/Actions/CashFlowReport.php`: hitung total income/expense/net per periode (harian/bulanan/tahunan + custom range)
- Chart.js bar chart tren bulanan (component Livewire `DashboardCharts` atau partial khusus) — dua seri warna (mis. New vs Existing / Income vs Expense)
- `ExpenseBreakdownReport.php`: agregat per kategori + ranking Top Expenses
- Doughnut chart + bar chart alternatif, klik kategori → drill-down daftar transaksi

**QA Fase 2:** angka cash flow & breakdown cocok dengan SUM manual di query test; chart re-render animasi saat filter periode berubah.

### Fase 3 — Budgeting vs Actual
- Migration + model `budgets`
- Livewire component `BudgetMatrix`: set limit per kategori per bulan, copy dari bulan sebelumnya
- Kalkulasi realisasi on-the-fly (lihat ARCHITECTURE.md §5.3), progress bar hijau/kuning/merah
- Alert in-app (banner/badge notifikasi) saat kategori ≥ `alert_threshold_percent`

**QA Fase 3:** progress bar berubah warna tepat di ambang 70%/100%; alert muncul konsisten dengan threshold per kategori (bukan hardcode 80%).

### Fase 4 — Net Worth (Assets & Liabilities) + Debt Tracker
- Migration + model `assets`, `liabilities`, `net_worth_snapshots`, `debts`, `debt_payments`
- Form tambah/update Assets (kategori investasi, real estate, kendaraan) & Liabilities (loan, KPR, kredit kendaraan)
- Job `SnapshotMonthlyNetWorth` (scheduled, akhir bulan) simpan snapshot untuk grafik tren
- Halaman Net Worth: kalkulasi live (§5.2 ARCHITECTURE.md) + grafik tren dari snapshot
- Debt Tracker: CRUD `debts` (payable & receivable), catat `debt_payments`, badge reminder jatuh tempo di dashboard

**QA Fase 4:** Net Worth = Aset − Kewajiban selalu konsisten antara halaman Net Worth dan widget dashboard; snapshot job idempotent (tidak dobel jika dijalankan ulang di hari sama).

### Fase 5 — Financial Health Score
- `Domain/Reports/Actions/HealthScoreCalculator.php`: Savings Rate, DTI, Emergency Fund Metric (formula di ARCHITECTURE.md §5.4)
- Widget gauge/skor di dashboard dengan status per rasio (Sehat/Waspada/Perlu Perhatian)
- Rekomendasi teks otomatis berbasis threshold (array rule sederhana, bukan AI/LLM call — deterministik)
- Checkbox "tandai sebagai dana darurat" di form Account

**QA Fase 5:** unit test untuk setiap kombinasi rasio (di bawah/tepat/di atas ideal) menghasilkan status & rekomendasi yang benar.

### Fase 6 — Export & Polish
- `export_jobs` + queue job generate PDF (Dompdf, layout cetak rapi) dan Excel/CSV (Maatwebsite) untuk: Cash Flow, Net Worth, Budget vs Actual, Expense Breakdown, daftar Transaksi
- Tombol "Export CSV" cepat di topbar dashboard (sesuai referensi UI)
- Polish: pastikan semua §5 Design System (hover, active bar, transisi, animasi chart) konsisten di seluruh halaman, bukan cuma dashboard
- Responsive check: sidebar auto-collapse di layar < 1024px, tabel → card-list di mobile
- Aksesibilitas: kontras teks AA, fokus keyboard terlihat di semua elemen interaktif neomorphic

**QA Fase 6:** file export terbuka benar (PDF tidak korup, CSV encoding UTF-8 benar untuk Rupiah/nama kategori), regresi visual dicek di 3 breakpoint (mobile/tablet/desktop).

---

## 4. Struktur Navigasi Sidebar (acuan)

```
MAIN MENU
 - Dashboard
 - Transactions
 - Accounts & Wallets
 - Budgets
 - Reports            (Cash Flow, Net Worth, Expense Breakdown)
 - Debt Tracker
CUSTOMERS  -> ganti label jadi WORKSPACE (khas AturAja, bukan e-commerce)
 - Categories & Tags
 - Financial Health
MANAGEMENT
 - Workspace Settings
 - Export & Reports History
 - Billing & Subscription (placeholder fase lanjutan)
```
> Catatan: referensi dashboard yang diberikan user berasal dari template e-commerce/admin generik (Bag\\UI) — struktur menunya (Products, Channels, Order Management, dst.) **tidak** dipakai apa adanya, hanya pola layout & komponennya yang direplikasi. Isi menu wajib mengikuti domain AturAja.com di atas.

---

## 5. Design System (untuk agent, jangan menyimpang)

### 5.1 Token warna (CSS variables, definisikan di `resources/css/app.css`)
```css
:root {
  --bg:            #EAF3EC;
  --surface:       #F2F8F4;
  --shadow-light:  #FFFFFF;
  --shadow-dark:   #C6D6CB;
  --primary:       #16A34A;
  --primary-dark:  #15803D;
  --primary-soft:  #DCFCE7;
  --text:          #1C2B22;
  --text-muted:    #6B8074;
  --danger:        #DC2626;
  --warning:       #D97706;
  --radius-lg:     20px;
  --radius-md:     14px;
  --ease:          cubic-bezier(.4,0,.2,1);
}
```
Daftarkan sebagai `theme.extend.colors` di `tailwind.config.js` agar bisa dipakai `bg-primary`, `text-muted`, dll.

### 5.2 Komponen wajib (buat sebagai Blade component reusable)
- `<x-neo-card>` — extruded shadow, dipakai KPI card, chart card, table wrapper
- `<x-neo-input>` — inset shadow saat idle, ring hijau saat focus
- `<x-neo-button variant="primary|ghost">` — extruded, tekan → inset sesaat (`active:` state)
- `<x-progress-bar value="" status="green|yellow|red">` — animasi width transition
- `<x-kpi-card>` — angka besar + label + delta indicator (naik/turun) + mini icon chart

### 5.3 Aturan animasi (implementasi via Tailwind transition + Alpine `x-transition`, hindari library berat)
| Elemen | Perilaku |
|---|---|
| Card/tombol hover | `transform: translateY(-2px)` + shadow membesar tipis, 200ms `--ease` |
| Sidebar collapse/expand | Width transition 250ms + label fade (opacity+translateX), ikon tetap center saat collapsed |
| Menu aktif | Bar indikator hijau di sisi kiri item, animasi slide antar item (bukan fade) saat pindah halaman |
| Tab switch (Weekly/Monthly/Yearly) | Pill background slide ke posisi tab aktif, 200ms |
| Chart pertama render | Bar grow dari 0 → nilai (Chart.js `animation.duration: 800`) |
| Chart update data | Transisi nilai lama→baru, bukan re-render instan |
| Modal/dropdown | Scale+fade in dari 95%→100% opacity 0→1, 150ms |

### 5.4 Prinsip layout Dashboard
- Topbar: breadcrumb kiri, search neomorphic-inset di tengah/kanan, ikon notifikasi (badge merah jika ada alert budget/jatuh tempo) + profil kanan
- Baris KPI: 4 card (`Total Pemasukan`, `Total Pengeluaran`, `Net Cash Flow`, `Net Worth`) — bukan copy persis referensi (Total Revenue/Orders/Customers/Conversion), sesuaikan ke domain finansial
- Chart utama besar (Cash Flow Trend, 2/3 lebar) + chart sekunder kecil (Expense Breakdown by Category, 1/3 lebar) berdampingan, sama seperti pola referensi
- Tabel bawah: transaksi terbaru, dengan search + tombol "+ Add Transaction" primary hijau

---

## 6. Definition of Done (per fase & keseluruhan)

Sebuah fase dianggap selesai jika:
1. Semua checklist QA di §3 untuk fase tersebut lulus.
2. `php artisan test` hijau, termasuk test kalkulasi baru untuk fase itu.
3. Tidak ada tabel domain baru tanpa `workspace_id` + trait `BelongsToWorkspace`.
4. Semua komponen baru memakai token/komponen di §5, bukan style ad-hoc.
5. Commit history mencerminkan langkah kerja granular, bukan satu commit besar.

Produk dianggap "v1 selesai" jika Fase 0–6 lulus dan seluruh scope `PRD.md` §3 (kecuali §8 Out of Scope) terimplementasi.
