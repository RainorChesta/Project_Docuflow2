# Implementation Plan: Sistem Master Company/Cabang & Document Numbering

Berdasarkan requirement yang dijelaskan, berikut rencana implementasinya:

## 1. Analisis Kebutuhan & Struktur Data

**Entitas utama:**
- **Master Main Office (Company)** — perusahaan induk
- **Master Cabang (Branch)** — turunan dari main office, termasuk branch khusus "Pusat"
- **User** — bisa di-assign ke 1 atau lebih company & 1 branch spesifik, punya role (Admin, Direktur, User biasa, dll), serta atribut nomor telepon & NIP
- **Document Numbering** — nomor dokumen yang mengikuti struktur tertentu

**Aturan bisnis kunci yang perlu ditangkap di desain:**
1. Branch dengan nama "Pusat" **mewarisi** `code_company` dari Main Office-nya (bukan punya kode sendiri).
2. Branch selain "Pusat" punya `code_cabang` sendiri, independen dari code company.
3. Nomor dokumen harus include kode cabang sesuai branch aktif user.
4. Visibilitas dokumen dibatasi berdasarkan cabang yang di-assign ke user — **kecuali role Direktur**, yang dapat melihat dokumen dari seluruh company & cabang.
5. User bisa multi-company (many-to-many), tapi company yang bisa dipilih dibatasi oleh apa yang sudah di-assign admin.
6. Role **Direktur** punya hak akses setara Admin (bisa lihat semua data), namun fokus utamanya adalah melihat/browsing dokumen lintas company & cabang, ditampilkan dalam bentuk **accordion** (Company → Cabang → Dokumen).

## 2. Desain Database (Skema Awal)

```sql
-- Main Office / Company
tbl_company
├── id
├── nama_company
├── code_company        -- ex: "JBM"
├── created_at, updated_at

-- Cabang (termasuk "Pusat")
tbl_branch
├── id
├── company_id (FK -> tbl_company)
├── nama_branch          -- ex: "Pusat", "Cabang Surabaya"
├── is_pusat (boolean)   -- flag penentu apakah ini branch pusat
├── code_branch          -- NULL jika is_pusat=true, karena ikut code_company
├── created_at, updated_at

-- User
tbl_user
├── id
├── nama
├── email / username
├── nomor_telepon        -- atribut baru
├── nip                  -- atribut baru, Nomor Induk Pegawai
├── role                 -- ex: "admin", "direktur", "user"
├── created_at, updated_at

-- User - Company (many to many)
tbl_user_company
├── id
├── user_id (FK)
├── company_id (FK)

-- User - Branch (assignment cabang spesifik)
tbl_user_branch
├── id
├── user_id (FK)
├── branch_id (FK)

-- Dokumen
tbl_document
├── id
├── nomor_dokumen         -- generated, ex: 001/INV/FIN/JBM/0826
├── jenis_dokumen_id (FK)
├── divisi_id (FK)
├── branch_id (FK)        -- penentu code cabang di nomor & visibilitas
├── created_by
├── created_at
```

**Catatan desain penting:**
- Kolom `code_branch` di tabel branch **nullable**. Saat `is_pusat = true`, kode efektif diambil dari `company.code_company` (via join/computed field), bukan disimpan redundant.
- Buat **computed/helper function** `get_effective_branch_code(branch_id)`:
  - Jika `is_pusat = true` → return `company.code_company`
  - Jika `is_pusat = false` → return `branch.code_branch`
- Kolom `role` di `tbl_user` menentukan hak akses & tampilan halaman dokumen (lihat bagian 8 — Role & Hak Akses).
- Untuk role **Direktur**, assignment ke `tbl_user_company` / `tbl_user_branch` **tidak dipakai sebagai pembatas** — validasi akses di level aplikasi cukup cek `role = 'direktur'` untuk membuka akses ke semua company & cabang, tanpa perlu mendaftarkan satu-satu di tabel assignment.
- `nip` diberi constraint **unique global** (`UNIQUE(nip)`) — 1 NIP hanya boleh dipakai 1 user di seluruh sistem, lintas company.
- `nomor_telepon` disimpan sebagai string bebas, tidak ada validasi format khusus.

