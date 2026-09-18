<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Branch;
use App\Models\Division;
use App\Models\Document;
use App\Models\DocumentTemplate;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use App\Services\OnlyOfficeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentTemplateSavingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;
    protected Company $company;
    protected Branch $branch;
    protected Division $division;
    protected DocumentType $docType;
    protected DocumentTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('onlyoffice.storage_disk', 'local'));
        Storage::fake('local');
        Storage::fake('public');

        $this->company = Company::create([
            'name' => 'PT Test Company',
            'code' => 'TEST',
        ]);

        $this->branch = Branch::create([
            'name' => 'Kantor Pusat',
            'code' => 'PST',
            'company_id' => $this->company->id,
            'is_pusat' => true,
            'is_active' => true,
        ]);

        $this->division = Division::create([
            'name' => 'Divisi Teknologi',
            'code' => 'DIV',
            'company_id' => $this->company->id,
        ]);

        $this->admin = User::factory()->create([
            'division_id' => $this->division->id,
            'system_role' => 'admin',
            'is_active' => true,
        ]);
        $this->admin->branches()->attach($this->branch->id);

        $this->user = User::factory()->create([
            'division_id' => $this->division->id,
            'system_role' => 'staff',
            'is_active' => true,
        ]);
        $this->user->branches()->attach($this->branch->id);

        $this->docType = DocumentType::create([
            'name' => 'Surat Keputusan',
            'code' => 'SK',
            'retention_period' => 5,
        ]);

        // Create a template with initial content
        $templatePath = 'templates/test_template.docx';
        Storage::disk(config('onlyoffice.storage_disk', 'local'))->put($templatePath, 'initial-template-content-v1');

        $this->template = DocumentTemplate::create([
            'title' => 'Template Surat Keputusan',
            'description' => 'Template resmi SK',
            'file_path' => $templatePath,
            'file_original_name' => 'Template Surat Keputusan.docx',
            'file_mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'document_type_id' => $this->docType->id,
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_admin_can_open_template_editor(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.templates.editor', $this->template));

        $response->assertStatus(200);
        $response->assertSee('onlyoffice-editor-container');
        $response->assertSee('Template Surat Keputusan');
    }

    public function test_onlyoffice_template_callback_status_2_saves_file_and_rotates_key(): void
    {
        $updatedDocxContent = 'updated-template-content-by-admin-v2';

        Http::fake([
            'http://onlyoffice-server/download/updated_template.docx' => Http::response($updatedDocxContent, 200),
        ]);

        $oldKey = app(OnlyOfficeService::class)->generateTemplateKey($this->template);

        $payload = [
            'status' => 2,
            'url' => 'http://onlyoffice-server/download/updated_template.docx',
            'users' => [(string) $this->admin->id],
            'key' => $oldKey,
        ];

        $response = $this->postJson(route('onlyoffice.templates.callback', $this->template), $payload);

        $response->assertStatus(200);
        $response->assertJson(['error' => 0]);

        $disk = Storage::disk(config('onlyoffice.storage_disk', 'local'));
        $this->assertEquals($updatedDocxContent, $disk->get($this->template->fresh()->file_path));

        // Key must be rotated
        $newKey = app(OnlyOfficeService::class)->generateTemplateKey($this->template->fresh());
        $this->assertNotEquals($oldKey, $newKey, 'ONLYOFFICE template key should rotate after save');

        // Audit log recorded
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'template.saved_onlyoffice',
            'target_type' => 'document_template',
            'target_id' => $this->template->id,
        ]);
    }

    public function test_onlyoffice_template_callback_status_6_forcesaves_file(): void
    {
        $status6DocxContent = 'intermediate-saved-template-content';

        Http::fake([
            'http://onlyoffice-server/download/status6_template.docx' => Http::response($status6DocxContent, 200),
        ]);

        $payload = [
            'status' => 6,
            'url' => 'http://onlyoffice-server/download/status6_template.docx',
            'users' => [(string) $this->admin->id],
            'key' => 'some_template_key',
        ];

        $response = $this->postJson(route('onlyoffice.templates.callback', $this->template), $payload);

        $response->assertStatus(200);
        $response->assertJson(['error' => 0]);

        $disk = Storage::disk(config('onlyoffice.storage_disk', 'local'));
        $this->assertEquals($status6DocxContent, $disk->get($this->template->fresh()->file_path));
    }

    public function test_creating_document_from_updated_template_uses_new_content(): void
    {
        // 1. Admin updates template via callback
        $updatedDocxContent = 'completely-new-template-content-from-admin-edit';

        Http::fake([
            'http://onlyoffice-server/download/edited.docx' => Http::response($updatedDocxContent, 200),
        ]);

        $this->postJson(route('onlyoffice.templates.callback', $this->template), [
            'status' => 2,
            'url' => 'http://onlyoffice-server/download/edited.docx',
            'users' => [(string) $this->admin->id],
        ]);

        // 2. User creates a new document using this template
        $response = $this->actingAs($this->user)->post(route('documents.store'), [
            'title' => 'Dokumen Baru Pengguna',
            'document_type_id' => $this->docType->id,
            'division_id' => $this->division->id,
            'branch_id' => $this->branch->id,
            'template_id' => $this->template->id,
        ]);

        $response->assertRedirect();
        $doc = Document::latest('id')->first();
        $this->assertNotNull($doc);
        $this->assertEquals($this->template->id, $doc->template_id);

        // 3. Verify the newly created document v1.docx contains the updated template content
        $disk = Storage::disk(config('onlyoffice.storage_disk', 'local'));
        $v1Path = 'documents/' . $doc->id . '/v1.docx';
        $this->assertTrue($disk->exists($v1Path));
        $this->assertEquals($updatedDocxContent, $disk->get($v1Path), 'New document must copy the updated template content');
    }

    public function test_admin_finish_editing_template_endpoint_rotates_key_and_touches_template(): void
    {
        $oldKey = app(OnlyOfficeService::class)->generateTemplateKey($this->template);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.templates.finish-editing', $this->template));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'redirect_url' => route('admin.templates.index'),
        ]);

        $newKey = app(OnlyOfficeService::class)->generateTemplateKey($this->template->fresh());
        $this->assertNotEquals($oldKey, $newKey, 'finish-editing must rotate template key');
    }

    public function test_updating_template_via_form_rotates_key(): void
    {
        $oldKey = app(OnlyOfficeService::class)->generateTemplateKey($this->template);

        $newFile = UploadedFile::fake()->create('uploaded_template.docx', 50, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $response = $this->actingAs($this->admin)->put(route('admin.templates.update', $this->template), [
            'title' => 'Updated Title SK',
            'document_type_id' => $this->docType->id,
            'file' => $newFile,
        ]);

        $response->assertRedirect(route('admin.templates.index'));

        $newKey = app(OnlyOfficeService::class)->generateTemplateKey($this->template->fresh());
        $this->assertNotEquals($oldKey, $newKey, 'Updating template via form must rotate ONLYOFFICE key');
    }
}
