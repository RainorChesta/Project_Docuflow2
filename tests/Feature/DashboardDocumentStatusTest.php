<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\UnitKerja;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardDocumentStatusTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;
    protected Company $company;
    protected Branch $branch;
    protected UnitKerja $unitKerja;
    protected DocumentType $docType;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('id');

        $this->company = Company::create(['name' => 'CMH Group', 'code' => 'CMH']);
        $this->branch = Branch::create(['company_id' => $this->company->id, 'name' => 'Pusat', 'is_pusat' => true]);
        $this->unitKerja = UnitKerja::create(['nama_unit_kerja' => 'Medical Services', 'kode_unit_kerja' => 'MED']);
        $this->docType = DocumentType::create(['name' => 'Surat Edaran', 'code' => 'S.ED', 'category' => 'internal']);

        $this->user = User::factory()->create([
            'system_role' => 'user',
            'unit_kerja_id' => $this->unitKerja->id,
        ]);
        $this->user->companies()->sync([$this->company->id]);
        $this->user->branches()->sync([$this->branch->id]);

        $this->admin = User::factory()->create(['system_role' => 'admin']);
    }

    public function test_draft_document_is_displayed_as_draft_on_staff_dashboard_and_matches_my_documents(): void
    {
        $doc = Document::create([
            'document_number' => '034/S.ED/MMC/X/2026',
            'title' => 'Surat Edaran 34',
            'owner_id' => $this->user->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'unit_kerja_id' => $this->unitKerja->id,
            'document_type_id' => $this->docType->id,
            'visibility' => Document::VISIBILITY_PERSONAL,
            'current_version_id' => null,
        ]);

        $version = DocumentVersion::create([
            'document_id' => $doc->id,
            'version_number' => 1,
            'content' => '<p>Draft content</p>',
            'author_id' => $this->user->id,
            'author_name' => $this->user->name,
            'status' => 'draft',
        ]);

        // 1. Check Dashboard for regular user
        $dashboardResponse = $this->actingAs($this->user)->get(route('dashboard'));
        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee('Surat Edaran 34');
        $dashboardResponse->assertSee(__('Draf'));
        // In the recent documents table, it should NOT say "Tertunda"
        $dashboardResponse->assertDontSee(__('Tertunda'));

        // 2. Check My Documents
        $myDocsResponse = $this->actingAs($this->user)->get(route('documents.index', ['type' => 'mine']));
        $myDocsResponse->assertOk();
        $myDocsResponse->assertSee('Surat Edaran 34');
        $myDocsResponse->assertSee(__('Draf'));
        $myDocsResponse->assertDontSee(__('Tertunda'));
    }

    public function test_pending_document_is_displayed_as_pending_on_staff_dashboard(): void
    {
        $doc = Document::create([
            'document_number' => '035/S.ED/MMC/X/2026',
            'title' => 'Surat Edaran 35 Pending',
            'owner_id' => $this->user->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'unit_kerja_id' => $this->unitKerja->id,
            'document_type_id' => $this->docType->id,
            'visibility' => Document::VISIBILITY_PERSONAL,
            'current_version_id' => null,
        ]);

        DocumentVersion::create([
            'document_id' => $doc->id,
            'version_number' => 1,
            'content' => '<p>Pending content</p>',
            'author_id' => $this->user->id,
            'author_name' => $this->user->name,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('Surat Edaran 35 Pending');
        $response->assertSee(__('Tertunda'));
    }

    public function test_active_document_is_displayed_as_active_on_staff_dashboard(): void
    {
        $doc = Document::create([
            'document_number' => '036/S.ED/MMC/X/2026',
            'title' => 'Surat Edaran 36 Aktif',
            'owner_id' => $this->user->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'unit_kerja_id' => $this->unitKerja->id,
            'document_type_id' => $this->docType->id,
            'visibility' => Document::VISIBILITY_PERSONAL,
        ]);

        $version = DocumentVersion::create([
            'document_id' => $doc->id,
            'version_number' => 1,
            'content' => '<p>Active content</p>',
            'author_id' => $this->user->id,
            'author_name' => $this->user->name,
            'status' => 'active',
        ]);

        $doc->update(['current_version_id' => $version->id]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('Surat Edaran 36 Aktif');
        $response->assertSee('v1');
    }

    public function test_expired_document_is_displayed_as_expired_on_staff_dashboard(): void
    {
        $doc = Document::create([
            'document_number' => '037/S.ED/MMC/X/2026',
            'title' => 'Surat Edaran 37 Expired',
            'owner_id' => $this->user->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'unit_kerja_id' => $this->unitKerja->id,
            'document_type_id' => $this->docType->id,
            'visibility' => Document::VISIBILITY_PERSONAL,
            'expiration_date' => now()->subDays(5)->format('Y-m-d'),
            'is_expired' => true,
        ]);

        $version = DocumentVersion::create([
            'document_id' => $doc->id,
            'version_number' => 1,
            'content' => '<p>Expired content</p>',
            'author_id' => $this->user->id,
            'author_name' => $this->user->name,
            'status' => 'active',
        ]);

        $doc->update(['current_version_id' => $version->id]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('Surat Edaran 37 Expired');
        $response->assertSee(__('Kedaluwarsa'));
    }

    public function test_draft_document_is_displayed_as_draft_on_admin_dashboard(): void
    {
        $doc = Document::create([
            'document_number' => '038/S.ED/MMC/X/2026',
            'title' => 'Surat Edaran 38 Admin View',
            'owner_id' => $this->user->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'unit_kerja_id' => $this->unitKerja->id,
            'document_type_id' => $this->docType->id,
            'visibility' => Document::VISIBILITY_GENERAL,
            'current_version_id' => null,
        ]);

        DocumentVersion::create([
            'document_id' => $doc->id,
            'version_number' => 1,
            'content' => '<p>Draft content</p>',
            'author_id' => $this->user->id,
            'author_name' => $this->user->name,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('Surat Edaran 38 Admin View');
        $response->assertSee(__('Draf'));
        // Assert that the table row for Surat Edaran 38 displays Draf
        $content = $response->getContent();
        $this->assertStringContainsString('Surat Edaran 38 Admin View', $content);
        $this->assertMatchesRegularExpression('/Surat Edaran 38 Admin View.*?Draf/s', $content);
    }
}