## 3. Modul Master Data (Admin)

**a. Master Main Office**
- CRUD: nama, code_company (unique, validasi format kode)
- Saat create Main Office baru, sistem otomatis membuat 1 branch default "Pusat" dengan `is_pusat = true`

**b. Master Cabang**
- CRUD cabang per company
- Form input: nama cabang, code cabang (wajib diisi kecuali untuk branch Pusat — field ini di-disable/hidden untuk Pusat)
- Validasi: `code_branch` unique per company (atau global, tergantung kebutuhan)

## 4. Modul Document Numbering

**Format:** `{nomor_urut}/{jenis_dokumen}/{divisi}/{code_cabang}/{bulan_romawi}/{tahun}`

**Contoh:** `001/I-S.KEL/IT/JBM/VIII/2026`

| Segmen | Nilai | Keterangan |
|---|---|---|
| `nomor_urut` | 001 | Nomor urut berjalan, **reset setiap tahun** |
| `jenis_dokumen` | I-S.KEL | Kode jenis dokumen (mis. Surat Keluar Internal) |
| `divisi` | IT | Kode/singkatan divisi pembuat dokumen |
| `code_cabang` | JBM | `effective_branch_code` — ikut `code_company` jika branch = Pusat, atau `code_branch` sendiri jika bukan Pusat |
| `bulan_romawi` | VIII | Bulan pembuatan dokumen dalam format angka romawi |
| `tahun` | 2026 | Tahun pembuatan dokumen, juga jadi basis reset nomor urut |

**Langkah implementasi:**
1. Buat konfigurasi format nomor dokumen (agar fleksibel bila urutan/parameter berubah) — bisa berupa template string tersimpan di `tbl_numbering_config`.
2. Buat service `generateDocumentNumber(jenis, divisi, branch_id)`:
   - Ambil `effective_branch_code` via helper di poin 2.
   - Ambil nomor urut berikutnya per kombinasi **jenis + divisi + cabang + tahun berjalan** — otomatis mulai dari 001 lagi setiap pergantian tahun.
   - Konversi bulan pembuatan dokumen ke format angka romawi (1 → I, 8 → VIII, 12 → XII, dst).
   - Susun string sesuai template: `nomor_urut/jenis/divisi/code_cabang/bulan_romawi/tahun`.
3. Pastikan proses ambil nomor urut **atomic/thread-safe** (pakai row lock atau sequence table per kombinasi jenis+divisi+cabang+tahun) supaya tidak ada nomor dobel saat banyak user input bersamaan.

## 5. Modul Assignment User ke Cabang

- Halaman admin: pilih user → assign **multi-cabang** (satu user bisa punya akses ke lebih dari 1 cabang, termasuk lintas company).
- Assign company juga dilakukan di sini/terpisah: admin tentukan company mana saja yang boleh diakses user.
- Validasi: cabang yang bisa dipilih hanya dari company yang sudah di-assign ke user tersebut (dependency dropdown/multi-select).
- Tabel `tbl_user_branch` (many-to-many) sudah mendukung skema ini secara langsung.

## 6. Modul Visibilitas Dokumen di Halaman User

- Saat user login, sistem tahu:
  - Company aktif (dipilih dari daftar company yang di-assign)
  - Daftar cabang yang di-assign ke user, dipersempit ke cabang-cabang di bawah company aktif tersebut
- Sediakan **switcher company** dan **switcher cabang** di UI (cabang mengikuti/di-filter berdasarkan company yang sedang aktif).
- **Default tampilan dokumen mengikuti company & cabang yang sedang dipilih saat itu** — bukan gabungan semua company/cabang milik user:
  - Saat pertama login, sistem otomatis memilihkan company & cabang default (mis. yang terakhir dipakai user, atau yang pertama dalam daftar assignment).
  - Query dokumen: `WHERE company_id = :selected_company_id AND branch_id = :selected_branch_id`.
- Setiap kali user mengganti company dan/atau cabang lewat switcher, daftar dokumen di-refresh mengikuti pilihan baru tersebut (context switch), tidak menampilkan gabungan lintas cabang/company secara default.
- State pilihan company/cabang aktif ini sebaiknya disimpan di session/state user (dan bisa di-cache sebagai "cabang terakhir dipilih" untuk pengalaman login berikutnya).

