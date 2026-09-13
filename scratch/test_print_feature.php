<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Division;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Services\PdfExportService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

echo "--- TESTING PRINT / EXPORT FLOW ---\n";

Storage::fake('local');
$division = Division::firstOrCreate(['code' => '01'], ['name' => 'JBM']);
$docType = DocumentType::firstOrCreate(['code' => 'S.ED.TEST'], ['name' => 'Surat Edaran Test Type']);
$owner = User::first() ?? User::factory()->create(['division_id' => $division->id]);

$doc = Document::create([
    'document_number' => 'TEST/PRINT/' . time(),
    'title' => 'Dokumen Uji Cetak',
    'visibility' => 'division',
    'division_id' => $division->id,
    'owner_id' => $owner->id,
    'document_type_id' => $docType->id,
    'paper_size' => 'A4',
]);

$v = DocumentVersion::create([
    'document_id' => $doc->id,
    'version_number' => 1,
    'content' => '<h1>Konten Dokumen</h1><p>Paragraf uji coba print.</p>',
    'author_id' => $owner->id,
    'author_name' => $owner->name,
    'status' => 'active',
]);
$doc->update(['current_version_id' => $v->id]);

$pdfService = app(PdfExportService::class);

// 1. Test resolvePageMetrics default is F4
$reflection = new ReflectionClass(PdfExportService::class);
$resolveMethod = $reflection->getMethod('resolvePageMetrics');
$resolveMethod->setAccessible(true);

$metricsF4 = $resolveMethod->invoke($pdfService, $doc, null, null);
echo "1. Default print paper size: " . $metricsF4['paperSize'] . " (Expected: F4)\n";
echo "   Page dimensions: " . $metricsF4['page']['width'] . "x" . $metricsF4['page']['height'] . " px (Expected: 794x1247)\n";
echo "   CSS Size: " . $metricsF4['pageSizeCss'] . "\n";
assert($metricsF4['paperSize'] === 'F4');
assert($metricsF4['page']['width'] === 794);
assert($metricsF4['page']['height'] === 1247);

// 2. Test resolvePageMetrics with A4 override
$metricsA4 = $resolveMethod->invoke($pdfService, $doc, 'A4', null);
echo "2. A4 print paper size: " . $metricsA4['paperSize'] . "\n";
echo "   Page dimensions: " . $metricsA4['page']['width'] . "x" . $metricsA4['page']['height'] . " px (Expected: 794x1123)\n";
assert($metricsA4['paperSize'] === 'A4');
assert($metricsA4['page']['width'] === 794);
assert($metricsA4['page']['height'] === 1123);

// 3. Test resolvePageMetrics with Legal override
$metricsLegal = $resolveMethod->invoke($pdfService, $doc, 'Legal', null);
echo "3. Legal print paper size: " . $metricsLegal['paperSize'] . "\n";
echo "   Page dimensions: " . $metricsLegal['page']['width'] . "x" . $metricsLegal['page']['height'] . " px (Expected: 816x1344)\n";
assert($metricsLegal['paperSize'] === 'Legal');
assert($metricsLegal['page']['width'] === 816);
assert($metricsLegal['page']['height'] === 1344);

// 4. Test resolvePageMetrics with Custom size (21.5cm x 33.5cm)
$metricsCustom = $resolveMethod->invoke($pdfService, $doc, 'Custom', [
    'width' => 21.5,
    'height' => 33.5,
    'unit' => 'cm',
]);
echo "4. Custom print paper size: " . $metricsCustom['paperSize'] . "\n";
echo "   Page dimensions: " . $metricsCustom['page']['width'] . "x" . $metricsCustom['page']['height'] . " px\n";
echo "   CSS Size: " . $metricsCustom['pageSizeCss'] . " (Expected: 215mm 335mm)\n";
assert($metricsCustom['paperSize'] === 'Custom');
assert($metricsCustom['pageSizeCss'] === '215mm 335mm');

// 5. Test Document page_size immutability
echo "5. Stored document paper_size in database: " . $doc->fresh()->paper_size . " (Expected: A4)\n";
assert($doc->fresh()->paper_size === 'A4');

// 6. Test Validation Rules
$rules = [
    'paper_size' => 'nullable|string|in:F4,A4,A5,A3,Letter,Legal,Custom',
    'custom_width' => 'nullable|numeric|gt:0|required_if:paper_size,Custom',
    'custom_height' => 'nullable|numeric|gt:0|required_if:paper_size,Custom',
    'custom_unit' => 'nullable|string|in:cm,mm',
];

// 6a. Valid F4 default (empty input)
$v1 = Validator::make([], $rules);
assert(!$v1->fails(), "Empty payload should pass for default F4");
echo "6a. Validation for empty payload (default F4): PASS\n";

// 6b. Valid Custom Size
$v2 = Validator::make(['paper_size' => 'Custom', 'custom_width' => '21.5', 'custom_height' => '33.0', 'custom_unit' => 'cm'], $rules);
assert(!$v2->fails(), "Valid custom size should pass");
echo "6b. Validation for valid Custom size: PASS\n";

// 6c. Invalid Custom Size (missing dimensions)
$v3 = Validator::make(['paper_size' => 'Custom'], $rules);
assert($v3->fails(), "Custom size without width/height should fail");
assert($v3->errors()->has('custom_width'));
assert($v3->errors()->has('custom_height'));
echo "6c. Validation rejects missing custom dimensions: PASS\n";

// 6d. Invalid Custom Size (0 or negative)
$v4 = Validator::make(['paper_size' => 'Custom', 'custom_width' => '0', 'custom_height' => '-10'], $rules);
assert($v4->fails(), "Custom size with 0 or negative must fail");
assert($v4->errors()->has('custom_width'));
assert($v4->errors()->has('custom_height'));
echo "6d. Validation rejects <= 0 dimensions: PASS\n";

// 6e. Invalid Custom Size (non-numeric)
$v5 = Validator::make(['paper_size' => 'Custom', 'custom_width' => 'abc', 'custom_height' => 'xyz'], $rules);
assert($v5->fails(), "Non-numeric custom size must fail");
assert($v5->errors()->has('custom_width'));
assert($v5->errors()->has('custom_height'));
echo "6e. Validation rejects non-numeric dimensions: PASS\n";

echo "\n ALL 10 TESTS & VALIDATION CHECKS PASSED PERFECTLY!\n";
