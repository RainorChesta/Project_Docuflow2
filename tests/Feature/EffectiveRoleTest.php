<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\Document;
use App\Models\DocumentDivisionShare;
use App\Models\DocumentShare;
use App\Models\User;
use App\Services\DocumentShareService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EffectiveRoleTest extends TestCase
{
    use RefreshDatabase;

    private DocumentShareService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DocumentShareService::class);
    }

    private function makeDocument(User $owner): Document
    {
        $division = Division::create(['code' => 'DIV', 'name' => 'Divisi']);
        $type = \App\Models\DocumentType::create(['code' => 'UMUM', 'name' => 'Umum']);

        return Document::create([
            'document_number' => 'DOC-' . uniqid(),
            'title' => 'Test Doc',
            'division_id' => $division->id,
            'document_type_id' => $type->id,
            'owner_id' => $owner->id,
            'general_access' => 'restricted',
        ]);
    }

    public function test_personal_viewer_only(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $doc = $this->makeDocument($owner);

        DocumentShare::create(['document_id' => $doc->id, 'user_id' => $user->id, 'role' => 'viewer', 'invited_by' => $owner->id]);

        $this->assertSame('viewer', $this->service->resolveEffectiveRole($doc, $user));
    }

    public function test_personal_editor_only(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $doc = $this->makeDocument($owner);

        DocumentShare::create(['document_id' => $doc->id, 'user_id' => $user->id, 'role' => 'editor', 'invited_by' => $owner->id]);

        $this->assertSame('editor', $this->service->resolveEffectiveRole($doc, $user));
    }

    public function test_division_viewer_only(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $division = Division::create(['code' => 'D1', 'name' => 'Div 1']);
        $user->division_id = $division->id;
        $user->save();
        $doc = $this->makeDocument($owner);

        DocumentDivisionShare::create(['document_id' => $doc->id, 'division_id' => $division->id, 'role' => 'viewer', 'invited_by' => $owner->id]);

        $this->assertSame('viewer', $this->service->resolveEffectiveRole($doc, $user));
    }

    public function test_division_editor_beats_personal_viewer(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $division = Division::create(['code' => 'D1', 'name' => 'Div 1']);
        $user->division_id = $division->id;
        $user->save();
        $doc = $this->makeDocument($owner);

        DocumentShare::create(['document_id' => $doc->id, 'user_id' => $user->id, 'role' => 'viewer', 'invited_by' => $owner->id]);
        DocumentDivisionShare::create(['document_id' => $doc->id, 'division_id' => $division->id, 'role' => 'editor', 'invited_by' => $owner->id]);

        $this->assertSame('editor', $this->service->resolveEffectiveRole($doc, $user));
    }

    public function test_personal_editor_beats_division_viewer(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $division = Division::create(['code' => 'D1', 'name' => 'Div 1']);
        $user->division_id = $division->id;
        $user->save();
        $doc = $this->makeDocument($owner);

        DocumentShare::create(['document_id' => $doc->id, 'user_id' => $user->id, 'role' => 'editor', 'invited_by' => $owner->id]);
        DocumentDivisionShare::create(['document_id' => $doc->id, 'division_id' => $division->id, 'role' => 'viewer', 'invited_by' => $owner->id]);

        $this->assertSame('editor', $this->service->resolveEffectiveRole($doc, $user));
    }

    public function test_multiple_divisions_highest_wins(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $d1 = Division::create(['code' => 'D1', 'name' => 'Div 1']);
        $d2 = Division::create(['code' => 'D2', 'name' => 'Div 2']);
        $user->divisions()->attach([$d1->id, $d2->id]);
        $doc = $this->makeDocument($owner);

        DocumentDivisionShare::create(['document_id' => $doc->id, 'division_id' => $d1->id, 'role' => 'viewer', 'invited_by' => $owner->id]);
        DocumentDivisionShare::create(['document_id' => $doc->id, 'division_id' => $d2->id, 'role' => 'editor', 'invited_by' => $owner->id]);

        $this->assertSame('editor', $this->service->resolveEffectiveRole($doc, $user));
    }

    public function test_no_share_restricted_returns_null(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $doc = $this->makeDocument($owner);

        $this->assertNull($this->service->resolveEffectiveRole($doc, $user));
    }

    public function test_no_share_anyone_with_link_returns_link_role(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $doc = $this->makeDocument($owner);
        $doc->update(['general_access' => 'anyone_with_link', 'link_role' => 'viewer']);

        $this->assertSame('viewer', $this->service->resolveEffectiveRole($doc, $user));
    }

    public function test_personal_share_beats_link_role(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $doc = $this->makeDocument($owner);
        $doc->update(['general_access' => 'anyone_with_link', 'link_role' => 'viewer']);

        DocumentShare::create(['document_id' => $doc->id, 'user_id' => $user->id, 'role' => 'editor', 'invited_by' => $owner->id]);

        $this->assertSame('editor', $this->service->resolveEffectiveRole($doc, $user));
    }

    public function test_owner_is_always_owner(): void
    {
        $owner = User::factory()->create();
        $doc = $this->makeDocument($owner);

        $this->assertSame('owner', $this->service->resolveEffectiveRole($doc, $owner));
    }

    public function test_effective_role_changes_after_share_removed(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $doc = $this->makeDocument($owner);

        $share = DocumentShare::create(['document_id' => $doc->id, 'user_id' => $user->id, 'role' => 'editor', 'invited_by' => $owner->id]);
        $this->assertSame('editor', $this->service->resolveEffectiveRole($doc, $user));

        $this->service->removeUserShare($share);
        $this->assertNull($this->service->resolveEffectiveRole($doc, $user));
    }
}