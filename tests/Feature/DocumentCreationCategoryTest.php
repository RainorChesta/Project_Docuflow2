<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\DocumentType;
use App\Models\Signature;
use App\Models\UnitKerja;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentCreationCategoryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Branch $branch;
    private UnitKerja $unitKerja;
    private DocumentType $naskahType;
    private DocumentType $akreditasiType;

    protected function setUp(): void
    {
        parent::setUp();

        $company = Company::create(['name' => 'PT Test Medika', 'code' => 'TM']);
        $this->branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Cabang Utama',
            'code' => 'JKT',
            'is_pusat' => true,
        ]);
        $this->unitKerja = UnitKerja::create([
            'kode_unit_kerja' => '01',
            'nama_unit_kerja' => 'Manajemen Mutu',
        ]);

        $this->user = User::factory()->create([
            'system_role' => 'staff',
            'unit_kerja_id' => $this->unitKerja->id,
        ]);
        $this->user->companies()->sync([$company->id]);
        $this->user->branches()->sync([$this->branch->id]);
        $this->user->unitKerjas()->sync([$this->unitKerja->id => ['branch_id' => $this->branch->id]]);

        Signature::create([
            'user_id' => $this->user->id,
            'file_path' => 'signatures/user.png',
            'type' => 'original',
            'created_via' => 'canvas',
        ]);

        $this->naskahType = DocumentType::create([
            'code' => 'S.ED',
            'name' => 'Surat Edaran',
            'category' => 'naskah_dinas',
        ]);

        $this->akreditasiType = DocumentType::create([
            'code' => 'SK',
            'name' => 'Keputusan Direktur',
            'category' => 'akreditasi',
        ]);
    }

    public function test_document_create_page_renders_category_filter_tabs_and_badges(): void
    {
        $response = $this->actingAs($this->user)->get(route('documents.create'));

        $response->assertStatus(200);
        $response->assertSee("categoryFilter: 'all'", false);
        $response->assertSee("categoryFilter = 'naskah_dinas'", false);
        $response->assertSee("categoryFilter = 'akreditasi'", false);
        $response->assertSee('Naskah Dinas');
        $response->assertSee('Dokumen Akreditasi');
        $response->assertSee('S.ED - Surat Edaran');
        $response->assertSee('SK - Keputusan Direktur');
    }

    public function test_numbering_format_reflects_document_category(): void
    {
        $service = app(DocumentService::class);

        // Naskah Dinas format does not have work unit code
        $naskahNumber = $service->formatNumber(1, $this->naskahType, $this->branch->code, 'X', 2026, $this->unitKerja);
        $this->assertSame('001/S.ED/JKT/X/2026', $naskahNumber);

        // Akreditasi format has work unit code
        $akreditasiNumber = $service->formatNumber(1, $this->akreditasiType, $this->branch->code, 'X', 2026, $this->unitKerja);
        $this->assertSame('001/SK-01/JKT/X/2026', $akreditasiNumber);
    }

    public function test_document_store_validates_unit_kerja_strictly_for_akreditasi(): void
    {
        // 1. Akreditasi without unit_kerja_id must fail
        $response = $this->actingAs($this->user)->post(route('documents.store'), [
            'title' => 'SK Akreditasi Dokumen',
            'document_type_id' => $this->akreditasiType->id,
            'branch_id' => $this->branch->id,
            'unit_kerja_id' => '', // empty
        ]);
        $response->assertSessionHasErrors('unit_kerja_id');

        // 2. Akreditasi with unit_kerja_id succeeds
        $responseSuccess = $this->actingAs($this->user)->post(route('documents.store'), [
            'title' => 'SK Akreditasi Dokumen',
            'document_type_id' => $this->akreditasiType->id,
            'branch_id' => $this->branch->id,
            'unit_kerja_id' => $this->unitKerja->id,
        ]);
        $responseSuccess->assertSessionHasNoErrors();
        $this->assertDatabaseHas('documents', [
            'title' => 'SK Akreditasi Dokumen',
            'document_type_id' => $this->akreditasiType->id,
            'unit_kerja_id' => $this->unitKerja->id,
        ]);
    }
}
