# Implementation Plan — AI PDF Summarization

## 1. Objective

Implement a PDF summarization feature where users can upload a document and request an AI-generated summary using the **Groq API**.

The system must satisfy these requirements:

- Summary is generated **only from the uploaded document**.
- The AI must not use external knowledge to add information that does not exist in the document.
- Large PDFs must be processed using chunking.
- Chunk data does not need to be permanently stored because the application only requires summarization.
- No Vector Database, embeddings, or RAG are required.
- Summary generation must run asynchronously so the user's web request does not become slow or blocked.
- Database queries and writes must be optimized.
- Follow clean-code and separation-of-concerns principles.
- API keys and model configuration must be stored in environment/configuration files.
- Failed AI jobs must be retryable and must not corrupt the document status.

---

# 2. Recommended Architecture

```text
User
 │
 │ Upload PDF
 ↓
Laravel Controller
 │
 ↓
DocumentService
 │
 ├── Validate PDF
 ├── Store PDF
 └── Create Document Record
          │
          ↓
       Queue Job
          │
          ↓
   SummarizeDocumentJob
          │
          ├── Extract PDF Text
          │
          ├── Clean Text
          │
          ├── Chunk Text
          │
          ├── Summarize Each Chunk
          │       ↓
          │    Groq API
          │
          ├── Combine Chunk Summaries
          │       ↓
          │    Groq API
          │
          └── Save Final Summary
                  │
                  ↓
              Database
                  │
                  ↓
                 User
```

The important part is that the request responsible for uploading the document should **not wait for Groq to finish generating the summary**.

---

# 3. Database Design

Use the existing `documents` table if possible.

Recommended fields:

```text
documents
------------------------------
id
user_id
title
file_path
file_size
mime_type
summary
summary_status
summary_error
summary_started_at
summary_completed_at
created_at
updated_at
```

Recommended `summary_status` values:

```text
pending
processing
completed
failed
```

Example:

```text
pending
   ↓
processing
   ↓
completed
```

If an error occurs:

```text
processing
   ↓
failed
```

Do not create a separate table for every chunk if chunks are only temporary processing data.

---

# 4. Database Indexing

Add indexes only for columns frequently used in queries.

Recommended:

```php
$table->index('user_id');
$table->index(['user_id', 'summary_status']);
```

If documents are commonly retrieved by user:

```sql
SELECT *
FROM documents
WHERE user_id = ?
ORDER BY created_at DESC;
```

the `user_id` index will help.

Avoid adding indexes to every column because unnecessary indexes increase storage requirements and can slow down INSERT/UPDATE operations.

---

# 5. Upload Flow

The controller should remain thin.

Example responsibility:

```php
public function store(StoreDocumentRequest $request)
{
    $document = $this->documentService->store(
        $request->file('document')
    );

    return response()->json([
        'message' => 'Document uploaded successfully.',
        'document_id' => $document->id,
    ]);
}
```

The controller should NOT:

- Parse the PDF.
- Call Groq.
- Perform chunking.
- Generate summaries.
- Contain prompt logic.

---

# 6. Service Layer

Create a dedicated service:

```text
app/
├── Services/
│   ├── DocumentService.php
│   ├── PdfTextExtractor.php
│   └── DocumentSummarizer.php
```

Responsibilities:

### DocumentService

Handles:

- File storage.
- Document creation.
- Dispatching summarization job.

### PdfTextExtractor

Handles:

- Reading PDF.
- Extracting text.
- Validating extracted content.

### DocumentSummarizer

Handles:

- Text chunking.
- Groq requests.
- Chunk summarization.
- Final summary generation.

This keeps the AI logic independent from the controller.

---

# 7. Queue-Based Processing

Use Laravel Queue.

Example:

```php
SummarizeDocumentJob::dispatch($document->id);
```

The job receives only the document ID instead of passing the entire PDF or extracted text through the queue.

Example:

```php
class SummarizeDocumentJob implements ShouldQueue
{
    public function __construct(
        public int $documentId
    ) {}

    public function handle(
        DocumentSummarizer $summarizer
    ): void {
        $summarizer->summarize($this->documentId);
    }
}
```

This keeps the queue payload small.

---

# 8. Prevent Duplicate Processing

Before starting the job, verify the document status.

Example:

```php
if ($document->summary_status === 'completed') {
    return;
}
```

Use Laravel's job uniqueness/locking mechanism where appropriate so the same document is not processed multiple times simultaneously.

