

# Implementation Plan: Dynamic Approval Routing (Head → Admin/Direktur Fallback)

## 1. Latar Belakang & Tujuan

Karyawan dapat ditempatkan di lebih dari satu PT (multi-assignment), misalnya:
- **PT A**: memiliki Head/Kepala Divisi.
- **PT B**: tidak memiliki Head/Kepala Divisi.

Saat karyawan membuat dokumen di bawah konteks **PT B**, sistem harus otomatis menentukan approver:
- Jika **Head** tersedia di PT tersebut → dokumen di-route ke Head.
- Jika **Head tidak tersedia** → dokumen fallback ke **Admin** atau **Direktur**.

Tujuan implementasi:
1. Menentukan approver secara dinamis berdasarkan struktur organisasi PT terkait, bukan PT asal karyawan.
2. Mengirim notifikasi yang jelas kepada pemohon dokumen tentang siapa yang akan approve.
3. Menyediakan fallback yang konsisten dan dapat diaudit ketika role Head kosong.

---

## 2. Asumsi & Pertanyaan Terbuka

> ⚠️ Perlu dikonfirmasi ke stakeholder/PM sebelum development dimulai:

| # | Pertanyaan | Asumsi Sementara |
|---|------------|-------------------|
| 1 | Prioritas fallback: Admin dulu atau Direktur dulu? | Admin diprioritaskan, Direktur sebagai fallback kedua |
| 2 | Pengecekan Head berdasarkan PT dokumen dibuat (PT B) atau PT induk karyawan (PT A)? | Berdasarkan **PT tempat dokumen dibuat** |
| 3 | Jika ada lebih dari satu Admin di PT B, siapa yang approve? | Semua Admin aktif menerima notifikasi; siapa yang approve duluan, dokumen selesai (first-approve-wins) |
| 4 | Apakah Direktur berlaku global (lintas PT) atau per-PT? | Direktur diasumsikan **per-PT**, bisa diubah ke global bila diperlukan |
| 5 | Bagaimana jika Admin & Direktur juga tidak ada? | Perlu didefinisikan: error state / escalation ke Super Admin |

---

## 3. Alur Logika (Business Logic)

```
FUNCTION getApprover(pt_context, dokumen):
    head = findActiveUser(pt = pt_context, role = "Head")

    IF head IS NOT NULL:
        approver = head
        notifikasi = "Dokumen Anda akan di-approve oleh {head.nama} (Head)"
    ELSE:
        admin = findActiveUser(pt = pt_context, role = "Admin")
        direktur = findActiveUser(pt = pt_context, role = "Direktur")

        IF admin IS NOT NULL:
            approver = admin
            notifikasi = "Tidak ada Head di {pt_context}, dokumen akan di-approve oleh {admin.nama} (Admin)"
        ELSE IF direktur IS NOT NULL:
            approver = direktur
            notifikasi = "Tidak ada Head/Admin di {pt_context}, dokumen akan di-approve oleh {direktur.nama} (Direktur)"
        ELSE:
            approver = NULL
            notifikasi = "Approver tidak ditemukan, silakan hubungi Super Admin"
            TRIGGER escalation_alert

    RETURN approver, notifikasi
```

### Diagram Alur (Flowchart Sederhana)

```
[Dokumen dibuat di PT B]
        |
        v
[Cek: Ada Head aktif di PT B?] --Ya--> [Route ke Head] --> [Notifikasi: "akan di-approve oleh <Head>"]
        |
        No
        v
[Cek: Ada Admin aktif di PT B?] --Ya--> [Route ke Admin] --> [Notifikasi: "akan di-approve oleh <Admin>"]
        |
        No
        v
[Cek: Ada Direktur aktif di PT B?] --Ya--> [Route ke Direktur] --> [Notifikasi: "akan di-approve oleh <Direktur>"]
        |
        No
        v
[Escalation ke Super Admin] --> [Notifikasi error/alert]
```

---

## 4. Perubahan Data Model

### Tabel/Entity yang Terlibat (perlu disesuaikan dengan skema aktual)

**`users`**
- `id`
- `nama`
- `role` (Head, Admin, Direktur, Staff, dll.)
- `pt_id` (PT tempat user berperan sebagai approver)
- `status` (active/inactive)

