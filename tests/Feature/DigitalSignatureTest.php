<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Division;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Signature;
use App\Models\SignatureRequest;
use App\Models\User;
use App\Services\SignatureResolverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DigitalSignatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_user_can_save_signature_drawn_on_canvas(): void
    {
        $user = User::factory()->create();

        // 1x1 transparent PNG base64 string
        $base64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

        $response = $this->actingAs($user)->postJson(route('profile.signature.store'), [
            'signature_data' => $base64,
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('signatures', ['user_id' => $user->id]);
        $this->assertTrue($user->fresh()->hasSignature());
    }

    public function test_user_can_delete_their_signature(): void
    {
        $user = User::factory()->create();
        $filePath = 'signatures/test_' . $user->id . '.png';
        Storage::disk('public')->put($filePath, 'fake content');

        Signature::create([
            'user_id' => $user->id,
            'file_path' => $filePath,
        ]);

        $response = $this->actingAs($user)->deleteJson(route('profile.signature.destroy'));

        $response->assertStatus(200);
        $this->assertDatabaseMissing('signatures', ['user_id' => $user->id]);
        Storage::disk('public')->assertMissing($filePath);
    }

    public function test_user_cannot_save_company_stamp_without_original_signature(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['name' => 'PT Jaya Bersama Makmur', 'code' => 'JBM']);
        $user->companies()->attach($company->id);

        $file = UploadedFile::fake()->image('stamp.png', 200, 100);

        $response = $this->actingAs($user)->postJson(route('profile.signature.store'), [
            'type' => 'company_stamp',
            'company_id' => $company->id,
            'signature_image' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
        $this->assertStringContainsString('HARUS MEMBUAT TANDA TANGAN ORIGINAL', $response->json('message'));
    }

    public function test_user_can_save_company_stamp_and_replacing_it_deletes_old_file(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['name' => 'PT Jaya Bersama Makmur', 'code' => 'JBM']);
        $user->companies()->attach($company->id);

        // First, create original signature
        $base64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
        $this->actingAs($user)->postJson(route('profile.signature.store'), [
            'type' => 'original',
            'signature_data' => $base64,
        ])->assertStatus(200);

        // Upload first stamp
        $file1 = UploadedFile::fake()->image('stamp1.png', 200, 100);
        $res1 = $this->actingAs($user)->postJson(route('profile.signature.store'), [
            'type' => 'company_stamp',
            'company_id' => $company->id,
            'signature_image' => $file1,
        ]);
        $res1->assertStatus(200)->assertJson(['success' => true]);

        $stamp1 = Signature::where('user_id', $user->id)->where('type', 'company_stamp')->where('company_id', $company->id)->first();
        $this->assertNotNull($stamp1);
        $oldFilePath = $stamp1->file_path;
        Storage::disk('public')->assertExists($oldFilePath);

        // Upload replacement stamp for same company
        $file2 = UploadedFile::fake()->image('stamp2.png', 200, 100);
        $res2 = $this->actingAs($user)->postJson(route('profile.signature.store'), [
            'type' => 'company_stamp',
            'company_id' => $company->id,
            'signature_image' => $file2,
        ]);
        $res2->assertStatus(200)->assertJson(['success' => true]);

        // Verify only 1 stamp exists for this company and old file was removed
        $stampCount = Signature::where('user_id', $user->id)->where('type', 'company_stamp')->where('company_id', $company->id)->count();
        $this->assertEquals(1, $stampCount);
        Storage::disk('public')->assertMissing($oldFilePath);
    }

    public function test_user_cannot_draw_on_canvas_if_signature_already_exists(): void
    {
        $user = User::factory()->create();

        $base64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
        $res1 = $this->actingAs($user)->postJson(route('profile.signature.store'), [
            'type' => 'original',
            'signature_data' => $base64,
        ]);
        $res1->assertStatus(200);

        $sig1 = $user->signatures()->where('type', 'original')->first();
        $this->assertNotNull($sig1);
        $oldPath = $sig1->file_path;
        Storage::disk('public')->assertExists($oldPath);

        // Attempting to draw new signature via canvas is blocked
        $res2 = $this->actingAs($user)->postJson(route('profile.signature.store'), [
            'type' => 'original',
            'signature_data' => $base64,
        ]);
        $res2->assertStatus(422);
        $this->assertStringContainsString('Canvas tanda tangan tidak dapat digunakan lagi', $res2->json('message'));

        // Deleting the existing signature unlocks canvas
        $delRes = $this->actingAs($user)->deleteJson(route('profile.signature.destroy'));
        $delRes->assertStatus(200);
        $this->assertFalse($user->fresh()->hasSignature());
        Storage::disk('public')->assertMissing($oldPath);

        // Now user can draw again
        $res3 = $this->actingAs($user)->postJson(route('profile.signature.store'), [
            'type' => 'original',
            'signature_data' => $base64,
        ]);
        $res3->assertStatus(200);
        $this->assertTrue($user->fresh()->hasSignature());
    }

    public function test_profile_page_renders_locked_canvas_when_user_has_signature(): void
    {
        $user = User::factory()->create();
        $base64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
        $this->actingAs($user)->postJson(route('profile.signature.store'), [
            'type' => 'original',
            'signature_data' => $base64,
        ]);

        $res = $this->actingAs($user)->get(route('profile.edit'));
        $res->assertStatus(200);
        $res->assertSee(__('Canvas Dinonaktifkan'));
        $res->assertSee('canvas-locked-overlay');
        $res->assertSee('pointer-events-none cursor-not-allowed');
    }

    public function test_profile_page_renders_digital_signature_view_matching_reference(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['name' => 'PT Jaya Bersama Makmur', 'code' => 'JBM']);
        $user->companies()->attach($company->id);

        $base64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
        $this->actingAs($user)->postJson(route('profile.signature.store'), [
            'type' => 'original',
            'signature_data' => $base64,
        ]);

        $res = $this->actingAs($user)->get(route('profile.edit'));
        $res->assertStatus(200);
        $res->assertSee('Digital Signature');
        $res->assertSee('Kelola tanda tangan original dan stempel perusahaan Anda.');
        $res->assertSee('TTD Original Aktif');
        $res->assertSee('Tambah Tanda Tangan Baru');
        $res->assertSee('Current Saved Signature');
        $res->assertSee('Original');
    }

    public function test_available_users_endpoint_returns_user_list(): void
    {
        $user1 = User::factory()->create(['name' => 'Alice']);
        $user2 = User::factory()->create(['name' => 'Bob']);

        $response = $this->actingAs($user1)->getJson(route('signatures.users'));

        $response->assertStatus(200)
            ->assertJsonStructure(['users']);
    }

    public function test_available_users_filters_signatures_by_document_company_context(): void
    {
        $companyA = Company::create(['name' => 'PT Company A', 'code' => 'PTA']);
        $companyB = Company::create(['name' => 'PT Company B', 'code' => 'PTB']);

        $user = User::factory()->create(['name' => 'Charlie']);

        // Original signature
        $sigOrig = Signature::create([
            'user_id' => $user->id,
            'type' => 'original',
            'file_path' => 'signatures/orig.png',
        ]);
        // Stamp for Company A
        $sigStampA = Signature::create([
            'user_id' => $user->id,
            'type' => 'company_stamp',
            'company_id' => $companyA->id,
            'file_path' => 'signatures/stamp_a.png',
        ]);
        // Stamp for Company B
        $sigStampB = Signature::create([
            'user_id' => $user->id,
            'type' => 'company_stamp',
            'company_id' => $companyB->id,
            'file_path' => 'signatures/stamp_b.png',
        ]);

        $docType = DocumentType::create(['name' => 'Surat', 'code' => 'SRT']);
        $documentA = Document::create([
            'document_number' => '001/SRT/2026',
            'title' => 'Doc Company A',
            'document_type_id' => $docType->id,
            'owner_id' => $user->id,
            'company_id' => $companyA->id,
            'visibility' => 'general',
        ]);

        $response = $this->actingAs($user)->getJson(route('signatures.users', ['document_id' => $documentA->id]));

        $response->assertStatus(200);
        $userData = collect($response->json('users'))->firstWhere('id', $user->id);
        $this->assertNotNull($userData);

        $signatureIds = collect($userData['signatures'])->pluck('id')->all();
        $this->assertContains($sigOrig->id, $signatureIds);
        $this->assertContains($sigStampA->id, $signatureIds);
        $this->assertNotContains($sigStampB->id, $signatureIds);
    }

    public function test_signature_resolver_handles_self_and_cross_user_requests(): void
    {
        $author = User::factory()->create(['name' => 'AuthorUser']);
        $targetUser = User::factory()->create(['name' => 'HeadUser']);

        $filePath = 'signatures/sig_head.png';
        Storage::disk('public')->put($filePath, 'fake signature content');
        Signature::create([
            'user_id' => $targetUser->id,
            'file_path' => $filePath,
        ]);

        $docType = DocumentType::create(['name' => 'Surat Edaran', 'code' => 'S.ED']);
        $document = Document::create([
            'document_number' => '001/S.ED/DIV/2026',
            'title' => 'Test Signature Doc',
            'document_type_id' => $docType->id,
            'owner_id' => $author->id,
            'visibility' => 'general',
        ]);

        $resolver = new SignatureResolverService();

        // 1. Initial resolution auto-creates pending request
        $htmlContent = '<p>Dokumen disetujui oleh [ttd:HeadUser].</p>';
        $resolvedHtml = $resolver->resolve($htmlContent, $document, $author);

        $this->assertStringContainsString('⏳ [TTD Menunggu Approval: HeadUser]', $resolvedHtml);
        $this->assertDatabaseHas('signature_requests', [
            'requester_id' => $author->id,
            'target_user_id' => $targetUser->id,
            'document_id' => $document->id,
            'status' => 'pending',
        ]);

        // 2. Target user approves request
        $sigRequest = SignatureRequest::first();
        $this->actingAs($targetUser)->post(route('signatures.requests.approve', $sigRequest));

        $this->assertEquals('approved', $sigRequest->fresh()->status);

        // 3. Resolution now renders target user's signature image
        $resolvedHtmlAfterApproval = $resolver->resolve($htmlContent, $document, $author);
        $this->assertStringContainsString('<img src=', $resolvedHtmlAfterApproval);
        $this->assertStringContainsString('alt="TTD HeadUser"', $resolvedHtmlAfterApproval);
    }

    public function test_mandatory_signature_guard_blocks_user_without_signature(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withHeaders(['X-Test-Enforce-Signature' => '1'])
            ->get(route('dashboard'));

        $response->assertRedirect(route('profile.edit', ['must_sign' => 1]));
    }

    public function test_target_user_approval_notifies_requester_and_allows_one_time_consumption(): void
    {
        \Illuminate\Support\Facades\Notification::fake();

        $requester = User::factory()->create(['name' => 'RequesterUser']);
        $targetUser = User::factory()->create(['name' => 'TargetSigner']);

        $filePath = 'signatures/sig_target.png';
        Storage::disk('public')->put($filePath, 'fake signature content');
        Signature::create([
            'user_id' => $targetUser->id,
            'file_path' => $filePath,
        ]);

        $docType = DocumentType::create(['name' => 'Surat Keputusan', 'code' => 'SK']);
        $document = Document::create([
            'document_number' => '002/SK/DIV/2026',
            'title' => 'Important Decision Doc',
            'document_type_id' => $docType->id,
            'owner_id' => $requester->id,
            'visibility' => 'general',
        ]);

        $sigRequest = SignatureRequest::create([
            'requester_id' => $requester->id,
            'target_user_id' => $targetUser->id,
            'document_id' => $document->id,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        // 1. Target user approves request
        $this->actingAs($targetUser)->post(route('signatures.requests.approve', $sigRequest));

        $this->assertTrue($sigRequest->fresh()->isApproved());
        $this->assertTrue($sigRequest->fresh()->isAvailable());

        // Assert requester received notification
        \Illuminate\Support\Facades\Notification::assertSentTo(
            $requester,
            \App\Notifications\SignatureRequestApprovedNotification::class
        );

        // 2. Requester checks available users API
        $resp = $this->actingAs($requester)->getJson(route('signatures.users', ['document_id' => $document->id]));
        $resp->assertStatus(200)
            ->assertJson([
                'available_to_replace_count' => 1,
            ]);

        // 3. First consumption (should succeed)
        $consumeResp = $this->actingAs($requester)->postJson(route('signatures.requests.consume', $sigRequest));
        $consumeResp->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertTrue($sigRequest->fresh()->isUsed());
        $this->assertFalse($sigRequest->fresh()->isAvailable());

        // 4. Second consumption attempt (must fail due to 1-to-1 rule)
        $secondConsumeResp = $this->actingAs($requester)->postJson(route('signatures.requests.consume', $sigRequest));
        $secondConsumeResp->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_requester_receives_notification_when_signature_request_is_rejected(): void
    {
        \Illuminate\Support\Facades\Notification::fake();

        $division = Division::create(['name' => 'IT Dept', 'code' => 'IT']);
        $requester = User::factory()->create(['division_id' => $division->id, 'name' => 'Requester User']);
        $targetUser = User::factory()->create(['division_id' => $division->id, 'name' => 'Signer User']);

        $docType = DocumentType::create(['name' => 'Surat Keputusan', 'code' => 'SK']);
        $document = Document::create([
            'document_number' => '003/SK/DIV/2026',
            'title' => 'Document Requiring Signature',
            'document_type_id' => $docType->id,
            'owner_id' => $requester->id,
            'visibility' => 'general',
        ]);

        $sigRequest = SignatureRequest::create([
            'requester_id' => $requester->id,
            'target_user_id' => $targetUser->id,
            'document_id' => $document->id,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        // Target user rejects request with reason
        $response = $this->actingAs($targetUser)->post(route('signatures.requests.reject', $sigRequest), [
            'reason' => 'Draft needs more detail',
        ]);

        $response->assertSessionHas('success');
        $this->assertTrue($sigRequest->fresh()->isRejected());
        $this->assertSame('Draft needs more detail', $sigRequest->fresh()->rejected_reason);

        // Assert requester received rejection notification
        \Illuminate\Support\Facades\Notification::assertSentTo(
            $requester,
            \App\Notifications\SignatureRequestRejectedNotification::class,
            function ($notification) use ($document, $targetUser) {
                $data = $notification->toArray($targetUser);
                return $data['type'] === 'signature_request_rejected'
                    && $data['icon'] === 'rejected'
                    && $data['document_id'] === $document->id
                    && $data['reason'] === 'Draft needs more detail';
            }
        );
    }

    public function test_cross_company_or_branch_signer_can_view_document_preview_only(): void
    {
        $companyA = \App\Models\Company::create(['name' => 'Company A', 'code' => 'CMPA']);
        $branchA = \App\Models\Branch::create(['company_id' => $companyA->id, 'name' => 'Branch A', 'is_pusat' => true]);

        $companyB = \App\Models\Company::create(['name' => 'Company B', 'code' => 'CMPB']);
        $branchB = \App\Models\Branch::create(['company_id' => $companyB->id, 'name' => 'Branch B', 'is_pusat' => true]);

        $authorA = User::factory()->create(['name' => 'Author Company A']);
        $authorA->companies()->sync([$companyA->id]);
        $authorA->branches()->sync([$branchA->id]);

        $signerB = User::factory()->create(['name' => 'Signer Company B']);
        $signerB->companies()->sync([$companyB->id]);
        $signerB->branches()->sync([$branchB->id]);

        $otherUserB = User::factory()->create(['name' => 'Unrelated User Company B']);
        $otherUserB->companies()->sync([$companyB->id]);
        $otherUserB->branches()->sync([$branchB->id]);

        $docType = DocumentType::create(['name' => 'Memo Internal', 'code' => 'MI']);
        $document = Document::create([
            'document_number' => '004/MI/CMPA/2026',
            'title' => 'Cross Company Doc',
            'document_type_id' => $docType->id,
            'company_id' => $companyA->id,
            'branch_id' => $branchA->id,
            'owner_id' => $authorA->id,
            'visibility' => 'personal',
        ]);

        // Unrelated user from Company B cannot view the document
        $this->actingAs($otherUserB)->get(route('documents.show', $document))
            ->assertStatus(403);

        // Signer B without request cannot view yet
        $this->actingAs($signerB)->get(route('documents.show', $document))
            ->assertStatus(403);

        // Create signature request for Signer B
        SignatureRequest::create([
            'requester_id' => $authorA->id,
            'target_user_id' => $signerB->id,
            'document_id' => $document->id,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        // Signer B can now view the document and preview
        $this->actingAs($signerB)->get(route('documents.show', $document))
            ->assertStatus(200);

        // Signer B cannot edit/update the document
        $this->actingAs($signerB)->get(route('documents.edit', $document))
            ->assertStatus(403);

        // Signer B cannot delete the document
        $this->actingAs($signerB)->delete(route('documents.destroy', $document))
            ->assertStatus(403);
    }

    public function test_signature_requests_index_filters_by_status_and_search(): void
    {
        $targetUser = User::factory()->create(['name' => 'Approver Person']);
        $requester1 = User::factory()->create(['name' => 'Alice Request']);
        $requester2 = User::factory()->create(['name' => 'Bob Request']);

        $docType = DocumentType::create(['name' => 'Standard Letter', 'code' => 'SL']);
        $doc1 = Document::create([
            'document_number' => '001/SL/2026',
            'title' => 'Important Contract Agreement',
            'document_type_id' => $docType->id,
            'owner_id' => $requester1->id,
            'visibility' => 'general',
        ]);
        $doc2 = Document::create([
            'document_number' => '002/SL/2026',
            'title' => 'Internal Memorandum',
            'document_type_id' => $docType->id,
            'owner_id' => $requester2->id,
            'visibility' => 'general',
        ]);

        $req1 = SignatureRequest::create([
            'requester_id' => $requester1->id,
            'target_user_id' => $targetUser->id,
            'document_id' => $doc1->id,
            'status' => 'pending',
            'requested_at' => now(),
        ]);
        $req2 = SignatureRequest::create([
            'requester_id' => $requester2->id,
            'target_user_id' => $targetUser->id,
            'document_id' => $doc2->id,
            'status' => 'approved',
            'requested_at' => now()->subDay(),
            'responded_at' => now(),
        ]);

        // 1. Filter by status 'pending'
        $responsePending = $this->actingAs($targetUser)->get(route('signatures.requests.index', ['status' => 'pending']));
        $responsePending->assertStatus(200);
        $responsePending->assertSee('Important Contract Agreement');
        $responsePending->assertDontSee('Internal Memorandum');

        // 2. Filter by status 'approved'
        $responseApproved = $this->actingAs($targetUser)->get(route('signatures.requests.index', ['status' => 'approved']));
        $responseApproved->assertStatus(200);
        $responseApproved->assertSee('Internal Memorandum');
        $responseApproved->assertDontSee('Important Contract Agreement');

        // 3. Search query
        $responseSearch = $this->actingAs($targetUser)->get(route('signatures.requests.index', ['search' => 'Contract']));
        $responseSearch->assertStatus(200);
        $responseSearch->assertSee('Important Contract Agreement');
        $responseSearch->assertDontSee('Internal Memorandum');
    }

    public function test_bulk_approve_signature_requests(): void
    {
        \Illuminate\Support\Facades\Notification::fake();

        $targetUser = User::factory()->create(['name' => 'Manager User']);
        $filePath = 'signatures/sig_mgr.png';
        Storage::disk('public')->put($filePath, 'fake signature png');
        Signature::create(['user_id' => $targetUser->id, 'file_path' => $filePath]);

        $requester = User::factory()->create(['name' => 'Staff User']);
        $docType = DocumentType::create(['name' => 'Memo', 'code' => 'MM']);
        $doc1 = Document::create(['document_number' => '001/MM/2026', 'title' => 'Doc 1', 'document_type_id' => $docType->id, 'owner_id' => $requester->id, 'visibility' => 'general']);
        $doc2 = Document::create(['document_number' => '002/MM/2026', 'title' => 'Doc 2', 'document_type_id' => $docType->id, 'owner_id' => $requester->id, 'visibility' => 'general']);

        $req1 = SignatureRequest::create(['requester_id' => $requester->id, 'target_user_id' => $targetUser->id, 'document_id' => $doc1->id, 'status' => 'pending', 'requested_at' => now()]);
        $req2 = SignatureRequest::create(['requester_id' => $requester->id, 'target_user_id' => $targetUser->id, 'document_id' => $doc2->id, 'status' => 'pending', 'requested_at' => now()]);

        $response = $this->actingAs($targetUser)->post(route('signatures.requests.bulk-approve'), [
            'request_ids' => [$req1->id, $req2->id],
        ]);

        $response->assertSessionHas('success');
        $this->assertSame('approved', $req1->fresh()->status);
        $this->assertSame('approved', $req2->fresh()->status);

        \Illuminate\Support\Facades\Notification::assertSentTo(
            $requester,
            \App\Notifications\SignatureRequestApprovedNotification::class
        );
    }

    public function test_approve_all_pending_signature_requests(): void
    {
        \Illuminate\Support\Facades\Notification::fake();

        $targetUser = User::factory()->create(['name' => 'Director User']);
        $requester = User::factory()->create(['name' => 'Staff 2']);
        $docType = DocumentType::create(['name' => 'Letter', 'code' => 'LTR']);
        $doc = Document::create(['document_number' => '001/LTR/2026', 'title' => 'Pending Letter', 'document_type_id' => $docType->id, 'owner_id' => $requester->id, 'visibility' => 'general']);

        $req = SignatureRequest::create(['requester_id' => $requester->id, 'target_user_id' => $targetUser->id, 'document_id' => $doc->id, 'status' => 'pending', 'requested_at' => now()]);

        $response = $this->actingAs($targetUser)->post(route('signatures.requests.approve-all-pending'));

        $response->assertSessionHas('success');
        $this->assertSame('approved', $req->fresh()->status);
    }

    public function test_bulk_reject_signature_requests(): void
    {
        \Illuminate\Support\Facades\Notification::fake();

        $targetUser = User::factory()->create(['name' => 'Rejector User']);
        $requester = User::factory()->create(['name' => 'Requester']);
        $docType = DocumentType::create(['name' => 'Contract', 'code' => 'CTR']);
        $doc1 = Document::create(['document_number' => '001/CTR/2026', 'title' => 'Contract 1', 'document_type_id' => $docType->id, 'owner_id' => $requester->id, 'visibility' => 'general']);
        $doc2 = Document::create(['document_number' => '002/CTR/2026', 'title' => 'Contract 2', 'document_type_id' => $docType->id, 'owner_id' => $requester->id, 'visibility' => 'general']);

        $req1 = SignatureRequest::create(['requester_id' => $requester->id, 'target_user_id' => $targetUser->id, 'document_id' => $doc1->id, 'status' => 'pending', 'requested_at' => now()]);
        $req2 = SignatureRequest::create(['requester_id' => $requester->id, 'target_user_id' => $targetUser->id, 'document_id' => $doc2->id, 'status' => 'pending', 'requested_at' => now()]);

        $response = $this->actingAs($targetUser)->post(route('signatures.requests.bulk-reject'), [
            'request_ids' => [$req1->id, $req2->id],
            'reason' => 'Bulk reject reason',
        ]);

        $response->assertSessionHas('success');
        $this->assertSame('rejected', $req1->fresh()->status);
        $this->assertSame('rejected', $req2->fresh()->status);
        $this->assertSame('Bulk reject reason', $req1->fresh()->rejected_reason);
    }
}
