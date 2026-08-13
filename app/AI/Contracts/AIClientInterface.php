<?php

namespace App\AI\Contracts;

interface AIClientInterface
{
    /**
     * Kirim satu permintaan chat completion.
     *
     * @param  string  $system  Instruksi sistem
     * @param  string  $content Isi pesan user/dokumen
     * @return string           Respons teks dari AI
     * @throws \RuntimeException Bila terjadi error dari API
     */
    public function chat(string $system, string $content): string;

    /**
     * Kirim beberapa permintaan chat completion secara paralel/concurrent.
     *
     * @param  array  $payloads     Array asosiatif berisi [['system' => '...', 'content' => '...'], ...]
     * @param  int    $concurrency  Maksimal request paralel per batch
     * @return array                Array berisi respons teks dari AI
     * @throws \RuntimeException    Bila terjadi error dari API
     */
    public function chatBatch(array $payloads, int $concurrency = 3): array;
}
