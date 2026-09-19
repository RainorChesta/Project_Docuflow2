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

        // Steps created: Step 1 (Staff - Bypassed because Agus is author), Step 2 (Kadiv - Pending)
        $steps = DocumentApprovalStep::where('version_id', $version->id)->orderBy('step_order')->get();
        $this->assertCount(2, $steps);
        $this->assertEquals('bypassed', $steps[0]->status);
        $this->assertEquals($this->staffUser->id, $steps[0]->assigned_user_id);
        $this->assertEquals('pending', $steps[1]->status);
        $this->assertEquals($this->kadivUser->id, $steps[1]->assigned_user_id);

        // 2. Kadiv approves
        $this->actingAs($this->kadivUser);
        $response = $this->post(route('approvals.approve', [$document, $version]), [
            'notes' => 'Disahkan oleh Kadiv',
        ]);
        $response->assertSessionHas('success');

        $steps[1]->refresh();
        $version->refresh();
        $document->refresh();

        $this->assertEquals('approved', $steps[1]->status);
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
}
