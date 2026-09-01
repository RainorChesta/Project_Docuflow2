<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Division;
use App\Models\Document;
use App\Models\DocumentShare;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use App\Services\DocumentShareService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShareLinkCrossBranchTest extends TestCase
{
    use RefreshDatabase;

    private Company $companyA;
    private Branch $branchA;
    private Division $divisionA;
    private User $userA;

    private Company $companyB;
    private Branch $branchB;
    private Division $divisionB;
    private User $userB;

    private DocumentType $docType;
    private DocumentService $documentService;
    private DocumentShareService $shareService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->documentService = app(DocumentService::class);
        $this->shareService = app(DocumentShareService::class);

        $this->companyA = Company::create(['name' => 'PT Alfa', 'code' => 'ALF']);
        $this->branchA = Branch::create(['company_id' => $this->companyA->id, 'name' => 'Pusat Alfa', 'is_pusat' => true]);
        $this->divisionA = Division::create(['code' => 'IT', 'name' => 'IT Department']);
        $this->userA = User::factory()->create(['division_id' => $this->divisionA->id]);
        $this->userA->companies()->sync([$this->companyA->id]);
        $this->userA->branches()->sync([$this->branchA->id]);

        $this->companyB = Company::create(['name' => 'PT Beta', 'code' => 'BET']);
        $this->branchB = Branch::create(['company_id' => $this->companyB->id, 'name' => 'Pusat Beta', 'is_pusat' => true]);
        $this->divisionB = Division::create(['code' => 'HR', 'name' => 'HR Department']);
        $this->userB = User::factory()->create(['division_id' => $this->divisionB->id]);
        $this->userB->companies()->sync([$this->companyB->id]);
        $this->userB->branches()->sync([$this->branchB->id]);

        $this->docType = DocumentType::create(['code' => 'S.KEL', 'name' => 'Surat Keluar']);
    }

    private function createDocInCompanyA(string $title, string $visibility = Document::VISIBILITY_DIVISION): Document
    {
        $doc = $this->documentService->create([
            'title' => $title,
            'document_type_id' => $this->docType->id,
            'division_id' => $this->divisionA->id,
            'branch_id' => $this->branchA->id,
            'company_id' => $this->companyA->id,
            'visibility' => $visibility,
            'general_access' => 'restricted',
        ], $this->userA->id);

        $version = $doc->versions()->first();
        $version->update(['status' => 'active']);
        $doc->update(['current_version_id' => $version->id]);

        return $doc;
    }

    public function test_user_from_different_company_can_open_document_via_share_link(): void
    {
        $doc = $this->createDocInCompanyA('Dokumen Share Link Alfa');
        $this->shareService->updateGeneralAccess($doc, 'anyone_with_link', 'viewer');
        $token = $this->shareService->regenerateShareToken($doc);
        $doc->refresh();

        // User B (Company B) accesses document via share link route
        $response = $this->actingAs($this->userB)->get(route('documents.shared', $token));
        $response->assertOk();
        $response->assertSee('Dokumen Share Link Alfa');
    }

    public function test_user_from_different_company_with_editor_sharelink_can_edit_document(): void
    {
        $doc = $this->createDocInCompanyA('Dokumen Edit Alfa');
        $this->shareService->updateGeneralAccess($doc, 'anyone_with_link', 'editor');
        $token = $this->shareService->regenerateShareToken($doc);
        $doc->refresh();

        // User B accesses document via share link
        $this->actingAs($this->userB)->get(route('documents.shared', $token))->assertOk();

        // User B can access edit page
        $editResp = $this->actingAs($this->userB)->get(route('documents.edit', $doc));
        $editResp->assertOk();

        // User B can save changes
        $saveResp = $this->actingAs($this->userB)->put(route('documents.save', $doc), [
            'content' => 'Updated content by user from different company',
        ]);
        $saveResp->assertRedirect(route('documents.show', $doc));
    }

    public function test_user_from_different_company_with_viewer_sharelink_cannot_edit(): void
    {
        $doc = $this->createDocInCompanyA('Dokumen View Only Alfa');
        $this->shareService->updateGeneralAccess($doc, 'anyone_with_link', 'viewer');
        $token = $this->shareService->regenerateShareToken($doc);
        $doc->refresh();

        // User B can view
        $this->actingAs($this->userB)->get(route('documents.shared', $token))->assertOk();

        // User B cannot edit
        $this->actingAs($this->userB)->get(route('documents.edit', $doc))->assertForbidden();

        // User B cannot save
        $this->actingAs($this->userB)->put(route('documents.save', $doc), [
            'content' => 'Malicious update attempt',
        ])->assertForbidden();
    }

    public function test_user_from_different_company_can_view_document_when_directly_shared(): void
    {
        $doc = $this->createDocInCompanyA('Dokumen Direct Share Alfa');
        $this->shareService->addUserShare($doc, $this->userB, 'viewer', $this->userA);

        // User B can view via normal show route
        $response = $this->actingAs($this->userB)->get(route('documents.show', $doc));
        $response->assertOk();
        $response->assertSee('Dokumen Direct Share Alfa');

        // User B sees it in 'shared' tab
        $sharedTabResp = $this->actingAs($this->userB)->get(route('documents.index', ['type' => 'shared']));
        $sharedTabResp->assertOk();
        $sharedTabResp->assertSee('Dokumen Direct Share Alfa');
    }

    public function test_user_from_different_company_cannot_access_unshared_restricted_document(): void
    {
        $doc = $this->createDocInCompanyA('Dokumen Rahasia Alfa');

        // User B cannot view unshared document
        $this->actingAs($this->userB)->get(route('documents.show', $doc))->assertForbidden();
        $this->actingAs($this->userB)->get(route('documents.edit', $doc))->assertForbidden();
    }

    public function test_global_search_includes_cross_branch_shared_documents(): void
    {
        $doc = $this->createDocInCompanyA('Dokumen Khusus Cross Search');
        $this->shareService->addUserShare($doc, $this->userB, 'viewer', $this->userA);

        // User B searches globally
        $searchResp = $this->actingAs($this->userB)->getJson(route('search', ['q' => 'Cross Search']));
        $searchResp->assertOk();
        $searchResp->assertJsonFragment(['title' => 'Dokumen Khusus Cross Search']);
    }

    public function test_user_with_editor_sharelink_cannot_change_document_scope(): void
    {
        $doc = $this->createDocInCompanyA('Dokumen Scope Guard');
        $this->shareService->updateGeneralAccess($doc, 'anyone_with_link', 'editor');
        $token = $this->shareService->regenerateShareToken($doc);
        $doc->refresh();

        // User B opens document - should not see "Ubah Cakupan" button or modal
        $showResp = $this->actingAs($this->userB)->get(route('documents.show', $doc));
        $showResp->assertOk();
        $showResp->assertDontSee('openModal(\'scope-modal\')', false);
        $showResp->assertDontSee('id="scope-modal"', false);

        // User B tries to change visibility to personal or general via patch route -> 403 Forbidden
        $patchResp = $this->actingAs($this->userB)->patch(route('documents.update-visibility', $doc), [
            'visibility' => 'general',
            'target_branch_ids' => [$this->branchB->id],
        ]);
        $patchResp->assertForbidden();

        // User B tries to distribute document -> 403 Forbidden
        $distResp = $this->actingAs($this->userB)->post(route('distributions.store', $doc), [
            'target_branch_ids' => [$this->branchB->id],
        ]);
        $distResp->assertForbidden();

        // Owner (User A) CAN see "Ubah Cakupan" and change scope
        $ownerShowResp = $this->actingAs($this->userA)->get(route('documents.show', $doc));
        $ownerShowResp->assertOk();
        $ownerShowResp->assertSee('openModal(\'scope-modal\')', false);
        $ownerShowResp->assertSee('id="scope-modal"', false);

        $ownerPatchResp = $this->actingAs($this->userA)->patch(route('documents.update-visibility', $doc), [
            'visibility' => 'general',
            'target_branch_ids' => [$this->branchA->id],
        ]);
        $ownerPatchResp->assertRedirect();
        $doc->refresh();
        $this->assertSame(Document::VISIBILITY_GENERAL, $doc->visibility);
    }
}

