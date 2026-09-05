# 🎙️ NASKAH LENGKAP PRESENTASI DOKUFLOW
> **Panduan Kata Demi Kata (*Word-by-Word Script*) & Jembatan Transisi Antar-Fitur**
> *Gunakan naskah ini agar Anda tidak bingung atau lupa alur ketika berpindah dari satu fitur ke fitur berikutnya.*

---

## 🧭 PETA INGATAN UTAMA (Hafalkan 7 Logika Jembatan Ini)

```
[1. SIAPKAN AKUN & CABANG] -> "Sebelum bikin dokumen, login dulu, pilih cabang aktif, dan siapkan TTD resmi."
           ↓
[2. BUAT DOKUMEN & CEK NOMOR] -> "Sekarang akun siap, kita buat dokumen dan buktikan nomor otomatisnya anti-dobel."
           ↓
[3. KETIK DI ONLYOFFICE & SISIP TTD] -> "Nomor sudah ada, kita ketik isinya, tempel TTD sendiri, dan minta TTD pimpinan."
           ↓
[4. BAGIKAN AKSES DRAF] -> "Sebelum difinalkan, kita bagikan draf ini ke rekan kerja lewat tautan aman (Share Link)."
           ↓
[5. PIMPINAN APPROVE] -> "Draf diajukan, pimpinan approve TTD dan approve versi sehingga resmi terbit V1."
           ↓
[6. VALIDASI QR CODE] -> "Dokumen terbit, kita scan QR Code-nya untuk uji hak akses: siapa yang boleh dan dilarang buka."
           ↓
[7. ADMIN, RETENSI & SAMPAH] -> "Terakhir, kita lihat kontrol admin: aturan retensi kearsipan dan proteksi sampah (recovery)."
```

---

# 🎬 NASKAH LENGKAP PER BABAK

---

## 📍 BABAK 1: Pembukaan, Autentikasi, Context Switcher & Identitas Digital
> **Kondisi Layar**: Buka Tab 1 di Halaman Login (`/login`).

### 🗣️ Kata-Kata Pembuka (Opening)
> *"Selamat pagi/siang bapak/ibu penguji dan rekan-rekan sekalian. Hari ini saya akan mendemonstrasikan sistem **DokuFlow**, sebuah platform Enterprise Document Management System yang dirancang untuk mengelola seluruh siklus hidup dokumen perkantoran—mulai dari pembuatan draf, penomoran cerdas, kolaborasi tanda tangan digital, persetujuan berjenjang, hingga validasi keaslian dokumen via QR Code."*

---

### 🖱️ Aksi: Demo Halaman Login & Aksesibilitas
*(Arahkan mouse ke pojok kanan atas)*
> *"Pertama, di halaman login ini, DokuFlow telah dilengkapi dengan **Theme Toggle** untuk mode gelap dan terang demi kenyamanan visual pengguna, serta **Language Switcher** bilingual Indonesia dan Inggris. Di bawahnya juga terdapat fitur pemulihan akun melalui reset kata sandi email."*

---

### 🌉 Jembatan ke Fitur Berikutnya (Login & Context Switcher):
> *"Sekarang, mari kita masuk sebagai seorang **Staff HR** untuk melihat bagaimana seorang karyawan berinteraksi dengan sistem ini setiap harinya."*

---

### 🖱️ Aksi: Login & Demonstrasi Context Switcher
*(Ketik email: `staff.hr@dokuflow.id`, password: `password`, klik Masuk $\rightarrow$ Arahkan mouse ke header atas)*
> *"Setelah berhasil masuk ke Dashboard, perhatikan di bagian atas terdapat fitur **Context Switcher**. Bagi perusahaan yang memiliki banyak anak perusahaan atau cabang, seorang staf dapat berpindah tempat kerja aktif—misalnya dari Kantor Pusat ke Cabang Jakarta—secara instan dengan satu klik tanpa perlu logout. Seluruh data dokumen, kearsipan, dan penomoran surat akan otomatis menyesuaikan dengan cabang yang aktif."*