## 7. Modul Multi-Company Selector (Master User)

- Di master user, tambahkan multi-select company (hanya menampilkan company yang sudah ditentukan admin dapat diakses).
- Saat login/switch context, user pilih company aktif → otomatis filter cabang yang tersedia untuk company tersebut.
- Field tambahan di form master user: **nomor telepon** dan **NIP** (wajib diisi, validasi format sesuai kebutuhan — mis. NIP numerik, nomor telepon dengan validasi panjang digit).

## 8. Modul Role & Hak Akses — Termasuk Role Direktur

**a. Penambahan Role**
- Tambahkan kolom `role` di `tbl_user` dengan minimal 3 nilai: `admin`, `direktur`, `user` (bisa dikembangkan lagi sesuai kebutuhan ke depan, mis. `manager`).
- Role ditentukan/di-set oleh Admin saat membuat atau mengedit data user.

**b. Karakteristik Role Direktur**
- Hak akses **read-only, setara Admin dari sisi cakupan data** — direktur bisa melihat seluruh **master data** (Master Main Office, Master Cabang, Master User) dan seluruh **dokumen**, namun **tidak punya hak create/edit/delete/approve** di modul manapun. Semua aksi ubah data tetap eksklusif milik role `admin`.
- **Tidak dibatasi assignment company/cabang** — direktur otomatis punya akses ke **seluruh company dan seluruh cabang** dalam sistem, tanpa perlu di-assign satu-satu oleh admin.
- **Tetap menggunakan mekanisme filter**, sama seperti role `user` (lihat bagian 6) — direktur tidak melihat seluruh dokumen dalam satu tampilan flat tanpa filter, melainkan tetap harus memilih/narrow-down company & cabang lewat filter (dalam bentuk accordion, lihat poin c) sebelum daftar dokumen ditampilkan.
- Perbedaan dengan role `user` biasa hanya di **cakupan pilihan yang tersedia di filter**: user biasa filter-nya dibatasi ke company/cabang hasil assignment (`tbl_user_company` / `tbl_user_branch`), sedangkan direktur filter-nya mencakup **seluruh company & cabang** yang ada di sistem.
- Di sisi UI, halaman master data & dokumen untuk direktur ditampilkan dalam mode **view-only** — tombol tambah/edit/hapus disembunyikan atau di-disable.

**c. Tampilan Halaman Dokumen untuk Direktur — Filter Berbentuk Accordion**
- Accordion berfungsi sebagai **mekanisme filter bertingkat** (bukan sekadar dekorasi tampilan), dengan 2 level:
  1. **Level 1 — Company**: daftar seluruh company yang terdaftar di sistem, ditampilkan sebagai header accordion (mis. "JBM", "Company B", dst).
  2. **Level 2 — Cabang**: saat header company diklik/di-expand, tampil sub-list cabang di bawah company tersebut (termasuk branch "Pusat"), masing-masing juga bisa di-expand untuk menampilkan daftar dokumen milik cabang tersebut.
- Dokumen hanya ditampilkan setelah direktur memilih/expand company → cabang tertentu — konsisten dengan pola filter di bagian 6, hanya saja pilihan company/cabang yang tersedia tidak dibatasi assignment.
- Contoh struktur visual:
  ```
  ▸ JBM (Main Office)
      ▸ Pusat
          - Daftar dokumen cabang Pusat
      ▸ Cabang Surabaya
          - Daftar dokumen cabang Surabaya
  ▸ Company B
      ▸ Pusat
      ▸ Cabang Bandung
  ```
- Query data: saat accordion company di-expand → fetch daftar cabang under company itu. Saat accordion cabang di-expand → fetch dokumen `WHERE branch_id = :branch_id` (bisa pakai lazy-load/on-demand fetch per level supaya ringan, tidak perlu load semua dokumen sekaligus di awal).
- Direktur tetap bisa pakai fitur pencarian/filter dokumen (jenis dokumen, divisi, rentang tanggal) di dalam tiap panel cabang yang sedang terbuka.

