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
}
