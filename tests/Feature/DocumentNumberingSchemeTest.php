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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentNumberingSchemeTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Branch $branchCabang;
    private Branch $branchPusat;
    private DocumentType $skAkreditasiType;
    private DocumentType $suratEdaranNaskahDinasType;
    private UnitKerja $unitKerja01;
    private UnitKerja $unitKerja02;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Company JBM
        $this->company = Company::create([
            'name' => 'PT JBM',
            'code' => 'JBM',
        ]);

        // Setup Cabang PT (MMC)
        $this->branchCabang = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Cabang MMC',
            'code' => 'MMC',
            'is_pusat' => false,
        ]);

        // Setup Cabang Pusat (JBM)
        $this->branchPusat = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Kantor Pusat JBM',
            'code' => 'JBM',
            'is_pusat' => true,
        ]);

        // Setup Dokumen Akreditasi Type SK
        $this->skAkreditasiType = DocumentType::create([
            'code' => 'SK',
            'name' => 'Surat Keputusan',
            'category' => 'akreditasi',
        ]);

        // Setup Naskah Dinas Type Surat Edaran (S.ED)
        $this->suratEdaranNaskahDinasType = DocumentType::create([
            'code' => 'S.ED',
            'name' => 'Surat Edaran',
            'category' => 'naskah_dinas',
        ]);

        // Setup Global Unit Kerja
        $this->unitKerja01 = UnitKerja::create([
            'kode_unit_kerja' => '01',
            'nama_unit_kerja' => 'Tim Manajemen Mutu',
        ]);

        $this->unitKerja02 = UnitKerja::create([
            'kode_unit_kerja' => '02',
            'nama_unit_kerja' => 'Tim Audit Internal',
        ]);
    }

    public function test_dokumen_akreditasi_numbering_format_includes_work_unit(): void
    {
        $service = app(DocumentService::class);
        $preview = $service->previewNumber(null, $this->skAkreditasiType, $this->branchCabang, $this->unitKerja01);
        $number = $service->generateId(null, $this->skAkreditasiType, $this->branchCabang, $this->unitKerja01);

        $now = Carbon::now();
        $expectedYear = (string) $now->year;
        $romanMonth = $this->getRomanMonth($now->month);

        // Format: {seq}/{TYPE}-{KODE_UK}/{CABANG}/{ROMAN_MONTH}/{YEAR}
        // Example: 001/SK-01/MMC/IX/2026
        $expectedFormat = sprintf('001/SK-01/MMC/%s/%s', $romanMonth, $expectedYear);

        $this->assertSame($expectedFormat, $number);
        $this->assertSame($expectedFormat, $preview);
    }

    public function test_naskah_dinas_numbering_format_excludes_work_unit(): void
    {
        $service = app(DocumentService::class);
        $preview = $service->previewNumber(null, $this->suratEdaranNaskahDinasType, $this->branchCabang, $this->unitKerja01);
        $number = $service->generateId(null, $this->suratEdaranNaskahDinasType, $this->branchCabang, $this->unitKerja01);

        $now = Carbon::now();
        $expectedYear = (string) $now->year;
        $romanMonth = $this->getRomanMonth($now->month);

        // Format: {seq}/{TYPE}/{CABANG}/{ROMAN_MONTH}/{YEAR}
        // Example: 001/S.ED/MMC/IX/2026
        $expectedFormat = sprintf('001/S.ED/MMC/%s/%s', $romanMonth, $expectedYear);

        $this->assertSame($expectedFormat, $number);
        $this->assertSame($expectedFormat, $preview);
    }

    public function test_sequential_number_increments_across_unit_kerjas_for_same_type_and_branch(): void
    {
        $service = app(DocumentService::class);

        // User at Cabang
        $author = User::factory()->create([
            'system_role' => 'user',
            'unit_kerja_id' => $this->unitKerja01->id,
        ]);
        $author->branches()->sync([$this->branchCabang->id]);
        $author->companies()->sync([$this->company->id]);

        // Create 1st document by Unit Kerja 01
        $num1 = $service->generateId(null, $this->skAkreditasiType, $this->branchCabang, $this->unitKerja01);
        $doc1 = Document::create([
            'document_number' => $num1,
            'title' => 'SK Dokumen 1',
            'owner_id' => $author->id,
            'company_id' => $this->company->id,
            'document_type_id' => $this->skAkreditasiType->id,
            'branch_id' => $this->branchCabang->id,
            'unit_kerja_id' => $this->unitKerja01->id,
            'format_choice' => 'lama',
        ]);

        $this->assertStringStartsWith('001/SK-01/MMC/', $doc1->document_number);

        // Preview and Generate 2nd document by Unit Kerja 02
        $preview2 = $service->previewNumber(null, $this->skAkreditasiType, $this->branchCabang, $this->unitKerja02);
        $num2 = $service->generateId(null, $this->skAkreditasiType, $this->branchCabang, $this->unitKerja02);

        // Must increment to 002 even though unit kerja is 02
        $this->assertStringStartsWith('002/SK-02/MMC/', $preview2);
        $this->assertStringStartsWith('002/SK-02/MMC/', $num2);
    }

    public function test_user_with_unit_kerja_is_verified(): void
    {
        $userWithoutUnit = User::factory()->create([
            'system_role' => 'user',
            'unit_kerja_id' => null,
        ]);
        $userWithoutUnit->companies()->sync([$this->company->id]);
        $userWithoutUnit->branches()->sync([$this->branchCabang->id]);

        $this->assertFalse($userWithoutUnit->isVerified());

        // Assign to Unit Kerja via foreign key
        $userWithoutUnit->unit_kerja_id = $this->unitKerja01->id;
        $userWithoutUnit->save();
        $userWithoutUnit->refresh();

        $this->assertTrue($userWithoutUnit->isVerified());

        // Assign to Unit Kerja via pivot table
        $userPivot = User::factory()->create([
            'system_role' => 'user',
            'unit_kerja_id' => null,
        ]);
        $userPivot->companies()->sync([$this->company->id]);
        $userPivot->branches()->sync([$this->branchCabang->id]);
        $userPivot->unitKerjas()->sync([$this->unitKerja02->id]);
        $userPivot->refresh();

        $this->assertTrue($userPivot->isVerified());
    }

    public function test_document_store_validates_unit_kerja_for_documents(): void
    {
        Storage::fake('local');

        $user = User::factory()->create([
            'system_role' => 'user',
            'unit_kerja_id' => $this->unitKerja01->id,
        ]);
        $user->companies()->sync([$this->company->id]);
        $user->branches()->sync([$this->branchCabang->id, $this->branchPusat->id]);

        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

        // Submitting Cabang document WITHOUT unit_kerja_id must fail validation
        $responseCabangFail = $this->actingAs($user)->post(route('documents.store'), [
            'title' => 'Test Cabang Missing UK',
            'document_type_id' => $this->skAkreditasiType->id,
            'branch_id' => $this->branchCabang->id,
            'source_type' => 'upload',
            'file' => $file,
        ]);
        $responseCabangFail->assertSessionHasErrors(['unit_kerja_id']);

        // Submitting Cabang document WITH unit_kerja_id must succeed
        $responseCabangSuccess = $this->actingAs($user)->post(route('documents.store'), [
            'title' => 'Test Cabang With UK',
            'document_type_id' => $this->skAkreditasiType->id,
            'branch_id' => $this->branchCabang->id,
            'unit_kerja_id' => $this->unitKerja01->id,
            'source_type' => 'upload',
            'file' => $file,
        ]);
        $responseCabangSuccess->assertRedirect();
        $this->assertDatabaseHas('documents', [
            'title' => 'Test Cabang With UK',
            'unit_kerja_id' => $this->unitKerja01->id,
            'branch_id' => $this->branchCabang->id,
        ]);
    }

    public function test_context_switching_with_unit_kerja(): void
    {
        $user = User::factory()->create([
            'system_role' => 'user',
            'unit_kerja_id' => $this->unitKerja01->id,
        ]);
        $user->companies()->sync([$this->company->id]);
        $user->branches()->sync([$this->branchCabang->id, $this->branchPusat->id]);
        $user->unitKerjas()->sync([$this->unitKerja01->id]);

        // Switch to Cabang PT with unit_kerja_id
        $respCabang = $this->actingAs($user)->post(route('context.switch'), [
            'company_id' => $this->company->id,
            'branch_id' => $this->branchCabang->id,
            'unit_kerja_id' => $this->unitKerja01->id,
        ], ['Accept' => 'application/json']);

        $respCabang->assertJson(['success' => true]);
        $this->assertEquals($this->unitKerja01->id, session('active_unit_kerja_id'));
    }

    public function test_admin_user_assignment_validation_with_unit_kerja(): void
    {
        $admin = User::factory()->create([
            'system_role' => 'admin',
        ]);

        // 1. Assigning to Branch without any Unit Kerja must fail
        $respFailCabang = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Cabang User No UK',
            'email' => 'cabang_no_uk@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'system_role' => 'user',
            'company_ids' => [$this->company->id],
            'branch_ids' => [$this->branchCabang->id],
            'unit_kerja_ids' => [],
        ]);
        $respFailCabang->assertSessionHasErrors(['unit_kerja_ids']);

        // 2. Assigning to Branch with Unit Kerja must succeed
        $respSuccessCabang = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Cabang User With UK',
            'email' => 'cabang_with_uk@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'system_role' => 'user',
            'company_ids' => [$this->company->id],
            'branch_ids' => [$this->branchCabang->id],
            'unit_kerja_ids' => [$this->unitKerja01->id, $this->unitKerja02->id],
        ]);
        $respSuccessCabang->assertRedirect(route('admin.users.index'));
        $createdUser = User::where('email', 'cabang_with_uk@example.com')->first();
        $this->assertNotNull($createdUser);
        $this->assertContains($this->unitKerja01->id, $createdUser->allUnitKerjaIds());
        $this->assertContains($this->unitKerja02->id, $createdUser->allUnitKerjaIds());

        // 3. Creating Unit Kerja globally succeeds
        $respCreateGlobalUk = $this->actingAs($admin)->post(route('admin.unit-kerja.store'), [
            'kode_unit_kerja' => '99',
            'nama_unit_kerja' => 'Unit Kerja Global Baru',
        ]);
        $respCreateGlobalUk->assertRedirect(route('admin.unit-kerja.index'));
        $this->assertDatabaseHas('unit_kerjas', ['kode_unit_kerja' => '99']);

        // 4. Duplicate kode_unit_kerja is rejected globally
        $respDuplicateUk = $this->actingAs($admin)->post(route('admin.unit-kerja.store'), [
            'kode_unit_kerja' => '99',
            'nama_unit_kerja' => 'Duplikat Unit Kerja',
        ]);
        $respDuplicateUk->assertSessionHasErrors(['kode_unit_kerja']);
    }

    public function test_multi_unit_kerja_document_creation_selection(): void
    {
        $user = User::factory()->create([
            'system_role' => 'user',
        ]);
        $user->companies()->sync([$this->company->id]);
        $user->branches()->sync([$this->branchCabang->id, $this->branchPusat->id]);
        $user->unitKerjas()->sync([$this->unitKerja01->id, $this->unitKerja02->id]);

        // 1. Next-number preview for Akreditasi Type at Unit Kerja 01
        $respPreviewAkreditasi = $this->actingAs($user)->getJson(route('documents.next-number', [
            'document_type_id' => $this->skAkreditasiType->id,
            'branch_id' => $this->branchCabang->id,
            'unit_kerja_id' => $this->unitKerja01->id,
        ]));
        $respPreviewAkreditasi->assertOk();
        $this->assertStringStartsWith('001/SK-01/MMC/', $respPreviewAkreditasi->json('number'));

        // 2. Next-number preview for Naskah Dinas Type at Unit Kerja 01
        $respPreviewNaskah = $this->actingAs($user)->getJson(route('documents.next-number', [
            'document_type_id' => $this->suratEdaranNaskahDinasType->id,
            'branch_id' => $this->branchCabang->id,
            'unit_kerja_id' => $this->unitKerja01->id,
        ]));
        $respPreviewNaskah->assertOk();
        $this->assertStringStartsWith('001/S.ED/MMC/', $respPreviewNaskah->json('number'));
    }

    private function getRomanMonth(int $month): string
    {
        $map = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];
        return $map[$month] ?? 'I';
    }
}
