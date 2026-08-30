# PRD — AturAja.com
**Product Requirements Document**
Versi: 1.0 · Status: Draft untuk Development · Tanggal: 30 Agustus 2026

---

## 1. Ringkasan Produk

**AturAja.com** adalah aplikasi web **manajemen keuangan pribadi (Personal Finance Management)** berbasis **SaaS multi-akun**, yang membantu pengguna mencatat arus kas, mengelola anggaran (budgeting), melacak utang/piutang, dan memahami kesehatan keuangan mereka lewat laporan dan skor otomatis.

**Tagline internal:** *"Atur uangmu, aja."* — kontrol penuh atas pemasukan, pengeluaran, aset, dan kewajiban dalam satu dashboard.

### 1.1 Problem Statement
Sebagian besar individu (khususnya pekerja/profesional muda di Indonesia):
- Tidak punya gambaran utuh Net Worth (aset vs kewajiban) mereka.
- Sulit disiplin anggaran karena tidak ada peringatan real-time saat mendekati limit.
- Mencatat utang/piutang secara manual di catatan terpisah, gampang lupa jatuh tempo.
- Tidak tahu apakah kondisi keuangan mereka "sehat" secara angka (savings rate, DTI, dana darurat).

### 1.2 Solusi
Satu platform terpusat yang menggabungkan **pencatatan transaksi**, **manajemen akun/dompet**, **budgeting**, **pelacakan utang**, dan **skor kesehatan finansial otomatis**, disajikan dalam dashboard yang enak dilihat dan cepat dipahami.

### 1.3 Target Pengguna
- Karyawan/profesional dengan multiple sumber income (gaji + freelance + investasi).
- Pengguna dengan cicilan/kartu kredit/paylater yang ingin kontrol DTI.
- Individu yang ingin mulai serius menabung & berinvestasi tapi butuh visibilitas dulu.

### 1.4 Model Bisnis
SaaS multi-akun (multi-tenant): setiap pengguna mendaftar sebagai akun independen (atau bagian dari sebuah "Ruang Keuangan" / workspace) dengan datanya terisolasi penuh dari pengguna lain. Terbuka untuk model freemium di masa depan (fase lanjutan, di luar cakupan v1).

---

## 2. Tujuan & Metrik Sukses

| Tujuan | Metrik |
|---|---|
| Adopsi awal | Jumlah akun terdaftar & retensi 30 hari |
| Engagement | Rata-rata jumlah transaksi dicatat per user/minggu |
| Nilai produk inti | % user yang mengecek Financial Health Score min. 1x/bulan |
| Kepuasan UX | Waktu rata-rata untuk mencatat 1 transaksi (target < 15 detik) |

---

## 3. Lingkup Fitur (Scope)

### 3.1 Komponen Utama & Input Data

#### A. Pencatatan Arus Kas (Transactions)
- **Pemasukan (Income):** Gaji, Hasil Usaha, Freelance, Passive Income, Investasi (kategori dapat ditambah sendiri).
- **Pengeluaran (Expenses):**
  - Rutin/Wajib: sewa, cicilan, listrik/air/internet.
  - Variabel: makan, hiburan, transportasi.
  - Tak Terduga: dikelompokkan khusus agar mudah dianalisis terpisah dari pola rutin.
- Setiap transaksi punya: tanggal, akun/dompet sumber, kategori, nominal, catatan, tag/label (opsional), lampiran bukti (opsional, upload gambar/PDF), status rekonsiliasi.
- Mendukung transaksi berulang (recurring) — misal gaji bulanan, cicilan bulanan — dengan auto-generate transaksi terjadwal.
- Mendukung transfer antar akun/dompet (tidak dihitung sebagai income/expense, hanya perpindahan saldo).

#### B. Manajemen Akun/Dompet (Accounts & Wallets)
- **Saldo Kas/Tunai**
- **Rekening Bank** (multi-bank)
- **E-Wallet** (Gopay, OVO, ShopeePay, DANA, dll — user bisa tambah custom)
- **Kartu Kredit / Paylater** — dicatat sebagai **Kewajiban (Liabilitas)**, punya field limit, tanggal cetak tagihan, dan tanggal jatuh tempo pembayaran.
- Setiap akun punya saldo real-time yang terupdate otomatis dari transaksi.
- Dapat diarsipkan (bukan dihapus) agar histori tetap utuh.

#### C. Kategori & Label (Categories & Tags)
- Kategori default (income & expense) yang **customizable** — user dapat ubah nama, ikon, warna, atau menambah kategori baru.
- Sub-kategori (2 level) untuk granularitas (misal: Makan → Makan di Luar / Groceries).
- Tag/Label bebas untuk pengelompokan lintas-kategori, contoh: `#LiburanBali`, `#ProjectA`. Satu transaksi bisa punya banyak tag.
- Filter & pencarian transaksi berdasarkan kombinasi kategori + tag + rentang tanggal + akun.

