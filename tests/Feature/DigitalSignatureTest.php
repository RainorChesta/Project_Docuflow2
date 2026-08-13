<?php

namespace Tests\Feature;

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
}
