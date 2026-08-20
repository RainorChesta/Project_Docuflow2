<?php

namespace App\AI\Prompts;

/**
 * Prompt template untuk ringkasan dokumen. Konstanta dipisah dari service
 * supaya prompt tidak bercampur dengan logika pemanggilan API.
 */
class DocumentSummaryPrompt
{
    /**
     * Resolve locale code ke nama bahasa yang dipahami LLM.
     */
    private static function languageName(string $locale): string
    {
        return match ($locale) {
            'id' => 'Indonesian',
            'en' => 'English',
            default => 'Indonesian',
        };
    }

    /**
     * Contoh filler-word yang harus dihindari, disesuaikan per bahasa.
     */
    private static function fillerExamples(string $locale): string
    {
        return match ($locale) {
            'en' => '"This document discusses...", "Summary:", or "The following is..."',
            default => '"Dokumen ini membahas...", "Ringkasan:", or "Berikut adalah..."',
        };
    }

    /**
     * Instruksi sistem untuk meringkas SATU chunk. Satu-satunya sumber
     * kebenaran adalah teks chunk yang dikirim — dilarang menambah
     * pengetahuan eksternal.
     */
    public static function chunkSystem(int $percentage = 30, string $locale = 'id'): string
    {
        $language = self::languageName($locale);
        $fillers = self::fillerExamples($locale);

        if ($percentage <= 30) {
            $brevityRule = "EXTREME BREVITY: The summary MUST be very short (around {$percentage}% of the original length). MAXIMUM LENGTH: 3 to 5 sentences. The summary MUST be strictly ONE single continuous paragraph.";
        } elseif ($percentage <= 50) {
            $brevityRule = "CONCISE SUMMARY: The summary MUST be concise (around {$percentage}% of the original length). MAXIMUM LENGTH: 2 short paragraphs.";
        } elseif ($percentage <= 70) {
            $brevityRule = "MODERATE SUMMARY: Provide a moderately detailed summary (around {$percentage}% of the original length). It should capture main points and important details.";
        } else {
            $brevityRule = "DETAILED SUMMARY: Provide a highly detailed and comprehensive summary (around {$percentage}% of the original length). Capture almost all information but rephrased continuously.";
        }

        return <<<PROMPT
You are a highly disciplined document summarizer. 

Task: Summarize the provided document text in {$language}.

ABSOLUTE RULES (YOU MUST OBEY THESE OR YOU WILL BE PENALIZED):
1. NO LISTS: You are STRICTLY FORBIDDEN from using bullet points, numbered lists, hyphens (-), or asterisks (*).
2. ONLY PARAGRAPHS: The entire output MUST be written as plain, continuous paragraphs.
3. {$brevityRule}
4. NO FILLER WORDS: Do not use introductory phrases like {$fillers}. Start immediately with the main facts.
5. NO HALLUCINATION: Only use facts explicitly stated in the document.
PROMPT;
    }

    public static function chunkContent(string $chunk, string $locale = 'id'): string
    {
        $language = self::languageName($locale);

        return "DOCUMENT CONTENT:\n---\n{$chunk}\n---\n\nNow, generate the summary in {$language} following ALL the rules above. DO NOT include the rules or any other text. OUTPUT ONLY THE SUMMARY:";
    }

    /**
     * Instruksi sistem untuk menggabungkan ringkasan chunk menjadi satu
     * ringkasan final. Hanya boleh memakai info yang ada di ringkasan chunk.
     */
    public static function combineSystem(int $percentage = 30, string $locale = 'id'): string
    {
        $language = self::languageName($locale);
        $fillers = self::fillerExamples($locale);

        if ($percentage <= 30) {
            $brevityRule = "EXTREME BREVITY: The final summary MUST be very short (around {$percentage}% of the combined length). MAXIMUM LENGTH: 3 to 5 sentences. The final summary MUST be strictly ONE single continuous paragraph.";
        } elseif ($percentage <= 50) {
            $brevityRule = "CONCISE SUMMARY: The final summary MUST be concise (around {$percentage}% of the combined length). MAXIMUM LENGTH: 2 short paragraphs.";
        } elseif ($percentage <= 70) {
            $brevityRule = "MODERATE SUMMARY: Provide a moderately detailed summary (around {$percentage}% of the combined length). It should capture main points and important details cohesively.";
        } else {
            $brevityRule = "DETAILED SUMMARY: Provide a highly detailed and comprehensive final summary (around {$percentage}% of the combined length). Combine all information smoothly.";
        }

        return <<<PROMPT
You are a highly disciplined document summarizer. 

Task: Combine the provided partial summaries into ONE final cohesive summary in {$language}.

ABSOLUTE RULES (YOU MUST OBEY THESE OR YOU WILL BE PENALIZED):
1. NO LISTS: You are STRICTLY FORBIDDEN from using bullet points, numbered lists, hyphens (-), or asterisks (*).
2. ONLY PARAGRAPHS: The entire output MUST be written as plain, continuous paragraphs.
3. {$brevityRule}
4. NO FILLER WORDS: Do not use introductory phrases like {$fillers}. Start immediately with the main facts.
5. NO HALLUCINATION: Only use facts explicitly stated in the partial summaries.
PROMPT;
    }

    public static function combineContent(array $chunkSummaries, string $locale = 'id'): string
    {
        $language = self::languageName($locale);
        $parts = implode("\n\n---\n\n", $chunkSummaries);

        return "PARTIAL SUMMARIES:\n---\n{$parts}\n---\n\nNow, generate the final combined summary in {$language} following ALL the rules above. DO NOT include the rules or any other text. OUTPUT ONLY THE FINAL SUMMARY:";
    }
}
