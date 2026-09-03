<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Division;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentFormatFilterAndBadgeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private DocumentType $docType;
    private Branch $branch;
    private Division $division;
    private Document $newDoc;
    private Document $oldDoc;

    protected function setUp(): void
    {
        parent::setUp();

        $company = Company::create(['name' => 'PT CMH Test', 'code' => 'CMH']);
        $this->branch = Branch::create(['name' => 'Branch Central', 'code' => 'BC', 'company_id' => $company->id]);
        $this->division = Division::create(['name' => 'Operations', 'code' => 'OPS']);
        $this->docType = DocumentType::create(['name' => 'Surat Keputusan', 'code' => 'SK']);

        $this->user = User::factory()->create([
            'system_role' => 'staff',
            'division_id' => $this->division->id,
            'is_active' => true,
        ]);
        $this->user->branches()->attach($this->branch->id);
        $this->user->companies()->attach($company->id);

        $this->user->divisions()->attach($this->division->id);

        $this->newDoc = Document::create([
            'title' => 'Dokumen Baru Standard',
            'document_number' => '001/SK/OPS/BC/IX/2026',
            'format_choice' => 'baru',
            'visibility' => Document::VISIBILITY_DIVISION,
            'owner_id' => $this->user->id,
            'division_id' => $this->division->id,
            'branch_id' => $this->branch->id,
            'company_id' => $company->id,
            'document_type_id' => $this->docType->id,
        ]);
        $v1 = $this->newDoc->versions()->create([
            'version_number' => 1,
            'content' => '<p>Konten baru</p>',
            'status' => 'active',
            'author_id' => $this->user->id,
            'author_name' => $this->user->name,
        ]);
        $this->newDoc->update(['current_version_id' => $v1->id]);

        $this->oldDoc = Document::create([
            'title' => 'Dokumen Lama Legacy',
            'document_number' => '002/SK-OPS/BC/IX/2026',
            'format_choice' => 'lama',
            'visibility' => Document::VISIBILITY_DIVISION,
            'owner_id' => $this->user->id,
            'division_id' => $this->division->id,
            'branch_id' => $this->branch->id,
            'company_id' => $company->id,
            'document_type_id' => $this->docType->id,
        ]);
        $v2 = $this->oldDoc->versions()->create([
            'version_number' => 1,
            'content' => '<p>Konten lama</p>',
            'status' => 'active',
            'author_id' => $this->user->id,
            'author_name' => $this->user->name,
        ]);
        $this->oldDoc->update(['current_version_id' => $v2->id]);
    }

    public function test_document_model_format_helpers(): void
    {
        $this->assertTrue($this->newDoc->isNewFormat());
        $this->assertFalse($this->newDoc->isOldFormat());

        $this->assertTrue($this->oldDoc->isOldFormat());
        $this->assertFalse($this->oldDoc->isNewFormat());

        $baruCount = Document::formatChoice('baru')->count();
        $lamaCount = Document::formatChoice('lama')->count();

        $this->assertEquals(1, $baruCount);
        $this->assertEquals(1, $lamaCount);
    }

    public function test_filter_documents_by_new_format(): void
    {
        $response = $this->actingAs($this->user)->get(route('documents.index', [
            'type' => 'division',
            'format_choice' => 'baru',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Dokumen Baru Standard');
        $response->assertDontSee('Dokumen Lama Legacy');
    }

    public function test_filter_documents_by_old_format(): void
    {
        $response = $this->actingAs($this->user)->get(route('documents.index', [
            'type' => 'division',
            'format_choice' => 'lama',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Dokumen Lama Legacy');
        $response->assertDontSee('Dokumen Baru Standard');
    }

    public function test_format_badges_rendered_in_document_list(): void
    {
        app()->setLocale('id');

        $response = $this->actingAs($this->user)->get(route('documents.index', [
            'type' => 'division',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Format Baru');
        $response->assertSee('Format Lama');
    }

    public function test_format_badge_rendered_in_document_show(): void
    {
        app()->setLocale('id');

        $response = $this->actingAs($this->user)->get(route('documents.show', $this->newDoc));
        $response->assertStatus(200);
        $response->assertSee('Format Baru');

        $responseOld = $this->actingAs($this->user)->get(route('documents.show', $this->oldDoc));
        $responseOld->assertStatus(200);
        $responseOld->assertSee('Format Lama');
    }

    public function test_format_badges_rendered_in_english(): void
    {
        app()->setLocale('en');

        $response = $this->actingAs($this->user)->get(route('documents.show', $this->newDoc));
        $response->assertStatus(200);
        $response->assertSee('New Format');

        $responseOld = $this->actingAs($this->user)->get(route('documents.show', $this->oldDoc));
        $responseOld->assertStatus(200);
        $responseOld->assertSee('Old Format');
    }

    public function test_global_search_filters_by_format_choice(): void
    {
        $responseNew = $this->actingAs($this->user)->getJson(route('search', [
            'q' => 'Dokumen',
            'format_choice' => 'baru',
        ]));

        $responseNew->assertStatus(200);
        $responseNew->assertJsonFragment(['title' => 'Dokumen Baru Standard', 'format_choice' => 'baru']);
        $responseNew->assertJsonMissing(['title' => 'Dokumen Lama Legacy']);

        $responseOld = $this->actingAs($this->user)->getJson(route('search', [
            'q' => 'Dokumen',
            'format_choice' => 'lama',
        ]));

        $responseOld->assertStatus(200);
        $responseOld->assertJsonFragment(['title' => 'Dokumen Lama Legacy', 'format_choice' => 'lama']);
        $responseOld->assertJsonMissing(['title' => 'Dokumen Baru Standard']);
    }
}
