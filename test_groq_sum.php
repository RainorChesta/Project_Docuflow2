<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$key = config('services.groq.key');
$res = Illuminate\Support\Facades\Http::withToken($key)
    ->withoutVerifying()
    ->get('https://api.groq.com/openai/v1/models');
$models = collect($res->json('data'))->pluck('id')->sort()->values();
echo "Available Groq models:\n";
foreach ($models as $m) {
    echo "- $m\n";
}
exit;