---

### 3.2 Laporan Wajib

#### 1) Laporan Arus Kas (Cash Flow Statement)
- Filter periode: harian, bulanan, tahunan (custom range juga).
- Total Pemasukan vs Total Pengeluaran (ringkasan angka + persentase perubahan periode sebelumnya).
- **Net Cash Flow** = Total Pemasukan − Total Pengeluaran, dengan indikator status (surplus/defisit).
- Grafik tren bulanan (bar/line chart) untuk melihat pola konsumsi sepanjang tahun, dengan breakdown New vs Existing kategori pemasukan (mengikuti pola referensi dashboard).

#### 2) Laporan Neraca Pribadi (Personal Balance Sheet / Net Worth)
- Formula utama:

  **Net Worth = Total Aset − Total Kewajiban**

- **Aset:**
  - Kas/Bank (auto dari saldo akun)
  - Investasi (Saham, Reksa Dana, Emas, Kripto) — dicatat manual dengan nilai terkini yang bisa diupdate berkala
  - Aset Riil (Properti, Kendaraan) — nilai awal + opsi update nilai wajar
- **Kewajiban (Liabilities):**
  - Utang Bank / KPR / Kredit Kendaraan (dengan sisa pokok, tenor, cicilan/bulan)
  - Tagihan Kartu Kredit & Paylater (auto dari akun kartu kredit)
- Grafik tren Net Worth dari waktu ke waktu (snapshot bulanan otomatis).

#### 3) Laporan Anggaran vs Realisasi (Budgeting vs Actuals)
- User set batas anggaran per kategori per bulan (dengan opsi copy dari bulan sebelumnya atau template).
- Progress bar indikator warna:
  - Hijau: < 70% terpakai
  - Kuning: 70–99% terpakai
  - Merah: ≥ 100% (overbudget)
- Peringatan (alert/notifikasi in-app, opsional email) saat mendekati (≥80%) atau melebihi limit.
- Ringkasan total anggaran vs total realisasi per bulan.

#### 4) Laporan Pengeluaran Berdasarkan Kategori (Expense Breakdown)
- Pie Chart / Donut Chart persentase pengeluaran per kategori.
- Bar Chart alternatif untuk perbandingan antar kategori.
- **Top Expenses**: daftar kategori/transaksi pengeluaran terbesar dalam periode, dapat diklik untuk drill-down ke daftar transaksi.

---

### 3.3 Fitur Analisis Ringkas (Nilai Tambah)

#### Financial Health Score
Skor komposit + breakdown 3 rasio:

| Rasio | Formula | Ideal |
|---|---|---|
| Savings Rate | (Total Tabungan/Investasi ÷ Total Pemasukan) × 100% | ≥ 20% |
| Debt-to-Income Ratio (DTI) | (Total Cicilan Utang Bulanan ÷ Total Pemasukan Bulanan) × 100% | ≤ 30% |
| Emergency Fund Metric | Total Dana Darurat ÷ Rata-rata Pengeluaran Bulanan | 3–6 bulan |

- Ditampilkan sebagai gauge/skor visual di dashboard dengan status (Sehat/Waspada/Perlu Perhatian) per rasio.
- Rekomendasi otomatis berbasis threshold (misal: "DTI kamu 42%, di atas ideal — pertimbangkan kurangi cicilan baru").

#### Laporan Utang/Piutang (Debt Tracker)
- Merekam sisa pokok utang per sumber, tanggal jatuh tempo, tenor, dan bunga (opsional).
- Mencatat piutang: uang yang dipinjamkan ke orang lain, status (belum lunas/lunas sebagian/lunas), target pengembalian.
- Reminder jatuh tempo (in-app, badge notifikasi).
- Riwayat pembayaran/pelunasan per item.

#### Ekspor & Rekap Laporan
- Ekspor laporan bulanan ke **PDF** (siap cetak, format rapi) dan **Excel/CSV** (untuk analisis lanjutan).
- Cakupan ekspor: Cash Flow, Net Worth, Budget vs Actual, Expense Breakdown, Daftar Transaksi mentah.
- Tombol "Export CSV" cepat dari dashboard (sesuai referensi UI).

---

## 4. Autentikasi & Multi-Tenancy (SaaS Multi-Akun)

- Login berbasis **SaaS multi-akun**: satu pengguna bisa memiliki lebih dari satu "Ruang Keuangan" (workspace/tenant) — misal: Personal & Bisnis Kecil — dan berpindah (switch) antar workspace tanpa logout.
- Isolasi data penuh per tenant (lihat ARCHITECTURE.md untuk strategi teknis).
- Autentikasi standar: email/password, opsi social login (Google) di fase lanjutan.
- Role dasar per workspace: Owner (fase 1, single-user per workspace); struktur data sudah disiapkan agar Member/Viewer bisa ditambah tanpa migrasi besar (fase lanjutan — shared household finance).

