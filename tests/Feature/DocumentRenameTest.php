<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Division;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Notifications\DocumentRenameRequested;
use App\Notifications\DocumentRenameResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DocumentRenameTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Branch $branch;
    private Division $division;
    private User $head;
    private User $staff;
    private DocumentType $docType;
    private Document $document;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create(['name' => 'PT Test', 'code' => 'TEST']);
        $this->branch = Branch::create(['company_id' => $this->company->id, 'name' => 'Pusat', 'is_pusat' => true]);
        $this->division = Division::create(['name' => 'IT Department', 'code' => 'IT']);

        $this->head = User::factory()->create([
            'division_id' => $this->division->id,
            'name' => 'Head of IT',
            'system_role' => 'head',
        ]);
        $this->head->companies()->attach($this->company->id);
        $this->head->branches()->attach($this->branch->id);

        $this->staff = User::factory()->create([
            'division_id' => $this->division->id,
            'name' => 'Staff IT',
            'system_role' => 'staff',
        ]);
        $this->staff->companies()->attach($this->company->id);
        $this->staff->branches()->attach($this->branch->id);

        $this->docType = DocumentType::create(['name' => 'Policy Doc', 'code' => 'POL']);

        $this->document = Document::create([
            'document_number' => '001/IT/POL/2026',
            'title' => 'Original Document Title',
            'document_type_id' => $this->docType->id,
            'owner_id' => $this->staff->id,
            'division_id' => $this->division->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'visibility' => 'general',
        ]);

        $version = DocumentVersion::create([
            'document_id' => $this->document->id,
            'version_number' => 1,
            'author_id' => $this->staff->id,
            'author_name' => $this->staff->name,
            'status' => 'active',
            'content' => '<p>Active content</p>',
        ]);

        $this->document->update(['current_version_id' => $version->id]);
    }

    public function test_admin_can_directly_rename_document(): void
    {
        $admin = User::factory()->create(['name' => 'Admin User', 'system_role' => 'admin']);
        $response = $this->actingAs($admin)
            ->post(route('documents.rename', $this->document), [
                'title' => 'Direct Renamed by Admin',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('documents', [
            'id' => $this->document->id,
            'title' => 'Direct Renamed by Admin',
            'pending_title' => null,
        ]);
    }

    public function test_head_of_division_cannot_directly_rename_and_must_request_approval(): void
    {
        $response = $this->actingAs($this->head)
            ->post(route('documents.rename', $this->document), [
                'title' => 'Direct Renamed by Head',
            ]);

        $response->assertStatus(403);
    }

    public function test_staff_cannot_directly_rename_active_document(): void
    {
        $response = $this->actingAs($this->staff)
            ->post(route('documents.rename', $this->document), [
                'title' => 'Unauthorized Rename',
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('documents', [
            'id' => $this->document->id,
            'title' => 'Original Document Title',
        ]);
    }

    public function test_staff_can_request_rename_on_active_document(): void
    {
        Notification::fake();

        $response = $this->actingAs($this->staff)
            ->post(route('documents.request-rename', $this->document), [
                'title' => 'Proposed New Title',
                'notes' => 'Please approve new nomenclature',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('documents', [
            'id' => $this->document->id,
            'title' => 'Original Document Title',
            'pending_title' => 'Proposed New Title',
            'rename_requested_by_id' => $this->staff->id,
            'rename_request_notes' => 'Please approve new nomenclature',
        ]);

        Notification::assertSentTo($this->head, DocumentRenameRequested::class, function ($notification) {
            return $notification->requestedTitle === 'Proposed New Title'
                && $notification->notes === 'Please approve new nomenclature';
        });
    }

    public function test_head_of_division_can_approve_rename_request(): void
    {
        Notification::fake();

        $this->document->update([
            'pending_title' => 'Approved New Title',
            'rename_requested_by_id' => $this->staff->id,
            'rename_requested_at' => now(),
            'rename_request_notes' => 'Needs update',
        ]);

        $response = $this->actingAs($this->head)
            ->post(route('approvals.rename-request.approve', $this->document));

        $response->assertRedirect(route('approvals.index'));

        $this->assertDatabaseHas('documents', [
            'id' => $this->document->id,
            'title' => 'Approved New Title',
            'pending_title' => null,
            'rename_requested_by_id' => null,
            'rename_requested_at' => null,
            'rename_request_notes' => null,
        ]);

        Notification::assertSentTo($this->staff, DocumentRenameResult::class, function ($notification) {
            return $notification->status === 'approved'
                && $notification->newTitle === 'Approved New Title';
        });
    }

    public function test_head_of_division_can_reject_rename_request(): void
    {
        Notification::fake();

        $this->document->update([
            'pending_title' => 'Rejected Title',
            'rename_requested_by_id' => $this->staff->id,
            'rename_requested_at' => now(),
            'rename_request_notes' => 'Some reason',
        ]);

        $response = $this->actingAs($this->head)
            ->post(route('approvals.rename-request.reject', $this->document), [
                'notes' => 'Does not comply with SOP formatting',
            ]);

        $response->assertRedirect(route('approvals.index'));

        $this->assertDatabaseHas('documents', [
            'id' => $this->document->id,
            'title' => 'Original Document Title',
            'pending_title' => null,
            'rename_requested_by_id' => null,
            'rename_requested_at' => null,
            'rename_request_notes' => null,
        ]);

        Notification::assertSentTo($this->staff, DocumentRenameResult::class, function ($notification) {
            return $notification->status === 'rejected'
                && $notification->notes === 'Does not comply with SOP formatting';
        });
    }

    public function test_requester_can_cancel_own_rename_request(): void
    {
        $this->document->update([
            'pending_title' => 'Proposed Title',
            'rename_requested_by_id' => $this->staff->id,
            'rename_requested_at' => now(),
        ]);

        $response = $this->actingAs($this->staff)
            ->post(route('documents.cancel-rename', $this->document));

        $response->assertRedirect();

        $this->assertDatabaseHas('documents', [
            'id' => $this->document->id,
            'pending_title' => null,
            'rename_requested_by_id' => null,
        ]);
    }

    public function test_pending_rename_is_included_in_pending_approvals_count(): void
    {
        $this->assertEquals(0, $this->head->pendingApprovalsCount());

        $this->document->update([
            'pending_title' => 'Proposed Title',
            'rename_requested_by_id' => $this->staff->id,
            'rename_requested_at' => now(),
        ]);

        $this->assertEquals(1, $this->head->fresh()->pendingApprovalsCount());
    }

    public function test_owner_cannot_directly_rename_draft_document_and_must_request_approval(): void
    {
        $draftDoc = Document::create([
            'document_number' => '002/IT/POL/2026',
            'title' => 'Draft Document Title',
            'document_type_id' => $this->docType->id,
            'owner_id' => $this->staff->id,
            'division_id' => $this->division->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'visibility' => 'general',
        ]);

        DocumentVersion::create([
            'document_id' => $draftDoc->id,
            'version_number' => 1,
            'author_id' => $this->staff->id,
            'author_name' => $this->staff->name,
            'status' => 'draft',
            'content' => '<p>Draft content</p>',
        ]);

        // Direct rename is denied
        $response = $this->actingAs($this->staff)
            ->post(route('documents.rename', $draftDoc), [
                'title' => 'Renamed Draft Title',
            ]);
        $response->assertStatus(403);

        // Request rename creates pending title waiting for approval
        $requestResponse = $this->actingAs($this->staff)
            ->post(route('documents.request-rename', $draftDoc), [
                'title' => 'Renamed Draft Title',
                'notes' => 'Changing draft title',
            ]);
        $requestResponse->assertRedirect();
        $this->assertDatabaseHas('documents', [
            'id' => $draftDoc->id,
            'title' => 'Draft Document Title',
            'pending_title' => 'Renamed Draft Title',
            'rename_requested_by_id' => $this->staff->id,
        ]);
    }

    public function test_staff_cannot_approve_own_rename_request(): void
    {
        Notification::fake();

        // Staff owns and requested rename
        $response = $this->actingAs($this->staff)
            ->post(route('approvals.rename-request.approve', $this->document));

        $response->assertStatus(403);
    }

    public function test_head_and_admin_can_approve_own_rename_request(): void
    {
        Notification::fake();

        // Let head own and request rename on this document in their division
        $headDoc = Document::create([
            'document_number' => '003/IT/POL/2026',
            'title' => 'Head Doc Title',
            'document_type_id' => $this->docType->id,
            'owner_id' => $this->head->id,
            'division_id' => $this->division->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'visibility' => 'general',
            'pending_title' => 'Approved Head Doc Title',
            'rename_requested_by_id' => $this->head->id,
            'rename_requested_at' => now(),
        ]);

        // Head CAN approve their own rename request
        $response = $this->actingAs($this->head)
            ->post(route('approvals.rename-request.approve', $headDoc));

        $response->assertRedirect();
        $this->assertDatabaseHas('documents', [
            'id' => $headDoc->id,
            'title' => 'Approved Head Doc Title',
            'pending_title' => null,
        ]);
    }
}
