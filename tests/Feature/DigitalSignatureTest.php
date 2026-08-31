<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Signature;
use App\Models\SignatureRequest;
use App\Models\User;
use App\Services\SignatureResolverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $base64Image = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $response = $this->actingAs($user)->postJson(route('profile.signature.store'), [
            'signature_data' => $base64Image,
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('signatures', [
            'user_id' => $user->id,
        ]);

        $this->assertTrue($user->fresh()->hasSignature());
    }

    public function test_user_can_delete_their_signature(): void
    {
        $user = User::factory()->create();
        $filePath = 'signatures/sig_test.png';
        Storage::disk('public')->put($filePath, 'fake image content');

        Signature::create([
            'user_id' => $user->id,
            'file_path' => $filePath,
        ]);

        $response = $this->actingAs($user)->deleteJson(route('profile.signature.destroy'));

        $response->assertStatus(200);
        $this->assertDatabaseMissing('signatures', ['user_id' => $user->id]);
        Storage::disk('public')->assertMissing($filePath);
    }

    public function test_available_users_endpoint_returns_user_list(): void
    {
        $user1 = User::factory()->create(['name' => 'Alice']);
        $user2 = User::factory()->create(['name' => 'Bob']);

        $response = $this->actingAs($user1)->getJson(route('signatures.users'));

        $response->assertStatus(200)
            ->assertJsonStructure(['users']);
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
}
