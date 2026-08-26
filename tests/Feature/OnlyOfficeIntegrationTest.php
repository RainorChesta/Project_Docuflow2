<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Services\OnlyOfficeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OnlyOfficeIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected Division $division;
    protected DocumentType $docType;
    protected Document $document;
    protected DocumentVersion $version;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->division = Division::create(['name' => 'IT Division', 'code' => 'IT']);
        $this->docType = DocumentType::create(['name' => 'Surat Edaran', 'code' => 'S.ED']);

        $this->user = User::factory()->create([
            'division_id' => $this->division->id,
            'system_role' => 'staff',
            'is_active' => true,
        ]);

        $this->otherUser = User::factory()->create([
            'division_id' => $this->division->id,
            'system_role' => 'staff',
            'is_active' => true,
        ]);

        // Create document with version
        $this->document = Document::create([
            'title' => 'Test ONLYOFFICE Doc',
            'document_number' => '001/S.ED/IT/JBM/VIII/2026',
            'division_id' => $this->division->id,
            'owner_id' => $this->user->id,
            'document_type_id' => $this->docType->id,
            'visibility' => Document::VISIBILITY_DIVISION,
        ]);

        $filePath = 'documents/' . $this->document->id . '/v1.docx';
        Storage::disk('local')->put($filePath, 'fake-docx-binary-content');

        $this->version = $this->document->versions()->create([
            'version_number' => 1,
            'content' => '',
            'file_path' => $filePath,
            'file_original_name' => 'Test ONLYOFFICE Doc.docx',
            'file_mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'author_id' => $this->user->id,
            'author_name' => $this->user->name,
            'status' => 'draft',
        ]);
    }

    public function test_authenticated_owner_can_open_onlyoffice_editor()
    {
        $response = $this->actingAs($this->user)->get(route('documents.edit', $this->document));

        $response->assertStatus(200);
        $response->assertSee('onlyoffice-editor-container');
        $response->assertSee('ONLYOFFICE Document Editor');
    }

    public function test_onlyoffice_file_endpoint_serves_document_binary()
    {
        $response = $this->get(route('onlyoffice.file', [
            'document' => $this->document->id,
            'version' => $this->version->id,
        ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    }

    public function test_onlyoffice_service_generates_valid_config()
    {
        $service = app(OnlyOfficeService::class);
        $config = $service->generateEditorConfig($this->document, $this->version, $this->user, 'edit');

        $this->assertEquals('word', $config['documentType']);
        $this->assertEquals('docx', $config['document']['fileType']);
        $this->assertTrue($config['document']['permissions']['edit']);
        $this->assertNotEmpty($config['editorConfig']['callbackUrl']);
    }

    public function test_onlyoffice_save_callback_updates_pending_version()
    {
        // Mock fake download URL from ONLYOFFICE
        \Illuminate\Support\Facades\Http::fake([
            'http://onlyoffice-server/download/updated.docx' => \Illuminate\Support\Facades\Http::response('updated-docx-content-bytes', 200),
        ]);

        $payload = [
            'status' => 2,
            'url' => 'http://onlyoffice-server/download/updated.docx',
            'users' => [(string) $this->user->id],
            'key' => 'doc_test_key',
        ];

        $response = $this->postJson(route('onlyoffice.callback', $this->document), $payload);

        $response->assertStatus(200);
        $response->assertJson(['error' => 0]);

        $this->assertDatabaseHas('document_versions', [
            'document_id' => $this->document->id,
            'status' => 'pending',
            'author_id' => $this->user->id,
        ]);
    }

    public function test_user_can_download_document_docx()
    {
        $response = $this->actingAs($this->user)->get(route('documents.download', $this->document));

        $response->assertStatus(200);
        $response->assertHeader('content-disposition', 'attachment; filename="Test ONLYOFFICE Doc.docx"');
    }

    public function test_uploaded_pdf_document_generates_pdf_editor_config()
    {
        $pdfPath = 'documents/' . $this->document->id . '/v2.pdf';
        Storage::disk('local')->put($pdfPath, '%PDF-1.4 fake pdf content');

        $pdfVersion = $this->document->versions()->create([
            'version_number' => 2,
            'content' => '',
            'file_path' => $pdfPath,
            'file_original_name' => 'UploadedReport.pdf',
            'file_mime' => 'application/pdf',
            'author_id' => $this->user->id,
            'author_name' => $this->user->name,
            'status' => 'pending',
        ]);

        $service = app(OnlyOfficeService::class);
        $config = $service->generateEditorConfig($this->document, $pdfVersion, $this->user, 'edit');

        $this->assertEquals('pdf', $config['documentType']);
        $this->assertEquals('pdf', $config['document']['fileType']);
        $this->assertTrue($config['document']['permissions']['edit']);
    }

    public function test_onlyoffice_signature_endpoint_serves_png_binary()
    {
        Storage::fake('public');
        $sigPath = 'signatures/sig_test.png';
        Storage::disk('public')->put($sigPath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));

        \App\Models\Signature::create([
            'user_id' => $this->user->id,
            'file_path' => $sigPath,
            'signature_type' => 'canvas',
        ]);

        $response = $this->get(route('onlyoffice.signature', ['user' => $this->user->id]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/png');
    }

    public function test_onlyoffice_qrcode_endpoint_serves_png_binary()
    {
        $response = $this->get(route('onlyoffice.qrcode', ['document' => $this->document->id]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/png');
    }

    public function test_onlyoffice_service_generates_valid_insert_image_token()
    {
        config([
            'onlyoffice.jwt_enabled' => true,
            'onlyoffice.jwt_secret' => 'test-secret-key-12345-dokuflow-2026',
        ]);

        $service = app(OnlyOfficeService::class);
        $imageUrl = 'http://localhost:8000/onlyoffice/users/1/signature';
        $token = $service->generateInsertImageToken($imageUrl);

        $this->assertNotNull($token);

        $decoded = (array) \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key('test-secret-key-12345-dokuflow-2026', 'HS256'));
        $this->assertEquals('add', $decoded['c']);
        $this->assertEquals($imageUrl, $decoded['url']);
    }

    public function test_signature_show_endpoint_returns_onlyoffice_url_and_token()
    {
        Storage::fake('public');
        $sigPath = 'signatures/sig_test.png';
        Storage::disk('public')->put($sigPath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));

        \App\Models\Signature::create([
            'user_id' => $this->user->id,
            'file_path' => $sigPath,
            'signature_type' => 'canvas',
        ]);

        config([
            'onlyoffice.jwt_enabled' => true,
            'onlyoffice.jwt_secret' => 'test-secret-key-12345-dokuflow-2026',
            'onlyoffice.internal_url' => 'http://host.docker.internal:8000',
        ]);

        $response = $this->actingAs($this->user)->getJson(route('profile.signature.show'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'url',
            'token',
            'client_url',
            'data_uri',
            'updated_at',
        ]);
        $response->assertJson([
            'success' => true,
            'url' => 'http://host.docker.internal:8000/onlyoffice/users/' . $this->user->id . '/signature',
        ]);
    }
}
