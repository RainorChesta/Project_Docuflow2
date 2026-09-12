<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Division;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Notifications\DocumentApprovalRequested;
use App\Notifications\DocumentApprovalResult;
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

    public function test_staff_can_directly_rename_active_document_and_creates_next_pending_version(): void
    {
        Notification::fake();

        $response = $this->actingAs($this->staff)
            ->post(route('documents.rename', $this->document), [
                'title' => 'Renamed Document Title',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Document title is updated
        $this->assertDatabaseHas('documents', [
            'id' => $this->document->id,
            'title' => 'Renamed Document Title',
        ]);

        // A new pending version (v2) is created with rename metadata
        $this->assertDatabaseHas('document_versions', [
            'document_id' => $this->document->id,
            'version_number' => 2,
            'status' => 'pending',
            'author_id' => $this->staff->id,
            'change_type' => 'rename',
            'old_title' => 'Original Document Title',
        ]);

        $v2 = DocumentVersion::where('document_id', $this->document->id)
            ->where('version_number', 2)
            ->firstOrFail();

        $this->assertTrue($v2->isRename());
        $this->assertEquals('Original Document Title', $v2->old_title);

        // Rename approval notification is sent to Head of IT with rename info
        Notification::assertSentTo($this->head, DocumentApprovalRequested::class, function ($notification) {
            $data = $notification->toArray($this->head);
            return $notification->document->id === $this->document->id
                && $notification->version->version_number === 2
                && !empty($data['is_rename'])
                && $data['old_title'] === 'Original Document Title'
                && str_contains($data['title'], 'Perubahan Nama')
                && str_contains($data['message'], 'Original Document Title');
        });
    }

    public function test_head_of_division_can_reject_renamed_document_version_and_reverts_title(): void
    {
        Notification::fake();

        // Staff renames active document
        $this->actingAs($this->staff)
            ->post(route('documents.rename', $this->document), [
                'title' => 'Renamed Document Title',
            ]);

        $v2 = DocumentVersion::where('document_id', $this->document->id)
            ->where('version_number', 2)
            ->firstOrFail();

        $this->assertEquals('Renamed Document Title', $this->document->fresh()->title);

        // Head rejects version 2
        $response = $this->actingAs($this->head)
            ->post(route('approvals.reject', [$this->document, $v2]), [
                'notes' => 'Judul tidak sesuai SOP.',
            ]);

        $response->assertRedirect();

        // Version 2 is now rejected
        $v2->refresh();
        $this->assertEquals('rejected', $v2->status);

        // Document title is reverted back to original title
        $this->assertEquals('Original Document Title', $this->document->fresh()->title);

        // Notification is sent to author
        Notification::assertSentTo($this->staff, DocumentApprovalResult::class, function ($notification) use ($v2) {
            return $notification->status === 'rejected'
                && $notification->version->id === $v2->id;
        });
    }

    public function test_author_can_discard_pending_rename_and_reverts_title(): void
    {
        // Staff renames active document
        $this->actingAs($this->staff)
            ->post(route('documents.rename', $this->document), [
                'title' => 'Renamed Document Title',
            ]);

        $this->assertEquals('Renamed Document Title', $this->document->fresh()->title);

        // Staff discards pending rename via HTTP endpoint
        $response = $this->actingAs($this->staff)
            ->post(route('documents.discard', $this->document));

        $response->assertRedirect();

        // Document title is reverted back to original title
        $this->assertEquals('Original Document Title', $this->document->fresh()->title);
    }

    public function test_head_of_division_can_approve_renamed_document_version(): void
    {
        Notification::fake();

        // Staff renames active document
        $this->actingAs($this->staff)
            ->post(route('documents.rename', $this->document), [
                'title' => 'Renamed Document Title',
            ]);

        $v2 = DocumentVersion::where('document_id', $this->document->id)
            ->where('version_number', 2)
            ->firstOrFail();

        // Head approves version 2 via standard version approval
        $response = $this->actingAs($this->head)
            ->post(route('approvals.approve', [$this->document, $v2]));

        $response->assertRedirect();

        // Version 2 is now active
        $v2->refresh();
        $this->assertEquals('active', $v2->status);
        $this->assertEquals($v2->id, $this->document->fresh()->current_version_id);

        // Previous version 1 is inactive and retains its historical title
        $v1 = DocumentVersion::where('document_id', $this->document->id)
            ->where('version_number', 1)
            ->first();
        $this->assertEquals('inactive', $v1->status);
        $this->assertEquals('Original Document Title', $v1->version_title);
        $this->assertEquals('Renamed Document Title', $v2->version_title);
        $this->assertNotEquals($v1->version_title, $v2->version_title);

        // Notification is sent to author
        Notification::assertSentTo($this->staff, DocumentApprovalResult::class, function ($notification) use ($v2) {
            return $notification->status === 'approved'
                && $notification->version->id === $v2->id;
        });
    }

    public function test_admin_can_rename_active_document_and_triggers_versioning(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['name' => 'Admin User', 'system_role' => 'admin']);

        $response = $this->actingAs($admin)
            ->post(route('documents.rename', $this->document), [
                'title' => 'Renamed by Admin',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('documents', [
            'id' => $this->document->id,
            'title' => 'Renamed by Admin',
        ]);

        $this->assertDatabaseHas('document_versions', [
            'document_id' => $this->document->id,
            'version_number' => 2,
            'status' => 'pending',
            'author_id' => $admin->id,
        ]);
    }

    public function test_renaming_draft_document_updates_title_in_place_without_new_version(): void
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

        $draftVersion = DocumentVersion::create([
            'document_id' => $draftDoc->id,
            'version_number' => 1,
            'author_id' => $this->staff->id,
            'author_name' => $this->staff->name,
            'status' => 'draft',
            'content' => '<p>Draft content</p>',
        ]);

        $response = $this->actingAs($this->staff)
            ->post(route('documents.rename', $draftDoc), [
                'title' => 'Updated Draft Title',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('documents', [
            'id' => $draftDoc->id,
            'title' => 'Updated Draft Title',
        ]);

        // Still only 1 version
        $this->assertEquals(1, $draftDoc->versions()->count());
        $this->assertEquals('draft', $draftVersion->fresh()->status);
    }

    public function test_renaming_pending_document_updates_title_in_place(): void
    {
        // Document already has pending v2
        $pendingVersion = DocumentVersion::create([
            'document_id' => $this->document->id,
            'version_number' => 2,
            'author_id' => $this->staff->id,
            'author_name' => $this->staff->name,
            'status' => 'pending',
            'content' => '<p>Pending content v2</p>',
        ]);

        $response = $this->actingAs($this->staff)
            ->post(route('documents.rename', $this->document), [
                'title' => 'Renamed Pending Document',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('documents', [
            'id' => $this->document->id,
            'title' => 'Renamed Pending Document',
        ]);

        // Total versions is still 2 (v1 active, v2 pending updated)
        $this->assertEquals(2, $this->document->versions()->count());
    }

    public function test_repeated_renaming_of_pending_document_automatically_updates_reviewer_notifications(): void
    {
        // 1. Staff renames active document first time (without Notification::fake so DB row is saved)
        $this->actingAs($this->staff)
            ->post(route('documents.rename', $this->document), [
                'title' => 'First Rename Title',
            ]);

        $this->assertEquals('First Rename Title', $this->document->fresh()->title);

        // Verify head received notification in DB
        $headNotifs = $this->head->notifications()->get();
        $this->assertCount(1, $headNotifs);
        $this->assertStringContainsString('First Rename Title', $headNotifs->first()->data['message']);

        // 2. Staff renames AGAIN to a second title while v2 is still pending
        $this->actingAs($this->staff)
            ->post(route('documents.rename', $this->document), [
                'title' => 'Second Rename Title JJJJ',
            ]);

        $this->assertEquals('Second Rename Title JJJJ', $this->document->fresh()->title);

        // 3. Verify notification in DB is automatically updated to the new title
        $headNotifFresh = $this->head->notifications()->first();
        $this->assertStringContainsString('Second Rename Title JJJJ', $headNotifFresh->data['message']);
        $this->assertEquals('Second Rename Title JJJJ', $headNotifFresh->data['document_title']);
        $this->assertEquals('Original Document Title', $headNotifFresh->data['old_title']);

        // 4. Verify /notifications API endpoint also dynamically returns the new title
        $apiResponse = $this->actingAs($this->head)
            ->getJson(route('notifications.index'));

        $apiResponse->assertOk();
        $apiNotifs = $apiResponse->json('notifications');
        $this->assertCount(1, $apiNotifs);
        $this->assertStringContainsString('Second Rename Title JJJJ', $apiNotifs[0]['message']);
        $this->assertEquals('Second Rename Title JJJJ', $apiNotifs[0]['document_title']);
    }

    public function test_rollback_to_previous_version_restores_original_document_title(): void
    {
        // 1. Staff renames document to v2
        $this->actingAs($this->staff)
            ->post(route('documents.rename', $this->document), [
                'title' => 'Renamed Title v2',
            ]);

        $v2 = DocumentVersion::where('document_id', $this->document->id)
            ->where('version_number', 2)
            ->firstOrFail();

        // 2. Head approves v2
        $this->actingAs($this->head)
            ->post(route('approvals.approve', [$this->document, $v2]));

        $this->assertEquals('Renamed Title v2', $this->document->fresh()->title);
        $this->assertEquals($v2->id, $this->document->fresh()->current_version_id);

        $v1 = DocumentVersion::where('document_id', $this->document->id)
            ->where('version_number', 1)
            ->firstOrFail();

        // 3. Staff requests rollback to v1
        $versionService = app(\App\Services\VersionService::class);
        $versionService->requestRollback($this->document->fresh(), $v1, $this->staff);

        $this->assertTrue($this->document->fresh()->hasPendingRollback());

        // 4. Head approves rollback request
        $response = $this->actingAs($this->head)
            ->post(route('approvals.rollback-request.approve', $this->document->fresh()));

        $response->assertRedirect();

        // 5. Document title is restored to v1's original title and current_version is v1
        $freshDoc = $this->document->fresh();
        $this->assertEquals('Original Document Title', $freshDoc->title);
        $this->assertEquals($v1->id, $freshDoc->current_version_id);
        $this->assertNull($freshDoc->pending_rollback_version_id);
        $this->assertEquals('active', $v1->fresh()->status);
    }

    public function test_unauthorized_user_cannot_rename_document(): void
    {
        $otherDivision = Division::create(['name' => 'HR Department', 'code' => 'HR']);
        $otherUser = User::factory()->create([
            'division_id' => $otherDivision->id,
            'name' => 'Other Staff',
            'system_role' => 'staff',
        ]);
        $otherUser->companies()->attach($this->company->id);
        $otherUser->branches()->attach($this->branch->id);

        $response = $this->actingAs($otherUser)
            ->post(route('documents.rename', $this->document), [
                'title' => 'Hacked Title',
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('documents', [
            'id' => $this->document->id,
            'title' => 'Original Document Title',
        ]);
    }
}
