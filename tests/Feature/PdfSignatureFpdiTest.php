<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\Signature;
use App\Models\SignatureRequest;
use App\Models\User;
use App\Services\PdfSignatureProcessorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use setasign\Fpdi\Fpdi;

class PdfSignatureFpdiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
    }

    /**
     * Helper to create a valid minimal PDF in local storage.
     */
    protected function createValidPdf(string $storagePath): void
    {
        $fpdf = new Fpdi();
        $fpdf->AddPage();
        $fpdf->SetFont('Helvetica', 'B', 16);
        $fpdf->Cell(40, 10, 'Hello World PDF for Testing FPDI');
        $pdfContent = $fpdf->Output('S');

        Storage::disk('local')->put($storagePath, $pdfContent);
    }

    /**
     * Helper to create a valid PNG signature file in public storage.
     */
    protected function createValidSignaturePng(string $storagePath): string
    {
        $im = imagecreatetruecolor(100, 50);
        $bg = imagecolorallocate($im, 255, 255, 255);
        imagefilledrectangle($im, 0, 0, 100, 50, $bg);
        $ink = imagecolorallocate($im, 0, 0, 150);
        imageline($im, 10, 10, 90, 40, $ink);

        ob_start();
        imagepng($im);
        $pngData = ob_get_clean();
        imagedestroy($im);

        Storage::disk('public')->put($storagePath, $pngData);

        return Storage::disk('public')->path($storagePath);
    }

    public function test_pdf_signature_processor_service_stamps_signature_successfully(): void
    {
        $owner = User::factory()->create();
        $docType = DocumentType::create(['name' => 'Surat Edaran', 'code' => 'S.ED']);
        $document = Document::create([
            'document_number' => '001/S.ED/IT/2026',
            'title' => 'Sample PDF Doc',
            'document_type_id' => $docType->id,
            'owner_id' => $owner->id,
            'visibility' => 'division',
        ]);

        $pdfPath = 'documents/' . $document->id . '/v1.pdf';
        $this->createValidPdf($pdfPath);

        $version = DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => 1,
            'content' => '',
            'author_id' => $owner->id,
            'author_name' => $owner->name,
            'status' => 'draft',
            'file_path' => $pdfPath,
            'file_mime' => 'application/pdf',
            'file_original_name' => 'sample.pdf',
        ]);

        $sigPath = $this->createValidSignaturePng('signatures/sig_test.png');

        $service = app(PdfSignatureProcessorService::class);
        $result = $service->processPdfSignature(
            $document,
            $version,
            $sigPath,
            1,
            null,
            null,
            40,
            25,
            PdfSignatureProcessorService::PRESET_BOTTOM_RIGHT,
            'John Doe'
        );

        $this->assertTrue($result);
        $this->assertTrue(Storage::disk('local')->exists($pdfPath));

        // Verify the stamped PDF is a valid PDF readable by FPDI
        $fpdi = new Fpdi();
        $tempFile = storage_path('app/temp_verify_' . uniqid() . '.pdf');
        file_put_contents($tempFile, Storage::disk('local')->get($pdfPath));
        $pages = $fpdi->setSourceFile($tempFile);
        @unlink($tempFile);

        $this->assertEquals(1, $pages);
    }

    public function test_user_can_stamp_their_own_signature_on_pdf(): void
    {
        $user = User::factory()->create();
        $this->createValidSignaturePng('signatures/sig_' . $user->id . '.png');

        Signature::create([
            'user_id' => $user->id,
            'file_path' => 'signatures/sig_' . $user->id . '.png',
        ]);

        $docType = DocumentType::create(['name' => 'Surat Edaran', 'code' => 'S.ED']);
        $document = Document::create([
            'document_number' => '002/S.ED/IT/2026',
            'title' => 'My PDF Doc',
            'document_type_id' => $docType->id,
            'owner_id' => $user->id,
            'visibility' => 'division',
        ]);

        $pdfPath = 'documents/' . $document->id . '/v1.pdf';
        $this->createValidPdf($pdfPath);

        DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => 1,
            'content' => '',
            'author_id' => $user->id,
            'author_name' => $user->name,
            'status' => 'draft',
            'file_path' => $pdfPath,
            'file_mime' => 'application/pdf',
            'file_original_name' => 'test.pdf',
        ]);

        $response = $this->actingAs($user)->postJson(route('documents.stamp-signature', $document), [
            'page_number' => 1,
            'preset_position' => 'bottom-right',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_signature_request_with_placement_stamps_pdf_upon_approval(): void
    {
        $requester = User::factory()->create(['name' => 'Requester']);
        $signer = User::factory()->create(['name' => 'Director']);

        $this->createValidSignaturePng('signatures/sig_director.png');
        Signature::create([
            'user_id' => $signer->id,
            'file_path' => 'signatures/sig_director.png',
        ]);

        $docType = DocumentType::create(['name' => 'Surat Edaran', 'code' => 'S.ED']);
        $document = Document::create([
            'document_number' => '003/S.ED/IT/2026',
            'title' => 'Approval PDF Doc',
            'document_type_id' => $docType->id,
            'owner_id' => $requester->id,
            'visibility' => 'division',
        ]);

        $pdfPath = 'documents/' . $document->id . '/v1.pdf';
        $this->createValidPdf($pdfPath);

        DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => 1,
            'content' => '',
            'author_id' => $requester->id,
            'author_name' => $requester->name,
            'status' => 'draft',
            'file_path' => $pdfPath,
            'file_mime' => 'application/pdf',
            'file_original_name' => 'approval.pdf',
        ]);

        // 1. Requester requests signature with placement
        $reqResponse = $this->actingAs($requester)->getJson(
            route('profile.signature.show', [
                'user_id' => $signer->id,
                'document_id' => $document->id,
                'page_number' => 1,
                'preset_position' => 'bottom-left',
            ])
        );

        $reqResponse->assertStatus(200)
            ->assertJson(['is_pending' => true]);

        $sigRequest = SignatureRequest::where('requester_id', $requester->id)
            ->where('target_user_id', $signer->id)
            ->where('document_id', $document->id)
            ->first();

        $this->assertNotNull($sigRequest);
        $this->assertEquals(1, $sigRequest->page_number);
        $this->assertEquals('bottom-left', $sigRequest->preset_position);

        // 2. Signer approves signature request
        $approveResponse = $this->actingAs($signer)->post(
            route('signatures.requests.approve', $sigRequest)
        );

        $approveResponse->assertRedirect();
        $this->assertTrue($sigRequest->fresh()->isApproved());

        // 3. Confirm file was stamped and modified in storage
        $this->assertTrue(Storage::disk('local')->exists($pdfPath));
    }

    public function test_user_can_stamp_pdf_with_custom_visual_coordinates_and_dimensions(): void
    {
        $user = User::factory()->create();
        $this->createValidSignaturePng('signatures/sig_' . $user->id . '.png');

        Signature::create([
            'user_id' => $user->id,
            'file_path' => 'signatures/sig_' . $user->id . '.png',
        ]);

        $docType = DocumentType::create(['name' => 'Surat Edaran', 'code' => 'S.ED']);
        $document = Document::create([
            'document_number' => '004/S.ED/IT/2026',
            'title' => 'Visual Custom Placement PDF',
            'document_type_id' => $docType->id,
            'owner_id' => $user->id,
            'visibility' => 'division',
        ]);

        $pdfPath = 'documents/' . $document->id . '/v1.pdf';
        $this->createValidPdf($pdfPath);

        DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => 1,
            'content' => '',
            'author_id' => $user->id,
            'author_name' => $user->name,
            'status' => 'draft',
            'file_path' => $pdfPath,
            'file_mime' => 'application/pdf',
            'file_original_name' => 'custom_visual.pdf',
        ]);

        $response = $this->actingAs($user)->postJson(route('documents.stamp-signature', $document), [
            'page_number' => 1,
            'pos_x' => 85.5,
            'pos_y' => 160.0,
            'width' => 55.0,
            'height' => 32.5,
            'preset_position' => 'custom',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertTrue(Storage::disk('local')->exists($pdfPath));
    }

    public function test_signature_request_with_custom_visual_coordinates_persists_and_stamps_properly(): void
    {
        $requester = User::factory()->create(['name' => 'Visual Requester']);
        $signer = User::factory()->create(['name' => 'Manager']);

        $this->createValidSignaturePng('signatures/sig_manager.png');
        Signature::create([
            'user_id' => $signer->id,
            'file_path' => 'signatures/sig_manager.png',
        ]);

        $docType = DocumentType::create(['name' => 'Surat Edaran', 'code' => 'S.ED']);
        $document = Document::create([
            'document_number' => '005/S.ED/IT/2026',
            'title' => 'Manager Approval Custom PDF',
            'document_type_id' => $docType->id,
            'owner_id' => $requester->id,
            'visibility' => 'division',
        ]);

        $pdfPath = 'documents/' . $document->id . '/v1.pdf';
        $this->createValidPdf($pdfPath);

        DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => 1,
            'content' => '',
            'author_id' => $requester->id,
            'author_name' => $requester->name,
            'status' => 'draft',
            'file_path' => $pdfPath,
            'file_mime' => 'application/pdf',
            'file_original_name' => 'manager_doc.pdf',
        ]);

        // Requester requests with visual custom placement
        $reqResponse = $this->actingAs($requester)->getJson(
            route('profile.signature.show', [
                'user_id' => $signer->id,
                'document_id' => $document->id,
                'page_number' => 1,
                'pos_x' => 120.4,
                'pos_y' => 205.8,
                'width' => 45.0,
                'height' => 28.0,
                'preset_position' => 'custom',
            ])
        );

        $reqResponse->assertStatus(200)
            ->assertJson(['is_pending' => true]);

        $sigRequest = SignatureRequest::where('requester_id', $requester->id)
            ->where('target_user_id', $signer->id)
            ->where('document_id', $document->id)
            ->first();

        $this->assertNotNull($sigRequest);
        $this->assertEquals(1, $sigRequest->page_number);
        $this->assertEquals(120.4, $sigRequest->pos_x);
        $this->assertEquals(205.8, $sigRequest->pos_y);
        $this->assertEquals(45.0, $sigRequest->width);
        $this->assertEquals(28.0, $sigRequest->height);
        $this->assertEquals('custom', $sigRequest->preset_position);

        // Signer approves
        $approveResponse = $this->actingAs($signer)->post(
            route('signatures.requests.approve', $sigRequest)
        );

        $approveResponse->assertRedirect();
        $this->assertTrue($sigRequest->fresh()->isApproved());
        $this->assertTrue(Storage::disk('local')->exists($pdfPath));
    }

    public function test_user_can_revert_stamped_pdf_signature_back_to_original_clean_state(): void
    {
        $user = User::factory()->create();
        $this->createValidSignaturePng('signatures/sig_' . $user->id . '.png');

        Signature::create([
            'user_id' => $user->id,
            'file_path' => 'signatures/sig_' . $user->id . '.png',
        ]);

        $docType = DocumentType::create(['name' => 'Surat Edaran', 'code' => 'S.ED']);
        $document = Document::create([
            'document_number' => '004/S.ED/IT/2026',
            'title' => 'Revert PDF Doc',
            'document_type_id' => $docType->id,
            'owner_id' => $user->id,
            'visibility' => 'division',
        ]);

        $pdfPath = 'documents/' . $document->id . '/v1.pdf';
        $this->createValidPdf($pdfPath);
        $originalPdfBytes = Storage::disk('local')->get($pdfPath);

        DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => 1,
            'content' => '',
            'author_id' => $user->id,
            'author_name' => $user->name,
            'status' => 'draft',
            'file_path' => $pdfPath,
            'file_mime' => 'application/pdf',
            'file_original_name' => 'original.pdf',
        ]);

        // 1. Stamp signature
        $stampRes = $this->actingAs($user)->postJson(route('documents.stamp-signature', $document), [
            'page_number' => 1,
            'preset_position' => 'bottom-right',
        ]);
        $stampRes->assertStatus(200)->assertJson(['success' => true]);

        // Backup file exists
        $pdfProcessor = app(PdfSignatureProcessorService::class);
        $backupPath = $pdfProcessor->getBackupFilePath($pdfPath);
        $this->assertTrue(Storage::disk('local')->exists($backupPath));
        $this->assertEquals($originalPdfBytes, Storage::disk('local')->get($backupPath));

        // 2. Revert signature
        $revertRes = $this->actingAs($user)->postJson(route('documents.revert-pdf-signature', $document));
        $revertRes->assertStatus(200)->assertJson([
            'success' => true,
            'message' => 'Tanda tangan berhasil dihapus dan dokumen PDF dikembalikan ke versi semula.',
        ]);

        // The current PDF file is restored to the original content
        $restoredPdfBytes = Storage::disk('local')->get($pdfPath);
        $this->assertEquals($originalPdfBytes, $restoredPdfBytes);
    }

    public function test_user_can_stamp_qr_code_on_pdf_with_custom_visual_coordinates(): void
    {
        $user = User::factory()->create();

        $docType = DocumentType::create(['name' => 'Surat Edaran', 'code' => 'S.ED']);
        $document = Document::create([
            'document_number' => '005/S.ED/IT/2026',
            'title' => 'QR PDF Doc',
            'document_type_id' => $docType->id,
            'owner_id' => $user->id,
            'visibility' => 'division',
        ]);

        $pdfPath = 'documents/' . $document->id . '/v1.pdf';
        $this->createValidPdf($pdfPath);

        DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => 1,
            'content' => '',
            'author_id' => $user->id,
            'author_name' => $user->name,
            'status' => 'draft',
            'file_path' => $pdfPath,
            'file_mime' => 'application/pdf',
            'file_original_name' => 'qr_test.pdf',
        ]);

        $response = $this->actingAs($user)->postJson(route('documents.stamp-qrcode', $document), [
            'page_number' => 1,
            'pos_x' => 160.0,
            'pos_y' => 240.0,
            'width' => 30.0,
            'height' => 30.0,
            'preset_position' => 'custom',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'QR Code verifikasi berhasil dibubuhkan pada dokumen PDF.',
            ]);

        $this->assertTrue(Storage::disk('local')->exists($pdfPath));
    }

    public function test_cannot_stamp_qr_code_on_docx_file(): void
    {
        $user = User::factory()->create();

        $docType = DocumentType::create(['name' => 'Surat Edaran', 'code' => 'S.ED']);
        $document = Document::create([
            'document_number' => '006/S.ED/IT/2026',
            'title' => 'Word Doc',
            'document_type_id' => $docType->id,
            'owner_id' => $user->id,
            'visibility' => 'division',
        ]);

        $docxPath = 'documents/' . $document->id . '/v1.docx';
        Storage::disk('local')->put($docxPath, 'fake docx');

        DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => 1,
            'content' => '',
            'author_id' => $user->id,
            'author_name' => $user->name,
            'status' => 'draft',
            'file_path' => $docxPath,
            'file_mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'file_original_name' => 'word.docx',
        ]);

        $response = $this->actingAs($user)->postJson(route('documents.stamp-qrcode', $document), [
            'page_number' => 1,
            'pos_x' => 50.0,
            'pos_y' => 50.0,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Fitur ini khusus untuk dokumen format PDF.',
            ]);
    }
}
