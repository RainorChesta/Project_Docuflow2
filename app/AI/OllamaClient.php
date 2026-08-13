<?php

namespace App\AI;

use App\AI\Contracts\AIClientInterface;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Klien HTTP minimal untuk Ollama API (menggunakan endpoint /api/generate native).
 */
class OllamaClient implements AIClientInterface
{
    public function __construct(
        private readonly string $baseUrl = 'http://127.0.0.1:11434',
        private readonly string $model = 'qwen2.5:1.5b'
    ) {}

    public function chat(string $system, string $content): string
    {
        $response = Http::timeout((int) config('services.ollama.timeout', 120))
            ->post(rtrim($this->baseUrl, '/') . '/api/generate', [
                'model' => $this->model,
                'system' => $system,
                'prompt' => $content,
                'stream' => false,
                'options' => [
                    'num_predict' => (int) config('services.ollama.max_tokens', 1500),
                    'temperature' => (float) config('services.ollama.temperature', 0.2),
                    'top_p' => 0.9,
                ]
            ]);

        if ($response->failed()) {
            $status = $response->status();
            $error = $response->json('error') ?? $response->body();
            throw new RuntimeException("Ollama API Error [{$status}]: {$error}");
        }

        $result = $response->json('response');

        if (!is_string($result)) {
            throw new RuntimeException("Format respons Ollama tidak dikenali.");
        }

        return trim($result);
    }

    public function chatBatch(array $payloads, int $concurrency = 3): array
    {
        $results = [];
        $batches = array_chunk($payloads, $concurrency, true);
        
        $timeout = (int) config('services.ollama.timeout', 120);
        $temperature = (float) config('services.ollama.temperature', 0.2);
        $maxTokens = (int) config('services.ollama.max_tokens', 1500);

        foreach ($batches as $batch) {
            $responses = Http::pool(function (\Illuminate\Http\Client\Pool $pool) use ($batch, $timeout, $temperature, $maxTokens) {
                $reqs = [];
                foreach ($batch as $key => $payload) {
                    $reqs[$key] = $pool->timeout($timeout)
                        ->post(rtrim($this->baseUrl, '/') . '/api/generate', [
                            'model' => $this->model,
                            'system' => $payload['system'],
                            'prompt' => $payload['content'],
                            'stream' => false,
                            'options' => [
                                'num_predict' => $maxTokens,
                                'temperature' => $temperature,
                                'top_p' => 0.9,
                            ]
                        ]);
                }
                return $reqs;
            });

            foreach ($responses as $key => $response) {
                if ($response instanceof \Exception) {
                    throw new RuntimeException("Ollama API Connection Error: " . $response->getMessage());
                }

                if ($response->failed()) {
                    $status = $response->status();
                    $error = $response->json('error') ?? $response->body();
                    throw new RuntimeException("Ollama API Error [{$status}]: {$error}");
                }

                $result = $response->json('response');
                
                if (!is_string($result)) {
                    throw new RuntimeException("Format respons Ollama tidak dikenali.");
                }

                $results[$key] = trim($result);
            }
        }

        return $results;
    }
}
