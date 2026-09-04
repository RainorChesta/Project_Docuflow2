<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Division;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Notifications\DocumentApprovalResult;
use App\Notifications\DocumentRollbackResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DocumentApprovalRejectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_reviewer_can_reject_document_version_with_notes(): void
    {
        Notification::fake();

        $company = Company::create(['name' => 'PT Test', 'code' => 'TEST']);
        $branch = Branch::create(['company_id' => $company->id, 'name' => 'Pusat', 'is_pusat' => true]);

        $division = Division::create(['name' => 'Finance', 'code' => 'FIN']);
        $author = User::factory()->create(['division_id' => $division->id, 'name' => 'Author User']);
        $author->companies()->attach($company->id);
        $author->branches()->attach($branch->id);

        $reviewer = User::factory()->create([
            'division_id' => $division->id,
            'name' => 'Reviewer Head',
            'system_role' => 'head',
        ]);
        $reviewer->companies()->attach($company->id);
        $reviewer->branches()->attach($branch->id);

        $docType = DocumentType::create(['name' => 'Standard SOP', 'code' => 'SOP']);
        $document = Document::create([
            'document_number' => '001/FIN/SOP/2026',
            'title' => 'Financial SOP',
            'document_type_id' => $docType->id,
            'owner_id' => $author->id,
            'division_id' => $division->id,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'visibility' => 'general',
        ]);

        $version = DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => 1,
            'author_id' => $author->id,
            'author_name' => $author->name,
            'status' => 'pending',
            'content' => '<p>Initial Content</p>',
        ]);

        $response = $this->actingAs($reviewer)->post(route('approvals.reject', [$document, $version]), [
            'notes' => 'Please revise section 3 and attach financial appendix.',
        ]);

        $response->assertRedirect(route('approvals.index'));
        $response->assertSessionHas('success');

        $this->assertSame('rejected', $version->fresh()->status);
        $this->assertSame('Please revise section 3 and attach financial appendix.', $version->fresh()->notes);

        Notification::assertSentTo($author, DocumentApprovalResult::class, function ($notification) {
            return $notification->status === 'rejected' &&
                str_contains($notification->notes, 'Please revise section 3');
        });
    }

    public function test_reviewer_can_reject_rollback_request_with_notes(): void
    {
        Notification::fake();

        $company = Company::create(['name' => 'PT Test', 'code' => 'TEST']);
        $branch = Branch::create(['company_id' => $company->id, 'name' => 'Pusat', 'is_pusat' => true]);

        $division = Division::create(['name' => 'HR Dept', 'code' => 'HR']);
        $author = User::factory()->create(['division_id' => $division->id, 'name' => 'HR Staff']);
        $author->companies()->attach($company->id);
        $author->branches()->attach($branch->id);

        $reviewer = User::factory()->create([
            'division_id' => $division->id,
            'name' => 'HR Head',
            'system_role' => 'head',
        ]);
        $reviewer->companies()->attach($company->id);
        $reviewer->branches()->attach($branch->id);

        $docType = DocumentType::create(['name' => 'Internal Policy', 'code' => 'POL']);
        $document = Document::create([
            'document_number' => '002/HR/POL/2026',
            'title' => 'Leave Policy',
            'document_type_id' => $docType->id,
            'owner_id' => $author->id,
            'division_id' => $division->id,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'visibility' => 'general',
        ]);

        $v1 = DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => 1,
            'author_id' => $author->id,
            'author_name' => $author->name,
            'status' => 'active',
            'content' => '<p>Version 1</p>',
        ]);

        $v2 = DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => 2,
            'author_id' => $author->id,
            'author_name' => $author->name,
            'status' => 'active',
            'content' => '<p>Version 2</p>',
        ]);

        $document->update([
            'current_version_id' => $v2->id,
            'pending_rollback_version_id' => $v1->id,
            'rollback_requested_by_id' => $author->id,
            'rollback_requested_at' => now(),
        ]);

        $response = $this->actingAs($reviewer)->post(route('approvals.rollback-request.reject', $document), [
            'notes' => 'Rollback not allowed without management consensus.',
        ]);

        $response->assertRedirect(route('documents.show', $document));
        $response->assertSessionHas('success');

        $this->assertNull($document->fresh()->pending_rollback_version_id);
        $this->assertNull($document->fresh()->rollback_requested_by);

        Notification::assertSentTo($author, DocumentRollbackResult::class, function ($notification) {
            return $notification->status === 'rejected' &&
                $notification->notes === 'Rollback not allowed without management consensus.';
        });
    }

    public function test_approvals_index_filters_by_search_and_tab(): void
    {
        $company = Company::create(['name' => 'PT Test', 'code' => 'TEST']);
        $branch = Branch::create(['company_id' => $company->id, 'name' => 'Pusat', 'is_pusat' => true]);
        $division = Division::create(['name' => 'Operations', 'code' => 'OPS']);

        $reviewer = User::factory()->create([
            'division_id' => $division->id,
            'name' => 'Ops Head',
            'system_role' => 'head',
        ]);
        $reviewer->companies()->attach($company->id);
        $reviewer->branches()->attach($branch->id);

        $author = User::factory()->create(['division_id' => $division->id, 'name' => 'Staff Alice']);
        $author->companies()->attach($company->id);
        $author->branches()->attach($branch->id);

        $docType = DocumentType::create(['name' => 'Manual', 'code' => 'MNL']);
        $doc1 = Document::create([
            'document_number' => '001/OPS/2026',
            'title' => 'Operating Standard Manual',
            'document_type_id' => $docType->id,
            'owner_id' => $author->id,
            'division_id' => $division->id,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'visibility' => 'general',
        ]);
        $v1 = DocumentVersion::create([
            'document_id' => $doc1->id,
            'version_number' => 1,
            'author_id' => $author->id,
            'author_name' => $author->name,
            'status' => 'pending',
            'content' => '<p>Manual content</p>',
        ]);

        // 1. Visit approvals index with search
        $responseSearch = $this->actingAs($reviewer)->get(route('approvals.index', ['search' => 'Operating Standard']));
        $responseSearch->assertStatus(200);
        $responseSearch->assertSee('Operating Standard Manual');

        // 2. Visit approvals index with tab 'versions'
        $responseTab = $this->actingAs($reviewer)->get(route('approvals.index', ['tab' => 'versions']));
        $responseTab->assertStatus(200);
        $responseTab->assertSee('Operating Standard Manual');
    }

    public function test_bulk_approve_and_bulk_reject_document_versions(): void
    {
        Notification::fake();

        $company = Company::create(['name' => 'PT Test', 'code' => 'TEST']);
        $branch = Branch::create(['company_id' => $company->id, 'name' => 'Pusat', 'is_pusat' => true]);
        $division = Division::create(['name' => 'Engineering', 'code' => 'ENG']);

        $reviewer = User::factory()->create([
            'division_id' => $division->id,
            'name' => 'Lead Engineer',
            'system_role' => 'head',
        ]);
        $reviewer->companies()->attach($company->id);
        $reviewer->branches()->attach($branch->id);

        $author = User::factory()->create(['division_id' => $division->id, 'name' => 'Junior Dev']);
        $author->companies()->attach($company->id);
        $author->branches()->attach($branch->id);

        $docType = DocumentType::create(['name' => 'Architecture Guide', 'code' => 'ARC']);
        $doc1 = Document::create(['document_number' => '001/ARC/2026', 'title' => 'System Design A', 'document_type_id' => $docType->id, 'owner_id' => $author->id, 'division_id' => $division->id, 'company_id' => $company->id, 'branch_id' => $branch->id, 'visibility' => 'general']);
        $doc2 = Document::create(['document_number' => '002/ARC/2026', 'title' => 'System Design B', 'document_type_id' => $docType->id, 'owner_id' => $author->id, 'division_id' => $division->id, 'company_id' => $company->id, 'branch_id' => $branch->id, 'visibility' => 'general']);

        $v1 = DocumentVersion::create(['document_id' => $doc1->id, 'version_number' => 1, 'author_id' => $author->id, 'author_name' => $author->name, 'status' => 'pending', 'content' => '<p>A</p>']);
        $v2 = DocumentVersion::create(['document_id' => $doc2->id, 'version_number' => 1, 'author_id' => $author->id, 'author_name' => $author->name, 'status' => 'pending', 'content' => '<p>B</p>']);

        // Bulk approve
        $responseApprove = $this->actingAs($reviewer)->post(route('approvals.bulk-approve-versions'), [
            'version_ids' => [$v1->id, $v2->id],
        ]);
        $responseApprove->assertSessionHas('success');
        $this->assertSame('active', $v1->fresh()->status);
        $this->assertSame('active', $v2->fresh()->status);

        Notification::assertSentTo($author, DocumentApprovalResult::class);

        // Create 2 more versions to test bulk reject
        $v3 = DocumentVersion::create(['document_id' => $doc1->id, 'version_number' => 2, 'author_id' => $author->id, 'author_name' => $author->name, 'status' => 'pending', 'content' => '<p>A2</p>']);
        $v4 = DocumentVersion::create(['document_id' => $doc2->id, 'version_number' => 2, 'author_id' => $author->id, 'author_name' => $author->name, 'status' => 'pending', 'content' => '<p>B2</p>']);

        $responseReject = $this->actingAs($reviewer)->post(route('approvals.bulk-reject-versions'), [
            'version_ids' => [$v3->id, $v4->id],
            'notes' => 'Bulk reject reason',
        ]);
        $responseReject->assertSessionHas('success');
        $this->assertSame('rejected', $v3->fresh()->status);
        $this->assertSame('rejected', $v4->fresh()->status);
    }

    public function test_reviewer_can_view_rollback_approvals_page_and_approve(): void
    {
        Notification::fake();

        $company = Company::create(['name' => 'PT Test', 'code' => 'TEST']);
        $branch = Branch::create(['company_id' => $company->id, 'name' => 'Pusat', 'is_pusat' => true]);
        $division = Division::create(['name' => 'Finance', 'code' => 'FIN']);

        $reviewer = User::factory()->create([
            'division_id' => $division->id,
            'name' => 'Reviewer Kadiv',
            'system_role' => 'head',
        ]);
        $reviewer->companies()->attach($company->id);
        $reviewer->branches()->attach($branch->id);

        $author = User::factory()->create(['division_id' => $division->id, 'name' => 'Staff Finance']);
        $author->companies()->attach($company->id);
        $author->branches()->attach($branch->id);

        $docType = DocumentType::create(['name' => 'Standard SOP', 'code' => 'SOP']);
        $document = Document::create([
            'document_number' => '001/FIN/SOP/2026',
            'title' => 'Financial SOP Document',
            'document_type_id' => $docType->id,
            'owner_id' => $author->id,
            'division_id' => $division->id,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'visibility' => 'general',
        ]);

        $v1 = DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => 1,
            'author_id' => $author->id,
            'author_name' => $author->name,
            'status' => 'active',
            'content' => '<p>Version 1 Content</p>',
        ]);

        $v2 = DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => 2,
            'author_id' => $author->id,
            'author_name' => $author->name,
            'status' => 'active',
            'content' => '<p>Version 2 Content</p>',
        ]);

        $document->update([
            'current_version_id' => $v2->id,
            'pending_rollback_version_id' => $v1->id,
            'rollback_requested_by_id' => $author->id,
            'rollback_requested_at' => now(),
        ]);

        // 1. Visit approvals.rollbacks page
        $response = $this->actingAs($reviewer)->get(route('approvals.rollbacks'));
        $response->assertStatus(200);
        $response->assertSee('Rollback Approval');
        $response->assertSee('Financial SOP Document');
        $response->assertSee('Staff Finance');

        // 2. Approve the rollback request
        $approveResponse = $this->actingAs($reviewer)->post(route('approvals.rollback-request.approve', $document));
        $approveResponse->assertSessionHas('success');

        $document->refresh();
        $this->assertNull($document->pending_rollback_version_id);
        $this->assertSame($v1->id, $document->current_version_id);
    }
}
