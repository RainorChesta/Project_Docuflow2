<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Division;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrashTest extends TestCase
{
    use RefreshDatabase;

    private DocumentType $docType;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('id');
        $this->docType = DocumentType::create(['name' => 'General Doc', 'code' => 'GEN']);
    }

    private function createDocument(array $attributes = []): Document
    {
        return Document::create(array_merge([
            'title' => 'Sample Doc',
            'document_number' => 'DOC/' . uniqid(),
            'document_type_id' => $this->docType->id,
            'visibility' => Document::VISIBILITY_PERSONAL,
        ], $attributes));
    }

    public function test_unauthenticated_user_cannot_access_trash(): void
    {
        $response = $this->get(route('trash.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_staff_can_view_own_trashed_documents(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        $doc = $this->createDocument([
            'title' => 'My Deleted Document',
            'document_number' => 'DOC/001/USER',
            'owner_id' => $user->id,
        ]);
        $doc->delete(); // Soft delete

        $response = $this->actingAs($user)->get(route('trash.index'));

        $response->assertOk();
        $response->assertSee('My Deleted Document');
        $response->assertSee('DOC/001/USER');
    }

    public function test_staff_cannot_view_others_trashed_documents(): void
    {
        $user1 = User::factory()->create(['system_role' => 'user']);
        $user2 = User::factory()->create(['system_role' => 'user']);

        $doc1 = $this->createDocument([
            'title' => 'User1 Deleted Document',
            'document_number' => 'DOC/USER1',
            'owner_id' => $user1->id,
        ]);
        $doc1->delete();

        $doc2 = $this->createDocument([
            'title' => 'User2 Secret Document',
            'document_number' => 'DOC/USER2',
            'owner_id' => $user2->id,
        ]);
        $doc2->delete();

        $response = $this->actingAs($user1)->get(route('trash.index'));

        $response->assertOk();
        $response->assertSee('User1 Deleted Document');
        $response->assertDontSee('User2 Secret Document');
    }

    public function test_staff_can_restore_own_trashed_document(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        $doc = $this->createDocument([
            'title' => 'Restore Me',
            'document_number' => 'DOC/RESTORE',
            'owner_id' => $user->id,
        ]);
        $doc->delete();

        $this->assertSoftDeleted('documents', ['id' => $doc->id]);

        $response = $this->actingAs($user)->post(route('trash.restore', $doc->id));

        $response->assertRedirect(route('trash.index'));
        $response->assertSessionHas('success');

        $this->assertNotSoftDeleted('documents', ['id' => $doc->id]);
    }

    public function test_staff_cannot_restore_others_trashed_document(): void
    {
        $user1 = User::factory()->create(['system_role' => 'user']);
        $user2 = User::factory()->create(['system_role' => 'user']);

        $doc2 = $this->createDocument([
            'title' => 'User2 Private Doc',
            'document_number' => 'DOC/U2',
            'owner_id' => $user2->id,
        ]);
        $doc2->delete();

        // User1 tries to restore User2's document -> 404 because not found in user1's scoped query
        $response = $this->actingAs($user1)->post(route('trash.restore', $doc2->id));

        $response->assertNotFound();
        $this->assertSoftDeleted('documents', ['id' => $doc2->id]);
    }

    public function test_staff_can_bulk_restore_own_trashed_documents(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);

        $doc1 = $this->createDocument([
            'title' => 'Bulk Doc 1',
            'document_number' => 'DOC/BULK/1',
            'owner_id' => $user->id,
        ]);
        $doc1->delete();

        $doc2 = $this->createDocument([
            'title' => 'Bulk Doc 2',
            'document_number' => 'DOC/BULK/2',
            'owner_id' => $user->id,
        ]);
        $doc2->delete();

        $response = $this->actingAs($user)->post(route('trash.bulk-restore'), [
            'ids' => [$doc1->id, $doc2->id],
        ]);

        $response->assertRedirect(route('trash.index'));
        $response->assertSessionHas('success');

        $this->assertNotSoftDeleted('documents', ['id' => $doc1->id]);
        $this->assertNotSoftDeleted('documents', ['id' => $doc2->id]);
    }

    public function test_staff_can_search_trashed_documents(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);

        $doc1 = $this->createDocument([
            'title' => 'Quarterly Report Finance',
            'document_number' => 'DOC/FIN/2026',
            'owner_id' => $user->id,
        ]);
        $doc1->delete();

        $doc2 = $this->createDocument([
            'title' => 'Annual Strategy HR',
            'document_number' => 'DOC/HR/2026',
            'owner_id' => $user->id,
        ]);
        $doc2->delete();

        $response = $this->actingAs($user)->get(route('trash.index', ['search' => 'Finance']));

        $response->assertOk();
        $response->assertSee('Quarterly Report Finance');
        $response->assertDontSee('Annual Strategy HR');
    }

    public function test_division_head_can_view_own_and_division_trashed_documents(): void
    {
        $div = Division::create(['name' => 'Marketing', 'code' => 'MKT']);
        $head = User::factory()->create([
            'system_role' => 'head',
            'division_id' => $div->id,
        ]);
        $staff = User::factory()->create([
            'system_role' => 'user',
            'division_id' => $div->id,
        ]);

        // Division-scoped document deleted by staff
        $divDoc = $this->createDocument([
            'title' => 'Marketing Campaign Plan',
            'document_number' => 'DOC/MKT/PLAN',
            'owner_id' => $staff->id,
            'division_id' => $div->id,
            'visibility' => Document::VISIBILITY_DIVISION,
        ]);
        $divDoc->delete();

        // Personal draft document belonging to staff
        $personalDoc = $this->createDocument([
            'title' => 'Staff Secret Draft',
            'document_number' => 'DOC/STAFF/SECRET',
            'owner_id' => $staff->id,
            'division_id' => $div->id,
            'visibility' => Document::VISIBILITY_PERSONAL,
        ]);
        $personalDoc->delete();

        $response = $this->actingAs($head)->get(route('trash.index'));

        $response->assertOk();
        $response->assertSee('Marketing Campaign Plan');
        // Head should NOT see personal draft of staff
        $response->assertDontSee('Staff Secret Draft');

        // Head can restore division document
        $restoreResp = $this->actingAs($head)->post(route('trash.restore', $divDoc->id));
        $restoreResp->assertRedirect(route('trash.index'));
        $this->assertNotSoftDeleted('documents', ['id' => $divDoc->id]);
    }

    public function test_director_can_view_trashed_documents_in_assigned_companies_and_branches(): void
    {
        $company = Company::create(['name' => 'PT Test Induk', 'code' => 'PTI']);
        $branch = Branch::create(['company_id' => $company->id, 'name' => 'Cabang Utama', 'is_pusat' => true]);

        $director = User::factory()->create(['system_role' => 'direktur']);
        $director->companies()->sync([$company->id]);
        $director->branches()->sync([$branch->id]);

        $staff = User::factory()->create(['system_role' => 'user']);

        $companyDoc = $this->createDocument([
            'title' => 'PT Test Board Memo',
            'document_number' => 'MEMO/001',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'owner_id' => $staff->id,
            'visibility' => Document::VISIBILITY_GENERAL,
        ]);
        $companyDoc->delete();

        $response = $this->actingAs($director)->get(route('trash.index'));

        $response->assertOk();
        $response->assertSee('PT Test Board Memo');

        // Director can restore it
        $restoreResp = $this->actingAs($director)->post(route('trash.restore', $companyDoc->id));
        $restoreResp->assertRedirect(route('trash.index'));
        $this->assertNotSoftDeleted('documents', ['id' => $companyDoc->id]);
    }

    public function test_admin_can_view_all_trashed_documents_and_empty_trash(): void
    {
        $admin = User::factory()->create(['system_role' => 'admin']);
        $user = User::factory()->create(['system_role' => 'user']);

        $doc1 = $this->createDocument([
            'title' => 'Admin Trashed Doc 1',
            'document_number' => 'DOC/A1',
            'owner_id' => $user->id,
            'visibility' => Document::VISIBILITY_PERSONAL,
        ]);
        $doc1->delete();

        $doc2 = $this->createDocument([
            'title' => 'Admin Trashed Doc 2',
            'document_number' => 'DOC/A2',
            'owner_id' => $admin->id,
            'visibility' => Document::VISIBILITY_PERSONAL,
        ]);
        $doc2->delete();

        $response = $this->actingAs($admin)->get(route('trash.index'));
        $response->assertOk();
        $response->assertSee('Admin Trashed Doc 1');
        $response->assertSee('Admin Trashed Doc 2');

        // Empty trash
        $emptyResp = $this->actingAs($admin)->delete(route('trash.empty'));
        $emptyResp->assertRedirect(route('trash.index'));

        $this->assertDatabaseMissing('documents', ['id' => $doc1->id]);
        $this->assertDatabaseMissing('documents', ['id' => $doc2->id]);
    }

    public function test_staff_can_force_delete_own_unapproved_document(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        $doc = $this->createDocument([
            'title' => 'Delete Permanently Me',
            'owner_id' => $user->id,
        ]);
        $doc->delete();

        $response = $this->actingAs($user)->delete(route('trash.force-delete', $doc->id));
        $response->assertRedirect(route('trash.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('documents', ['id' => $doc->id]);
    }

    public function test_staff_cannot_force_delete_approved_document(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        $doc = $this->createDocument([
            'title' => 'Approved Document Deleted by Admin',
            'owner_id' => $user->id,
        ]);
        \App\Models\DocumentVersion::create([
            'document_id' => $doc->id,
            'version_number' => '1.0',
            'status' => 'active',
            'author_id' => $user->id,
            'author_name' => $user->name,
            'content' => '<p>Official approved content</p>',
        ]);
        $doc->delete();

        $response = $this->actingAs($user)->delete(route('trash.force-delete', $doc->id));
        $response->assertForbidden();

        $this->assertDatabaseHas('documents', ['id' => $doc->id]);
    }

    public function test_trash_sidebar_navigation_link_is_rendered(): void
    {
        // For regular staff
        $staff = User::factory()->create(['system_role' => 'user']);
        $responseStaff = $this->actingAs($staff)->get(route('dashboard'));
        $responseStaff->assertOk();
        $responseStaff->assertSee(route('trash.index'));
        $responseStaff->assertSee(__('Sampah'));

        // For director
        $director = User::factory()->create(['system_role' => 'direktur']);
        $responseDirector = $this->actingAs($director)->get(route('dashboard'));
        $responseDirector->assertOk();
        $responseDirector->assertSee(route('trash.index'));

        // For admin
        $admin = User::factory()->create(['system_role' => 'admin']);
        $responseAdmin = $this->actingAs($admin)->get(route('dashboard'));
        $responseAdmin->assertOk();
        $responseAdmin->assertSee(route('trash.index'));
    }

    public function test_empty_trash_button_is_removed_and_confirmation_modals_are_rendered(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        $doc = $this->createDocument([
            'title' => 'Modal Confirmation Doc',
            'owner_id' => $user->id,
        ]);
        $doc->delete();

        $response = $this->actingAs($user)->get(route('trash.index'));
        $response->assertOk();

        // Empty trash button should NOT be rendered
        $response->assertDontSee(__('Kosongkan Sampah'));
        $response->assertDontSee(route('trash.empty'));

        // Confirmation modals for single actions should be rendered
        $response->assertSee('confirm-restore-' . $doc->id);
        $response->assertSee('confirm-force-delete-' . $doc->id);

        // Confirmation modals for bulk actions should be rendered
        $response->assertSee('confirm-bulk-restore');
        $response->assertSee('confirm-bulk-force-delete');
    }
}