**`documents`**
- `id`
- `pt_context_id` (PT tempat dokumen dibuat — **kunci utama untuk routing**)
- `created_by`
- `approver_id` (nullable, diisi setelah routing logic dijalankan)
- `approver_role` (Head/Admin/Direktur — untuk audit trail)
- `status` (pending, approved, rejected)

**`document_approval_logs`** (baru, untuk audit trail)
- `id`
- `document_id`
- `evaluated_role` (role yang dicek: Head → Admin → Direktur)
- `result` (found/not_found)
- `timestamp`

> Tabel log ini penting supaya tim bisa audit kenapa suatu dokumen di-route ke Admin/Direktur, bukan Head.

---

## 5. Perubahan Backend (API/Service Layer)

1. **Service baru: `ApprovalRoutingService`**
   - Method: `resolveApprover(ptContextId, documentType)`
   - Berisi logika fallback Head → Admin → Direktur.
   - Dipanggil setiap kali dokumen baru dibuat/diajukan.

2. **Modifikasi endpoint pembuatan dokumen**
   - Setelah dokumen tersimpan, panggil `ApprovalRoutingService` untuk set `approver_id` dan `approver_role`.
   - Simpan hasil evaluasi ke `document_approval_logs`.

3. **Modifikasi notification service**
   - Kirim **dua notifikasi** setiap kali dokumen diajukan:
     a. Ke **requester** — info siapa approver-nya (lihat Bagian 7.1).
     b. Ke **approver** (Head/Admin/Direktur) — info ada dokumen baru yang menunggu approval-nya (lihat Bagian 7.2).
   - Template notifikasi dinamis berdasarkan `approver_role`, termasuk deep-link ke halaman approval.
   - Jika approver adalah multi-Admin, kirim notifikasi "sudah di-approve" ke Admin lain setelah salah satu Admin approve, untuk mencegah duplikasi aksi.

4. **Handle multi-approver (jika Admin > 1)**
   - Broadcast notifikasi ke semua Admin aktif.
   - Approval bersifat first-come-first-approve, dengan lock/transaction untuk mencegah race condition (dua admin approve bersamaan).

---

## 6. Perubahan Frontend/UI

1. **Halaman pembuatan dokumen**
   - Tampilkan info approver yang akan menangani dokumen (real-time preview sebelum submit, jika memungkinkan).

2. **Halaman detail dokumen**
   - Tampilkan `approver_role` (Head/Admin/Direktur) sebagai badge, supaya user paham kenapa approver-nya bukan Head.

3. **Dashboard Admin/Direktur**
   - Tambahkan filter/tab: "Dokumen fallback (bukan dari Head)" agar Admin/Direktur bisa memprioritaskan dokumen yang memang tanggung jawab mereka.

---

## 7. Notifikasi

Ada dua arah notifikasi yang perlu dikirim setiap kali dokumen diajukan: **ke pemohon** (info siapa yang akan approve) dan **ke approver** (info ada dokumen yang menunggu approval-nya).

### 7.1 Notifikasi ke Pemohon (Requester)

| Kondisi | Isi Notifikasi |
|---|---|
| Head tersedia | "Dokumen Anda akan di-approve oleh **{nama_head}** (Head)" |
| Head tidak tersedia, Admin tersedia | "Tidak ada Head di **{PT}**, dokumen akan di-approve oleh **{nama_admin}** (Admin)" |
| Head & Admin tidak tersedia, Direktur tersedia | "Tidak ada Head/Admin di **{PT}**, dokumen akan di-approve oleh **{nama_direktur}** (Direktur)" |
| Tidak ada approver sama sekali | "Approver tidak ditemukan di **{PT}**, tim support telah diberi tahu" (+ alert internal) |

### 7.2 Notifikasi ke Approver (Head/Admin/Direktur)

Setiap kali `resolveApprover()` selesai menentukan approver, sistem juga mengirim notifikasi ke approver terkait bahwa ada dokumen baru yang menunggu approval-nya.

