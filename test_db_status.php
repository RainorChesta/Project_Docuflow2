<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$jobsCount = \Illuminate\Support\Facades\DB::table('jobs')->count();
$failedJobsCount = \Illuminate\Support\Facades\DB::table('failed_jobs')->count();
$processingDocs = \App\Models\Document::where('summary_status', 'processing')->get(['id', 'title', 'summary_status', 'summary_started_at']);

echo "Queue Connection: " . config('queue.default') . "\n";
echo "Groq Key configured: " . (config('services.groq.key') ? 'YES' : 'NO') . "\n";
echo "Groq Model: " . config('services.groq.model') . "\n";
echo "Jobs in queue: " . $jobsCount . "\n";
echo "Failed jobs: " . $failedJobsCount . "\n";
echo "Documents with processing summary: " . $processingDocs->count() . "\n";
foreach ($processingDocs as $doc) {
    echo " - Doc ID {$doc->id}: '{$doc->title}', started at: {$doc->summary_started_at}\n";
}
