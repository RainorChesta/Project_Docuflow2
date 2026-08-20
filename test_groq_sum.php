<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$key = config('services.groq.key');
$model = config('services.groq.model');

$system = App\AI\Prompts\DocumentSummaryPrompt::chunkSystem(30);
$content = App\AI\Prompts\DocumentSummaryPrompt::chunkContent('PT Digital Nusantara Teknologi adalah perusahaan teknologi yang didirikan pada tahun 2010. Perusahaan ini berfokus pada pengembangan perangkat lunak untuk UMKM.');

$client = new App\AI\GroqClient($key, $model);
try {
    $result = $client->chat($system, $content);
    echo "SUCCESS:\n";
    echo $result;
} catch (\Exception $e) {
    echo "ERROR:\n";
    echo $e->getMessage();
}
