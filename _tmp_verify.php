<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$d = App\Models\Document::with('versions')->find(1);
$user = App\Models\User::find(1);
$svc = app(App\Services\PdfExportService::class);
$result = $svc->export($d, $user);
echo "EXPORTED: " . $result['path'] . "\n";

// Cek apakah PDF berisi objek gambar (XObject / /Image)
$pdf = file_get_contents(storage_path('app/private/' . $result['path']));
echo "PDF SIZE: " . strlen($pdf) . " bytes\n";
echo "HAS /Image: " . (str_contains($pdf, '/Image') ? 'YES' : 'NO') . "\n";
echo "HAS /Subtype /Image: " . (str_contains($pdf, '/Subtype /Image') ? 'YES' : 'NO') . "\n";
echo "HAS /XObject: " . (str_contains($pdf, '/XObject') ? 'YES' : 'NO') . "\n";