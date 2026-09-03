<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SignatureStoreValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_cannot_upload_signature_image_larger_than_2mb(): void
    {
        $user = User::factory()->create();

        // 3MB image file (3072 KB)
        $largeFile = UploadedFile::fake()->image('signature.png')->size(3072);

        $response = $this->actingAs($user)->postJson(route('profile.signature.store'), [
            'type' => 'original',
            'signature_image' => $largeFile,
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'success',
            'message',
            'errors' => [
                'signature_image',
            ],
        ]);
        $this->assertFalse($response->json('success'));
        $this->assertStringContainsString('2MB', $response->json('message'));
    }

    public function test_can_upload_valid_signature_image_under_2mb(): void
    {
        $user = User::factory()->create();

        // 500KB image file
        $file = UploadedFile::fake()->image('signature.png', 200, 100)->size(500);

        $response = $this->actingAs($user)->postJson(route('profile.signature.store'), [
            'type' => 'original',
            'signature_image' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('signatures', [
            'user_id' => $user->id,
            'type' => 'original',
            'created_via' => 'upload',
        ]);
    }
}