Recommended state:

```text
pending
processing
completed
failed
```

---

# 9. PDF Text Extraction

Use:

```bash
composer require smalot/pdfparser
```

Create:

```text
PdfTextExtractor
```

Example responsibility:

```php
$text = $pdfTextExtractor->extract($document);
```

After extraction:

```php
$text = trim($text);

if ($text === '') {
    throw new RuntimeException(
        'Unable to extract text from the PDF.'
    );
}
```

This should happen before calling Groq.

---

# 10. Text Cleaning

Clean unnecessary content before chunking.

For example:

- Normalize whitespace.
- Remove excessive blank lines.
- Normalize line breaks.
- Remove repeated headers/footers if reliably detectable.

Do not aggressively modify the content because the original meaning must remain intact.

Example:

```php
$text = preg_replace('/[ \t]+/', ' ', $text);
$text = preg_replace("/\n{3,}/", "\n\n", $text);
$text = trim($text);
```

---

# 11. Chunking Strategy

Do not send an entire large PDF to Groq in one request.

Use chunks based on the model's token limits.

Recommended approach:

```text
PDF Text
   ↓
Chunk 1
Chunk 2
Chunk 3
Chunk 4
...
```

Use a configurable chunk size rather than hardcoding it throughout the application.

Example configuration:

```php
'chunk_size' => env('AI_SUMMARY_CHUNK_SIZE', 12000),
```

For production, token-aware chunking is preferable to simply splitting by characters.

Also consider a small overlap between chunks if splitting may separate related sentences.

---

# 12. Groq Integration

Create a dedicated client/service:

```text
app/
└── AI/
    └── GroqClient.php
```

The rest of the application should not directly call HTTP endpoints.

Example:

```php
$summary = $groqClient->generateSummary($prompt);
```

Store configuration in `.env`:

```env
GROQ_API_KEY=your_key
GROQ_MODEL=your_model
```

And expose it through:

```text
config/services.php
```

Never hardcode the API key.

---

# 13. Strict Document-Grounded Prompt

The most important requirement is:

> The summary must only contain information present in the uploaded document.

Use a system instruction similar to:

```text
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
```

Then provide the document content separately.

Example:

```text
DOCUMENT CONTENT:
---
{{chunk}}
---
```

Do NOT allow user-controlled document content to override the system instructions.

---

# 14. Two-Stage Summarization

For large PDFs:

```text
Chunk
 ↓
Chunk Summary
 ↓
Final Summary
```

Example:

```text
Chunk 1 → Summary 1
Chunk 2 → Summary 2
Chunk 3 → Summary 3
Chunk 4 → Summary 4

Summary 1
Summary 2
Summary 3
Summary 4
      ↓
   Groq API
      ↓
 Final Summary
```

The final prompt should again explicitly require that the final summary use only the provided chunk summaries.

---

# 15. Avoiding Hallucination

The system cannot mathematically guarantee zero hallucinations from an LLM.

Therefore, use multiple safeguards.

### Prompt constraint

Tell the model:

```text
Use only the provided document.
```

### No external tools

Do not give the summarization agent:

- Web search.
- External knowledge retrieval.
- Vector search.
- Other documents.

### Low temperature

Use a low temperature where supported to make the output more deterministic.

### Structured output

Prefer a predictable response format.

For example:

```json
{
    "summary": "...",
    "key_points": [
        "...",
        "...",
        "..."
    ]
}
```

Then validate the JSON before saving it.

---

# 16. Database Optimization During Processing

Do not repeatedly query the same document.

Bad:

```text
Query document
Extract PDF

Query document
Update status

Query document
Save summary
```

Prefer:

```php
$document = Document::findOrFail($id);
```

Then perform the entire processing using the loaded model and update it when necessary.

For status updates, use targeted updates:

```php
Document::whereKey($document->id)->update([
    'summary_status' => 'processing',
]);
```

Do not save huge extracted text into the database if it is not required.

The extracted text and chunks should remain temporary.

---

# 17. Memory Optimization

Do not create unnecessary copies of the entire PDF/text.

For example, avoid storing:

```text
PDF
Extracted Text
All Chunks
All Chunk Responses
Final Prompt
```

simultaneously if documents can be very large.

For normal-sized documents this may be acceptable, but production systems should process chunks incrementally.

Conceptually:

```text
Extract
 ↓
Chunk
 ↓
Send Chunk
 ↓
Store Summary in temporary variable
 ↓
Release Chunk
 ↓
Next Chunk
```

