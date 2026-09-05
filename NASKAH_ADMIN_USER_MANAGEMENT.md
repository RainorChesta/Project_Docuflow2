# 👔 PANDUAN KHUSUS ADMIN: MANAJEMEN PENGGUNA (USER MANAGEMENT)
> **Modul Terpisah: Penugasan Role, Multi-Divisi, Perusahaan & Cabang ke Pengguna**

---

## 🎯 Nilai Bisnis & Konsep Utama (*Enterprise Multi-Tenancy*)
Di dalam **DokuFlow**, hak akses dokumen tidak dibuat statis, melainkan terikat pada struktur organisasi:
1. **Perusahaan (*Company*)**: Entitas legal atau holding/anak perusahaan.
2. **Cabang (*Branch*)**: Unit lokasi fisik kantor (Pusat, Jakarta, Surabaya, dll.).
3. **Divisi (*Division*)**: Departemen kerja (HR, IT, Finance, Legal, dll.). Seorang pengguna bisa memiliki **lebih dari satu divisi** (*Multi-Division Support*).
4. **Role Sistem (*System Role*)**:
   * **`user` (Staff)**: Membuat draf, meminta TTD, dan melihat dokumen divisinya.
   * **`head` (Kepala Divisi)**: Memeriksa dan menyetujui dokumen/TTD untuk divisinya.
   * **`direktur`**: Memantau dokumen lintas cabang/perusahaan (tidak terikat satu divisi).
   * **`admin`**: Pengendali penuh master data dan konfigurasi sistem.

---

## 🧭 Form Cerdas Berbasis Role (*Smart Reactive Form*)

Form penambahan pengguna DokuFlow dirancang dinamis dengan **Alpine.js**:

| Field / Komponen | Role: `user` / `head` | Role: `direktur` | Role: `admin` |
| :--- | :--- | :--- | :--- |
| **NIP & No. Telepon** | Wajib / Opsional | **Otomatis Nonaktif (N/A)** | Opsional |
| **Pilihan Divisi** | **Bisa pilih $\ge 1$ divisi** | **Otomatis Nonaktif (N/A)** | Akses Semua Divisi |
| **Pilihan Perusahaan** | Pilih $\ge 1$ Perusahaan | Pilih Perusahaan yang dibawahi | Otomatis Semua Perusahaan |
| **Pilihan Cabang** | Pilih $\ge 1$ Cabang penempatan | Mengawasi Cabang terkait | Otomatis Semua Cabang |

---

# 🎬 SKENARIO DEMO & NASKAH BICARA KATA DEMI KATA

---

### 📍 LANGKAH 1: Masuk ke Menu Manajemen Pengguna
* 🖥️ **Tampilan**: Login sebagai Administrator (`admin@dokuflow.id`), buka menu sidebar **Pengguna** (`/admin/users`).
* 🖱️ **Aksi**: Tunjukkan tabel daftar pengguna lengkap dengan badge Role, Divisi, Perusahaan, dan Cabang.
* 🗣️ **Script Narasi**:
  > *"Bapak/ibu penguji, sekarang kita masuk ke modul khusus Administrator, yaitu **Manajemen Pengguna & Penugasan Organisasi**.
  > 
  > Di halaman ini, Admin dapat memantau seluruh akun karyawan di berbagai anak perusahaan dan cabang. DokuFlow mendukung penugasan multi-perusahaan, multi-cabang, hingga multi-divisi untuk setiap pegawai."*

---

### 📍 LANGKAH 2: Membuka Form Tambah Pengguna & Data Dasar
* 🖱️ **Aksi**: Klik tombol **+ Tambah Pengguna** (`/admin/users/create`).
* 🖥️ **Tampilan**: Formulir penambahan pengguna baru.
* 🖱️ **Aksi**: Isi data contoh:
  * Nama Lengkap: `Rian Pratama`
  * Email: `rian.hr@dokuflow.id`
  * NIP: `199508122020121002`
  * Nomor Telepon: `081299887766`
  * Password: `password` & Konfirmasi: `password`
* 🗣️ **Script Narasi**:
  > *"Kita mulai dengan mengisi identitas dasar pegawai seperti Nama, Email korporat, NIP resmi, nomor telepon, dan kata sandi awal."*

---

