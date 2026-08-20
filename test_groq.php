<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$key = config('services.groq.key');
$model = config('services.groq.model');

echo "Key: " . substr($key, 0, 10) . "...\n";
echo "Model: $model\n";

$response = Illuminate\Support\Facades\Http::withToken($key)
    ->withoutVerifying()
    ->post('https://api.groq.com/openai/v1/chat/completions', [
        'model' => $model,
        'messages' => [
            ['role' => 'user', 'content' => 'Hello']
        ],
    ]);

echo "Status: " . $response->status() . "\n";
echo "Body: " . $response->body() . "\n";
