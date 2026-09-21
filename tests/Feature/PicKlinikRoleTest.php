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
use App\Services\ApprovalRoutingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PicKlinikRoleTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected UnitKerja $unitKerja;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create(['name' => 'PT Test Medika', 'code' => 'TTM']);
        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Klinik Cabang Alpha',
            'code' => 'KCA',
            'is_pusat' => false,
        ]);
        $this->unitKerja = UnitKerja::create(['kode_unit_kerja' => '01', 'nama_unit_kerja' => 'Poli Gigi']);

        $this->admin = User::factory()->create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'system_role' => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_create_pic_klinik_without_unit_kerja(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'name' => 'drg. Rina (PIC Klinik)',
            'email' => 'rina.kacab@test.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'system_role' => 'pic_klinik',
            'is_active' => '1',
            'company_ids' => [$this->company->id],
            'branch_ids' => [$this->branch->id],
            // Note: unit_kerja_ids and branch_unit_kerjas are omitted
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        $user = User::where('email', 'rina.kacab@test.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->isPicKlinik());
        $this->assertFalse($user->isDirector());
        $this->assertFalse($user->isHead());
        $this->assertTrue($user->isVerified());
        $this->assertFalse($user->isPendingVerification());
        $this->assertNull($user->unit_kerja_id);
        $this->assertCount(0, $user->unitKerjas);
        $this->assertTrue($user->branches->contains($this->branch));
    }

    public function test_admin_can_update_user_to_pic_klinik(): void
    {
        $staff = User::factory()->create([
            'system_role' => 'user',
            'is_active' => true,
            'unit_kerja_id' => $this->unitKerja->id,
        ]);
        $staff->companies()->sync([$this->company->id]);
        $staff->branches()->sync([$this->branch->id]);

        $response = $this->actingAs($this->admin)->put(route('admin.users.update', $staff), [
            'name' => 'Promoted to PIC Klinik',
            'email' => $staff->email,
            'system_role' => 'pic_klinik',
            'is_active' => '1',
            'company_ids' => [$this->company->id],
            'branch_ids' => [$this->branch->id],
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $staff->refresh();

        $this->assertTrue($staff->isPicKlinik());
        $this->assertTrue($staff->isVerified());
        $this->assertNull($staff->unit_kerja_id);
        $this->assertCount(0, $staff->unitKerjas);
    }

    public function test_pic_klinik_role_weight_is_30_in_signature_workflow(): void
    {
        $picKlinik = User::factory()->create([
            'system_role' => 'pic_klinik',
            'is_active' => true,
        ]);
        $picKlinik->companies()->sync([$this->company->id]);
        $picKlinik->branches()->sync([$this->branch->id]);

        $docType = DocumentType::create(['name' => 'SOP', 'code' => 'SOP']);
        $doc = Document::create([
            'title' => 'SOP Pelayanan',
            'document_number' => '001/SOP-01/KCA/09/2026',
            'document_type_id' => $docType->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'unit_kerja_id' => $this->unitKerja->id,
            'owner_id' => $this->admin->id,
        ]);

        $sigReq = SignatureRequest::create([
            'document_id' => $doc->id,
            'requester_id' => $this->admin->id,
            'target_user_id' => $picKlinik->id,
            'status' => 'pending',
        ]);

        $this->assertEquals(30, $sigReq->role_weight);
    }

    public function test_approval_routing_service_generates_pic_klinik_step(): void
    {
        $picKlinik = User::factory()->create([
            'name' => 'dr. PIC Cabang',
            'system_role' => 'pic_klinik',
            'is_active' => true,
        ]);
        $picKlinik->companies()->sync([$this->company->id]);
        $picKlinik->branches()->sync([$this->branch->id]);

        $author = User::factory()->create([
            'name' => 'Staff Pembuat',
            'system_role' => 'user',
            'is_active' => true,
            'unit_kerja_id' => $this->unitKerja->id,
        ]);
        $author->companies()->sync([$this->company->id]);
        $author->branches()->sync([$this->branch->id]);

        $docType = DocumentType::create(['name' => 'SOP', 'code' => 'SOP']);
        $doc = Document::create([
            'title' => 'SOP Tindakan Medis',
            'document_number' => '002/SOP-01/KCA/09/2026',
            'document_type_id' => $docType->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'unit_kerja_id' => $this->unitKerja->id,
            'owner_id' => $author->id,
        ]);
        $version = DocumentVersion::create([
            'document_id' => $doc->id,
            'version_number' => 1,
            'author_id' => $author->id,
            'author_name' => $author->name,
            'content' => '<p>Isi dokumen SOP</p>',
            'status' => 'draft',
        ]);

        $sigReq = SignatureRequest::create([
            'document_id' => $doc->id,
            'requester_id' => $author->id,
            'target_user_id' => $picKlinik->id,
            'status' => 'pending',
        ]);

        $service = app(ApprovalRoutingService::class);
        $result = $service->compileWorkflowFromSignatures($doc, $version, $author);

        $steps = $result['steps'];
        $this->assertCount(1, $steps);
        $this->assertEquals('pic_klinik_approval', $steps[0]->step_type);
        $this->assertEquals($picKlinik->id, $steps[0]->assigned_user_id);
        $this->assertEquals('pending', $steps[0]->status);
    }

    public function test_pic_klinik_pending_approvals_count_scopes_to_branch(): void
    {
        $picKlinik = User::factory()->create([
            'system_role' => 'pic_klinik',
            'is_active' => true,
        ]);
        $picKlinik->companies()->sync([$this->company->id]);
        $picKlinik->branches()->sync([$this->branch->id]);

        $docType = DocumentType::create(['name' => 'SOP', 'code' => 'SOP']);

        // Document in same branch pending approval with approver_role = pic_klinik
        $docInBranch = Document::create([
            'title' => 'Dokumen Cabang Alpha',
            'document_number' => '003/SOP-01/KCA/09/2026',
            'document_type_id' => $docType->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'unit_kerja_id' => $this->unitKerja->id,
            'owner_id' => $this->admin->id,
            'approver_role' => 'pic_klinik',
            'approver_id' => $picKlinik->id,
        ]);
        DocumentVersion::create([
            'document_id' => $docInBranch->id,
            'version_number' => 1,
            'author_id' => $this->admin->id,
            'author_name' => $this->admin->name,
            'content' => '<p>Konten</p>',
            'status' => 'pending',
        ]);

        // Another branch document
        $otherBranch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Klinik Cabang Beta',
            'code' => 'KCB',
            'is_pusat' => false,
        ]);
        $docOtherBranch = Document::create([
            'title' => 'Dokumen Cabang Beta',
            'document_number' => '004/SOP-01/KCB/09/2026',
            'document_type_id' => $docType->id,
            'company_id' => $this->company->id,
            'branch_id' => $otherBranch->id,
            'unit_kerja_id' => $this->unitKerja->id,
            'owner_id' => $this->admin->id,
            'approver_role' => 'pic_klinik',
        ]);
        DocumentVersion::create([
            'document_id' => $docOtherBranch->id,
            'version_number' => 1,
            'author_id' => $this->admin->id,
            'author_name' => $this->admin->name,
            'content' => '<p>Konten</p>',
            'status' => 'pending',
        ]);

        $count = $picKlinik->pendingVersionApprovalsCount();
        $this->assertEquals(1, $count);
    }

    public function test_pic_klinik_can_access_approvals_page_and_view_pending_versions(): void
    {
        $picKlinik = User::factory()->create([
            'system_role' => 'pic_klinik',
            'is_active' => true,
        ]);
        $picKlinik->companies()->sync([$this->company->id]);
        $picKlinik->branches()->sync([$this->branch->id]);

        $docType = DocumentType::create(['name' => 'SOP', 'code' => 'SOP']);
        $doc = Document::create([
            'title' => 'Dokumen Klinik Siap Approve',
            'document_number' => '005/SOP-01/KCA/09/2026',
            'document_type_id' => $docType->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'unit_kerja_id' => $this->unitKerja->id,
            'owner_id' => $this->admin->id,
            'approver_role' => 'pic_klinik',
        ]);
        DocumentVersion::create([
            'document_id' => $doc->id,
            'version_number' => 1,
            'author_id' => $this->admin->id,
            'author_name' => $this->admin->name,
            'content' => '<p>Konten</p>',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($picKlinik)->get(route('approvals.versions'));
        $response->assertStatus(200);
        $response->assertSee('Dokumen Klinik Siap Approve');
    }

    public function test_pic_klinik_can_view_unit_kerja_documents_in_branch(): void
    {
        $picKlinik = User::factory()->create([
            'system_role' => 'pic_klinik',
            'is_active' => true,
        ]);
        $picKlinik->companies()->sync([$this->company->id]);
        $picKlinik->branches()->sync([$this->branch->id]);

        $docType = DocumentType::create(['name' => 'SOP', 'code' => 'SOP']);
        $activeDoc = Document::create([
            'title' => 'Dokumen Unit Kerja Klinik',
            'document_number' => '006/SOP-01/KCA/09/2026',
            'document_type_id' => $docType->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'unit_kerja_id' => $this->unitKerja->id,
            'owner_id' => $this->admin->id,
            'visibility' => Document::VISIBILITY_UNIT_KERJA,
        ]);
        DocumentVersion::create([
            'document_id' => $activeDoc->id,
            'version_number' => 1,
            'author_id' => $this->admin->id,
            'author_name' => $this->admin->name,
            'content' => '<p>Konten</p>',
            'status' => 'active',
        ]);

        $docs = Document::query()->unitKerja($picKlinik)->get();
        $this->assertTrue($docs->contains($activeDoc));
    }
}
