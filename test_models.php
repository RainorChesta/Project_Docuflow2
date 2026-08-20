<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$key = config('services.groq.key');

$response = Illuminate\Support\Facades\Http::withToken($key)
    ->withoutVerifying()
    ->get('https://api.groq.com/openai/v1/models');

echo $response->body();
