<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "USERS:\n";
foreach (App\Models\User::all() as $u) {
    echo $u->id . ' ' . ($u->name ?? '') . ' div=' . ($u->division_id ?? 'null') . ' active=' . ($u->is_active ? '1' : '0') . "\n";
}
echo "DOCS:\n";
foreach (App\Models\Document::all() as $d) {
    echo $d->id . ' owner=' . $d->owner_id . ' div=' . ($d->division_id ?? 'null') . ' vis=' . $d->visibility . ' cur=' . ($d->current_version_id ?? 'null') . ' title=' . $d->title . "\n";
}
