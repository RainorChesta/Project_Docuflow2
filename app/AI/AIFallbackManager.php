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
        $lastException = null;

        foreach ($this->clients as $client) {
            try {
                return $client->chat($system, $content);
            } catch (Throwable $e) {
                // Log exception if needed, then continue to the next client
                report($e);
                $lastException = $e;
            }
        }

        throw new RuntimeException('All AI clients failed. Last error: ' . $lastException?->getMessage(), 0, $lastException);
    }

    public function chatBatch(array $payloads, int $concurrency = 3): array
    {
        $lastException = null;

        foreach ($this->clients as $client) {
            try {
                return $client->chatBatch($payloads, $concurrency);
            } catch (Throwable $e) {
                // Log exception if needed, then continue to the next client
                report($e);
                $lastException = $e;
            }
        }

        throw new RuntimeException('All AI clients failed on batch request. Last error: ' . $lastException?->getMessage(), 0, $lastException);
    }
}