| Kondisi | Penerima | Isi Notifikasi |
|---|---|---|
| Approver = Head | Head yang bersangkutan | "Anda memiliki permintaan approval baru dari **{nama_requester}** ({PT}) — [Nama Dokumen]" |
| Approver = Admin (fallback) | Semua Admin aktif di PT tersebut | "Ada permintaan approval baru (fallback, tidak ada Head) dari **{nama_requester}** ({PT}) — [Nama Dokumen]" |
| Approver = Direktur (fallback) | Direktur yang bersangkutan | "Ada permintaan approval baru (fallback, tidak ada Head/Admin) dari **{nama_requester}** ({PT}) — [Nama Dokumen]" |

Catatan implementasi:
- Notifikasi ke approver dikirim **bersamaan** dengan proses `resolveApprover()`, bukan menunggu aksi tambahan dari requester — jadi satu event pembuatan dokumen memicu dua notifikasi (ke requester dan ke approver).
- Untuk kasus multi-Admin (lebih dari satu Admin aktif di PT), semua Admin menerima notifikasi yang sama; begitu salah satu approve, kirim notifikasi lanjutan ke Admin lain bahwa dokumen "sudah di-approve oleh {nama}, tidak perlu action lagi" (mencegah duplikasi approval).
- Sertakan link/deep-link langsung ke halaman approval dokumen di dalam notifikasi approver agar mempercepat proses.
- Channel notifikasi (in-app, email, push) mengikuti konfigurasi channel yang sudah ada di sistem notifikasi saat ini — perlu dikonfirmasi apakah semua channel dipakai untuk notifikasi approver atau hanya sebagian.

---

## 8. Testing Plan

### Unit Test
- [ ] `resolveApprover()` mengembalikan Head jika tersedia.
- [ ] `resolveApprover()` fallback ke Admin jika Head tidak ada.
- [ ] `resolveApprover()` fallback ke Direktur jika Head & Admin tidak ada.
- [ ] `resolveApprover()` mengembalikan error/escalation jika semua kosong.

### Integration Test
- [ ] Dokumen dibuat di PT dengan Head → notifikasi & approver benar.
- [ ] Dokumen dibuat di PT tanpa Head, ada Admin → notifikasi & approver benar.
- [ ] Dokumen dibuat di PT tanpa Head & Admin, ada Direktur → notifikasi & approver benar.
- [ ] Dua Admin approve dokumen yang sama secara bersamaan → hanya satu approval yang tercatat (race condition test).

### UAT (User Acceptance Test)
- [ ] User di PT A & PT B mencoba membuat dokumen di kedua PT, memverifikasi approver berbeda sesuai struktur masing-masing PT.

---

## 9. Rollout Plan

| Fase | Aktivitas | Estimasi |
|---|---|---|
| 1 | Finalisasi jawaban atas pertanyaan terbuka (Bagian 2) bersama stakeholder | 1-2 hari |
| 2 | Desain data model & migrasi (`document_approval_logs`) | 1 hari |
| 3 | Implementasi `ApprovalRoutingService` + unit test | 2-3 hari |
| 4 | Integrasi ke endpoint dokumen & notification service | 2 hari |
| 5 | Update frontend (badge approver, dashboard filter) | 2 hari |
| 6 | Integration test & UAT | 2 hari |
| 7 | Deploy staging → review → deploy production | 1-2 hari |

---

## 10. Risiko & Mitigasi

| Risiko | Mitigasi |
|---|---|
| Race condition saat multiple Admin approve bersamaan | Gunakan database transaction/lock saat approval |
| Salah menentukan PT context (PT A vs PT B) | Pastikan `pt_context_id` diambil dari PT tempat dokumen dibuat, bukan PT utama user |
| Tidak ada approver sama sekali (Head/Admin/Direktur kosong) | Buat mekanisme escalation otomatis ke Super Admin + alert |
| Perubahan role user di tengah proses approval | Snapshot `approver_id` saat dokumen dibuat, jangan re-evaluate otomatis kecuali di-trigger manual |

---

## 11. Next Steps

1. Review & konfirmasi jawaban Bagian 2 (Asumsi & Pertanyaan Terbuka) dengan stakeholder.
2. Validasi skema database aktual (nama tabel/kolom sebenarnya mungkin berbeda dari asumsi di sini).
3. Breakdown task ke tiket (Jira/Trello) berdasarkan Bagian 5-6.
4. Mulai development sesuai rollout plan di Bagian 9.
