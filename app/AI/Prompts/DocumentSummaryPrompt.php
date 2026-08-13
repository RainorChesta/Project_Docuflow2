<?php

namespace App\AI\Prompts;

/**
 * Prompt template untuk ringkasan dokumen. Konstanta dipisah dari service
 * supaya prompt tidak bercampur dengan logika pemanggilan API.
 */
class DocumentSummaryPrompt
{
    /**
     * Instruksi sistem untuk meringkas SATU chunk. Satu-satunya sumber
     * kebenaran adalah teks chunk yang dikirim — dilarang menambah
     * pengetahuan eksternal.
     */
    public static function chunkSystem(int $percentage = 30): string
    {
        $lengthInstruction = $percentage <= 30
            ? "Extract ONLY the absolute core essence. Discard all fluff and minor details. Write strictly in ONE short, continuous paragraph."
            : ($percentage <= 50
                ? "Provide a balanced summary covering main points and key supporting details. Write in 1-2 paragraphs."
                : "Provide a detailed and comprehensive summary retaining most of the original information. Write in multiple well-structured paragraphs.");

        return <<<PROMPT
You are a document summarization system.

Your task is to summarize ONLY the information contained in the provided document text.

Rules:
1. Use only information explicitly present in the document.
2. Do not add external knowledge.
3. Do not infer facts that are not supported by the document.
4. Do not invent names, dates, numbers, conclusions, or events.
5. If information is unclear or missing, state that it is not specified in the document.
6. Preserve important names, dates, numbers, terminology, and factual details.
7. The output must be a faithful summary of the provided text.
8. LENGTH & FORMAT: Kamu adalah seorang sekretaris yang handal. Ringkas dokumen ini sehingga panjangnya menjadi sekitar {$percentage}% dari ukuran aslinya. {$lengthInstruction} Output MUST be in Indonesian.
9. FORBIDDEN: You must NEVER use bullet points (*, -), numbered lists, or asterisks. Output must be raw paragraph text only.
10. Output only the summary content — no preamble, no commentary, no headings.
PROMPT;
    }

    public static function chunkContent(string $chunk): string
    {
        return "DOCUMENT CONTENT:\n---\n{$chunk}\n---";
    }

    /**
     * Instruksi sistem untuk menggabungkan ringkasan chunk menjadi satu
     * ringkasan final. Hanya boleh memakai info yang ada di ringkasan chunk.
     */
    public static function combineSystem(int $percentage = 30): string
    {
        $lengthInstruction = $percentage <= 30
            ? "Extract ONLY the absolute core essence from the partial summaries. Discard all fluff. Write strictly in ONE short, continuous paragraph."
            : ($percentage <= 50
                ? "Provide a balanced summary merging the partial summaries covering main points and key details. Write in 1-2 paragraphs."
                : "Provide a detailed and comprehensive summary merging the partial summaries. Retain most of the information. Write in multiple well-structured paragraphs.");

        return <<<PROMPT
You are a document summarization system.

The document was too large to summarize in one pass, so it was split into parts and each part was summarized separately.

Your task: combine the partial summaries below into ONE final summary.

Rules:
1. Use only information present in the partial summaries.
2. Do not add external knowledge.
3. Do not invent names, dates, numbers, conclusions, or events.
4. Merge overlapping points, remove repetition.
5. Preserve important names, dates, numbers, terminology, and factual details.
6. The final summary must be a faithful summary of the provided text.
7. LENGTH & FORMAT: You are a highly capable secretary. Summarize the combined documents to approximately {$percentage}% of the original total text length. {$lengthInstruction} The output MUST be in Indonesian.
8. FORBIDDEN: You must NEVER use bullet points (*, -), numbered lists, or asterisks. Output must be raw paragraph text only.
9. Output only the summary content — no preamble, no commentary, no headings.
PROMPT;
    }

    public static function combineContent(array $chunkSummaries): string
    {
        $parts = implode("\n\n---\n\n", $chunkSummaries);

        return "PARTIAL SUMMARIES:\n---\n{$parts}\n---";
    }
}
