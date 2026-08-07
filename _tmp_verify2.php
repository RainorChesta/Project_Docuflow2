<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$d = App\Models\Document::with('versions')->find(1);
$user = App\Models\User::find(1);
$svc = app(App\Services\PdfExportService::class);
$result = $svc->export($d, $user);
echo "EXPORTED: " . $result['path'] . "\n";

$pdf = file_get_contents(storage_path('app/private/' . $result['path']));
echo "PDF SIZE: " . strlen($pdf) . " bytes\n";
echo "HAS /Subtype /Image: " . (str_contains($pdf, '/Subtype /Image') ? 'YES' : 'NO') . "\n";

// Cek dimensi gambar yang di-embed (W/H di objek image)
preg_match_all('/\/Width\s+(\d+)/', $pdf, $w);
preg_match_all('/\/Height\s+(\d+)/', $pdf, $h);
echo "IMG WIDTHS: " . implode(', ', $w[1]) . "\n";
echo "IMG HEIGHTS: " . implode(', ', $h[1]) . "\n";