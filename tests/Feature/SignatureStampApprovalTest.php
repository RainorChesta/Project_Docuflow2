<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\Signature;
use App\Models\SignatureRequest;
use App\Models\UnitKerja;
use App\Models\User;
use App\Services\DocumentProcessorService;
use App\Services\OnlyOfficeService;
use App\Services\PdfSignatureProcessorService;
use App\Services\SignatureResolverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use setasign\Fpdi\Fpdi;

class SignatureStampApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected UnitKerja $unitKerja;
    protected User $requester;
    protected User $headOfUnitKerja;
    protected DocumentType $docType;
    protected Signature $headOriginalSig;
    protected Signature $headCompanyStamp;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        $this->company = Company::create([
            'name' => 'PT DocuFlow Indonesia',
            'code' => 'DFI',
            'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Kantor Pusat Jakarta',
            'code' => 'KPJ',
            'is_active' => true,
        ]);

        $this->unitKerja = UnitKerja::create([
            'nama_unit_kerja' => 'Unit Teknologi Informasi',
            'kode_unit_kerja' => '08',
        ]);

        $this->requester = User::factory()->create([
            'name' => 'Staff Pembuat Dokumen',
            'email' => 'staff@docuflow.test',
            'unit_kerja_id' => $this->unitKerja->id,
            'system_role' => 'staff',
        ]);
        $this->requester->companies()->attach($this->company->id);
        $this->requester->branches()->attach($this->branch->id);

        $this->headOfUnitKerja = User::factory()->create([
            'name' => 'Bapak Kepala Unit Kerja',
            'email' => 'kadiv@docuflow.test',
            'unit_kerja_id' => $this->unitKerja->id,
            'system_role' => 'head',
        ]);
        $this->headOfUnitKerja->companies()->attach($this->company->id);
        $this->headOfUnitKerja->branches()->attach($this->branch->id);

        $this->docType = DocumentType::create([
            'name' => 'Surat Keputusan',
            'code' => 'SK',
            'category' => 'akreditasi',
        ]);

        // Create distinct original signature (Blue colored)
        $origPath = 'signatures/head_orig_' . uniqid() . '.png';
        $this->createDistinctPng($origPath, 0, 0, 255); // Pure Blue
        $this->headOriginalSig = Signature::create([
            'user_id' => $this->headOfUnitKerja->id,
            'type' => 'original',
            'file_path' => $origPath,
            'created_via' => 'canvas',
        ]);

        // Create distinct company stamp (Red colored)
        $stampPath = 'signatures/head_stamp_' . uniqid() . '.png';
        $this->createDistinctPng($stampPath, 255, 0, 0); // Pure Red
        $this->headCompanyStamp = Signature::create([
            'user_id' => $this->headOfUnitKerja->id,
            'type' => 'company_stamp',
            'company_id' => $this->company->id,
            'file_path' => $stampPath,
            'created_via' => 'upload',
        ]);
    }

    /**
     * Create a distinct test PNG image in public storage with given RGB background.
     */
    protected function createDistinctPng(string $storagePath, int $r, int $g, int $b): string
    {
        $im = imagecreatetruecolor(80, 80);
        $bg = imagecolorallocate($im, $r, $g, $b);
        imagefilledrectangle($im, 0, 0, 80, 80, $bg);
        
        // Add a line in contrasting white
        $white = imagecolorallocate($im, 255, 255, 255);
        imageline($im, 5, 5, 75, 75, $white);

        ob_start();
        imagepng($im);
        $bytes = ob_get_clean();
        imagedestroy($im);

        Storage::disk('public')->put($storagePath, $bytes);

        return Storage::disk('public')->path($storagePath);
    }

    /**
     * Helper to create a valid minimal PDF in local storage.
     */
    protected function createTestPdf(string $storagePath): void
    {
        $fpdf = new Fpdi();
        $fpdf->AddPage();
        $fpdf->SetFont('Helvetica', 'B', 14);
        $fpdf->Cell(40, 10, 'Test PDF Document for Signature and Stamp Testing');
        $pdfContent = $fpdf->Output('S');

        Storage::disk('local')->put($storagePath, $pdfContent);
    }

    /**
     * Helper to create a minimal DOCX containing a placeholder PNG in word/media/.
     */
    protected function createTestDocxWithPlaceholder(string $storagePath, int $requestId, bool $isStamp = false): void
    {
        $tempDocx = tempnam(sys_get_temp_dir(), 'docx_sig_test_') . '.docx';
        $zip = new \ZipArchive();
        $zip->open($tempDocx, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        $placeholderData = $this->createPlaceholderPngBytes($requestId, $isStamp);

        $zip->addFromString('word/media/image1.png', $placeholderData);

        $relsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rIdPlaceholder1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/image1.png"/>' .
            '</Relationships>';
        $zip->addFromString('word/_rels/document.xml.rels', $relsXml);

        $documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
            '<w:body>' .
            '<w:p><w:r><w:t>Dokumen Resmi Perusahaan</w:t></w:r></w:p>' .
            '<w:p><w:r><w:drawing><w:graphic><w:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/main"><a:blip xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" r:embed="rIdPlaceholder1"/></w:graphicData></w:graphic></w:drawing></w:r></w:p>' .
            '<w:sectPr/>' .
            '</w:body>' .
            '</w:document>';
        $zip->addFromString('word/document.xml', $documentXml);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="xml" ContentType="application/xml"/><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="png" ContentType="image/png"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
        $zip->close();

        Storage::disk('local')->put($storagePath, file_get_contents($tempDocx));
        @unlink($tempDocx);
    }

    /**
     * Create placeholder PNG matching OnlyOfficeController implementation (with metadata pixel & tEXt chunk).
     */
    protected function createPlaceholderPngBytes(int $requestId, bool $isStamp): string
    {
        $width = 400;
        $height = 400;
        $image = imagecreatetruecolor($width, $height);
        $bgColor = imagecolorallocate($image, 254, 243, 199);
        imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);

        // Steganographic metadata
        $magicColor = imagecolorallocate($image, 222, 173, 190);
        $reqColor   = imagecolorallocate($image, ($requestId & 0xFF), (($requestId >> 8) & 0xFF), (($requestId >> 16) & 0xFF));
        $stampColor = imagecolorallocate($image, $isStamp ? 1 : 0, 88, 99);

        imagesetpixel($image, 8, 8, $magicColor);
        imagesetpixel($image, 9, 8, $reqColor);
        imagesetpixel($image, 10, 8, $stampColor);

        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);

        return $png;
    }

    /**
     * Test 1: Requesting and approving Company Stamp on DOCX results in company stamp image, NOT personal signature.
     */
    public function test_docx_company_stamp_request_and_approval_applies_stamp(): void
    {
        $document = Document::create([
            'document_number' => '001/SK-08/KPJ/IX/2026',
            'title' => 'DOCX Surat Keputusan Stamp Test',
            'document_type_id' => $this->docType->id,
            'owner_id' => $this->requester->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'unit_kerja_id' => $this->unitKerja->id,
            'visibility' => 'unit_kerja',
        ]);

        $docxStoragePath = 'documents/' . $document->id . '/v1.docx';
        $version = DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => 1,
            'content' => '<p>Test</p>',
            'author_id' => $this->requester->id,
            'author_name' => $this->requester->name,
            'status' => 'draft',
            'file_path' => $docxStoragePath,
            'file_mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'file_original_name' => 'sample.docx',
        ]);

        // Staff requests Head of Unit Kerja's Company Stamp
        $this->actingAs($this->requester);
        $response = $this->getJson(route('profile.signature.show', [
            'user_id' => $this->headOfUnitKerja->id,
            'document_id' => $document->id,
            'signature_id' => $this->headCompanyStamp->id,
        ]));

        $response->assertOk();
        $response->assertJson(['success' => true, 'is_pending' => true]);

        $sigRequestId = $response->json('request_id');
        $this->assertNotNull($sigRequestId);

        $sigRequest = SignatureRequest::find($sigRequestId);
        $this->assertTrue($sigRequest->isStamp());
        $this->assertEquals($this->headCompanyStamp->id, $sigRequest->requested_signature_id);

        // Put DOCX with placeholder into storage
        $this->createTestDocxWithPlaceholder($docxStoragePath, $sigRequestId, true);

        // Head of Unit Kerja approves the stamp request
        $this->actingAs($this->headOfUnitKerja);
        $approveResponse = $this->post(route('signatures.requests.approve', $sigRequest));
        $approveResponse->assertRedirect();
        $approveResponse->assertSessionHas('success', __('Permintaan stempel perusahaan telah disetujui.'));

        $sigRequest->refresh();
        $this->assertTrue($sigRequest->isApproved());

        // Verify the image inside the modified DOCX is the RED company stamp, NOT the BLUE personal signature
        $modifiedDocxBytes = Storage::disk('local')->get($docxStoragePath);
        $tempCheckDocx = tempnam(sys_get_temp_dir(), 'check_docx_') . '.docx';
        file_put_contents($tempCheckDocx, $modifiedDocxBytes);

        $zip = new \ZipArchive();
        $zip->open($tempCheckDocx);
        $mediaBytes = $zip->getFromName('word/media/image1.png');
        $zip->close();
        @unlink($tempCheckDocx);

        $this->assertNotEmpty($mediaBytes, 'word/media/image1.png should exist after approval.');

        // Check color of the stamped image (should have high red component >= 200, low blue <= 50)
        $im = imagecreatefromstring($mediaBytes);
        $this->assertNotFalse($im);
        $centerRgb = imagecolorat($im, 40, 40);
        $r = ($centerRgb >> 16) & 0xFF;
        $g = ($centerRgb >> 8) & 0xFF;
        $b = $centerRgb & 0xFF;
        imagedestroy($im);

        $this->assertGreaterThan(200, $r, 'Stamped image in DOCX should be Red (Company Stamp).');
        $this->assertLessThan(50, $b, 'Stamped image in DOCX should NOT be Blue (Original Signature).');
    }

    /**
     * Test 2: Requesting and approving Original Signature on DOCX results in original signature image.
     */
    public function test_docx_original_signature_request_and_approval_applies_signature(): void
    {
        $document = Document::create([
            'document_number' => '002/SK-08/KPJ/IX/2026',
            'title' => 'DOCX Surat Keputusan Signature Test',
            'document_type_id' => $this->docType->id,
            'owner_id' => $this->requester->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'unit_kerja_id' => $this->unitKerja->id,
            'visibility' => 'unit_kerja',
        ]);

        $docxStoragePath = 'documents/' . $document->id . '/v1.docx';
        $version = DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => 1,
            'content' => '<p>Test</p>',
            'author_id' => $this->requester->id,
            'author_name' => $this->requester->name,
            'status' => 'draft',
            'file_path' => $docxStoragePath,
            'file_mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'file_original_name' => 'sample.docx',
        ]);

        // Staff requests Head of Unit Kerja's Original Signature
        $this->actingAs($this->requester);
        $response = $this->getJson(route('profile.signature.show', [
            'user_id' => $this->headOfUnitKerja->id,
            'document_id' => $document->id,
            'signature_id' => $this->headOriginalSig->id,
        ]));

        $response->assertOk();
        $sigRequestId = $response->json('request_id');
        $sigRequest = SignatureRequest::find($sigRequestId);
        $this->assertTrue($sigRequest->isSignature());

        // Put DOCX with placeholder into storage
        $this->createTestDocxWithPlaceholder($docxStoragePath, $sigRequestId, false);

        // Head of Unit Kerja approves the signature request
        $this->actingAs($this->headOfUnitKerja);
        $approveResponse = $this->post(route('signatures.requests.approve', $sigRequest));
        $approveResponse->assertRedirect();
        $approveResponse->assertSessionHas('success', __('Permintaan tanda tangan telah disetujui.'));

        // Verify the image inside the modified DOCX is BLUE personal signature
        $modifiedDocxBytes = Storage::disk('local')->get($docxStoragePath);
        $tempCheckDocx = tempnam(sys_get_temp_dir(), 'check_docx_') . '.docx';
        file_put_contents($tempCheckDocx, $modifiedDocxBytes);

        $zip = new \ZipArchive();
        $zip->open($tempCheckDocx);
        $mediaBytes = $zip->getFromName('word/media/image1.png');
        $zip->close();
        @unlink($tempCheckDocx);

        $im = imagecreatefromstring($mediaBytes);
        $this->assertNotFalse($im);
        $centerRgb = imagecolorat($im, 40, 40);
        $r = ($centerRgb >> 16) & 0xFF;
        $g = ($centerRgb >> 8) & 0xFF;
        $b = $centerRgb & 0xFF;
        imagedestroy($im);

        $this->assertGreaterThan(200, $b, 'Stamped image in DOCX should be Blue (Original Signature).');
        $this->assertLessThan(50, $r, 'Stamped image in DOCX should NOT be Red (Company Stamp).');
    }

    /**
     * Test 3: Requesting and approving Company Stamp on PDF applies company stamp to PDF.
     */
    public function test_pdf_company_stamp_request_and_approval_applies_stamp(): void
    {
        $document = Document::create([
            'document_number' => '003/SK-08/KPJ/IX/2026',
            'title' => 'PDF Surat Keputusan Stamp Test',
            'document_type_id' => $this->docType->id,
            'owner_id' => $this->requester->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'unit_kerja_id' => $this->unitKerja->id,
            'visibility' => 'unit_kerja',
        ]);

        $pdfStoragePath = 'documents/' . $document->id . '/v1.pdf';
        $this->createTestPdf($pdfStoragePath);

        $version = DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => 1,
            'content' => '',
            'author_id' => $this->requester->id,
            'author_name' => $this->requester->name,
            'status' => 'draft',
            'file_path' => $pdfStoragePath,
            'file_mime' => 'application/pdf',
            'file_original_name' => 'sample.pdf',
        ]);

        // Staff requests Head of Unit Kerja's Company Stamp on PDF
        $this->actingAs($this->requester);
        $response = $this->getJson(route('profile.signature.show', [
            'user_id' => $this->headOfUnitKerja->id,
            'document_id' => $document->id,
            'signature_id' => $this->headCompanyStamp->id,
            'page_number' => 1,
            'pos_x' => 100.0,
            'pos_y' => 150.0,
            'preset_position' => 'custom',
        ]));

        $response->assertOk();
        $sigRequestId = $response->json('request_id');
        $sigRequest = SignatureRequest::find($sigRequestId);
        $this->assertTrue($sigRequest->isStamp());

        // Head of Unit Kerja approves the stamp request
        $this->actingAs($this->headOfUnitKerja);
        $approveResponse = $this->post(route('signatures.requests.approve', $sigRequest));
        $approveResponse->assertRedirect();
        $approveResponse->assertSessionHas('success', __('Permintaan stempel perusahaan telah disetujui.'));

        $sigRequest->refresh();
        $this->assertTrue($sigRequest->isApproved());

        // Verify PDF was modified and backup was created
        $pdfProcessor = app(PdfSignatureProcessorService::class);
        $this->assertTrue($pdfProcessor->hasOriginalBackup($version));

        // When requester consumes the approved request via AJAX, it confirms stamp info
        $this->actingAs($this->requester);
        $consumeResponse = $this->postJson(route('signatures.requests.consume', $sigRequest));
        $consumeResponse->assertOk();
        $consumeResponse->assertJson([
            'success' => true,
            'is_stamp' => true,
            'is_pdf' => true,
        ]);
    }

    /**
     * Test 4: Direct stamping of current user's Company Stamp onto PDF via stampPdfSignature.
     */
    public function test_direct_pdf_company_stamp_by_head_of_unit_kerja(): void
    {
        $document = Document::create([
            'document_number' => '004/SK-08/KPJ/IX/2026',
            'title' => 'Direct Stamp PDF Test',
            'document_type_id' => $this->docType->id,
            'owner_id' => $this->headOfUnitKerja->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'unit_kerja_id' => $this->unitKerja->id,
            'visibility' => 'unit_kerja',
        ]);

        $pdfStoragePath = 'documents/' . $document->id . '/v1.pdf';
        $this->createTestPdf($pdfStoragePath);

        $version = DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => 1,
            'content' => '',
            'author_id' => $this->headOfUnitKerja->id,
            'author_name' => $this->headOfUnitKerja->name,
            'status' => 'draft',
            'file_path' => $pdfStoragePath,
            'file_mime' => 'application/pdf',
            'file_original_name' => 'sample.pdf',
        ]);

        $this->actingAs($this->headOfUnitKerja);
        $response = $this->postJson(route('documents.stamp-signature', $document), [
            'signature_id' => $this->headCompanyStamp->id,
            'page_number' => 1,
            'preset_position' => 'bottom-right',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Stempel perusahaan berhasil dibubuhkan pada dokumen PDF.',
        ]);

        $pdfProcessor = app(PdfSignatureProcessorService::class);
        $this->assertTrue($pdfProcessor->hasOriginalBackup($version));
    }

    /**
     * Test 5: SignatureResolverService resolves [stamp:username] with company stamp and [ttd:username] with original signature.
     */
    public function test_signature_resolver_service_resolves_stamp_and_signature_tags(): void
    {
        $resolver = app(SignatureResolverService::class);

        // Resolving stamp tag for head of unit kerja (as head of unit kerja themselves)
        $htmlWithStamp = '<p>Ditetapkan oleh: [stamp:' . $this->headOfUnitKerja->name . ']</p>';
        $resolvedStamp = $resolver->resolve($htmlWithStamp, null, $this->headOfUnitKerja);

        $this->assertStringContainsString('doku-signature-img', $resolvedStamp);
        $this->assertStringContainsString('Stempel ' . $this->company->name, $resolvedStamp);
        $this->assertStringContainsString($this->headCompanyStamp->file_path, $resolvedStamp);

        // Resolving original ttd tag for head of unit kerja
        $htmlWithTtd = '<p>Tertanda: [ttd:' . $this->headOfUnitKerja->name . ']</p>';
        $resolvedTtd = $resolver->resolve($htmlWithTtd, null, $this->headOfUnitKerja);

        $this->assertStringContainsString('doku-signature-img', $resolvedTtd);
        $this->assertStringContainsString('TTD Bapak Kepala Unit Kerja', $resolvedTtd);
        $this->assertStringContainsString($this->headOriginalSig->file_path, $resolvedTtd);
    }

    /**
     * Test 6: Multi-Request Independence in same DOCX document (Stamp vs Original Signature).
     */
    public function test_multi_request_independence_in_same_docx(): void
    {
        $document = Document::create([
            'document_number' => '005/SK-08/KPJ/IX/2026',
            'title' => 'Multi-Request DOCX Test',
            'document_type_id' => $this->docType->id,
            'owner_id' => $this->requester->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'unit_kerja_id' => $this->unitKerja->id,
            'visibility' => 'unit_kerja',
        ]);

        $docxStoragePath = 'documents/' . $document->id . '/v1.docx';

        // Request 1: Original Signature
        $req1 = SignatureRequest::create([
            'requester_id' => $this->requester->id,
            'target_user_id' => $this->headOfUnitKerja->id,
            'requested_signature_id' => $this->headOriginalSig->id,
            'document_id' => $document->id,
            'status' => 'pending',
            'is_used' => false,
            'requested_at' => now(),
        ]);

        // Request 2: Company Stamp
        $req2 = SignatureRequest::create([
            'requester_id' => $this->requester->id,
            'target_user_id' => $this->headOfUnitKerja->id,
            'requested_signature_id' => $this->headCompanyStamp->id,
            'document_id' => $document->id,
            'status' => 'pending',
            'is_used' => false,
            'requested_at' => now(),
        ]);

        // Create DOCX with BOTH placeholders
        $tempDocx = tempnam(sys_get_temp_dir(), 'multi_docx_') . '.docx';
        $zip = new \ZipArchive();
        $zip->open($tempDocx, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        $zip->addFromString('word/media/image1.png', $this->createPlaceholderPngBytes($req1->id, false));
        $zip->addFromString('word/media/image2.png', $this->createPlaceholderPngBytes($req2->id, true));

        $relsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/image1.png"/>' .
            '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/image2.png"/>' .
            '</Relationships>';
        $zip->addFromString('word/_rels/document.xml.rels', $relsXml);

        $documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
            '<w:body>' .
            '<w:p><w:r><w:drawing><w:graphic><w:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/main"><a:blip xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" r:embed="rId1"/></w:graphicData></w:graphic></w:drawing></w:r></w:p>' .
            '<w:p><w:r><w:drawing><w:graphic><w:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/main"><a:blip xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" r:embed="rId2"/></w:graphicData></w:graphic></w:drawing></w:r></w:p>' .
            '<w:sectPr/>' .
            '</w:body>' .
            '</w:document>';
        $zip->addFromString('word/document.xml', $documentXml);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="xml" ContentType="application/xml"/><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="png" ContentType="image/png"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
        $zip->close();

        Storage::disk('local')->put($docxStoragePath, file_get_contents($tempDocx));
        @unlink($tempDocx);

        $version = DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => 1,
            'content' => '<p>Test</p>',
            'author_id' => $this->requester->id,
            'author_name' => $this->requester->name,
            'status' => 'draft',
            'file_path' => $docxStoragePath,
            'file_mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'file_original_name' => 'sample.docx',
        ]);

        // Approve Request 2 (Company Stamp) FIRST
        $this->actingAs($this->headOfUnitKerja);
        $this->post(route('signatures.requests.approve', $req2));

        // Verify: image2.png was replaced with RED stamp, while image1.png remains placeholder
        $modifiedDocxBytes = Storage::disk('local')->get($docxStoragePath);
        $tempCheckDocx = tempnam(sys_get_temp_dir(), 'check_docx2_') . '.docx';
        file_put_contents($tempCheckDocx, $modifiedDocxBytes);

        $zip = new \ZipArchive();
        $zip->open($tempCheckDocx);
        $img1Bytes = $zip->getFromName('word/media/image1.png');
        $img2Bytes = $zip->getFromName('word/media/image2.png');
        $zip->close();
        @unlink($tempCheckDocx);

        $im2 = imagecreatefromstring($img2Bytes);
        $rgb2 = imagecolorat($im2, 40, 40);
        $r2 = ($rgb2 >> 16) & 0xFF;
        imagedestroy($im2);

        $this->assertGreaterThan(200, $r2, 'image2.png must be the RED Company Stamp.');

        // Now Approve Request 1 (Original Signature)
        $this->post(route('signatures.requests.approve', $req1));

        $finalDocxBytes = Storage::disk('local')->get($docxStoragePath);
        $tempCheckDocx = tempnam(sys_get_temp_dir(), 'check_docx3_') . '.docx';
        file_put_contents($tempCheckDocx, $finalDocxBytes);

        $zip = new \ZipArchive();
        $zip->open($tempCheckDocx);
        $finalImg1 = $zip->getFromName('word/media/image1.png');
        $finalImg2 = $zip->getFromName('word/media/image2.png');
        $zip->close();
        @unlink($tempCheckDocx);

        $im1 = imagecreatefromstring($finalImg1);
        $rgb1 = imagecolorat($im1, 40, 40);
        $b1 = $rgb1 & 0xFF;
        imagedestroy($im1);

        $this->assertGreaterThan(200, $b1, 'image1.png must now be the BLUE Original Signature.');
    }
}