---

# 18. Queue and User Experience

The browser should immediately receive:

```json
{
    "status": "processing",
    "document_id": 123
}
```

The frontend can periodically request:

```text
GET /documents/123/summary-status
```

Response:

```json
{
    "status": "processing"
}
```

Then:

```json
{
    "status": "completed",
    "summary": "..."
}
```

This prevents the user from waiting for the Groq API request.

---

# 19. Error Handling

If Groq fails:

```text
Groq API
   ↓
Error
   ↓
Laravel Job Retry
```

Configure retries:

```php
public $tries = 3;
```

If all retries fail:

```text
summary_status = failed
summary_error = "..."
```

The user should see:

```text
Summary gagal dibuat.
Silakan coba lagi.
```

Do not expose:

- API keys.
- Raw exceptions.
- Internal server details.
- Groq request information.

---

# 20. Rate Limit Handling

The Groq API may return rate-limit errors.

Implement:

```text
429 / Rate Limit
       ↓
Retry
       ↓
Backoff
       ↓
Try again
```

Example concept:

```php
public function backoff(): array
{
    return [5, 15, 30];
}
```

This prevents repeatedly hitting the API immediately after a rate-limit response.

---

# 21. Suggested Folder Structure

```text
app/
├── AI/
│   ├── GroqClient.php
│   └── Prompts/
│       └── DocumentSummaryPrompt.php
│
├── Jobs/
│   └── SummarizeDocumentJob.php
│
├── Services/
│   ├── DocumentService.php
│   ├── PdfTextExtractor.php
│   └── DocumentSummarizer.php
│
├── Http/
│   ├── Controllers/
│   │   └── DocumentController.php
│   │
│   └── Requests/
│       └── StoreDocumentRequest.php
│
└── Models/
    └── Document.php
```

This keeps responsibilities separated.

---

# 22. What NOT to Implement

For the current requirement, do NOT add:

```text
❌ Vector Database
❌ Embeddings
❌ RAG
❌ Semantic Search
❌ Web Search
❌ External knowledge retrieval
❌ Permanent chunk storage
❌ Separate database table for chunks
```

These features are unnecessary for a simple document summarization system.

---

# 23. Final Recommended Stack

```text
Laravel
│
├── Laravel Storage
│      └── PDF
│
├── smalot/pdfparser
│      └── PDF → Text
│
├── Laravel Queue
│      └── Background Processing
│
├── Groq API
│      └── Summarization
│
└── MySQL
       └── Document metadata
       └── Summary
       └── Processing status
```

The core principle is:

> **The uploaded document is the only source of truth.**

The AI receives only the extracted/chunked content from that specific document. There is no retrieval from other documents, web search, vector database, or external knowledge source.

---

# 24. Implementation Order

Implement in this order:

### Phase 1 — Document Upload

```text
Upload PDF
↓
Validate
↓
Store
↓
Create document
```

### Phase 2 — PDF Extraction

```text
PDF
↓
PdfTextExtractor
↓
Text
```

### Phase 3 — Chunking

```text
Text
↓
Chunker
↓
Temporary chunks
```

### Phase 4 — Groq Integration

```text
Chunk
↓
GroqClient
↓
Chunk Summary
```

### Phase 5 — Final Summary

```text
Chunk Summaries
↓
Groq
↓
Final Summary
```

### Phase 6 — Queue

```text
Upload
↓
Dispatch Job
↓
Background AI Processing
```

### Phase 7 — Frontend Status

```text
Processing
↓
Polling / realtime status
↓
Completed
↓
Display Summary
```

### Phase 8 — Hardening

Add:

- Retry handling.
- Rate-limit handling.
- Duplicate job prevention.
- Validation.
- Logging.
- Timeout handling.
- Maximum PDF size/page limits.
- Error states.
- AI response validation.

---

## Target Result

The final user experience should be:

```text
User
 │
 ├── Upload "Laporan.pdf"
 │
 ↓
Document saved
 │
 ↓
"Summary sedang dibuat..."
 │
 ↓
Background Job
 │
 ├── Extract PDF
 ├── Chunk
 ├── Groq
 └── Final Summary
 │
 ↓
Database
 │
 ↓
"Summary selesai"
 │
 ↓
User sees summary
```

This design is intentionally **simple, clean, scalable, and appropriate for a summarization-only feature** without introducing RAG/vector infrastructure that the application does not currently need.