**d. Validasi & Middleware**
- Buat middleware/guard khusus: jika `role = 'direktur'`, lewati filter `company_id`/`branch_id` yang biasa diterapkan ke role `user` (khusus untuk operasi baca/GET).
- **Enforce read-only di level backend**, bukan hanya UI: endpoint create/update/delete pada seluruh master data & dokumen menolak request dari `role = 'direktur'` (mis. return 403 Forbidden), supaya tidak ada celah akses meskipun tombol di UI berhasil disembunyikan.
- Pastikan endpoint/API dokumen & master data membedakan query berdasarkan role — bukan hanya di level UI, supaya tidak ada celah akses data dari sisi backend.

## 9. Urutan Pengerjaan (Fasa)

| Fase | Deliverable |
|---|---|
| 1 | Desain & migrasi database (company, branch, user, user_company, user_branch — termasuk kolom role, nomor_telepon, nip) |
| 2 | Master Main Office + auto-create branch Pusat |
| 3 | Master Cabang (CRUD + validasi code) |
| 4 | Service generate nomor dokumen + testing concurrency |
| 5 | Modul assign user ke company & cabang (admin) + field nomor telepon & NIP di master user |
| 6 | Company/branch switcher di sisi user + filter dokumen |
| 7 | Role & hak akses Direktur + UI accordion Company → Cabang → Dokumen |
| 8 | UAT bersama business user (khususnya validasi format nomor dokumen, aturan reset nomor urut, dan hak akses Direktur) |

## 10. Keputusan Bisnis (Sudah Dikonfirmasi)

| Pertanyaan | Keputusan |
|---|---|
| Berapa cabang yang boleh diakses 1 user? | **Multi-cabang** — 1 user bisa di-assign ke lebih dari 1 cabang |
| Kapan nomor urut dokumen reset? | **Per tahun** — nomor urut kembali ke 001 setiap pergantian tahun |
| Scope uniqueness `code_branch`? | **Unique per company** — code cabang boleh sama di company berbeda, tapi tidak boleh duplikat dalam 1 company yang sama |
| Format penomoran dokumen | `nomor_urut/jenis_dokumen/divisi/code_cabang/bulan_romawi/tahun` — contoh: `001/I-S.KEL/IT/JBM/VIII/2026` (lihat detail di bagian 4) |
| Role Direktur | **Read-only** — bisa lihat semua dokumen & seluruh master data lintas company & cabang, tanpa hak create/edit/delete. **Tetap pakai filter** (accordion Company → Cabang) untuk narrow-down tampilan, sama seperti user biasa — bedanya pilihan company/cabang di filter tidak dibatasi assignment |
| Cakupan akses Direktur | **Seluruh master data** (Main Office, Cabang, User) + seluruh dokumen — bukan hanya modul dokumen |
| Atribut tambahan di master user | **Nomor telepon** (tanpa format/validasi khusus, input bebas) dan **NIP** ditambahkan ke `tbl_user` |
| Scope uniqueness `nip` | **Unique global** — 1 NIP hanya boleh dipakai oleh 1 user di seluruh sistem, lintas company |

**Dampak ke desain teknis:**
- `tbl_user_branch` tetap many-to-many (mendukung multi-cabang per user).
- Constraint unique pada `code_branch` dibuat composite: `UNIQUE(company_id, code_branch)`.
- Constraint unique pada `nip` dibuat **global**: `UNIQUE(nip)` di `tbl_user`.
- `nomor_telepon` disimpan sebagai string bebas, tanpa validasi format khusus di level database/aplikasi.
- Sequence nomor urut dokumen di-key oleh kombinasi `jenis + divisi + branch_id + tahun`, sehingga otomatis reset saat tahun berganti (tidak perlu job/cron reset manual, cukup sequence baru muncul tiap tahun berjalan).
- `tbl_user` bertambah kolom `role`, `nomor_telepon`, `nip`.
- Logic filter dokumen & master data di backend perlu percabangan berdasarkan role:
  - `direktur` → tanpa filter company/branch (bisa lihat semua), tapi endpoint write (create/update/delete) diblok di seluruh modul.
  - role lain → filter sesuai assignment seperti biasa, hak CRUD sesuai role masing-masing (mis. `admin` full access).