---

## 5. Kebutuhan UI/UX

### 5.1 Layout
- **Sidebar navigasi kiri**, dapat **di-hide/unhide**:
  - Expanded: label + ikon menu.
  - Collapsed: hanya ikon (dengan tooltip saat hover).
  - Sub-grouping menu: Main Menu, Reports, Management (mengikuti pola referensi dashboard).
- **Dashboard utama** meniru pola referensi (Bag\\UI style): topbar breadcrumb + search + notifikasi + profil, 4 KPI card di baris atas, grafik tren utama (besar) + grafik sekunder (kecil, revenue/expense by category), tabel data di bawah dengan search & tombol aksi utama.

### 5.2 Gaya Visual — Neomorphism
- Elemen UI (card, tombol, input) menggunakan efek **soft-UI / neomorphism**: shadow ganda (terang di satu sisi, gelap di sisi lain) di atas background senada agar terlihat "timbul" (extruded) atau "tenggelam" (pressed/inset) — dipakai pada card KPI, tombol utama, toggle switch, dan search bar.
- **Palet warna utama: Putih & Hijau.**
  - Background dasar: off-white kehijauan lembut (bukan putih murni, agar shadow neomorphism terlihat).
  - Aksen hijau untuk elemen aktif, tombol primer, indikator positif, dan grafik.
  - Merah/kuning tetap dipakai terbatas untuk status alert (overbudget, jatuh tempo).

### 5.3 Animasi & Interaksi
- **Hover motions**: tombol & card memberi efek elevasi/scale halus saat hover (transisi 150–250ms, easing standar).
- **Component transitions**: sidebar collapse/expand, modal open/close, dropdown, toggle — semua dengan transisi halus (bukan langsung snap).
- **Active bar/indicator**: menu aktif di sidebar & tab aktif diberi indikator bar animasi (slide/morph saat pindah menu).
- **Bar chart animation**: grafik (cash flow trend, expense breakdown) animasi saat pertama render (grow-in) dan saat data berubah (transisi nilai lama → baru), mengikuti pola batang dua-warna (nilai utama solid, nilai pembanding lebih pudar) seperti pada referensi dashboard.
- Mode gelap/terang: opsional fase lanjutan (fase 1 fokus pada tema terang Neomorphism Putih & Hijau).

---

## 6. Non-Functional Requirements

| Kategori | Requirement |
|---|---|
| Performa | Kalkulasi laporan (cash flow, net worth, budget) < 200ms untuk data 1 tahun transaksi pada Laragon lokal |
| Presisi angka | Semua nominal uang `DECIMAL(15,2)`, tidak memakai float |
| Keamanan | Isolasi data antar tenant wajib di level query (global scope), password hashing (bcrypt/argon2), rate limiting login |
| Skalabilitas | Struktur schema siap untuk >100k transaksi/tenant tanpa perubahan besar (indexing tepat) |
| Aksesibilitas | Kontras warna cukup meski bergaya neomorphism (WCAG AA minimum untuk teks), fokus keyboard terlihat jelas |
| Responsif | Layout adaptif mobile (sidebar auto-collapse di layar kecil, tabel jadi card-list) |
| Lokalitas | Mata uang default IDR (Rupiah), format tanggal & angka Indonesia |

---

## 7. Fase Pengembangan (Roadmap Ringkas)

| Fase | Cakupan |
|---|---|
| Fase 0 | Setup project, auth multi-tenant, struktur sidebar & dashboard shell |
| Fase 1 | Accounts/Wallets + Transactions (CRUD, transfer, recurring) + Categories & Tags |
| Fase 2 | Cash Flow Statement + Expense Breakdown (report & chart) |
| Fase 3 | Budgeting vs Actual (limit, progress bar, alert) |
| Fase 4 | Net Worth (assets & liabilities) + Debt/Receivable Tracker |
| Fase 5 | Financial Health Score + rekomendasi otomatis |
| Fase 6 | Export PDF/Excel/CSV + polish UI/animasi + QA |

Detail teknis tiap fase ada di `ARCHITECTURE.md`, dan prompt eksekusi step-by-step untuk AI coding agent ada di `AGENT.md`.

---

## 8. Out of Scope (v1)
- Integrasi langsung ke rekening bank/e-wallet (open banking API) — pencatatan manual dulu.
- Aplikasi mobile native (fokus web responsive dulu).
- Multi-currency selain IDR.
- Kolaborasi multi-user dalam satu workspace (role Member/Viewer) — schema disiapkan, fitur menyusul.
