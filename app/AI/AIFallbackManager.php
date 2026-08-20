<?php

namespace App\AI;

use App\AI\Contracts\AIClientInterface;
use RuntimeException;
use Throwable;

class AIFallbackManager implements AIClientInterface
{
    /**
     * @param AIClientInterface[] $clients
     */
    public function __construct(
        private readonly array $clients
    ) {
        if (empty($this->clients)) {
            throw new RuntimeException('AIFallbackManager requires at least one AI client.');
        }
    }

    public function chat(string $system, string $content): string
    {
        $errors = [];

        foreach ($this->clients as $client) {
            try {
                return $client->chat($system, $content);
            } catch (Throwable $e) {
                // Log exception if needed, then continue to the next client
                report($e);
                $errors[] = class_basename($client) . ': ' . $e->getMessage();
            }
        }

        throw new RuntimeException("Semua AI client gagal.\nDetail Error:\n" . implode("\n", $errors));
    }

    public function chatBatch(array $payloads, int $concurrency = 3): array
    {
        $errors = [];

        foreach ($this->clients as $client) {
            try {
                return $client->chatBatch($payloads, $concurrency);
            } catch (Throwable $e) {
                // Log exception if needed, then continue to the next client
                report($e);
                $errors[] = class_basename($client) . ': ' . $e->getMessage();
            }
        }

        throw new RuntimeException("Semua AI client gagal pada batch request.\nDetail Error:\n" . implode("\n", $errors));
    }
}