---

### 🌉 Jembatan ke Fitur Berikutnya (Setup Profil & TTD):
> *"Namun, sebelum staf ini diperbolehkan membuat atau mengajukan dokumen resmi, sistem mewajibkan setiap pegawai memiliki identitas dan tanda tangan digital terverifikasi. Mari kita lihat menu profil."*

---

### 🖱️ Aksi: Buka Menu Profil & Demo TTD / Stempel
*(Klik foto profil di kiri bawah $\rightarrow$ Buka halaman `/profile` $\rightarrow$ Scroll ke bagian Tanda Tangan)*
> *"Di halaman profil ini, pengguna dapat mengelola data diri, foto profil, dan kata sandi. Di bagian bawah terdapat modul **Tanda Tangan & Stempel Resmi**:
> 1. Pengguna dapat menggambar tanda tangan langsung di canvas digital melalui fitur **Draw Signature**.
> 2. Pengguna juga dapat mengunggah file tanda tangan transparan melalui **Upload Signature**.
> 3. Serta mengunggah **Stempel Perusahaan** yang sah.
> 
> Tanda tangan dan stempel inilah yang nanti akan menjadi aset legal saat membubuhi dokumen di dalam editor."*

---

## 📍 BABAK 2: Inisiasi Dokumen & Mesin Penomoran Cerdas (Smart Numbering)

### 🌉 Jembatan ke Fitur Pembuatan Dokumen:
> *"Identitas digital sudah siap. Sekarang kita masuk ke skenario utama: staf HR ingin membuat sebuah dokumen resmi instansi."*

---

### 🖱️ Aksi: Buka Template Chooser
*(Klik menu **Dokumen Saya** $\rightarrow$ Klik tombol **+ Buat Dokumen Baru**)*
> *"Di halaman pembuatan dokumen ini, DokuFlow menyediakan 3 fleksibilitas:
> 1. **Buat Manual**: Menulis langsung dari lembar kosong.
> 2. **Gunakan Template Resmi**: Menggunakan template baku yang telah disediakan perusahaan (bergaya Microsoft Word).
> 3. **Upload File**: Mengunggah dokumen DOCX atau PDF yang sudah ada di komputer."*

---

### 🌉 Jembatan ke Pengujian Penomoran Otomatis:
> *"Salah satu masalah terbesar di administrasi kantor adalah nomor surat yang dobel, salah kode divisi, atau salah format. Mari kita uji bagaimana **Smart Numbering Engine** di DokuFlow menyelesaikan masalah ini secara otomatis."*

---

### 🖱️ Aksi: Uji Dropdown Penomoran (Lama vs Baru vs SOP)
*(Pilih salah satu template $\rightarrow$ Masuk ke form create $\rightarrow$ Ganti-ganti dropdown format)*
> *"Perhatikan pada kolom Nomor Dokumen:
> 1. Ketika saya memilih **Format Baru**, nomor surat otomatis ter-generate mengikuti standar divisi dan cabang saat ini.
> 2. Ketika saya ganti ke **Format Lama**, sistem langsung menyesuaikan penomoran berbasis Unit Kerja.
> 3. Ketika tipe dokumen saya ubah menjadi **SOP**, sistem secara cerdas mengubah struktur kodenya menjadi format baku penomoran SOP.
> 4. Sistem juga melakukan pengecekan ketersediaan secara real-time di database untuk memastikan nomor ini unik dan belum pernah dipakai."*

---

## 📍 BABAK 3: Editor ONLYOFFICE, Kolaborasi TTD, & Proteksi Draf

### 🌉 Jembatan ke Editor Dokumen:
> *"Setelah nomor dokumen resmi terbentuk, mari kita masuk ke lembar kerja untuk mengetik isi dokumen dan membubuhkan tanda tangan."*

---

