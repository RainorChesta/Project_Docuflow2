<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach ([1, 3] as $id) {
    $d = App\Models\Document::find($id);
    echo 'Doc ' . $id . ': paper_size=' . var_export($d->paper_size, true)
        . ' paper_margin=' . json_encode($d->paper_margin) . PHP_EOL;
}