<?php

namespace Tests\Feature;

use App\Exceptions\BusinessLogicException;
use App\Models\Document;
use App\Models\DocumentAccessLink;
use App\Models\User;
use App\Services\AccessLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShareLinkDuplicateTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $admin;
    private Document $document;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['division_id' => 1]);
        $this->admin = User::factory()->create(['system_role' => 'admin', 'is_active' => true]);
        $this->document = Document::create([
            'document_number' => 'TST/001',
            'title' => 'Test Doc',
            'visibility' => 'division',
            'division_id' => 1,
            'owner_id' => $this->owner->id,
        ]);
    }

    #[Test]
    public function generating_first_link_creates_it(): void
    {
        $link = app(AccessLinkService::class)->create($this->document, 'viewer', null, $this->owner);

        $this->assertNotNull($link->id);
        $this->assertDatabaseHas('document_access_links', [
            'document_id' => $this->document->id,
            'role' => 'viewer',
        ]);
        $this->assertCount(1, $this->document->accessLinks()->get());
    }

    #[Test]
    public function duplicate_same_role_active_link_is_rejected(): void
    {
        $service = app(AccessLinkService::class);
        $service->create($this->document, 'viewer', null, $this->owner);

        try {
            $service->create($this->document, 'viewer', null, $this->owner);
            $this->fail('BusinessLogicException was not thrown');
        } catch (BusinessLogicException $e) {
            $this->assertStringContainsString("'viewer'", $e->getMessage());
        }

        $this->assertCount(1, $this->document->accessLinks()->get());
    }

    #[Test]
    public function different_role_link_can_coexist(): void
    {
        $service = app(AccessLinkService::class);
        $service->create($this->document, 'viewer', null, $this->owner);
        $service->create($this->document, 'editor', null, $this->owner);

        $this->assertCount(2, $this->document->accessLinks()->get());
        $this->assertCount(1, $this->document->accessLinks()->where('role', 'viewer')->get());
        $this->assertCount(1, $this->document->accessLinks()->where('role', 'editor')->get());
    }

    #[Test]
    public function role_available_again_after_expiration(): void
    {
        $service = app(AccessLinkService::class);
        $service->create($this->document, 'viewer', now()->addDay(), $this->owner);

        $this->travel(2)->days();

        $new = $service->create($this->document, 'viewer', null, $this->owner);

        $this->assertNotNull($new->id);
        $this->assertCount(1, $this->document->accessLinks()->get());
        $this->assertNull($this->document->accessLinks()->first()->expires_at);
    }

    #[Test]
    public function role_available_again_after_revocation(): void
    {
        $service = app(AccessLinkService::class);
        $link = $service->create($this->document, 'viewer', null, $this->owner);
        $service->revoke($link);

        $new = $service->create($this->document, 'viewer', null, $this->owner);

        $this->assertNotNull($new->id);
        $this->assertCount(1, $this->document->accessLinks()->get());
    }

    #[Test]
    public function route_returns_existing_link_with_notice_on_duplicate(): void
    {
        $service = app(AccessLinkService::class);
        $existing = $service->create($this->document, 'viewer', null, $this->admin);

        $response = $this->actingAs($this->admin)
            ->post(route('links.store', $this->document), ['role' => 'viewer']);

        $response->assertRedirect();
        $response->assertSessionHas('share_link', route('shared.documents', $existing->token));
        $response->assertSessionHas('notice');
        $this->assertCount(1, $this->document->accessLinks()->get());
    }

    #[Test]
    public function concurrent_requests_cannot_create_duplicates(): void
    {
        DB::table('document_access_links')->insert([
            'document_id' => $this->document->id,
            'token' => str_repeat('a', 64),
            'role' => 'viewer',
            'expires_at' => null,
            'created_by' => $this->admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::transaction(function () {
            app(AccessLinkService::class)->create($this->document, 'viewer', null, $this->admin);
        });
    }
}