### 📍 LANGKAH 3: Demonstrasi Perubahan Role Dinamis (*Reactive Role Switch*)
* 🖱️ **Aksi**:
  1. Pada dropdown **Peran Sistem (Role)**, ubah dari `User` ke `Direktur`.
  2. Tunjukkan ke audiens bahwa input **NIP** dan **Pilihan Divisi** otomatis menjadi abu-abu (*disabled / N/A*).
  3. Ubah kembali ke `User` atau `Kepala Divisi (Head)`.
* 🗣️ **Script Narasi**:
  > *"Perhatikan kecerdasan formulir DokuFlow:
  > Ketika saya memilih peran **Direktur**, sistem secara otomatis menonaktifkan kolom NIP dan Divisi, karena Direktur berada di level eksekutif yang membawahi seluruh divisi.
  > 
  > Namun ketika saya kembalikan ke peran **Staff (User)** atau **Kepala Divisi (Head)**, kolom divisi dan NIP kembali aktif secara instan."*

---

### 📍 LANGKAH 4: Menambahkan Multi-Divisi ke Pengguna
* 🖱️ **Aksi**:
  1. Klik pada kolom **Divisi**.
  2. Ketik kata kunci (misal: `HR` atau `IT`).
  3. Pilih divisi utama (misal: *HR - Human Resources*), lalu tambahkan divisi kedua (misal: *GA - General Affairs*).
  4. Tunjukkan tag-tag divisi terpilih muncul secara rapi.
* 🗣️ **Script Narasi**:
  > *"Dalam dunia kerja nyata, seorang staf sering kali memegang lebih dari satu tanggung jawab—misalnya merangkap di divisi HR dan General Affairs. Di DokuFlow, Admin dapat menambahkan **lebih dari satu divisi** ke satu akun pegawai dengan sistem pencarian dan multi-tagging yang sangat intuitif."*

---

### 📍 LANGKAH 5: Menetapkan Perusahaan & Kantor Cabang (Company & Branch Mapping)
* 🖱️ **Aksi**:
  1. Centang **Perusahaan** (misal: *PT ABC Holding*).
  2. Centang **Cabang** penempatan (misal: *ABC Kantor Pusat* dan *ABC Cabang Jakarta*).
  3. Pastikan toggle **Status Akun Aktif** menyala hijau.
  4. Klik tombol **Simpan Pengguna**.
* 🖥️ **Tampilan**: Kembali ke tabel pengguna `/admin/users` dengan alert sukses hijau.
* 🗣️ **Script Narasi**:
  > *"Terakhir, Admin memetakan pegawai ini ke entitas bisnis dan cabang tempat ia bertugas. Pegawai dapat ditempatkan di satu cabang spesifik maupun beberapa cabang sekaligus.
  > 
  > Setelah saya klik Simpan, pengguna langsung aktif dan saat ia login, seluruh dokumen yang bisa ia buat, lihat, dan nomor surat yang ia dapatkan akan langsung terisolasi sesuai Perusahaan, Cabang, dan Divisi yang baru saja kita tetapkan."*

---

# 💡 ANTISIPASI PERTANYAAN PENGUJI (Q&A)

### ❓ Pertanyaan 1: *"Apa dampaknya jika 1 user memiliki 2 divisi berbeda?"*
> **Jawaban Anda**:
> *"User tersebut akan memiliki hak akses untuk melihat dan membuat dokumen di kedua divisi tersebut. Pada menu pembuatan dokumen, user dapat memilih nomor surat atas nama divisi HR atau divisi GA sesuai kebutuhan pekerjaan yang sedang ia kerjakan."*

### ❓ Pertanyaan 2: *"Bagaimana jika ada pegawai yang dimutasi ke cabang lain?"*
> **Jawaban Anda**:
> *"Admin cukup mengedit akun pegawai tersebut di menu Pengguna, lalu mencabut centang cabang lama dan mencentang cabang barunya. Riwayat dokumen lama yang pernah dibuat oleh pegawai tersebut tetap aman dan tidak akan hilang di cabang sebelumnya."*

### ❓ Pertanyaan 3: *"Mengapa Admin tidak perlu dicentang perusahaannya?"*
> **Jawaban Anda**:
> *"Sistem DokuFlow secara otomatis memberikan hak akses global kepada Administrator untuk mengawasi dan mengelola master data seluruh perusahaan dan cabang tanpa batasan."*
