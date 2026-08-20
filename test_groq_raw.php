<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$key = config('services.groq.key');
$model = config('services.groq.model');

$system = App\AI\Prompts\DocumentSummaryPrompt::chunkSystem(30);
$content = App\AI\Prompts\DocumentSummaryPrompt::chunkContent('PT Digital Nusantara Teknologi adalah perusahaan teknologi yang didirikan pada tahun 2010. Perusahaan ini berfokus pada pengembangan perangkat lunak untuk UMKM.');

$response = Illuminate\Support\Facades\Http::withToken($key)
    ->withoutVerifying()
    ->timeout((int) config('services.groq.timeout', 90))
    ->post('https://api.groq.com/openai/v1/chat/completions', [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $content],
        ],
        'temperature' => (float) config('services.groq.temperature', 0.2),
        'max_tokens' => 4096,
    ]);

if ($response->failed()) {
    echo "FAILED: " . $response->body() . "\n";
} else {
    $text = trim($response->json('choices.0.message.content') ?? '');
    echo "SUCCESS: " . strlen($text) . " bytes\n";
    echo $text;
}