### 🖱️ Aksi: Buka Editor ONLYOFFICE & Sisipkan TTD / Request TTD
*(Klik tombol **Lanjut ke Editor** $\rightarrow$ Jendela ONLYOFFICE terbuka)*
> *"Di sini, DokuFlow terintegrasi langsung dengan editor **ONLYOFFICE** berbasis browser dengan dukungan formatting pengolah kata yang sangat lengkap.
> 
> Di dalam editor ini:
> 1. Saya dapat langsung menempelkan **Tanda Tangan dan Stempel Diri Sendiri** yang sudah kita siapkan di profil tadi.
> 2. Namun, jika dokumen ini membutuhkan tanda tangan atasan, saya cukup mengklik fitur **Minta TTD Orang Lain**, lalu memilih pejabat yang berwenang—misalnya Bu Siti selaku Kepala HR. Sistem tidak mengotori dokumen dengan teks placeholder sementara, melainkan langsung mengirimkan **Notifikasi Permintaan Izin Resmi** ke akun Bu Siti dan memunculkan konfirmasi pengiriman di layar pemohon."*

---

### 🌉 Jembatan ke Pengujian Proteksi Draf (Leave Modal):
> *"Bagaimana jika di tengah proses pengetikan, pengguna tidak sengaja menekan tombol kembali atau menutup browser? Apakah datanya hilang? Mari kita buktikan proteksi keamanannya."*

---

### 🖱️ Aksi: Uji Coba Tombol Back & Leave Protection
*(Tekan tombol Back browser tanpa simpan $\rightarrow$ Modal peringatan muncul)*
> *"Sistem langsung memunculkan **Modal Proteksi Navigasi**. Jika pengguna tetap keluar, dokumen ini tidak akan hilang, melainkan secara otomatis diamankan ke dalam status **Draf (Draft)**.
> 
> Sekarang, mari kita simpan dokumen ini secara resmi."*
*(Klik Simpan & Ajukan $\rightarrow$ Tunjukkan notifikasi pop-up)*
> *"Sistem langsung memberikan konfirmasi: Dokumen berhasil disimpan dan otomatis masuk ke antrean persetujuan Kepala HR."*

---

## 📍 BABAK 4: Kolaborasi Berbagi Akses & Tautan (Google Docs Model)

### 🌉 Jembatan ke Fitur Sharing & Kolaborasi:
> *"Sebelum pimpinan mengesahkan dokumen ini secara final, staf pembuat dokumen mungkin ingin rekan kerjanya di divisi HR ikut membaca atau memberi masukan. DokuFlow menyediakan sistem pembagian hak akses bergaya Google Docs."*

---

### 🖱️ Aksi: Buka Modal Bagikan (Share Modal) & Salin Tautan
*(Klik tombol **Bagikan / Share** pada dokumen)*
> *"Di modal pembagian ini:
> 1. Pemilik dokumen dapat menambahkan rekan kerja spesifik atau membagikan langsung ke satu divisi penuh dengan hak akses berjenjang: **Viewer (Hanya Lihat)**, **Commenter**, atau **Editor**.
> 2. Di bagian **Akses Umum (General Access)**, kita bisa mengubah statusnya dari Dibatasi (*Restricted*) menjadi Siapa saja yang memiliki tautan (*Anyone with the link*).
> 3. Kita dapat menyalin **Share Link** bertoken unik untuk dibagikan secara aman, dan token ini bisa di-regenerate sewaktu-waktu jika ingin mencabut akses lama.
> 4. Bagi rekan yang menerima akses, dokumen ini akan langsung muncul di menu sidebar **Dokumen Dibagikan** lengkap dengan indikator badge notifikasi dokumen baru."*

---

## 📍 BABAK 5: Meja Pimpinan (Approval TTD & Penerbitan Versi Dokumen)

### 🌉 Jembatan ke Sudut Pandang Pimpinan / Penyetuju:
> *"Sekarang permohonan tanda tangan dan draf dokumen sudah diajukan. Mari kita berpindah ke layar pimpinan untuk melihat alur verifikasi dan persetujuannya."*

