<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\UnitKerja;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Notifications\DocumentApprovalResult;
use App\Notifications\DocumentRollbackResult;
use App\Notifications\DocumentApprovalRequested;
use App\Notifications\ApprovalRouteResolved;
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

        $unitKerja = UnitKerja::create(['nama_unit_kerja' => 'Finance', 'kode_unit_kerja' => '05']);
        $author = User::factory()->create(['unit_kerja_id' => $unitKerja->id, 'name' => 'Author User']);
        $author->companies()->attach($company->id);
        $author->branches()->attach($branch->id);

        $reviewer = User::factory()->create([
            'unit_kerja_id' => $unitKerja->id,
            'name' => 'Reviewer Head',
            'system_role' => 'head',
        ]);
        $reviewer->companies()->attach($company->id);
        $reviewer->branches()->attach($branch->id);

        $docType = DocumentType::create(['name' => 'Standard SOP', 'code' => 'SOP', 'category' => 'akreditasi']);
        $document = Document::create([
            'document_number' => '001/SOP-05/TEST/IX/2026',
            'title' => 'Financial SOP',
            'document_type_id' => $docType->id,
            'owner_id' => $author->id,
            'unit_kerja_id' => $unitKerja->id,
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
            'content' => '<p>Financial SOP Content</p>',
        ]);

        // Rejection action
        $response = $this->actingAs($reviewer)->post(route('approvals.reject', [$document, $version]), [
            'notes' => 'Please revise section 3 regarding budget allocation.',
        ]);

        $response->assertRedirect(route('approvals.index'));
        $response->assertSessionHas('success');

        $this->assertSame('rejected', $version->fresh()->status);
        $this->assertSame('Please revise section 3 regarding budget allocation.', $version->fresh()->rejection_notes);

        Notification::assertSentTo($author, DocumentApprovalResult::class, function ($notification) use ($version) {
            return $notification->status === 'rejected' &&
                $notification->notes === 'Please revise section 3 regarding budget allocation.';
        });
    }

    public function test_reviewer_can_reject_rollback_request_with_notes(): void
    {
        Notification::fake();

        $company = Company::create(['name' => 'PT Test', 'code' => 'TEST']);
        $branch = Branch::create(['company_id' => $company->id, 'name' => 'Pusat', 'is_pusat' => true]);
        $unitKerja = UnitKerja::create(['nama_unit_kerja' => 'HR', 'kode_unit_kerja' => '02']);

        $author = User::factory()->create(['unit_kerja_id' => $unitKerja->id, 'name' => 'Author User']);
        $author->companies()->attach($company->id);
        $author->branches()->attach($branch->id);

        $reviewer = User::factory()->create([
            'unit_kerja_id' => $unitKerja->id,
            'name' => 'HR Head',
            'system_role' => 'head',
        ]);
        $reviewer->companies()->attach($company->id);
        $reviewer->branches()->attach($branch->id);

        $docType = DocumentType::create(['name' => 'Internal Policy', 'code' => 'POL', 'category' => 'akreditasi']);
        $document = Document::create([
            'document_number' => '002/POL-02/TEST/IX/2026',
            'title' => 'Leave Policy',
            'document_type_id' => $docType->id,
            'owner_id' => $author->id,
            'unit_kerja_id' => $unitKerja->id,
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
        $unitKerja = UnitKerja::create(['nama_unit_kerja' => 'Operations', 'kode_unit_kerja' => '03']);

        $reviewer = User::factory()->create([
            'unit_kerja_id' => $unitKerja->id,
            'name' => 'Ops Head',
            'system_role' => 'head',
        ]);
        $reviewer->companies()->attach($company->id);
        $reviewer->branches()->attach($branch->id);

        $author = User::factory()->create(['unit_kerja_id' => $unitKerja->id, 'name' => 'Staff Alice']);
        $author->companies()->attach($company->id);
        $author->branches()->attach($branch->id);

        $docType = DocumentType::create(['name' => 'Manual', 'code' => 'MNL', 'category' => 'akreditasi']);
        $doc1 = Document::create([
            'document_number' => '001/MNL-03/TEST/IX/2026',
            'title' => 'Operating Standard Manual',
            'document_type_id' => $docType->id,
            'owner_id' => $author->id,
            'unit_kerja_id' => $unitKerja->id,
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
        $unitKerja = UnitKerja::create(['nama_unit_kerja' => 'Engineering', 'kode_unit_kerja' => '04']);

        $reviewer = User::factory()->create([
            'unit_kerja_id' => $unitKerja->id,
            'name' => 'Lead Engineer',
            'system_role' => 'head',
        ]);
        $reviewer->companies()->attach($company->id);
        $reviewer->branches()->attach($branch->id);

        $author = User::factory()->create(['unit_kerja_id' => $unitKerja->id, 'name' => 'Junior Dev']);
        $author->companies()->attach($company->id);
        $author->branches()->attach($branch->id);

        $docType = DocumentType::create(['name' => 'Architecture Guide', 'code' => 'ARC', 'category' => 'akreditasi']);
        $doc1 = Document::create(['document_number' => '001/ARC-04/TEST/IX/2026', 'title' => 'System Design A', 'document_type_id' => $docType->id, 'owner_id' => $author->id, 'unit_kerja_id' => $unitKerja->id, 'company_id' => $company->id, 'branch_id' => $branch->id, 'visibility' => 'general']);
        $doc2 = Document::create(['document_number' => '002/ARC-04/TEST/IX/2026', 'title' => 'System Design B', 'document_type_id' => $docType->id, 'owner_id' => $author->id, 'unit_kerja_id' => $unitKerja->id, 'company_id' => $company->id, 'branch_id' => $branch->id, 'visibility' => 'general']);

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
        $unitKerja = UnitKerja::create(['nama_unit_kerja' => 'Finance', 'kode_unit_kerja' => '05']);

        $reviewer = User::factory()->create([
            'unit_kerja_id' => $unitKerja->id,
            'name' => 'Reviewer Kadiv',
            'system_role' => 'head',
        ]);
        $reviewer->companies()->attach($company->id);
        $reviewer->branches()->attach($branch->id);

        $author = User::factory()->create(['unit_kerja_id' => $unitKerja->id, 'name' => 'Staff Finance']);
        $author->companies()->attach($company->id);
        $author->branches()->attach($branch->id);

        $docType = DocumentType::create(['name' => 'Standard SOP', 'code' => 'SOP', 'category' => 'akreditasi']);
        $document = Document::create([
            'document_number' => '001/SOP-05/TEST/IX/2026',
            'title' => 'Financial SOP Document',
            'document_type_id' => $docType->id,
            'owner_id' => $author->id,
            'unit_kerja_id' => $unitKerja->id,
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

    public function test_finish_editing_pdf_document_triggers_approval_notifications(): void
    {
        Notification::fake();

        $company = Company::create(['name' => 'PT Demo', 'code' => 'DEMO']);
        $branch = Branch::create(['company_id' => $company->id, 'name' => 'Cabang Demo', 'is_pusat' => true]);
        $unitKerja = UnitKerja::create(['nama_unit_kerja' => 'Legal', 'kode_unit_kerja' => '06']);

        $author = User::factory()->create(['unit_kerja_id' => $unitKerja->id, 'name' => 'Legal Staff']);
        $author->companies()->attach($company->id);
        $author->branches()->attach($branch->id);

        $head = User::factory()->create([
            'unit_kerja_id' => $unitKerja->id,
            'name' => 'Head Legal',
            'system_role' => 'head',
            'is_active' => true,
        ]);
        $head->companies()->attach($company->id);
        $head->branches()->attach($branch->id);

        $docType = DocumentType::create(['name' => 'Kontrak', 'code' => 'KTR', 'category' => 'akreditasi']);
        $document = Document::create([
            'document_number' => '001/KTR-06/DEMO/IX/2026',
            'title' => 'Perjanjian Kerjasama PDF',
            'document_type_id' => $docType->id,
            'owner_id' => $author->id,
            'unit_kerja_id' => $unitKerja->id,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'visibility' => 'unit_kerja',
        ]);

        $version = DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => 1,
            'author_id' => $author->id,
            'author_name' => $author->name,
            'status' => 'pending',
            'content' => '',
            'file_path' => 'documents/' . $document->id . '/v1.pdf',
            'file_original_name' => 'perjanjian.pdf',
            'file_mime' => 'application/pdf',
        ]);

        // Prior to finishing edit, approver is not resolved on document
        $this->assertNull($document->approver_id);

        // Author finishes editing the PDF document
        $response = $this->actingAs($author)
            ->post(route('documents.finish-editing', $document));

        $response->assertRedirect(route('documents.show', ['document' => $document, 'saving' => 1]));
        $response->assertSessionHas('success');

        // Document approver should be resolved to the unit kerja head
        $document->refresh();
        $this->assertSame($head->id, $document->approver_id);
        $this->assertSame('head', $document->approver_role);

        // Notifications must be sent:
        // 1. Head receives DocumentApprovalRequested
        Notification::assertSentTo(
            $head,
            DocumentApprovalRequested::class,
            function (DocumentApprovalRequested $notification) use ($document, $version, $author) {
                return $notification->document->id === $document->id &&
                       $notification->version->id === $version->id &&
                       $notification->authorName === $author->name;
            }
        );

        // 2. Author receives ApprovalRouteResolved
        Notification::assertSentTo(
            $author,
            ApprovalRouteResolved::class,
            function (ApprovalRouteResolved $notification) use ($document, $head) {
                return $notification->document->id === $document->id &&
                       $notification->approverRole === 'head' &&
                       str_contains($notification->routingMessage, $head->name);
            }
        );
    }

    public function test_document_is_locked_for_editing_when_version_is_pending_approval(): void
    {
        $company = Company::create(['name' => 'PT Test Lock', 'code' => 'LOCK']);
        $branch = Branch::create(['company_id' => $company->id, 'name' => 'Pusat', 'is_pusat' => true]);
        $unitKerja = UnitKerja::create(['nama_unit_kerja' => 'IT', 'kode_unit_kerja' => 'IT01']);

        $author = User::factory()->create(['unit_kerja_id' => $unitKerja->id, 'name' => 'Author IT']);
        $author->companies()->attach($company->id);
        $author->branches()->attach($branch->id);

        $docType = DocumentType::create(['name' => 'IT Policy', 'code' => 'POL', 'category' => 'internal']);
        $document = Document::create([
            'document_number' => '002/POL-IT/LOCK/IX/2026',
            'title' => 'Security Policy',
            'document_type_id' => $docType->id,
            'owner_id' => $author->id,
            'unit_kerja_id' => $unitKerja->id,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'visibility' => 'general',
        ]);

        $pendingVersion = DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => 1,
            'author_id' => $author->id,
            'author_name' => $author->name,
            'status' => 'pending',
            'document_name' => 'Security Policy',
            'content' => '<p>Security Policy content</p>',
            'file_path' => 'documents/test_lock.docx',
        ]);

        // Assert model method
        $this->assertTrue($document->isLockedForEditing());
        $this->assertEquals($pendingVersion->id, $document->pendingVersion()->id);

        // Assert policy forbids edit
        $this->assertFalse($author->can('edit', $document));

        // Assert edit route redirects with error
        $response = $this->actingAs($author)->get(route('documents.edit', $document));
        $response->assertRedirect(route('documents.show', $document));
        $response->assertSessionHas('error');

        // Assert upload-version route redirects with error when locked
        $uploadResponse = $this->actingAs($author)->post(route('documents.upload-version', $document));
        $uploadResponse->assertRedirect(route('documents.show', $document));
        $uploadResponse->assertSessionHas('error');

        // When version is rejected, document is unlocked
        $pendingVersion->update(['status' => 'rejected']);
        $document->refresh();
        $this->assertFalse($document->isLockedForEditing());
        $this->assertTrue($author->can('edit', $document));

        // When approved and becomes active, document is unlocked
        $pendingVersion->update(['status' => 'active']);
        $document->update(['current_version_id' => $pendingVersion->id]);
        $document->refresh();
        $this->assertFalse($document->isLockedForEditing());
        $this->assertTrue($author->can('edit', $document));
    }

    public function test_revision_reverts_signatures_to_placeholders_while_keeping_rejected_version_intact(): void
    {
        $company = Company::create(['name' => 'PT Test', 'code' => 'TEST']);
        $branch = Branch::create(['company_id' => $company->id, 'name' => 'Klinik Surabaya', 'is_pusat' => false]);

        $unitKerja = UnitKerja::create(['nama_unit_kerja' => 'K3', 'kode_unit_kerja' => '04']);
        $unitMutu = UnitKerja::create(['nama_unit_kerja' => 'Manajemen Mutu', 'kode_unit_kerja' => '01']);

        $author = User::factory()->create(['unit_kerja_id' => $unitKerja->id, 'name' => 'Author User']);
        $author->companies()->attach($company->id);
        $author->branches()->attach($branch->id);

        $spv = User::factory()->create(['unit_kerja_id' => $unitKerja->id, 'name' => 'SPV User', 'system_role' => 'head']);
        $spv->companies()->attach($company->id);
        $spv->branches()->attach($branch->id);

        $mutu = User::factory()->create(['unit_kerja_id' => $unitMutu->id, 'name' => 'Mutu User', 'system_role' => 'head']);
        $mutu->companies()->attach($company->id);
        $mutu->branches()->attach($branch->id);

        $pjKlinik = User::factory()->create(['name' => 'PJ Klinik User', 'system_role' => 'pic_klinik']);
        $pjKlinik->companies()->attach($company->id);
        $pjKlinik->branches()->attach($branch->id);
        $branch->update(['pic_klinik_id' => $pjKlinik->id]);
        $personalSigPj = $pjKlinik->signatures()->create([
            'type' => 'original',
            'file_path' => 'signatures/pj_klinik.png',
        ]);

        $docType = DocumentType::create(['name' => 'KAK', 'code' => 'KAK', 'category' => 'akreditasi']);
        $document = Document::create([
            'document_number' => '001/KAK-04/TEST/IX/2026',
            'title' => 'Dokumen Uji',
            'document_type_id' => $docType->id,
            'owner_id' => $author->id,
            'unit_kerja_id' => $unitKerja->id,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'visibility' => 'general',
        ]);

        $v1 = DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => 1,
            'author_id' => $author->id,
            'author_name' => $author->name,
            'status' => 'pending',
            'content' => '<p>Konten Dokumen</p><img data-ttd-user="SPV User" src="spv.png"><img data-ttd-user="Mutu User" src="mutu.png">',
        ]);

        $sigReqSpv = \App\Models\SignatureRequest::create([
            'requester_id' => $author->id,
            'target_user_id' => $spv->id,
            'document_id' => $document->id,
            'status' => 'approved',
            'is_used' => true,
        ]);

        $sigReqMutu = \App\Models\SignatureRequest::create([
            'requester_id' => $author->id,
            'target_user_id' => $mutu->id,
            'document_id' => $document->id,
            'status' => 'approved',
            'is_used' => true,
        ]);

        $sigReqPj = \App\Models\SignatureRequest::create([
            'requester_id' => $author->id,
            'target_user_id' => $pjKlinik->id,
            'document_id' => $document->id,
            'status' => 'pending',
        ]);

        // Step 1 SPV is approved
        $step1 = \App\Models\DocumentApprovalStep::create([
            'document_id' => $document->id,
            'version_id' => $v1->id,
            'signature_request_id' => $sigReqSpv->id,
            'step_order' => 1,
            'step_type' => 'head_approval',
            'step_name' => 'Review SPV',
            'assigned_user_id' => $spv->id,
            'assigned_role' => 'head',
            'status' => 'approved',
            'action_by_id' => $spv->id,
            'action_at' => now(),
        ]);

        // Step 2 Mutu is approved
        $step2 = \App\Models\DocumentApprovalStep::create([
            'document_id' => $document->id,
            'version_id' => $v1->id,
            'signature_request_id' => $sigReqMutu->id,
            'step_order' => 2,
            'step_type' => 'head_approval',
            'step_name' => 'Review Mutu',
            'assigned_user_id' => $mutu->id,
            'assigned_role' => 'head',
            'status' => 'approved',
            'action_by_id' => $mutu->id,
            'action_at' => now(),
        ]);

        // Step 3 PJ Klinik rejects
        $step3 = \App\Models\DocumentApprovalStep::create([
            'document_id' => $document->id,
            'version_id' => $v1->id,
            'signature_request_id' => $sigReqPj->id,
            'step_order' => 3,
            'step_type' => 'pic_klinik_approval',
            'step_name' => 'Pengesahan PJ Klinik',
            'assigned_user_id' => $pjKlinik->id,
            'assigned_role' => 'pic_klinik',
            'status' => 'pending',
        ]);

        // PJ Klinik rejects
        app(\App\Services\ApprovalRoutingService::class)->rejectApproval($step3, $pjKlinik, 'Perlu perbaikan format');

        // Assert v1 is marked rejected
        $v1->refresh();
        $this->assertEquals('rejected', $v1->status);
        $this->assertEquals('Perlu perbaikan format', $v1->review_notes);

        // Assert author accesses edit -> prepares v2 revision
        $response = $this->actingAs($author)->get(route('documents.edit', $document));
        $response->assertOk();

        // Assert v2 was created as draft with reverted content / placeholders intact
        $v2 = $document->versions()->where('version_number', 2)->first();
        $this->assertNotNull($v2);
        $this->assertStringContainsString('[ttd:SPV User]', $v2->content);
        $this->assertStringContainsString('[ttd:Mutu User]', $v2->content);

        // Assert all signature requests (SPV, Mutu, PJ Klinik) are reset to pending for v2 so layout & workflow remain intact
        $this->assertEquals('pending', $sigReqSpv->fresh()->status);
        $this->assertEquals('pending', $sigReqMutu->fresh()->status);
        $this->assertEquals('pending', $sigReqPj->fresh()->status);
        $this->assertFalse($sigReqSpv->fresh()->is_used);
        $this->assertFalse($sigReqMutu->fresh()->is_used);
        $this->assertFalse($sigReqPj->fresh()->is_used);

        // Assert v1 content remained unchanged for historical audit
        $this->assertStringContainsString('data-ttd-user="Mutu User"', $v1->fresh()->content);
    }

    public function test_rollback_request_sends_notification_to_head_and_shows_requester_in_notification_and_banner(): void
    {
        $company = Company::create(['name' => 'PT Test CMH', 'code' => 'CMH']);
        $branch = Branch::create(['company_id' => $company->id, 'name' => 'Cabang Utama', 'is_pusat' => true]);
        $unitKerja = UnitKerja::create(['nama_unit_kerja' => 'Tim K3', 'kode_unit_kerja' => '04']);

        $author = User::factory()->create([
            'unit_kerja_id' => $unitKerja->id,
            'name' => 'Staff Agus',
            'email' => 'agus.k3@cmh.test',
            'system_role' => 'staff',
            'is_active' => true,
        ]);
        $author->companies()->attach($company->id);
        $author->branches()->attach($branch->id);
        $author->unitKerjas()->attach($unitKerja->id);

        $head = User::factory()->create([
            'unit_kerja_id' => $unitKerja->id,
            'name' => 'Fajri SPV',
            'email' => 'fajri.spv@cmh.test',
            'system_role' => 'head',
            'is_active' => true,
        ]);
        $head->companies()->attach($company->id);
        $head->branches()->attach($branch->id);
        $head->unitKerjas()->attach($unitKerja->id);
        $unitKerja->update(['pic_user_id' => $head->id]);

        $docType = DocumentType::create(['name' => 'SOP Medis', 'code' => 'SOP']);
        $document = Document::create([
            'document_number' => '009/K3/SOP/2026',
            'title' => 'SOP Penanganan Limbah Medis',
            'document_type_id' => $docType->id,
            'owner_id' => $author->id,
            'unit_kerja_id' => $unitKerja->id,
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
            'content' => '<p>Versi 1</p>',
        ]);

        $v2 = DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => 2,
            'author_id' => $author->id,
            'author_name' => $author->name,
            'status' => 'active',
            'content' => '<p>Versi 2</p>',
        ]);

        $document->update(['current_version_id' => $v2->id]);

        // 1. Staff requests rollback to v1
        $response = $this->actingAs($author)->post(route('approvals.rollback', [$document, $v1]));
        $response->assertRedirect(route('documents.show', $document));

        // 2. Head receives notification in DB
        $headNotifs = $head->notifications()->get();
        $this->assertCount(1, $headNotifs);
        $notifData = $headNotifs->first()->data;
        $this->assertEquals('rollback_request', $notifData['type']);
        $this->assertEquals('Staff Agus', $notifData['actor_name']);
        $this->assertStringContainsString('Staff Agus', $notifData['message']);

        // 3. /notifications API endpoint returns notification for Head with requester name
        $apiResponse = $this->actingAs($head)->getJson(route('notifications.index'));
        $apiResponse->assertOk();
        $apiNotifs = $apiResponse->json('notifications');
        $this->assertCount(1, $apiNotifs);
        $this->assertEquals('Staff Agus', $apiNotifs[0]['actor_name']);
        $this->assertEquals('rollback_request', $apiNotifs[0]['type']);

        // 4. Head visits document show page: sees pending rollback banner with requester name
        $showResponse = $this->actingAs($head)->get(route('documents.show', $document));
        $showResponse->assertOk();
        $showResponse->assertSee(__('Menunggu Persetujuan Rollback'));
        $showResponse->assertSee('Staff Agus');
    }
}
