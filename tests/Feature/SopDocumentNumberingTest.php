<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Division;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\UnitKerja;
use App\Models\User;
use App\Services\DocumentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SopDocumentNumberingTest extends TestCase
{
    use RefreshDatabase;

    public function test_sop_document_numbering_format_matches_specification(): void
    {
        // Setup: Branch CDC-DIP under PT CMH
        $company = Company::create(['name' => 'PT CMH', 'code' => 'CMH']);
        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'CDC Diponegoro',
            'is_pusat' => false,
            'code' => 'CDC-DIP',
        ]);

        // Master Unit Kerja 11 in branch CDC-DIP
        $unitKerja = UnitKerja::create([
            'cabang_id' => $branch->id,
            'kode_unit_kerja' => '11',
            'nama_unit_kerja' => 'Unit Operasional',
        ]);

        $sopType = DocumentType::create([
            'code' => 'SOP',
            'name' => 'Standard Operating Procedure',
        ]);

        $service = app(DocumentService::class);
        $preview = $service->previewNumber(null, $sopType, $branch, $unitKerja);
        $number = $service->generateId(null, $sopType, $branch, $unitKerja);

        $now = Carbon::now();
        $expectedYear = $now->year;
        $segments = explode('/', $number);

        // 5 Segments: {nomor_surat}/SOP-{unit_kerja}/{kode_cabang}/{bulan_romawi}/{tahun}
        // Example: 001/SOP-11/CDC-DIP/I/2023
        $this->assertCount(5, $segments);
        $this->assertSame('001', $segments[0]);
        $this->assertSame('SOP-11', $segments[1]);
        $this->assertSame('CDC-DIP', $segments[2]);
        $this->assertSame((string) $expectedYear, $segments[4]);

        $this->assertStringContainsString('/SOP-11/CDC-DIP/', $preview);
        $this->assertStringContainsString('/SOP-11/CDC-DIP/', $number);
    }

    public function test_unit_kerja_can_differ_between_branches(): void
    {
        $company = Company::create(['name' => 'PT CMH', 'code' => 'CMH']);
        
        $branchA = Branch::create([
            'company_id' => $company->id,
            'name' => 'CDC Diponegoro',
            'is_pusat' => false,
            'code' => 'CDC-DIP',
        ]);

        $branchB = Branch::create([
            'company_id' => $company->id,
            'name' => 'CDC Surabaya',
            'is_pusat' => false,
            'code' => 'CDC-SBY',
        ]);

        // Branch A has Unit Kerja 11
        $unit11A = UnitKerja::create([
            'cabang_id' => $branchA->id,
            'kode_unit_kerja' => '11',
            'nama_unit_kerja' => 'Layanan Diponegoro',
        ]);

        // Branch B also has Unit Kerja 11 (allowed because cabang_id is different)
        $unit11B = UnitKerja::create([
            'cabang_id' => $branchB->id,
            'kode_unit_kerja' => '11',
            'nama_unit_kerja' => 'Layanan Surabaya',
        ]);

        // Branch B has Unit Kerja 25
        $unit25B = UnitKerja::create([
            'cabang_id' => $branchB->id,
            'kode_unit_kerja' => '25',
            'nama_unit_kerja' => 'Logistik Surabaya',
        ]);

        $sopType = DocumentType::create(['code' => 'SOP', 'name' => 'SOP']);
        $service = app(DocumentService::class);

        $numberA = $service->generateId(null, $sopType, $branchA, $unit11A);
        $numberB = $service->generateId(null, $sopType, $branchB, $unit25B);

        $this->assertStringContainsString('/SOP-11/CDC-DIP/', $numberA);
        $this->assertStringContainsString('/SOP-25/CDC-SBY/', $numberB);
    }

    public function test_api_next_number_preview_returns_correct_sop_number(): void
    {
        $company = Company::create(['name' => 'PT CMH', 'code' => 'CMH']);
        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'CDC Diponegoro',
            'is_pusat' => false,
            'code' => 'CDC-DIP',
        ]);
        $unitKerja = UnitKerja::create([
            'cabang_id' => $branch->id,
            'kode_unit_kerja' => '11',
            'nama_unit_kerja' => 'Unit Operasional',
        ]);
        $sopType = DocumentType::create(['code' => 'SOP', 'name' => 'SOP']);

        $user = User::factory()->create([
            'system_role' => 'admin',
        ]);
        $user->branches()->sync([$branch->id]);

        $response = $this->actingAs($user)->getJson(route('documents.next-number', [
            'document_type_id' => $sopType->id,
            'branch_id' => $branch->id,
            'unit_kerja_id' => $unitKerja->id,
        ]));

        $response->assertOk();
        $response->assertJsonStructure(['number']);
        $this->assertStringContainsString('/SOP-11/CDC-DIP/', $response->json('number'));
    }

    public function test_admin_can_manage_unit_kerja(): void
    {
        $company = Company::create(['name' => 'PT CMH', 'code' => 'CMH']);
        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'CDC Diponegoro',
            'is_pusat' => false,
            'code' => 'CDC-DIP',
        ]);

        $admin = User::factory()->create(['system_role' => 'admin']);

        // Index
        $response = $this->actingAs($admin)->get(route('admin.unit-kerja.index'));
        $response->assertOk();

        // View create form with cascading dropdown
        $createResp = $this->actingAs($admin)->get(route('admin.unit-kerja.create'));
        $createResp->assertOk();
        $createResp->assertSee(__('Perusahaan'));
        $createResp->assertSee('selectedCompanyId', false);
        $createResp->assertSee('selectedCabangId', false);
        $createResp->assertSee($company->name);
        $createResp->assertSee($branch->name);

        // Create
        $response = $this->actingAs($admin)->post(route('admin.unit-kerja.store'), [
            'company_id' => $company->id,
            'cabang_id' => $branch->id,
            'kode_unit_kerja' => '11',
            'nama_unit_kerja' => 'Unit Operasional',
        ]);
        $response->assertRedirect(route('admin.unit-kerja.index', ['cabang_id' => $branch->id]));
        $this->assertDatabaseHas('unit_kerjas', [
            'cabang_id' => $branch->id,
            'kode_unit_kerja' => '11',
            'nama_unit_kerja' => 'Unit Operasional',
        ]);

        // Duplicate code in same branch should fail
        $responseDuplicate = $this->actingAs($admin)->post(route('admin.unit-kerja.store'), [
            'company_id' => $company->id,
            'cabang_id' => $branch->id,
            'kode_unit_kerja' => '11',
            'nama_unit_kerja' => 'Unit Operasional Lain',
        ]);
        $responseDuplicate->assertSessionHasErrors('kode_unit_kerja');

        // View edit form with cascading dropdown prefill
        $unit = UnitKerja::where('cabang_id', $branch->id)->where('kode_unit_kerja', '11')->first();
        $editResp = $this->actingAs($admin)->get(route('admin.unit-kerja.edit', $unit));
        $editResp->assertOk();
        $editResp->assertSee(__('Perusahaan'));
        $editResp->assertSee($company->name);
        $editResp->assertSee($branch->name);
        $editResp->assertSee('Unit Operasional');

        // Update
        $responseUpdate = $this->actingAs($admin)->put(route('admin.unit-kerja.update', $unit), [
            'cabang_id' => $branch->id,
            'kode_unit_kerja' => '11',
            'nama_unit_kerja' => 'Unit Operasional & CS',
        ]);
        $responseUpdate->assertRedirect(route('admin.unit-kerja.index', ['cabang_id' => $branch->id]));
        $this->assertDatabaseHas('unit_kerjas', [
            'id' => $unit->id,
            'nama_unit_kerja' => 'Unit Operasional & CS',
        ]);

        // Delete
        $responseDelete = $this->actingAs($admin)->delete(route('admin.unit-kerja.destroy', $unit));
        $responseDelete->assertRedirect(route('admin.unit-kerja.index', ['cabang_id' => $branch->id]));
        $this->assertDatabaseMissing('unit_kerjas', ['id' => $unit->id]);
    }
}
