<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Doc dengan gambar (dari investigasi sebelumnya: doc 1 & 2 punya <img>)
$d = App\Models\Document::with('versions')->find(1);
$content = $d?->displayVersion()?->content ?? '';
preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $content, $m);
echo "DOC: " . ($d?->title ?? 'null') . "\n";
echo "IMG COUNT: " . count($m[1]) . "\n";
foreach ($m[1] as $src) {
    echo "  SRC: {$src}\n";
    echo "  public_path: " . public_path(ltrim($src, '/')) . "\n";
    echo "  is_file: " . (is_file(public_path(ltrim($src, '/'))) ? 'YES' : 'NO') . "\n";
}
