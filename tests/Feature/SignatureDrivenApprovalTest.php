<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentApprovalStep;
use App\Models\DocumentType;
use App\Models\SignatureRequest;
use App\Models\UnitKerja;
use App\Models\User;
use App\Notifications\DirectorDocumentTembusanNotification;
use App\Services\ApprovalRoutingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SignatureDrivenApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $pusatBranch;
    protected UnitKerja $hrUnitKerja;
    protected DocumentType $sopType;
    protected User $staffUser;
    protected User $kadivUser;
    protected User $directorUser;
    protected ApprovalRoutingService $routingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->routingService = app(ApprovalRoutingService::class);

        $this->company = Company::create(['name' => 'PT CITRA MEDIKA HOSPITALITA', 'code' => 'CMH']);
        $this->pusatBranch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'KANTOR PUSAT',
            'code' => 'PST',
            'is_pusat' => true,
        ]);

        $this->hrUnitKerja = UnitKerja::create(['nama_unit_kerja' => 'Human Resources', 'kode_unit_kerja' => '03']);

        $this->sopType = DocumentType::create([
            'code' => 'SOP',
            'name' => 'Standar Operasional Prosedur',
            'category' => 'akreditasi',
        ]);

        $this->directorUser = User::factory()->create([
            'name' => 'Budi Direktur',
            'email' => 'direktur@cmh.co.id',
            'system_role' => 'direktur',
            'is_active' => true,
        ]);
        $this->directorUser->companies()->sync([$this->company->id]);
        $this->directorUser->branches()->sync([$this->pusatBranch->id]);

        $this->kadivUser = User::factory()->create([
            'name' => 'Siti Kadiv HR',
            'email' => 'kadiv.hr@cmh.co.id',
            'system_role' => 'head',
            'unit_kerja_id' => $this->hrUnitKerja->id,
            'is_active' => true,
        ]);
        $this->kadivUser->companies()->sync([$this->company->id]);
        $this->kadivUser->branches()->sync([$this->pusatBranch->id]);

        $this->staffUser = User::factory()->create([
            'name' => 'Agus Staff HR',
            'email' => 'staff.hr@cmh.co.id',
            'system_role' => 'user',
            'unit_kerja_id' => $this->hrUnitKerja->id,
            'is_active' => true,
        ]);
        $this->staffUser->companies()->sync([$this->company->id]);
        $this->staffUser->branches()->sync([$this->pusatBranch->id]);
    }

    public function test_scenario_1_document_with_staff_and_kadiv_signatures_releases_and_sends_director_tembusan(): void
    {
        Notification::fake();

        // 1. Staff creates document
        $document = Document::create([
            'document_number' => '001/HR/SOP/2026',
            'title' => 'SOP Rekrutmen Pegawai',
            'company_id' => $this->company->id,
            'branch_id' => $this->pusatBranch->id,
            'unit_kerja_id' => $this->hrUnitKerja->id,
            'document_type_id' => $this->sopType->id,
            'owner_id' => $this->staffUser->id,
            'visibility' => 'unit_kerja',
        ]);

        $version = $document->versions()->create([
            'version_number' => 1,
            'content' => '<p>Konten SOP [ttd:agus] [ttd:siti]</p>',
            'author_id' => $this->staffUser->id,
            'author_name' => $this->staffUser->name,
            'status' => 'pending',
        ]);

        // Place 2 SignatureRequests: Agus (Staff) and Siti (Kadiv)
        $sigStaff = SignatureRequest::create([
            'requester_id' => $this->staffUser->id,
            'target_user_id' => $this->staffUser->id,
            'document_id' => $document->id,
            'status' => 'pending',
        ]);

        $sigKadiv = SignatureRequest::create([
            'requester_id' => $this->staffUser->id,
            'target_user_id' => $this->kadivUser->id,
            'document_id' => $document->id,
            'status' => 'pending',
        ]);

        // Compile workflow
        $this->routingService->compileWorkflowFromSignatures($document, $version, $this->staffUser);

        // Steps created: Step 1 (Kadiv - Origin Unit Head - Pending), Step 2 (Staff - Bypassed because Agus is author)
        $steps = DocumentApprovalStep::where('version_id', $version->id)->orderBy('step_order')->get();
        $this->assertCount(2, $steps);
        $this->assertEquals('pending', $steps[0]->status);
        $this->assertEquals($this->kadivUser->id, $steps[0]->assigned_user_id);
        $this->assertEquals('bypassed', $steps[1]->status);
        $this->assertEquals($this->staffUser->id, $steps[1]->assigned_user_id);

        // 2. Kadiv approves
        $this->actingAs($this->kadivUser);
        $response = $this->post(route('approvals.approve', [$document, $version]), [
            'notes' => 'Disahkan oleh Kadiv',
        ]);
        $response->assertSessionHas('success');

        $steps[0]->refresh();
        $version->refresh();
        $document->refresh();

        $this->assertEquals('approved', $steps[0]->status);
        $this->assertEquals('active', $version->status);
        $this->assertEquals($version->id, $document->current_version_id);

        // Director receives tembusan (Only To Know) notification automatically
        $this->assertTrue($document->isDirectorNotified());
        Notification::assertSentTo($this->directorUser, DirectorDocumentTembusanNotification::class);
    }

    public function test_scenario_2_document_with_director_signature_requires_director_final_approval(): void
    {
        Notification::fake();

        // 1. Document with Kadiv and Director signatures
        $document = Document::create([
            'document_number' => '002/HR/SK/2026',
            'title' => 'SK Direksi Pengangkatan Pegawai',
            'company_id' => $this->company->id,
            'branch_id' => $this->pusatBranch->id,
            'unit_kerja_id' => $this->hrUnitKerja->id,
            'document_type_id' => $this->sopType->id,
            'owner_id' => $this->staffUser->id,
            'visibility' => 'unit_kerja',
        ]);

        $version = $document->versions()->create([
            'version_number' => 1,
            'content' => '<p>Konten SK [ttd:siti] [ttd:budi]</p>',
            'author_id' => $this->staffUser->id,
            'author_name' => $this->staffUser->name,
            'status' => 'pending',
        ]);

        SignatureRequest::create([
            'requester_id' => $this->staffUser->id,
            'target_user_id' => $this->kadivUser->id,
            'document_id' => $document->id,
            'status' => 'pending',
        ]);

        SignatureRequest::create([
            'requester_id' => $this->staffUser->id,
            'target_user_id' => $this->directorUser->id,
            'document_id' => $document->id,
            'status' => 'pending',
        ]);

        $this->routingService->compileWorkflowFromSignatures($document, $version, $this->staffUser);

        // Assert 2 steps created: Step 1 Kadiv (pending), Step 2 Director (waiting)
        $steps = DocumentApprovalStep::where('version_id', $version->id)->orderBy('step_order')->get();
        $this->assertCount(2, $steps);
        $this->assertEquals('pending', $steps[0]->status);
        $this->assertEquals('waiting', $steps[1]->status);
        $this->assertEquals($this->directorUser->id, $steps[1]->assigned_user_id);

        // 2. Kadiv approves Step 1
        $this->actingAs($this->kadivUser);
        $this->post(route('approvals.approve', [$document, $version]));

        $steps[0]->refresh();
        $steps[1]->refresh();
        $version->refresh();

        $this->assertEquals('approved', $steps[0]->status);
        $this->assertEquals('pending', $steps[1]->status);
        $this->assertEquals('pending', $version->status); // Still pending until Director approves!

        // 3. Director approves Step 2
        $this->actingAs($this->directorUser);
        $this->post(route('approvals.approve', [$document, $version]), [
            'notes' => 'Disahkan oleh Direktur PT',
        ]);

        $steps[1]->refresh();
        $version->refresh();
        $document->refresh();

        $this->assertEquals('approved', $steps[1]->status);
        $this->assertEquals('active', $version->status);
        $this->assertEquals($version->id, $document->current_version_id);

        // Since Director approved directly as blocking step, duplicate tembusan notification is NOT sent
        Notification::assertNotSentTo($this->directorUser, DirectorDocumentTembusanNotification::class);
    }

    public function test_scenario_3_fallback_when_no_signatures_placed(): void
    {
        Notification::fake();

        $document = Document::create([
            'document_number' => '003/HR/SOP/2026',
            'title' => 'SOP Tanpa Kotak TTD',
            'company_id' => $this->company->id,
            'branch_id' => $this->pusatBranch->id,
            'unit_kerja_id' => $this->hrUnitKerja->id,
            'document_type_id' => $this->sopType->id,
            'owner_id' => $this->staffUser->id,
            'visibility' => 'unit_kerja',
        ]);

        $version = $document->versions()->create([
            'version_number' => 1,
            'content' => '<p>Konten SOP Polos</p>',
            'author_id' => $this->staffUser->id,
            'author_name' => $this->staffUser->name,
            'status' => 'pending',
        ]);

        // No SignatureRequests added
        $this->routingService->compileWorkflowFromSignatures($document, $version, $this->staffUser);

        // 1 step created (PIC Unit / Head)
        $steps = DocumentApprovalStep::where('version_id', $version->id)->get();
        $this->assertCount(1, $steps);
        $this->assertEquals('pic_unit_acknowledge', $steps[0]->step_type);
        $this->assertEquals('pending', $steps[0]->status);
        $this->assertEquals($this->kadivUser->id, $steps[0]->assigned_user_id);

        // Kadiv approves -> releases & notifies Director as tembusan
        $this->actingAs($this->kadivUser);
        $this->post(route('approvals.approve', [$document, $version]));

        $version->refresh();
        $document->refresh();

        $this->assertEquals('active', $version->status);
        $this->assertTrue($document->isDirectorNotified());
        Notification::assertSentTo($this->directorUser, DirectorDocumentTembusanNotification::class);
    }

    public function test_scenario_4_director_acknowledges_read_receipt(): void
    {
        $document = Document::create([
            'document_number' => '004/HR/SOP/2026',
            'title' => 'SOP Tembusan Direktur',
            'company_id' => $this->company->id,
            'branch_id' => $this->pusatBranch->id,
            'unit_kerja_id' => $this->hrUnitKerja->id,
            'document_type_id' => $this->sopType->id,
            'owner_id' => $this->staffUser->id,
            'visibility' => 'unit_kerja',
            'director_notified_at' => now(),
        ]);

        $this->assertFalse($document->isDirectorRead());

        // Director marks document as read / acknowledged
        $this->actingAs($this->directorUser);
        $response = $this->post(route('director.documents.acknowledge', $document));
        $response->assertSessionHas('success');

        $document->refresh();
        $this->assertTrue($document->isDirectorRead());
        $this->assertEquals($this->directorUser->id, $document->director_acknowledged_by_id);
    }

    public function test_scenario_5_rejection_at_step_marks_signature_request_rejected(): void
    {
        $document = Document::create([
            'document_number' => '005/HR/SOP/2026',
            'title' => 'SOP Ditolak Kadiv',
            'company_id' => $this->company->id,
            'branch_id' => $this->pusatBranch->id,
            'unit_kerja_id' => $this->hrUnitKerja->id,
            'document_type_id' => $this->sopType->id,
            'owner_id' => $this->staffUser->id,
            'visibility' => 'unit_kerja',
        ]);

        $version = $document->versions()->create([
            'version_number' => 1,
            'content' => '<p>Konten Ditolak</p>',
            'author_id' => $this->staffUser->id,
            'author_name' => $this->staffUser->name,
            'status' => 'pending',
        ]);

        $sigKadiv = SignatureRequest::create([
            'requester_id' => $this->staffUser->id,
            'target_user_id' => $this->kadivUser->id,
            'document_id' => $document->id,
            'status' => 'pending',
        ]);

        $this->routingService->compileWorkflowFromSignatures($document, $version, $this->staffUser);

        // Kadiv rejects
        $this->actingAs($this->kadivUser);
        $response = $this->post(route('approvals.reject', [$document, $version]), [
            'reason' => 'Perlu perbaikan format',
        ]);
        $response->assertSessionHas('success');

        $sigKadiv->refresh();
        $version->refresh();

        $this->assertEquals('rejected', $sigKadiv->status);
        $this->assertEquals('Perlu perbaikan format', $sigKadiv->rejected_reason);
        $this->assertEquals('rejected', $version->status);
    }

    public function test_scenario_6_three_tier_approval_with_unit_head_manajemen_mutu_and_pic_klinik(): void
    {
        $k3UnitKerja = UnitKerja::create(['nama_unit_kerja' => 'Tim K3', 'kode_unit_kerja' => '04']);
        $mutuUnitKerja = UnitKerja::create(['nama_unit_kerja' => 'Tim Manajemen Mutu', 'kode_unit_kerja' => '01']);

        $headK3 = User::factory()->create([
            'name' => 'Fajri',
            'email' => 'fajri@dhu.com',
            'system_role' => 'head',
            'unit_kerja_id' => $k3UnitKerja->id,
            'is_active' => true,
        ]);
        $headK3->companies()->sync([$this->company->id]);
        $headK3->branches()->sync([$this->pusatBranch->id]);
        $headK3->unitKerjas()->sync([$k3UnitKerja->id]);

        $headMutu = User::factory()->create([
            'name' => 'ManajemenMutu',
            'email' => 'mutu@dhu.com',
            'system_role' => 'head',
            'unit_kerja_id' => $mutuUnitKerja->id,
            'is_active' => true,
        ]);
        $headMutu->companies()->sync([$this->company->id]);
        $headMutu->branches()->sync([$this->pusatBranch->id]);
        $headMutu->unitKerjas()->sync([$mutuUnitKerja->id]);

        $picKlinik = User::factory()->create([
            'name' => 'PJDhu',
            'email' => 'pjdhu@dhu.com',
            'system_role' => 'pic_klinik',
            'is_active' => true,
        ]);
        $picKlinik->companies()->sync([$this->company->id]);
        $picKlinik->branches()->sync([$this->pusatBranch->id]);

        $document = Document::create([
            'document_number' => '006/K3/SOP/2026',
            'title' => 'SOP Keselamatan Kerja',
            'company_id' => $this->company->id,
            'branch_id' => $this->pusatBranch->id,
            'unit_kerja_id' => $k3UnitKerja->id,
            'document_type_id' => $this->sopType->id,
            'owner_id' => $this->staffUser->id,
            'visibility' => 'unit_kerja',
        ]);

        $version = $document->versions()->create([
            'version_number' => 1,
            'content' => '<p>Konten SOP [ttd:Fajri] [ttd:ManajemenMutu] [ttd:PJDhu]</p>',
            'author_id' => $this->staffUser->id,
            'author_name' => $this->staffUser->name,
            'status' => 'pending',
        ]);

        // Create signature requests in arbitrary order
        SignatureRequest::create([
            'requester_id' => $this->staffUser->id,
            'target_user_id' => $picKlinik->id,
            'document_id' => $document->id,
            'status' => 'pending',
        ]);
        SignatureRequest::create([
            'requester_id' => $this->staffUser->id,
            'target_user_id' => $headMutu->id,
            'document_id' => $document->id,
            'status' => 'pending',
        ]);
        SignatureRequest::create([
            'requester_id' => $this->staffUser->id,
            'target_user_id' => $headK3->id,
            'document_id' => $document->id,
            'status' => 'pending',
        ]);

        $res = $this->routingService->compileWorkflowFromSignatures($document, $version, $this->staffUser);
        $steps = DocumentApprovalStep::where('version_id', $version->id)->orderBy('step_order')->get();

        $this->assertCount(3, $steps);

        // Step 1: Head of Tim K3 (Fajri) - Active
        $this->assertEquals(1, $steps[0]->step_order);
        $this->assertEquals($headK3->id, $steps[0]->assigned_user_id);
        $this->assertStringContainsString('Tim K3', $steps[0]->step_name);
        $this->assertStringContainsString('Fajri', $steps[0]->step_name);
        $this->assertEquals('pending', $steps[0]->status);

        // Step 2: Head of Tim Manajemen Mutu (ManajemenMutu) - Waiting
        $this->assertEquals(2, $steps[1]->step_order);
        $this->assertEquals($headMutu->id, $steps[1]->assigned_user_id);
        $this->assertStringContainsString('Tim Manajemen Mutu', $steps[1]->step_name);
        $this->assertStringContainsString('ManajemenMutu', $steps[1]->step_name);
        $this->assertEquals('waiting', $steps[1]->status);

        // Step 3: PIC Klinik / Kepala Cabang (PJDhu) - Waiting
        $this->assertEquals(3, $steps[2]->step_order);
        $this->assertEquals($picKlinik->id, $steps[2]->assigned_user_id);
        $this->assertStringContainsString('PJDhu', $steps[2]->step_name);
        $this->assertEquals('waiting', $steps[2]->status);

        // Security / Gating Check: ManajemenMutu and PJDhu CANNOT approve while Step 1 is active
        $this->assertTrue($headK3->can('approve', $document));
        $this->assertFalse($headMutu->can('approve', $document));
        $this->assertFalse($picKlinik->can('approve', $document));

        // Step 1: Fajri approves
        $this->actingAs($headK3);
        $response = $this->post(route('approvals.approve', [$document, $version]));
        $response->assertSessionHas('success');

        $steps = DocumentApprovalStep::where('version_id', $version->id)->orderBy('step_order')->get();
        $this->assertEquals('approved', $steps[0]->status);
        $this->assertEquals('pending', $steps[1]->status); // Step 2 (ManajemenMutu) is now active!
        $this->assertEquals('waiting', $steps[2]->status); // Step 3 (PJDhu) is still waiting

        // Security / Gating Check: Fajri is done, PJDhu CANNOT approve yet, ONLY ManajemenMutu can approve
        $this->assertFalse($headK3->can('approve', $document));
        $this->assertTrue($headMutu->can('approve', $document));
        $this->assertFalse($picKlinik->can('approve', $document));

        // Step 2: ManajemenMutu approves
        $this->actingAs($headMutu);
        $response = $this->post(route('approvals.approve', [$document, $version]));
        $response->assertSessionHas('success');

        $steps = DocumentApprovalStep::where('version_id', $version->id)->orderBy('step_order')->get();
        $this->assertEquals('approved', $steps[0]->status);
        $this->assertEquals('approved', $steps[1]->status);
        $this->assertEquals('pending', $steps[2]->status); // Step 3 (PJDhu) is now active!

        // Security / Gating Check: ONLY PJDhu can approve Step 3
        $this->assertFalse($headK3->can('approve', $document));
        $this->assertFalse($headMutu->can('approve', $document));
        $this->assertTrue($picKlinik->can('approve', $document));

        // Step 3: PJDhu approves
        $this->actingAs($picKlinik);
        $response = $this->post(route('approvals.approve', [$document, $version]));
        $response->assertSessionHas('success');

        $steps = DocumentApprovalStep::where('version_id', $version->id)->orderBy('step_order')->get();
        $this->assertEquals('approved', $steps[0]->status);
        $this->assertEquals('approved', $steps[1]->status);
        $this->assertEquals('approved', $steps[2]->status);

        $version->refresh();
        $this->assertEquals('active', $version->status);
    }

    public function test_scenario_7_origin_unit_head_is_automatically_required_even_without_signature_tag(): void
    {
        $k3UnitKerja = UnitKerja::create(['nama_unit_kerja' => 'Tim K3', 'kode_unit_kerja' => '04']);
        $mutuUnitKerja = UnitKerja::create(['nama_unit_kerja' => 'Tim Manajemen Mutu', 'kode_unit_kerja' => '01']);

        // Head of Tim K3 (Fajri)
        $headK3 = User::factory()->create([
            'name' => 'Fajri',
            'email' => 'fajri.head@dhu.com',
            'system_role' => 'head',
            'unit_kerja_id' => $k3UnitKerja->id,
            'is_active' => true,
        ]);
        $headK3->companies()->sync([$this->company->id]);
        $headK3->branches()->sync([$this->pusatBranch->id]);
        $headK3->unitKerjas()->sync([$k3UnitKerja->id]);

        // Head of Manajemen Mutu
        $headMutu = User::factory()->create([
            'name' => 'ManajemenMutu',
            'email' => 'mutu.head@dhu.com',
            'system_role' => 'head',
            'unit_kerja_id' => $mutuUnitKerja->id,
            'is_active' => true,
        ]);
        $headMutu->companies()->sync([$this->company->id]);
        $headMutu->branches()->sync([$this->pusatBranch->id]);
        $headMutu->unitKerjas()->sync([$mutuUnitKerja->id]);

        // PIC Klinik / Kepala Cabang
        $picKlinik = User::factory()->create([
            'name' => 'PJDhu',
            'email' => 'pjdhu.head@dhu.com',
            'system_role' => 'pic_klinik',
            'is_active' => true,
        ]);
        $picKlinik->companies()->sync([$this->company->id]);
        $picKlinik->branches()->sync([$this->pusatBranch->id]);

        // Staff creates document for Tim K3
        $staffRehan = User::factory()->create([
            'name' => 'RehanDHU',
            'email' => 'rehan@dhu.com',
            'system_role' => 'user',
            'unit_kerja_id' => $k3UnitKerja->id,
            'is_active' => true,
        ]);
        $staffRehan->companies()->sync([$this->company->id]);
        $staffRehan->branches()->sync([$this->pusatBranch->id]);
        $staffRehan->unitKerjas()->sync([$k3UnitKerja->id]);

        $document = Document::create([
            'document_number' => '007/K3/SOP/2026',
            'title' => 'SOP Kebersihan dan K3',
            'company_id' => $this->company->id,
            'branch_id' => $this->pusatBranch->id,
            'unit_kerja_id' => $k3UnitKerja->id,
            'document_type_id' => $this->sopType->id,
            'owner_id' => $staffRehan->id,
            'visibility' => 'unit_kerja',
        ]);

        $version = $document->versions()->create([
            'version_number' => 1,
            'content' => '<p>Konten SOP hanya ada TTD Mutu dan TTD PJClinic: [ttd:ManajemenMutu] [ttd:PJDhu]</p>',
            'author_id' => $staffRehan->id,
            'author_name' => $staffRehan->name,
            'status' => 'pending',
        ]);

        // ONLY 2 signature requests created (NO signature tag for Fajri)
        SignatureRequest::create([
            'requester_id' => $staffRehan->id,
            'target_user_id' => $picKlinik->id,
            'document_id' => $document->id,
            'status' => 'pending',
        ]);
        SignatureRequest::create([
            'requester_id' => $staffRehan->id,
            'target_user_id' => $headMutu->id,
            'document_id' => $document->id,
            'status' => 'pending',
        ]);

        // Compile workflow
        $res = $this->routingService->compileWorkflowFromSignatures($document, $version, $staffRehan);
        $steps = DocumentApprovalStep::where('version_id', $version->id)->orderBy('step_order')->get();

        // 3 steps must be created!
        $this->assertCount(3, $steps);

        // Tahap 1: Fajri (Head of Tim K3) review step is automatically created without signature_request_id!
        $this->assertEquals(1, $steps[0]->step_order);
        $this->assertEquals($headK3->id, $steps[0]->assigned_user_id);
        $this->assertNull($steps[0]->signature_request_id);
        $this->assertStringContainsString('Tim K3', $steps[0]->step_name);
        $this->assertStringContainsString('Fajri', $steps[0]->step_name);
        $this->assertEquals('pending', $steps[0]->status); // Active

        // Tahap 2: ManajemenMutu (Head of Manajemen Mutu)
        $this->assertEquals(2, $steps[1]->step_order);
        $this->assertEquals($headMutu->id, $steps[1]->assigned_user_id);
        $this->assertNotNull($steps[1]->signature_request_id);
        $this->assertEquals('waiting', $steps[1]->status);

        // Tahap 3: PJDhu (PIC Klinik / Kepala Cabang)
        $this->assertEquals(3, $steps[2]->step_order);
        $this->assertEquals($picKlinik->id, $steps[2]->assigned_user_id);
        $this->assertNotNull($steps[2]->signature_request_id);
        $this->assertEquals('waiting', $steps[2]->status);

        // Check gating: Fajri must approve first
        $this->assertTrue($headK3->can('approve', $document));
        $this->assertFalse($headMutu->can('approve', $document));
        $this->assertFalse($picKlinik->can('approve', $document));

        // Fajri approves
        $this->actingAs($headK3);
        $this->post(route('approvals.approve', [$document, $version]))->assertSessionHas('success');

        // Now step 2 is active for ManajemenMutu
        $this->assertTrue($headMutu->can('approve', $document));
        $this->assertFalse($picKlinik->can('approve', $document));

        // ManajemenMutu approves
        $this->actingAs($headMutu);
        $this->post(route('approvals.approve', [$document, $version]))->assertSessionHas('success');

        // Now step 3 is active for PJDhu
        $this->assertTrue($picKlinik->can('approve', $document));

        // PJDhu approves
        $this->actingAs($picKlinik);
        $this->post(route('approvals.approve', [$document, $version]))->assertSessionHas('success');

        $version->refresh();
        $this->assertEquals('active', $version->status);
    }

    public function test_sidebar_navigation_unifies_approval_and_signature_requests(): void
    {
        // Give kadivUser 1 signature request and 1 pending document version
        SignatureRequest::create([
            'requester_id' => $this->staffUser->id,
            'target_user_id' => $this->kadivUser->id,
            'status' => 'pending',
            'is_used' => false,
            'requested_at' => now(),
            'notified_at' => now(),
        ]);

        $doc = Document::create([
            'document_number' => 'DOC-TEST-UNIFY-01',
            'title' => 'Unified Approval Doc',
            'document_type_id' => $this->sopType->id,
            'owner_id' => $this->staffUser->id,
            'unit_kerja_id' => $this->hrUnitKerja->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->pusatBranch->id,
            'visibility' => Document::VISIBILITY_UNIT_KERJA,
            'approver_role' => 'head',
        ]);
        $doc->versions()->create([
            'version_number' => 1,
            'author_id' => $this->staffUser->id,
            'author_name' => $this->staffUser->name,
            'status' => 'pending',
            'content' => '<p>Pending Doc</p>',
        ]);

        $response = $this->actingAs($this->kadivUser)->get(route('dashboard'));
        $response->assertOk();

        // 1. Sidebar contains Document Approval (Version) and Rollback Approval, but NOT Signature Approvals menu
        $response->assertSee(route('approvals.versions'));
        $response->assertDontSee(route('signatures.requests.index'));
        $response->assertSee(route('approvals.rollbacks'));

        // 2. Total approval count is 1 (1 pending version, signature requests are unified)
        $response->assertSee('1');

        // 3. Test approvals/signatures route alias
        $sigResponse = $this->actingAs($this->kadivUser)->get(route('approvals.signatures'));
        $sigResponse->assertOk();
        $sigResponse->assertViewIs('signature_requests.index');

        // 4. Test approvals?tab=signatures redirect
        $tabResponse = $this->actingAs($this->kadivUser)->get('/approvals?tab=signatures');
        $tabResponse->assertRedirect(route('signatures.requests.index'));
    }

    public function test_review_only_step_can_optionally_insert_signature_when_approved(): void
    {
        $k3UnitKerja = UnitKerja::create(['nama_unit_kerja' => 'Tim K3', 'kode_unit_kerja' => '04']);
        $mutuUnitKerja = UnitKerja::create(['nama_unit_kerja' => 'Tim Manajemen Mutu', 'kode_unit_kerja' => '01']);

        // Head of Tim K3 (Fajri)
        $headK3 = User::factory()->create([
            'name' => 'Fajri Reviewer',
            'email' => 'fajri.reviewer@dhu.com',
            'system_role' => 'head',
            'unit_kerja_id' => $k3UnitKerja->id,
            'is_active' => true,
        ]);
        $headK3->companies()->sync([$this->company->id]);
        $headK3->branches()->sync([$this->pusatBranch->id]);
        $headK3->unitKerjas()->sync([$k3UnitKerja->id]);

        // Fajri has a registered signature
        $fajriSig = $headK3->signatures()->create([
            'type' => 'original',
            'file_path' => 'signatures/fajri_sig.png',
            'created_via' => 'upload',
        ]);

        // Staff creates document without [ttd:Fajri] tag
        $document = Document::create([
            'document_number' => '008/K3/SOP/2026',
            'title' => 'SOP Review Only with Optional Signature',
            'company_id' => $this->company->id,
            'branch_id' => $this->pusatBranch->id,
            'unit_kerja_id' => $k3UnitKerja->id,
            'document_type_id' => $this->sopType->id,
            'owner_id' => $this->staffUser->id,
            'visibility' => 'unit_kerja',
        ]);

        $version = $document->versions()->create([
            'version_number' => 1,
            'content' => '<p>Konten SOP Polos</p>',
            'author_id' => $this->staffUser->id,
            'author_name' => $this->staffUser->name,
            'status' => 'pending',
        ]);

        $this->routingService->compileWorkflowFromSignatures($document, $version, $this->staffUser);

        $step = DocumentApprovalStep::where('version_id', $version->id)->first();
        $this->assertNotNull($step);
        $this->assertEquals($headK3->id, $step->assigned_user_id);
        $this->assertNull($step->signature_request_id); // Initially no signature request

        // Fajri approves WITH include_signature = 1
        $this->actingAs($headK3);
        $response = $this->post(route('approvals.approve', [$document, $version]), [
            'notes' => 'Review disetujui dan TTD dilampirkan',
            'include_signature' => '1',
            'signature_id' => $fajriSig->id,
        ]);
        $response->assertSessionHas('success');

        $step->refresh();
        $this->assertEquals('approved', $step->status);
        $this->assertNotNull($step->signature_request_id);

        // Verify that SignatureRequest was dynamically created and approved
        $createdSigReq = SignatureRequest::find($step->signature_request_id);
        $this->assertNotNull($createdSigReq);
        $this->assertEquals($headK3->id, $createdSigReq->target_user_id);
        $this->assertEquals('approved', $createdSigReq->status);
        $this->assertEquals($fajriSig->id, $createdSigReq->requested_signature_id);
    }

    public function test_review_only_step_approves_without_signature_when_unchecked(): void
    {
        $k3UnitKerja = UnitKerja::create(['nama_unit_kerja' => 'Tim K3', 'kode_unit_kerja' => '04']);

        $headK3 = User::factory()->create([
            'name' => 'Fajri Reviewer 2',
            'email' => 'fajri.reviewer2@dhu.com',
            'system_role' => 'head',
            'unit_kerja_id' => $k3UnitKerja->id,
            'is_active' => true,
        ]);
        $headK3->companies()->sync([$this->company->id]);
        $headK3->branches()->sync([$this->pusatBranch->id]);
        $headK3->unitKerjas()->sync([$k3UnitKerja->id]);

        $document = Document::create([
            'document_number' => '009/K3/SOP/2026',
            'title' => 'SOP Review Only without Signature',
            'company_id' => $this->company->id,
            'branch_id' => $this->pusatBranch->id,
            'unit_kerja_id' => $k3UnitKerja->id,
            'document_type_id' => $this->sopType->id,
            'owner_id' => $this->staffUser->id,
            'visibility' => 'unit_kerja',
        ]);

        $version = $document->versions()->create([
            'version_number' => 1,
            'content' => '<p>Konten SOP Polos 2</p>',
            'author_id' => $this->staffUser->id,
            'author_name' => $this->staffUser->name,
            'status' => 'pending',
        ]);

        $this->routingService->compileWorkflowFromSignatures($document, $version, $this->staffUser);

        $step = DocumentApprovalStep::where('version_id', $version->id)->first();
        $this->assertNotNull($step);
        $this->assertNull($step->signature_request_id);

        // Fajri approves WITHOUT include_signature
        $this->actingAs($headK3);
        $response = $this->post(route('approvals.approve', [$document, $version]), [
            'notes' => 'Review disetujui tanpa TTD',
        ]);
        $response->assertSessionHas('success');

        $step->refresh();
        $this->assertEquals('approved', $step->status);
        $this->assertNull($step->signature_request_id);
        $this->assertEmpty(SignatureRequest::where('document_id', $document->id)->get());
    }

    public function test_review_only_step_can_draw_signature_directly_on_modal_when_approved(): void
    {
        $k3UnitKerja = UnitKerja::create(['nama_unit_kerja' => 'Tim K3', 'kode_unit_kerja' => '04']);

        $headK3 = User::factory()->create([
            'name' => 'Fajri Canvas Reviewer',
            'email' => 'fajri.canvas@dhu.com',
            'system_role' => 'head',
            'unit_kerja_id' => $k3UnitKerja->id,
            'is_active' => true,
        ]);
        $headK3->companies()->sync([$this->company->id]);
        $headK3->branches()->sync([$this->pusatBranch->id]);
        $headK3->unitKerjas()->sync([$k3UnitKerja->id]);

        $document = Document::create([
            'document_number' => '010/K3/SOP/2026',
            'title' => 'SOP Review with Canvas Signature',
            'company_id' => $this->company->id,
            'branch_id' => $this->pusatBranch->id,
            'unit_kerja_id' => $k3UnitKerja->id,
            'document_type_id' => $this->sopType->id,
            'owner_id' => $this->staffUser->id,
            'visibility' => 'unit_kerja',
        ]);

        $version = $document->versions()->create([
            'version_number' => 1,
            'content' => '<p>Konten Dokumen Review</p>',
            'author_id' => $this->staffUser->id,
            'author_name' => $this->staffUser->name,
            'status' => 'pending',
        ]);

        $this->routingService->compileWorkflowFromSignatures($document, $version, $this->staffUser);

        // Generate a 1x1 transparent PNG data URL
        $pngBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $this->actingAs($headK3);
        $response = $this->post(route('approvals.approve', [$document, $version]), [
            'notes' => 'Disetujui via gores TTD langsung di modal',
            'include_signature' => '1',
            'signature_data' => $pngBase64,
        ]);
        $response->assertSessionHas('success');

        $step = DocumentApprovalStep::where('version_id', $version->id)->first();
        $this->assertEquals('approved', $step->status);
        $this->assertNotNull($step->signature_request_id);

        $createdSigReq = SignatureRequest::find($step->signature_request_id);
        $this->assertNotNull($createdSigReq);
        $this->assertEquals($headK3->id, $createdSigReq->target_user_id);
        $this->assertEquals('approved', $createdSigReq->status);
        $this->assertTrue($headK3->hasSignature('original'));
    }

    public function test_review_only_step_can_upload_signature_directly_on_modal_when_approved(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $k3UnitKerja = UnitKerja::create(['nama_unit_kerja' => 'Tim K3', 'kode_unit_kerja' => '04']);

        $headK3 = User::factory()->create([
            'name' => 'Fajri Upload Reviewer',
            'email' => 'fajri.upload@dhu.com',
            'system_role' => 'head',
            'unit_kerja_id' => $k3UnitKerja->id,
            'is_active' => true,
        ]);
        $headK3->companies()->sync([$this->company->id]);
        $headK3->branches()->sync([$this->pusatBranch->id]);
        $headK3->unitKerjas()->sync([$k3UnitKerja->id]);

        $document = Document::create([
            'document_number' => '011/K3/SOP/2026',
            'title' => 'SOP Review with Uploaded Signature',
            'company_id' => $this->company->id,
            'branch_id' => $this->pusatBranch->id,
            'unit_kerja_id' => $k3UnitKerja->id,
            'document_type_id' => $this->sopType->id,
            'owner_id' => $this->staffUser->id,
            'visibility' => 'unit_kerja',
        ]);

        $version = $document->versions()->create([
            'version_number' => 1,
            'content' => '<p>Konten Dokumen Review Upload</p>',
            'author_id' => $this->staffUser->id,
            'author_name' => $this->staffUser->name,
            'status' => 'pending',
        ]);

        $this->routingService->compileWorkflowFromSignatures($document, $version, $this->staffUser);

        $file = \Illuminate\Http\UploadedFile::fake()->image('signature.png', 100, 50);

        $this->actingAs($headK3);
        $response = $this->post(route('approvals.approve', [$document, $version]), [
            'notes' => 'Disetujui via unggah TTD langsung di modal',
            'include_signature' => '1',
            'signature_image' => $file,
        ]);
        $response->assertSessionHas('success');

        $step = DocumentApprovalStep::where('version_id', $version->id)->first();
        $this->assertEquals('approved', $step->status);
        $this->assertNotNull($step->signature_request_id);

        $createdSigReq = SignatureRequest::find($step->signature_request_id);
        $this->assertNotNull($createdSigReq);
        $this->assertEquals($headK3->id, $createdSigReq->target_user_id);
        $this->assertEquals('approved', $createdSigReq->status);
        $this->assertTrue($headK3->hasSignature('original'));
    }

    public function test_document_with_colleague_signature_requires_head_of_unit_review_first_before_colleague_signs(): void
    {
        $k3UnitKerja = UnitKerja::create(['nama_unit_kerja' => 'Tim K3', 'kode_unit_kerja' => '04']);

        // Head of Tim K3 (Fajri)
        $headK3 = User::factory()->create([
            'name' => 'Fajri SPV K3',
            'email' => 'fajri.spv@dhu.com',
            'system_role' => 'head',
            'unit_kerja_id' => $k3UnitKerja->id,
            'is_active' => true,
        ]);
        $headK3->companies()->sync([$this->company->id]);
        $headK3->branches()->sync([$this->pusatBranch->id]);
        $headK3->unitKerjas()->sync([$k3UnitKerja->id]);

        // Colleague (Staff Budi)
        $colleagueBudi = User::factory()->create([
            'name' => 'Budi Colleague',
            'email' => 'budi.colleague@dhu.com',
            'system_role' => 'staff',
            'unit_kerja_id' => $k3UnitKerja->id,
            'is_active' => true,
        ]);
        $colleagueBudi->companies()->sync([$this->company->id]);
        $colleagueBudi->branches()->sync([$this->pusatBranch->id]);
        $colleagueBudi->unitKerjas()->sync([$k3UnitKerja->id]);

        // Drafter / Author (Staff Agus)
        $document = Document::create([
            'document_number' => '012/K3/SOP/2026',
            'title' => 'SOP Kolaborasi Tim K3',
            'company_id' => $this->company->id,
            'branch_id' => $this->pusatBranch->id,
            'unit_kerja_id' => $k3UnitKerja->id,
            'document_type_id' => $this->sopType->id,
            'owner_id' => $this->staffUser->id,
            'visibility' => 'unit_kerja',
        ]);

        $version = $document->versions()->create([
            'version_number' => 1,
            'content' => '<p>Konten SOP Kolaborasi [ttd:budi]</p>',
            'author_id' => $this->staffUser->id,
            'author_name' => $this->staffUser->name,
            'status' => 'pending',
        ]);

        // Signature Request for Colleague Budi
        $sigBudi = SignatureRequest::create([
            'requester_id' => $this->staffUser->id,
            'target_user_id' => $colleagueBudi->id,
            'document_id' => $document->id,
            'status' => 'pending',
        ]);

        // Compile workflow
        $this->routingService->compileWorkflowFromSignatures($document, $version, $this->staffUser);

        // Steps must be: Step 1 (Fajri - Head SPV - Pending), Step 2 (Budi - Colleague - Waiting)
        $steps = DocumentApprovalStep::where('version_id', $version->id)->orderBy('step_order')->get();
        $this->assertCount(2, $steps);

        $this->assertEquals($headK3->id, $steps[0]->assigned_user_id);
        $this->assertEquals('pending', $steps[0]->status);
        $this->assertEquals('head_approval', $steps[0]->step_type);

        $this->assertEquals($colleagueBudi->id, $steps[1]->assigned_user_id);
        $this->assertEquals('waiting', $steps[1]->status);
        $this->assertEquals('peer_review', $steps[1]->step_type);

        // 1. Fajri (Head SPV) approves Step 1
        $this->actingAs($headK3);
        $response1 = $this->post(route('approvals.approve', [$document, $version]), [
            'notes' => 'Draft SOP diverifikasi dan disetujui oleh SPV K3',
        ]);
        $response1->assertSessionHas('success');

        $steps[0]->refresh();
        $steps[1]->refresh();
        $this->assertEquals('approved', $steps[0]->status);
        $this->assertEquals('pending', $steps[1]->status); // Colleague step now becomes pending!

        // Colleague Budi now sees the Signature menu item (and NOT Rollback Approval)
        $this->assertEquals(1, $colleagueBudi->pendingVersionApprovalsCount());
        $dashboardResponse = $this->actingAs($colleagueBudi)->get(route('dashboard'));
        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee(__('Signature'));
        $dashboardResponse->assertDontSee(__('Rollback Approval'));
        $dashboardResponse->assertSee(route('approvals.versions'));

        $approvalsResponse = $this->actingAs($colleagueBudi)->get(route('approvals.versions'));
        $approvalsResponse->assertOk();
        $approvalsResponse->assertSee('SOP Kolaborasi Tim K3');

        // 2. Colleague Budi approves Step 2
        $this->actingAs($colleagueBudi);
        $response2 = $this->post(route('approvals.approve', [$document, $version]), [
            'notes' => 'TTD dibubuhkan oleh Budi',
        ]);
        $response2->assertSessionHas('success');

        $steps[1]->refresh();
        $version->refresh();
        $document->refresh();

        $this->assertEquals('approved', $steps[1]->status);
        $this->assertEquals('active', $version->status);
        $this->assertEquals($version->id, $document->current_version_id);
    }
}


