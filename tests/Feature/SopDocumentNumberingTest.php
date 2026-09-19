<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
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

        // Master Global Unit Kerja 11
        $unitKerja = UnitKerja::create([
            'kode_unit_kerja' => '11',
            'nama_unit_kerja' => 'Unit Operasional',
        ]);

        $sopType = DocumentType::create([
            'code' => 'SOP',
            'name' => 'Standard Operating Procedure',
            'category' => 'akreditasi',
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

    public function test_unit_kerja_can_be_used_across_different_branches(): void
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

        // Global Unit Kerja 11 can be used across multiple branches
        $unit11 = UnitKerja::create([
            'kode_unit_kerja' => '11',
            'nama_unit_kerja' => 'Layanan',
        ]);

        // Global Unit Kerja 25
        $unit25 = UnitKerja::create([
            'kode_unit_kerja' => '25',
            'nama_unit_kerja' => 'Logistik',
        ]);

        $sopType = DocumentType::create(['code' => 'SOP', 'name' => 'SOP', 'category' => 'akreditasi']);
        $service = app(DocumentService::class);

        $numberA = $service->generateId(null, $sopType, $branchA, $unit11);
        $numberB = $service->generateId(null, $sopType, $branchB, $unit25);

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
            'kode_unit_kerja' => '11',
            'nama_unit_kerja' => 'Unit Operasional',
        ]);
        $sopType = DocumentType::create(['code' => 'SOP', 'name' => 'SOP', 'category' => 'akreditasi']);

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
        $admin = User::factory()->create(['system_role' => 'admin']);

        // Index
        $response = $this->actingAs($admin)->get(route('admin.unit-kerja.index'));
        $response->assertOk();

        // View create form
        $createResp = $this->actingAs($admin)->get(route('admin.unit-kerja.create'));
        $createResp->assertOk();
        $createResp->assertSee(__('Kode Unit Kerja'));
        $createResp->assertSee(__('Nama Unit Kerja'));

        // Create
        $response = $this->actingAs($admin)->post(route('admin.unit-kerja.store'), [
            'kode_unit_kerja' => '11',
            'nama_unit_kerja' => 'Unit Operasional',
        ]);
        $response->assertRedirect(route('admin.unit-kerja.index'));
        $this->assertDatabaseHas('unit_kerjas', [
            'kode_unit_kerja' => '11',
            'nama_unit_kerja' => 'Unit Operasional',
        ]);

        // Duplicate code globally should fail
        $responseDuplicate = $this->actingAs($admin)->post(route('admin.unit-kerja.store'), [
            'kode_unit_kerja' => '11',
            'nama_unit_kerja' => 'Unit Operasional Lain',
        ]);
        $responseDuplicate->assertSessionHasErrors('kode_unit_kerja');

        // View edit form
        $unit = UnitKerja::where('kode_unit_kerja', '11')->first();
        $editResp = $this->actingAs($admin)->get(route('admin.unit-kerja.edit', $unit));
        $editResp->assertOk();
        $editResp->assertSee('Unit Operasional');

        // Update
        $responseUpdate = $this->actingAs($admin)->put(route('admin.unit-kerja.update', $unit), [
            'kode_unit_kerja' => '11',
            'nama_unit_kerja' => 'Unit Operasional & CS',
        ]);
        $responseUpdate->assertRedirect(route('admin.unit-kerja.index'));
        $this->assertDatabaseHas('unit_kerjas', [
            'id' => $unit->id,
            'nama_unit_kerja' => 'Unit Operasional & CS',
        ]);

        // Delete
        $responseDelete = $this->actingAs($admin)->delete(route('admin.unit-kerja.destroy', $unit));
        $responseDelete->assertRedirect(route('admin.unit-kerja.index'));
        $this->assertDatabaseMissing('unit_kerjas', ['id' => $unit->id]);
    }
}
