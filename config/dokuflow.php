<?php

return [
    // Kode pusat tetap - dipakai di semua nomor dokumen
    'central_code' => 'JBM',

    // Konfigurasi ringkasan AI (chunking). Ukuran karakter adalah
    // perkiraan kasar token — untuk produksi bisa diganti chunking
    // berbasis token (lihat DocumentSummarizer::chunk()).
    'ai_summary' => [
        'chunk_size' => (int) env('AI_SUMMARY_CHUNK_SIZE', 12000),
        'chunk_overlap' => (int) env('AI_SUMMARY_CHUNK_OVERLAP', 200),
    ],
];
