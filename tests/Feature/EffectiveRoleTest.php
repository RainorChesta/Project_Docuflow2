<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentShare;
use App\Models\DocumentType;
use App\Models\DocumentUnitKerjaShare;
use App\Models\UnitKerja;
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
        $unitKerja = UnitKerja::create(['kode_unit_kerja' => 'UK' . uniqid(), 'nama_unit_kerja' => 'Unit Kerja Test']);
        $type = DocumentType::create(['code' => 'UMUM' . uniqid(), 'name' => 'Umum', 'category' => 'akreditasi']);

        return Document::create([
            'document_number' => 'DOC-' . uniqid(),
            'title' => 'Test Doc',
            'unit_kerja_id' => $unitKerja->id,
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

    public function test_unit_kerja_viewer_only(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $unit = UnitKerja::create(['kode_unit_kerja' => 'U1', 'nama_unit_kerja' => 'Unit 1']);
        $user->unit_kerja_id = $unit->id;
        $user->save();
        $doc = $this->makeDocument($owner);

        DocumentUnitKerjaShare::create(['document_id' => $doc->id, 'unit_kerja_id' => $unit->id, 'role' => 'viewer', 'invited_by' => $owner->id]);

        $this->assertSame('viewer', $this->service->resolveEffectiveRole($doc, $user));
    }

    public function test_unit_kerja_editor_beats_personal_viewer(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $unit = UnitKerja::create(['kode_unit_kerja' => 'U1', 'nama_unit_kerja' => 'Unit 1']);
        $user->unit_kerja_id = $unit->id;
        $user->save();
        $doc = $this->makeDocument($owner);

        DocumentShare::create(['document_id' => $doc->id, 'user_id' => $user->id, 'role' => 'viewer', 'invited_by' => $owner->id]);
        DocumentUnitKerjaShare::create(['document_id' => $doc->id, 'unit_kerja_id' => $unit->id, 'role' => 'editor', 'invited_by' => $owner->id]);

        $this->assertSame('editor', $this->service->resolveEffectiveRole($doc, $user));
    }

    public function test_personal_editor_beats_unit_kerja_viewer(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $unit = UnitKerja::create(['kode_unit_kerja' => 'U1', 'nama_unit_kerja' => 'Unit 1']);
        $user->unit_kerja_id = $unit->id;
        $user->save();
        $doc = $this->makeDocument($owner);

        DocumentShare::create(['document_id' => $doc->id, 'user_id' => $user->id, 'role' => 'editor', 'invited_by' => $owner->id]);
        DocumentUnitKerjaShare::create(['document_id' => $doc->id, 'unit_kerja_id' => $unit->id, 'role' => 'viewer', 'invited_by' => $owner->id]);

        $this->assertSame('editor', $this->service->resolveEffectiveRole($doc, $user));
    }

    public function test_multiple_unit_kerjas_highest_wins(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $u1 = UnitKerja::create(['kode_unit_kerja' => 'U1', 'nama_unit_kerja' => 'Unit 1']);
        $u2 = UnitKerja::create(['kode_unit_kerja' => 'U2', 'nama_unit_kerja' => 'Unit 2']);
        $user->unitKerjas()->attach([$u1->id, $u2->id]);
        $doc = $this->makeDocument($owner);

        DocumentUnitKerjaShare::create(['document_id' => $doc->id, 'unit_kerja_id' => $u1->id, 'role' => 'viewer', 'invited_by' => $owner->id]);
        DocumentUnitKerjaShare::create(['document_id' => $doc->id, 'unit_kerja_id' => $u2->id, 'role' => 'editor', 'invited_by' => $owner->id]);

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

        $doc->update(['link_role' => 'editor']);
        $this->assertSame('editor', $this->service->resolveEffectiveRole($doc, $user));
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

    public function test_update_general_access_with_editor_role(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $doc = $this->makeDocument($owner);

        $this->service->updateGeneralAccess($doc, 'anyone_with_link', 'editor');
        $doc->refresh();

        $this->assertSame('anyone_with_link', $doc->general_access);
        $this->assertSame('editor', $doc->link_role);
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