<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Berita Acara Penyusutan / Pemusnahan Arsip') }} - {{ $disposition->certificate_number }}</title>
    <style>
        @page { size: A4; margin: 2.5cm; }
        body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; line-height: 1.6; color: #111; margin: 0 auto; max-width: 800px; padding: 2cm 1cm; background: #fff; }
        .header { text-align: center; border-bottom: 3px double #111; padding-bottom: 12px; margin-bottom: 24px; }
        .header h1 { font-size: 15pt; text-transform: uppercase; margin: 0; letter-spacing: 1px; }
        .header h2 { font-size: 13pt; margin: 4px 0 0 0; font-weight: normal; }
        .cert-number { font-family: 'Courier New', monospace; font-size: 11pt; font-weight: bold; margin-top: 6px; }
        .statement { text-align: justify; margin: 16px 0; }
        .details-table { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 11pt; }
        .details-table th, .details-table td { border: 1px solid #333; padding: 8px 12px; text-align: left; }
        .details-table th { background-color: #f2f2f2; width: 35%; }
        .signatures { margin-top: 48px; width: 100%; display: flex; justify-content: space-between; }
        .sig-box { width: 45%; text-align: center; }
        .sig-space { height: 80px; }
        .print-btn { position: fixed; top: 20px; right: 20px; padding: 10px 18px; background: #2563eb; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; }
        @media print { .print-btn { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">Cetak Berita Acara</button>

    <div class="header">
        <h1>BERITA ACARA PENYUSUTAN / PEMUSNAHAN REKAMAN ARSIP</h1>
        <h2>DOKUFLOW RECORDS MANAGEMENT SYSTEM</h2>
        <div class="cert-number">Nomor Sertifikat: {{ $disposition->certificate_number }}</div>
    </div>

    <div class="statement">
        Pada hari ini, tanggal <strong>{{ $disposition->executed_at?->isoFormat('D MMMM Y') ?? now()->isoFormat('D MMMM Y') }}</strong>, bertempat di lingkungan kantor {{ $archive->company_name_snapshot ?? 'Perusahaan' }}, berdasarkan verifikasi Jadwal Retensi Arsip (JRA) dan persetujuan pejabat yang berwenang, telah dilakukan tindakan penyusutan/pemusnahan resmi terhadap rekaman arsip di bawah ini:
    </div>

    <table class="details-table">
        <tr>
            <th>Nomor Arsip Resmi</th>
            <td><strong style="font-family: monospace;">{{ $archive->archive_number }}</strong></td>
        </tr>
        <tr>
            <th>Judul Rekaman Arsip</th>
            <td>{{ $archive->title }}</td>
        </tr>
        <tr>
            <th>Kode Klasifikasi</th>
            <td>{{ $archive->classification_code_snapshot }} - {{ $archive->classification_name_snapshot }}</td>
        </tr>
        <tr>
            <th>Unit / Cabang Asal</th>
            <td>{{ $archive->company_code_snapshot }} / {{ $archive->branch_code_snapshot }} / {{ $archive->unit_code_snapshot }}</td>
        </tr>
        <tr>
            <th>Tanggal Registrasi Asli</th>
            <td>{{ $archive->registered_at?->format('d F Y H:i:s') }}</td>
        </tr>
        <tr>
            <th>Tindakan Eksekusi</th>
            <td><strong style="text-transform: uppercase;">{{ $disposition->action_type }}</strong></td>
        </tr>
        <tr>
            <th>Dasar Justifikasi</th>
            <td>{{ $disposition->justification }}</td>
        </tr>
        <tr>
            <th>Sidik Jari SHA-256 Berkas</th>
            <td style="font-family: monospace; font-size: 9pt; word-break: break-all;">
                {{ $disposition->certificate_snapshot['hash_witness']['content_hash_sha256'] ?? $archive->snapshot?->content_hash_sha256 ?? '-' }}
            </td>
        </tr>
    </table>

    <div class="statement">
        Demikian Berita Acara ini dibuat dengan sebenarnya dan dicatat secara permanen dalam buku register pembatalan dan audit DokuFlow, untuk dapat dipergunakan sebagaimana mestinya menurut hukum dan tata kelola kearsipan yang berlaku.
    </div>

    <table style="width: 100%; margin-top: 50px;">
        <tr>
            <td style="width: 50%; text-align: center;">
                <div>Pemohon Penyusutan,</div>
                <div style="height: 70px;"></div>
                <div><strong>{{ $disposition->requester?->name }}</strong></div>
                <div style="font-size: 10pt; color: #444;">{{ $disposition->requester?->nip ?? '-' }}</div>
            </td>
            <td style="width: 50%; text-align: center;">
                <div>Pejabat Berwenang / Penyetuju,</div>
                <div style="height: 70px;"></div>
                <div><strong>{{ $disposition->executor?->name ?? $disposition->reviewer?->name }}</strong></div>
                <div style="font-size: 10pt; color: #444;">{{ $disposition->executor?->nip ?? '-' }}</div>
            </td>
        </tr>
    </table>
</body>
</html>

