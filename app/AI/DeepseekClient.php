<?php

namespace App\AI;

use App\AI\Contracts\AIClientInterface;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DeepseekClient implements AIClientInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
    ) {}

    public function chat(string $system, string $content): string
    {
        $response = Http::withToken($this->apiKey)
            ->timeout((int) config('services.deepseek.timeout', 90))
            ->post('https://api.deepseek.com/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $content],
                ],
                'temperature' => (float) config('services.deepseek.temperature', 0.2),
                'max_tokens' => (int) config('services.deepseek.max_tokens', 800),
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Deepseek API error: HTTP ' . $response->status());
        }

        $text = trim($response->json('choices.0.message.content') ?? '');

        if ($text === '') {
            throw new RuntimeException('Deepseek API mengembalikan respons kosong.');
        }

        return $text;
    }

    public function chatBatch(array $payloads, int $concurrency = 3): array
    {
        $results = [];
        $batches = array_chunk($payloads, $concurrency, true);
        
        $timeout = (int) config('services.deepseek.timeout', 90);
        $temperature = (float) config('services.deepseek.temperature', 0.2);
        $maxTokens = (int) config('services.deepseek.max_tokens', 800);

        foreach ($batches as $batch) {
            $responses = Http::pool(function (\Illuminate\Http\Client\Pool $pool) use ($batch, $timeout, $temperature, $maxTokens) {
                $reqs = [];
                foreach ($batch as $key => $payload) {
                    $reqs[] = $pool->as((string) $key)
                        ->withToken($this->apiKey)
                        ->timeout($timeout)
                        ->post('https://api.deepseek.com/chat/completions', [
                            'model' => $this->model,
                            'messages' => [
                                ['role' => 'system', 'content' => $payload['system']],
                                ['role' => 'user', 'content' => $payload['content']],
                            ],
                            'temperature' => $temperature,
                            'max_tokens' => $maxTokens,
                        ]);
                }
                return $reqs;
            });

            foreach ($responses as $key => $response) {
                if ($response instanceof \Throwable) {
                    throw new RuntimeException('Deepseek API error pada batch request: ' . $response->getMessage());
                }
                
                if ($response->failed()) {
                    throw new RuntimeException('Deepseek API error pada batch request: HTTP ' . $response->status());
                }

                $text = trim($response->json('choices.0.message.content') ?? '');
                if ($text === '') {
                    throw new RuntimeException('Deepseek API mengembalikan respons kosong pada batch request.');
                }
                $results[$key] = $text;
            }
        }

        return $results;
    }
}
