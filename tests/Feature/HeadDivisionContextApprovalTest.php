<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Division;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeadDivisionContextApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected Company $companyA;
    protected Company $companyB;
    protected Branch $branchA1;
    protected Branch $branchB1;
    protected Division $division;
    protected User $head;
    protected User $staffB;
    protected DocumentType $docType;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Company A and Branch A1
        $this->companyA = Company::create(['name' => 'Company A', 'code' => 'CMPA']);
        $this->branchA1 = Branch::create(['company_id' => $this->companyA->id, 'name' => 'Branch A1', 'is_pusat' => true]);

        // 2. Setup Company B and Branch B1
        $this->companyB = Company::create(['name' => 'Company B', 'code' => 'CMPB']);
        $this->branchB1 = Branch::create(['company_id' => $this->companyB->id, 'name' => 'Branch B1', 'is_pusat' => true]);

        // 3. Shared division
        $this->division = Division::create(['name' => 'Finance', 'code' => 'FIN']);

        // 4. Head of Division assigned to both companies and branches
        $this->head = User::factory()->create([
            'division_id' => $this->division->id,
            'name' => 'Head Finance',
            'system_role' => 'head',
        ]);
        $this->head->companies()->attach([$this->companyA->id, $this->companyB->id]);
        $this->head->branches()->attach([$this->branchA1->id, $this->branchB1->id]);

        // 5. Staff in Company B
        $this->staffB = User::factory()->create([
            'division_id' => $this->division->id,
            'name' => 'Staff Finance B',
            'system_role' => 'staff',
        ]);
        $this->staffB->companies()->attach($this->companyB->id);
        $this->staffB->branches()->attach($this->branchB1->id);

        $this->docType = DocumentType::create(['name' => 'Standard SOP', 'code' => 'SOP']);
    }

    public function test_head_division_sees_no_counter_or_request_on_approval_page_when_in_different_company_context(): void
    {
        // Create a document in Company B / Branch B1 with a pending version approval
        $documentB = Document::create([
            'document_number' => 'DOC-B-001',
            'title' => 'Invoice Policy B',
            'document_type_id' => $this->docType->id,
            'owner_id' => $this->staffB->id,
            'division_id' => $this->division->id,
            'company_id' => $this->companyB->id,
            'branch_id' => $this->branchB1->id,
            'visibility' => Document::VISIBILITY_DIVISION,
            'approver_role' => 'head',
        ]);

        $pendingVersion = DocumentVersion::create([
            'document_id' => $documentB->id,
            'version_number' => 1,
            'author_id' => $this->staffB->id,
            'author_name' => $this->staffB->name,
            'status' => 'pending',
            'content' => '<p>Pending version in Company B</p>',
        ]);

        // Head is currently viewing Company A / Branch A1
        $response = $this->actingAs($this->head)
            ->withSession([
                'active_company_id' => $this->companyA->id,
                'active_branch_id' => $this->branchA1->id,
                'active_division_id' => $this->division->id,
            ])
            ->get(route('approvals.versions'));

        $response->assertOk();
        // The document from Company B must NOT appear in the pending list
        $response->assertDontSee('DOC-B-001');
        $response->assertDontSee('Invoice Policy B');

        // Verify view counts on Approval page are strictly 0
        $this->assertSame(0, $response->viewData('counts')['versions']);
        $this->assertSame(0, $response->viewData('counts')['total']);
        $this->assertSame(0, $response->viewData('pendingVersions')->total());

        // Header status badge is not displayed
        $response->assertDontSee('badge-primary gap-1.5 text-xs py-2.5 px-3 font-semibold shadow-xs');

        // Check scoped model counts in session context
        $this->assertSame(0, $this->head->pendingVersionApprovalsCount());
        $this->assertSame(0, $this->head->pendingApprovalsCount());
    }

    public function test_company_switcher_dropdown_shows_notification_counter_for_company_with_pending_approvals(): void
    {
        // Document in Company B with pending version
        Document::create([
            'document_number' => 'DOC-B-002',
            'title' => 'Company B Document',
            'document_type_id' => $this->docType->id,
            'owner_id' => $this->staffB->id,
            'division_id' => $this->division->id,
            'company_id' => $this->companyB->id,
            'branch_id' => $this->branchB1->id,
            'visibility' => Document::VISIBILITY_DIVISION,
            'approver_role' => 'head',
        ])->versions()->create([
            'version_number' => 1,
            'author_id' => $this->staffB->id,
            'author_name' => $this->staffB->name,
            'status' => 'pending',
            'content' => '<p>Pending</p>',
        ]);

        // Check pending counts per company
        $countsByCompany = $this->head->pendingApprovalsCountByCompany();

        // Company B has 1 pending approval request
        $this->assertSame(1, $countsByCompany[$this->companyB->id] ?? 0);
        // Company A has 0 pending requests (key not set or 0)
        $this->assertArrayNotHasKey($this->companyA->id, $countsByCompany);

        // Render a page that contains the switcher while in Company A context
        $response = $this->actingAs($this->head)
            ->withSession([
                'active_company_id' => $this->companyA->id,
                'active_branch_id' => $this->branchA1->id,
            ])
            ->get(route('dashboard'));

        $response->assertOk();
        // Company B should show counter reminder in option or badge
        $response->assertSee('CMPB');
        $response->assertSee('1 menunggu');
    }

    public function test_approval_request_appears_when_switched_to_matching_company_and_branch(): void
    {
        // Document in Company B
        $documentB = Document::create([
            'document_number' => 'DOC-B-003',
            'title' => 'Payroll Document B',
            'document_type_id' => $this->docType->id,
            'owner_id' => $this->staffB->id,
            'division_id' => $this->division->id,
            'company_id' => $this->companyB->id,
            'branch_id' => $this->branchB1->id,
            'visibility' => Document::VISIBILITY_DIVISION,
            'approver_role' => 'head',
        ]);

        $documentB->versions()->create([
            'version_number' => 1,
            'author_id' => $this->staffB->id,
            'author_name' => $this->staffB->name,
            'status' => 'pending',
            'content' => '<p>Pending in B</p>',
        ]);

        // Switched to Company B / Branch B1
        $response = $this->actingAs($this->head)
            ->withSession([
                'active_company_id' => $this->companyB->id,
                'active_branch_id' => $this->branchB1->id,
                'active_division_id' => $this->division->id,
            ])
            ->get(route('approvals.versions'));

        $response->assertOk();
        // The document from Company B MUST appear
        $response->assertSee('DOC-B-003');
        $response->assertSee('Payroll Document B');
        $this->assertSame(1, $response->viewData('counts')['versions']);

        // Scoped count returns 1 when passing Company B / Branch B1 or setting session
        session([
            'active_company_id' => $this->companyB->id,
            'active_branch_id' => $this->branchB1->id,
        ]);
        $this->assertSame(1, $this->head->pendingVersionApprovalsCount());
        $this->assertSame(1, $this->head->pendingApprovalsCount());
    }

    public function test_rename_and_rollback_approvals_are_isolated_by_company_and_branch_context(): void
    {
        // Document in Company B
        Document::create([
            'document_number' => 'DOC-B-RENAME',
            'title' => 'Original Title B',
            'document_type_id' => $this->docType->id,
            'owner_id' => $this->staffB->id,
            'division_id' => $this->division->id,
            'company_id' => $this->companyB->id,
            'branch_id' => $this->branchB1->id,
            'visibility' => Document::VISIBILITY_DIVISION,
            'approver_role' => 'head',
            'pending_title' => 'Requested New Title B',
            'rename_requested_by_id' => $this->staffB->id,
            'rename_requested_at' => now(),
        ]);

        // 1. Viewing Company A: should NOT appear in renames approval page
        session([
            'active_company_id' => $this->companyA->id,
            'active_branch_id' => $this->branchA1->id,
        ]);
        $responseA = $this->actingAs($this->head)
            ->withSession([
                'active_company_id' => $this->companyA->id,
                'active_branch_id' => $this->branchA1->id,
            ])
            ->get(route('approvals.renames'));

        $responseA->assertOk();
        $responseA->assertDontSee('DOC-B-RENAME');
        $this->assertSame(0, $responseA->viewData('counts')['renames']);
        $this->assertSame(0, $responseA->viewData('counts')['total']);
        $this->assertSame(0, $responseA->viewData('pendingRenames')->total());
        $responseA->assertDontSee('badge-warning font-bold text-amber-900 gap-1.5');
        $this->assertSame(0, $this->head->pendingRenameApprovalsCount());

        // 2. Switched to Company B: MUST appear
        session([
            'active_company_id' => $this->companyB->id,
            'active_branch_id' => $this->branchB1->id,
        ]);
        $responseB = $this->actingAs($this->head)
            ->withSession([
                'active_company_id' => $this->companyB->id,
                'active_branch_id' => $this->branchB1->id,
            ])
            ->get(route('approvals.renames'));

        $responseB->assertOk();
        $responseB->assertSee('DOC-B-RENAME');
        $responseB->assertSee('Requested New Title B');
        $this->assertSame(1, $responseB->viewData('counts')['renames']);
        $this->assertSame(1, $this->head->pendingRenameApprovalsCount());
    }

    public function test_signature_approval_requests_remain_global_across_all_companies(): void
    {
        // Document in Company B
        $doc = Document::create([
            'document_number' => 'DOC-B-SIG',
            'title' => 'Contract B',
            'document_type_id' => $this->docType->id,
            'owner_id' => $this->staffB->id,
            'division_id' => $this->division->id,
            'company_id' => $this->companyB->id,
            'branch_id' => $this->branchB1->id,
            'visibility' => Document::VISIBILITY_DIVISION,
        ]);

        // Signature request targeted to Head
        \App\Models\SignatureRequest::create([
            'requester_id' => $this->staffB->id,
            'target_user_id' => $this->head->id,
            'document_id' => $doc->id,
            'status' => 'pending',
            'requested_at' => now(),
            'notified_at' => now(),
        ]);

        // Head viewing Company A context
        $response = $this->actingAs($this->head)
            ->withSession([
                'active_company_id' => $this->companyA->id,
                'active_branch_id' => $this->branchA1->id,
            ])
            ->get(route('signatures.requests.index'));

        $response->assertOk();
        // Signature request is visible regardless of company/branch
        $response->assertSee('DOC-B-SIG');
        $this->assertSame(1, $this->head->receivedSignatureRequests()->where('status', 'pending')->count());
    }

    public function test_head_cannot_approve_document_while_in_different_company_context(): void
    {
        $documentB = Document::create([
            'document_number' => 'DOC-B-POLICY',
            'title' => 'Policy Document B',
            'document_type_id' => $this->docType->id,
            'owner_id' => $this->staffB->id,
            'division_id' => $this->division->id,
            'company_id' => $this->companyB->id,
            'branch_id' => $this->branchB1->id,
            'visibility' => Document::VISIBILITY_DIVISION,
            'approver_role' => 'head',
        ]);

        // In Company A context: Head cannot approve Document B
        session([
            'active_company_id' => $this->companyA->id,
            'active_branch_id' => $this->branchA1->id,
        ]);
        $this->assertFalse($this->head->can('approve', $documentB));

        // Switched to Company B context: Head can approve Document B
        session([
            'active_company_id' => $this->companyB->id,
            'active_branch_id' => $this->branchB1->id,
        ]);
        $this->assertTrue($this->head->can('approve', $documentB));
    }
}