---

### 🖱️ Aksi: Pindah ke Tab 2 (Akun Kepala HR: `head.hr@dokuflow.id`)
*(Buka Tab 2 $\rightarrow$ Tunjukkan ikon lonceng menyala merah)*
> *"Di layar Kepala HR, sistem langsung memberikan **Notifikasi Real-time**. Pimpinan dapat melihat ada permohonan tanda tangan dan draf dokumen yang menunggu tindakan."*

---

### 🖱️ Aksi: Approve TTD Pimpinan
*(Buka menu **Persetujuan TTD** $\rightarrow$ Klik Tinjau $\rightarrow$ Klik **Accept TTD**)*
> *"Pertama, di menu **Persetujuan TTD**, pimpinan dapat memeriksa draf dan mengklik **Accept** untuk menyetujui pembubuhan tanda tangan resminya. Sebaliknya, jika ditolak, staf pembuat akan langsung mendapat notifikasi penolakan beserta catatannya."*

---

### 🌉 Jembatan ke Approval Versi & Logika Status:
> *"Setelah tanda tangan disetujui, pimpinan kini masuk ke meja pengesahan akhir dokumen di menu Approval."*

---

### 🖱️ Aksi: Buka Menu Approval Versi & Approve V1
*(Buka menu **Approval > Document Approval (Version)** $\rightarrow$ Klik **Approve**)*
> *"Ketika pimpinan mengklik **Approve**:
> 1. Status dokumen resmi berubah menjadi **ACTIVE / PUBLISHED**.
> 2. Dokumen ini resmi menyandang label **Versi 1 (V1)**.
> 
> Ada 2 logika penting yang diterapkan sistem:
> * **Jika Ditolak (Reject)**: Apabila dokumen baru ditolak, sistem otomatis memindahkannya ke **Sampah (Soft Delete)**. Namun jika ini dokumen revisi (V2 ke atas) yang ditolak, sistem akan mengembalikannya ke versi aktif sebelumnya.
> * **Rollback Approval**: Jika di masa depan ditemukan kekeliruan, staf dapat mengajukan **Rollback** ke versi lama melalui tab Rollback Approval."*

---

## 📍 BABAK 6: Detail Dokumen & Matriks Keamanan QR Code

### 🌉 Jembatan ke Detail Dokumen & Uji QR Code:
> *"Dokumen sekarang sudah sah terbit. Mari kita buka halaman Detail Dokumen dan menguji fitur verifikasi QR Code-nya."*

---

### 🖱️ Aksi: Buka Detail Dokumen & Tunjukkan QR Code
*(Buka halaman Detail Dokumen `/documents/{id}`)*
> *"Di halaman Detail Dokumen, tercatat riwayat audit lengkap: siapa pembuatnya, siapa penandatangannya, tanggal terbit, serta pratinjau file yang sudah terstempel resmi. Di pojok dokumen terdapat **QR Code Otentikasi**."*

---

### 🌉 Jembatan ke Pengujian Matriks Hak Akses QR:
> *"Pertanyaannya: Siapa saja yang bisa membuka dokumen ini jika QR Code tersebut di-scan? Mari kita bedah sistem keamanannya."*

---

### 🖱️ Aksi: Buka URL QR Code `/d/{token}` & Jelaskan 4 Level Akses
> *"Sistem verifikasi QR Code DokuFlow memiliki 4 lapisan proteksi:
> 1. **Wajib Terautentikasi**: Orang asing tanpa login tidak bisa sembarangan mengintip isi dokumen rahasia.
> 2. **Dokumen General**: Jika dokumen berstatus Umum, seluruh staf internal yang login dapat melihat pratinjaunya.
> 3. **Dokumen Division Only**: Jika dokumen diatur hanya untuk Divisi HR, maka staf dari Divisi IT atau Finance yang men-scan QR Code ini akan langsung **diblokir oleh sistem** dengan peringatan akses ditolak.
> 4. **Dokumen Spesifik / Restricted**: Hanya orang-orang tertentu yang namanya didaftarkan pada menu pembagian tadi yang diizinkan membaca isinya."*

