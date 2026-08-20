<?php
$enPath = __DIR__ . '/lang/en.json';
$idPath = __DIR__ . '/lang/id.json';

$en = json_decode(file_get_contents($enPath), true);
$id = json_decode(file_get_contents($idPath), true);

$newKeys = [
    // Signature Requests
    "Signature Approvals & History" => ["en" => "Signature Approvals & History", "id" => "Persetujuan & Riwayat TTD"],
    "Incoming Requests (Your Signature Approval)" => ["en" => "Incoming Requests (Your Signature Approval)", "id" => "Permintaan Masuk (Persetujuan TTD Anda)"],
    "List of users requesting to use your signature in their documents." => ["en" => "List of users requesting to use your signature in their documents.", "id" => "Daftar pengguna yang meminta persetujuan untuk menyisipkan tanda tangan Anda dalam dokumen mereka."],
    "No incoming requests at this time." => ["en" => "No incoming requests at this time.", "id" => "Tidak ada permintaan masuk saat ini."],
    "Signature Requester" => ["en" => "Signature Requester", "id" => "Peminta TTD"],
    "Request Time" => ["en" => "Request Time", "id" => "Waktu Permintaan"],
    "Action" => ["en" => "Action", "id" => "Aksi"],
    "General Document / Context" => ["en" => "General Document / Context", "id" => "Dokumen Umum / Konteks"],
    "Approved" => ["en" => "Approved", "id" => "Disetujui"],
    "Rejected" => ["en" => "Rejected", "id" => "Ditolak"],
    "Reject Signature Request" => ["en" => "Reject Signature Request", "id" => "Tolak Permintaan TTD"],
    "Reject use of your signature by :name." => ["en" => "Reject use of your signature by :name.", "id" => "Tolak penggunaan tanda tangan Anda oleh :name."],
    "Rejection Reason (Optional)" => ["en" => "Rejection Reason (Optional)", "id" => "Alasan Penolakan (Opsional)"],
    "Enter rejection reason..." => ["en" => "Enter rejection reason...", "id" => "Masukkan alasan penolakan..."],
    "Reject Request" => ["en" => "Reject Request", "id" => "Tolak Permintaan"],
    "Done" => ["en" => "Done", "id" => "Selesai"],
    "My Request History" => ["en" => "My Request History", "id" => "Riwayat Permintaan Saya"],
    "List of other users' signatures you requested for use in your documents." => ["en" => "List of other users' signatures you requested for use in your documents.", "id" => "Daftar tanda tangan pengguna lain yang Anda minta untuk digunakan dalam dokumen Anda."],
    "You have never requested another user's signature approval." => ["en" => "You have never requested another user's signature approval.", "id" => "Anda belum pernah meminta persetujuan TTD pengguna lain."],
    "Target Signature Owner" => ["en" => "Target Signature Owner", "id" => "Pemilik TTD Target"],
    "Notes" => ["en" => "Notes", "id" => "Catatan"],
    "Signature Approvals" => ["en" => "Signature Approvals", "id" => "Persetujuan TTD"],

    // Document Show / Share
    "Share" => ["en" => "Share", "id" => "Bagikan"],
    "Summarize Document" => ["en" => "Summarize Document", "id" => "Ringkas Dokumen"],
    "PDF successfully created." => ["en" => "PDF successfully created.", "id" => "PDF berhasil dibuat."],
    "AI Document Summary" => ["en" => "AI Document Summary", "id" => "Ringkasan AI Dokumen"],
    "Automatic summary based on original document content" => ["en" => "Automatic summary based on original document content", "id" => "Ringkasan otomatis berbasis konten asli dokumen"],
    "AI Model:" => ["en" => "AI Model:", "id" => "Model AI:"],
    "Density:" => ["en" => "Density:", "id" => "Kepadatan:"],
    "Copy Summary" => ["en" => "Copy Summary", "id" => "Salin Ringkasan"],
    "Re-summarize" => ["en" => "Re-summarize", "id" => "Ringkas Ulang"],
    "AI is reading & summarizing the document... Please wait a moment." => ["en" => "AI is reading & summarizing the document... Please wait a moment.", "id" => "AI sedang membaca & meringkas dokumen... Mohon tunggu sebentar."],
    "Failed to create summary. Please try again." => ["en" => "Failed to create summary. Please try again.", "id" => "Ringkasan gagal dibuat. Silakan coba lagi."],
    "Please try again." => ["en" => "Please try again.", "id" => "Silakan coba lagi."],
    "Share \":title\"" => ["en" => "Share \":title\"", "id" => "Bagikan \":title\""],
    "Search user name or division…" => ["en" => "Search user name or division…", "id" => "Cari nama pengguna atau divisi…"],
    "Add people or divisions to access this document." => ["en" => "Add people or divisions to access this document.", "id" => "Tambahkan orang atau divisi untuk mengakses dokumen ini."],
    "People with access" => ["en" => "People with access", "id" => "Orang dengan akses"],
    "General access" => ["en" => "General access", "id" => "Akses umum"],
    "Restricted — invited people only" => ["en" => "Restricted — invited people only", "id" => "Restricted — hanya orang yang diundang"],
    "Anyone with the link" => ["en" => "Anyone with the link", "id" => "Siapa saja yang punya link"],
    "Create new link" => ["en" => "Create new link", "id" => "Buat link baru"],
    "Export to PDF" => ["en" => "Export to PDF", "id" => "Export ke PDF"],
    "Creating PDF…" => ["en" => "Creating PDF…", "id" => "Membuat PDF…"],
    "Margin follows current document margin; if it doesn't fit in the selected paper, the margin will be adjusted automatically." => ["en" => "Margin follows current document margin; if it doesn't fit in the selected paper, the margin will be adjusted automatically.", "id" => "Margin tetap mengikuti margin dokumen saat ini; kalau tidak muat di kertas yang dipilih, margin akan disesuaikan otomatis."],
    "No other access yet." => ["en" => "No other access yet.", "id" => "Belum ada akses lain."],
    "Processing…" => ["en" => "Processing…", "id" => "Memproses…"],
    "Visible to all users." => ["en" => "Visible to all users.", "id" => "Terlihat oleh semua pengguna."],
    "Only division :code can view." => ["en" => "Only division :code can view.", "id" => "Hanya divisi :code yang bisa melihat."],
    "Only you can view." => ["en" => "Only you can view.", "id" => "Hanya kamu yang bisa melihat."],
    "Rollback to v:version?" => ["en" => "Rollback to v:version?", "id" => "Rollback ke v:version?"],
    "Submit Rollback" => ["en" => "Submit Rollback", "id" => "Ajukan Rollback"],
    "Rollback request will be submitted to the division head. If approved, all versions after v:version will be permanently deleted." => ["en" => "Rollback request will be submitted to the division head. If approved, all versions after v:version will be permanently deleted.", "id" => "Permintaan rollback akan diajukan ke kepala divisi. Jika disetujui, semua versi setelah v:version akan dihapus permanen."],
    "Rollback to v:version pending approval — other rollback options disabled temporarily." => ["en" => "Rollback to v:version pending approval — other rollback options disabled temporarily.", "id" => "Rollback ke v:version sedang menunggu approval — opsi rollback lain dinonaktifkan sementara."],
    "File" => ["en" => "File", "id" => "Berkas"],
    "Loading…" => ["en" => "Loading…", "id" => "Memuat…"],
    "Failed to load access data." => ["en" => "Failed to load access data.", "id" => "Gagal memuat data akses."],
    "Failed to create new link." => ["en" => "Failed to create new link.", "id" => "Gagal membuat link baru."],
    "New link created successfully" => ["en" => "New link created successfully", "id" => "Link baru berhasil dibuat"],
    "Failed to copy automatically. Please copy the link below manually:" => ["en" => "Failed to copy automatically. Please copy the link below manually:", "id" => "Gagal menyalin otomatis. Silakan salin link berikut secara manual:"],
    "Copied" => ["en" => "Copied", "id" => "Tersalin"],
    "Not found." => ["en" => "Not found.", "id" => "Tidak ditemukan."],
    "Copy" => ["en" => "Copy", "id" => "Salin"],

    // Edit Page
    "There is a pending version (v:version) not yet reviewed. Save will update the pending version (no new version)." => ["en" => "There is a pending version (v:version) not yet reviewed. Save will update the pending version (no new version).", "id" => "Ada versi pending (v:version) yang belum di-review. Save akan memperbarui versi pending tersebut (tanpa versi baru)."],
    "Save Changes submits the draft for approval (status becomes pending)." => ["en" => "Save Changes submits the draft for approval (status becomes pending).", "id" => "Save Changes mengirim draft untuk approval (status jadi pending)."],
    "Save will create a new version awaiting Head approval." => ["en" => "Save will create a new version awaiting Head approval.", "id" => "Save akan membuat versi baru yang menunggu approval Head."],
    "The existing pending version will be updated (not a new version)." => ["en" => "The existing pending version will be updated (not a new version).", "id" => "Versi pending yang ada akan diperbarui (bukan versi baru)."],
    "Manual input based on file" => ["en" => "Manual input based on file", "id" => "Isi manual sesuai berkas"],
    "Failed to load preview" => ["en" => "Failed to load preview", "id" => "Gagal memuat preview"],

    // Other
    "Jenis Dokumen" => ["en" => "Document Types", "id" => "Jenis Dokumen"],
    "Pending" => ["en" => "Pending", "id" => "Menunggu"]
];

foreach ($newKeys as $key => $values) {
    if (!isset($en[$key])) {
        $en[$key] = $values['en'];
    }
    if (!isset($id[$key])) {
        $id[$key] = $values['id'];
    }
}

file_put_contents($enPath, json_encode($en, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
file_put_contents($idPath, json_encode($id, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Translations updated.\n";
