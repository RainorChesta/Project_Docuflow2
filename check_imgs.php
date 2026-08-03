<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach ([27, 28, 29, 30] as $id) {
    $d = App\Models\Document::find($id);
    $v = $d ? $d->displayVersion() : null;
    $hasImg = $v && str_contains($v->content, '<img');
    $len = $v ? strlen($v->content) : 0;
    echo "$id: " . ($hasImg ? 'IMG' : 'no-img') . " len=$len\n";
}
