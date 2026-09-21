# Sistem Pembuatan Dokumen Digital

## User Prompt 1: Logika Approval dan Acknowledge
**User prompt:** 
> bagaimana logicnya, untuk approval dan acknowledge jadi ketika user dengan role staff membuat dokumen kepala devisi perlu mengetahui terlebih dahulu, namun kepala devisi juga bisa menolak dokumen tersebut, kemudian baru bisa dilanjutkan ke approval dan approval juga bisa mengaprove dan menolak

**Response:** 
Logika alur kerja (workflow) untuk proses **Acknowledge** (Pengecekan/Mengetahui) dan **Approval** (Persetujuan) dapat dirancang menggunakan konsep **State Machine** (Perubahan Status Dokumen).

### 1. Status Dokumen (Document States)
Untuk menjalankan logika ini, dokumen perlu memiliki kolom status dalam database:
1. **Draft**: Dokumen baru dibuat oleh Staff dan belum diajukan.
2. **Pending Acknowledge**: Dokumen diajukan dan menunggu tanggapan Kepala Divisi.
3. **Acknowledged**: Kepala Divisi menyetujui/mengetahui dokumen.
4. **Approved**: Dokumen disetujui penuh oleh Approver.
5. **Rejected**: Dokumen ditolak (baik di tahap Acknowledge maupun Approval).

### 2. Alur Logika Logis & Hak Akses
**Tahap 1: Pengajuan oleh Staff**
1. **Aksi**: Staff menekan tombol `Submit`.
2. **Sistem**: Mengubah status dokumen dari `Draft` menjadi `Pending Acknowledge`. Notifikasi dikirim ke Kepala Divisi.

**Tahap 2: Review Kepala Divisi (Acknowledge)**
1. **Kondisi**: Hanya pengguna dengan `Role = Kepala Divisi` dan `Status = Pending Acknowledge` yang dapat melihat tombol aksi.
2. **Opsi A (Setuju/Acknowledge)**:
   - Status dokumen berubah dari `Pending Acknowledge` menjadi `Acknowledged` (atau langsung `Pending Approval`).
   - Notifikasi dikirim ke pihak *Approver*.
3. **Opsi B (Tolak/Reject)**:
   - Kepala Divisi wajib mengisi alasan penolakan.
   - Status dokumen berubah menjadi `Rejected`.
   - Dokumen dikembalikan ke Staff untuk direvisi atau diarsipkan. Notifikasi penolakan dikirim ke Staff.

**Tahap 3: Keputusan Akhir (Approval)**
1. **Kondisi**: Hanya pengguna dengan `Role = Approver` dan `Status = Acknowledged` (atau `Pending Approval`) yang dapat melihat tombol aksi.
2. **Opsi A (Approve)**:
   - Status dokumen berubah menjadi `Approved`.
   - Dokumen dikunci (hanya-baca / *read-only*), tanda tangan digital/stempel otomatis ditambahkan, dan proses selesai.
3. **Opsi B (Tolak/Reject)**:
   - Approver wajib mengisi alasan penolakan.
   - Status dokumen berubah menjadi `Rejected`.
   - Notifikasi dikirim ke Staff dan Kepala Divisi.

### 3. Struktur Database Sederhana

**Tabel `documents`**
| Field | Tipe Data | Keterangan |
| :--- | :--- | :--- |
| id | BigInt | ID unik dokumen |
| title | String | Judul dokumen |
| created_by | BigInt | Foreign Key ke ID Staff |
| status | Enum | draft, pending_acknowledge, pending_approval, approved, rejected |

**Tabel `document_logs` (Riwayat Persetujuan)**
| Field | Tipe Data | Keterangan |
| :--- | :--- | :--- |
| id | BigInt | ID log |
| document_id | BigInt | Foreign Key ke ID Dokumen |
| actor_id | BigInt | User yang melakukan aksi |
| action | Enum | submit, acknowledge, reject_acknowledge, approve, reject_approval |
| notes | Text | Catatan/alasan jika ditolak |
| created_at | Timestamp | Waktu aksi dilakukan |

### 4. Hal Penting untuk Dipertimbangkan
1. **Revisi setelah Ditolak**: Jika dokumen statusnya `Rejected`, beri fitur `Resubmit`. Saat Staff memperbarui isi dokumen dan mengirim ulang, status kembali lagi dari awal ke `Pending Acknowledge`.
2. **Locking Data**: Ketika status masuk ke `Pending Acknowledge` hingga `Approved`, batasi akses Staff agar tidak bisa lagi mengedit isi dokumen.

---

## User Prompt 2: Workflow Profesional
**User prompt:** 
> bagaimana workflow sistem dokumen digital yang sudah ada saat ini, apakah seperti yang saya sebutkan barusan atau tidak? jika tidak berikan workflow yang lebih professional

**Response:** 
Workflow yang Anda sebutkan **sudah sangat mendekati standar industri** dan banyak diterapkan di berbagai sistem digital (e-Office, Document Management System, maupun modul ERP). Namun, dalam praktik profesional kelas korporasi atau instansi resmi, workflow tersebut biasanya disempurnakan agar lebih fleksibel, aman, dan meminimalisir hambatan operasional.

### Perbandingan Workflow Anda vs Workflow Profesional
1. **Acknowledge (Pemeriksaan / Paraf Digital)**
   - *Workflow Anda*: Kepala Divisi melakukan acknowledge, jika menolak dokumen langsung berhenti/gagal.
   - *Standar Profesional*: Tahap acknowledge oleh Kepala Divisi berfungsi sebagai pemeriksaan/penelaahan (paraf digital). Jika ada yang kurang tepat, sistem profesional biasanya menyediakan Opsi **Revision Request (Minta Revisi)** selain opsi Reject total, agar Staff cukup memperbaiki bagian yang salah tanpa perlu membuat dokumen baru dari awal.
2. **Approval (Persetujuan)**
   - *Workflow Anda*: Setelah di-acknowledge, dokumen lanjut ke Approval.
   - *Standar Profesional*: Penanganan penolakan di tahap Approval lebih spesifik. Approver bisa mengembalikan dokumen ke tahap Kepala Divisi (jika kesalahan ada pada pengawasan) atau langsung ke Staff (jika kesalahan materiil).
3. **Hasil Akhir Dokumen**
   - *Workflow Anda*: Status berubah jadi Approved.
   - *Standar Profesional*: Setelah status Approved, sistem otomatis melakukan Penerbitan Nomor Surat / Hash Dokumen / E-Meterai / QR Code Validasi dan mengunci dokumen secara permanen (PDF/A read-only).

### Workflow Digital Profesional (Standar Korporasi)
Berikut alur kerja profesional yang disempurnakan dengan penanganan revisi dan penerbitan dokumen resmi:

```text
[Staff] Create Draft 
   │
   ▼
[Submit] ──> Status: Pending Review (Paraf)
   │
   ├─► [Kepala Divisi] Request Revision ──► Returning to Staff (Edit mode)
   ├─► [Kepala Divisi] Reject ────────────► Status: Cancelled/Archived
   │
   ▼ (Acknowledge / Paraf Digital OK)
Status: Pending Approval
   │
   ├─► [Approver] Request Revision ───────► Returning to Staff / Kadiv
   ├─► [Approver] Reject ─────────────────► Status: Rejected
   │
   ▼ (Approve OK)
Status: Approved & Finalized
   │
   ▼ (Automated Background Process)
[System] Generate QR Code / Digital Signature Stamp / Document Registration Number / PDF Lock