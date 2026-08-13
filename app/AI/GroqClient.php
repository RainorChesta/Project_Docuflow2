<?php

namespace App\AI;

use App\AI\Contracts\AIClientInterface;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Klien HTTP minimal untuk Groq API. Seluruh akses ke Groq lewat sini —
 * tidak ada kode lain yang memanggil endpoint Groq secara langsung.
 */
class GroqClient implements AIClientInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
    ) {}

    /**
     * Kirim satu permintaan chat completion. Konfigurasi model, temperatur,
     * dan timeout dibaca dari config/services.php (dari .env).
     *
     * @param  string  $system  Instruksi sistem (tidak boleh dari input user).
     * @param  string  $content  Isi dokumen/chunk.
     * @throws RuntimeException Bila API error atau respons tidak valid.
     */
    public function chat(string $system, string $content): string
    {
        $response = Http::withToken($this->apiKey)
            ->timeout((int) config('services.groq.timeout', 90))
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $content],
                ],
                'temperature' => (float) config('services.groq.temperature', 0.2),
                'max_tokens' => (int) config('services.groq.max_tokens', 800),
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Groq API error: HTTP ' . $response->status());
        }

        $text = trim($response->json('choices.0.message.content') ?? '');

        if ($text === '') {
            throw new RuntimeException('Groq API mengembalikan respons kosong.');
        }

        return $text;
    }

    /**
     * Kirim beberapa permintaan chat completion secara paralel/concurrent dengan batasan batch
     * untuk mencegah Rate Limit dari Groq.
     * 
     * @param array $payloads Array asosiatif berisi [['system' => '...', 'content' => '...'], ...]
     * @param int $concurrency Maksimal request paralel per batch
     */
    public function chatBatch(array $payloads, int $concurrency = 3): array
    {
        $results = [];
        $batches = array_chunk($payloads, $concurrency, true);
        
        $timeout = (int) config('services.groq.timeout', 90);
        $temperature = (float) config('services.groq.temperature', 0.2);
        $maxTokens = (int) config('services.groq.max_tokens', 800);

        foreach ($batches as $batch) {
            $responses = Http::pool(function (\Illuminate\Http\Client\Pool $pool) use ($batch, $timeout, $temperature, $maxTokens) {
                $reqs = [];
                foreach ($batch as $key => $payload) {
                    $reqs[] = $pool->as((string) $key)
                        ->withToken($this->apiKey)
                        ->timeout($timeout)
                        ->post('https://api.groq.com/openai/v1/chat/completions', [
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
                    throw new RuntimeException('Groq API error pada batch request: ' . $response->getMessage());
                }
                
                if ($response->failed()) {
                    throw new RuntimeException('Groq API error pada batch request: HTTP ' . $response->status());
                }

                $text = trim($response->json('choices.0.message.content') ?? '');
                if ($text === '') {
                    throw new RuntimeException('Groq API mengembalikan respons kosong pada batch request.');
                }
                $results[$key] = $text;
            }
        }

        return $results;
    }
}