---

## 📍 BABAK 7: Fitur Administrator, Retensi & Sampah Dokumen

### 🌉 Jembatan ke Panel Administrator:
> *"Terakhir, di balik seluruh alur operasional staf dan pimpinan tadi, bagaimana sistem ini dikelola oleh tim Administrator? Mari kita lihat panel Admin."*

---

### 🖱️ Aksi: Pindah ke Tab 3 (Akun Admin: `admin@dokuflow.id`)
*(Buka Tab 3 $\rightarrow$ Masuk ke menu Administrasi)*
> *"Di panel Administrator ini:
> 1. Admin mengelola **Master Organisasi**: mulai dari Perusahaan, Cabang, Unit Kerja, Divisi, hingga Manajemen Pengguna.
> 2. Admin mengelola **Template Dokumen** standar instansi.
> 3. Terdapat menu **Retensi Dokumen** untuk menentukan berapa lama suatu dokumen disimpan sebelum otomatis diarsipkan saat kedaluwarsa."*

---

### 🖱️ Aksi: Buka Menu Sampah (Trash) & Demonstrasi Restore
*(Buka menu `/trash`)*
> *"Dan terakhir, DokuFlow menjamin keamanan data dari kelalaian manusia melalui fitur **Sampah Dokumen (Trash Bin)**. Dokumen yang terhapus atau ditolak tidak langsung hilang, melainkan dapat dipulihkan kembali melalui tombol **Restore**, atau dibersihkan permanen melalui **Force Delete**."*

---

## 🎯 BABAK 8: Penutup & Tanya Jawab (Closing)

### 🗣️ Kata-Kata Penutup
> *"Sebagai kesimpulan, DokuFlow bukan sekadar aplikasi penyimpan file, melainkan ekosistem terpadu yang mendigitalisasi seluruh birokrasi perkantoran: mulai dari penyiapan tanda tangan digital, kemudahan penomoran otomatis anti-dobel, pengeditan kaya fitur, alur persetujuan yang transparan, hingga pembuktian keabsahan hukum dengan QR Code terenkripsi.
> 
> Demikian presentasi dari saya. Terima kasih atas perhatian bapak/ibu penguji, dan waktu saya kembalikan untuk sesi tanya jawab."*

---

# 💡 RANGKUMAN CEPAT: 7 KUNCI INGATAN SAAT PRESENTASI

| No | Nama Babak | Inti Yang Harus Diingat & Diucapkan |
| :---: | :--- | :--- |
| **1** | **Profil & Switch Cabang** | *"Sebelum bikin dokumen, login dulu, pilih cabang aktif, dan siapkan TTD/Stempel resmi."* |
| **2** | **Inisiasi & Cek Nomor** | *"Buktikan generator nomor otomatis: Baru vs Lama vs SOP, anti-nomor ganda."* |
| **3** | **ONLYOFFICE & TTD** | *"Ketik di ONLYOFFICE, sisip TTD sendiri, request TTD pimpinan, uji modal Back (Draf)."* |
| **4** | **Bagikan Akses (Share)** | *"Bagikan draf ke rekan kerja ala Google Docs (Viewer/Editor/Share Link)."* |
| **5** | **Persetujuan Pimpinan** | *"Pimpinan terima notif $\rightarrow$ Approve TTD $\rightarrow$ Approve versi $\rightarrow$ Dokumen terbit V1."* |
| **6** | **Uji QR Code** | *"Scan QR Code $\rightarrow$ Buktikan matriks keamanan: General vs Divisi vs Spesifik."* |
| **7** | **Admin & Sampah** | *"Kontrol master data, aturan retensi kedaluwarsa, dan fitur pemulihan sampah."* |
