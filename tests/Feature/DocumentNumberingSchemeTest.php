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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentNumberingSchemeTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Branch $branchCabang;
    private Branch $branchPusat;
    private DocumentType $skType;
    private UnitKerja $unitKerja01;
    private UnitKerja $unitKerja02;
    private Division $divisionSkrt;

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
            'code' => 'JBM-PST',
            'is_pusat' => true,
        ]);

        // Setup Document Type SK
        $this->skType = DocumentType::create([
            'code' => 'SK',
            'name' => 'Surat Keterangan',
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

        // Setup Division for Pusat
        $this->divisionSkrt = Division::create([
            'company_id' => $this->company->id,
            'code' => 'SKRT',
            'name' => 'Sekretariat',
        ]);
    }

    public function test_cabang_pt_document_numbering_format(): void
    {
        $service = app(DocumentService::class);
        $preview = $service->previewNumber(null, $this->skType, $this->branchCabang, $this->unitKerja01);
        $number = $service->generateId(null, $this->skType, $this->branchCabang, $this->unitKerja01);

        $now = Carbon::now();
        $expectedYear = (string) $now->year;
        $romanMonth = $this->getRomanMonth($now->month);

        // Format: {seq}/{TYPE}-{KODE_UK}/{CABANG}/{ROMAN_MONTH}/{YEAR}
        // Example: 001/SK-01/MMC/IX/2026
        $expectedFormat = sprintf('001/SK-01/MMC/%s/%s', $romanMonth, $expectedYear);

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
        $num1 = $service->generateId(null, $this->skType, $this->branchCabang, $this->unitKerja01);
        $doc1 = Document::create([
            'document_number' => $num1,
            'title' => 'SK Dokumen 1',
            'owner_id' => $author->id,
            'company_id' => $this->company->id,
            'document_type_id' => $this->skType->id,
            'branch_id' => $this->branchCabang->id,
            'unit_kerja_id' => $this->unitKerja01->id,
            'format_choice' => 'lama',
        ]);

        $this->assertStringStartsWith('001/SK-01/MMC/', $doc1->document_number);

        // Preview and Generate 2nd document by Unit Kerja 02
        $preview2 = $service->previewNumber(null, $this->skType, $this->branchCabang, $this->unitKerja02);
        $num2 = $service->generateId(null, $this->skType, $this->branchCabang, $this->unitKerja02);

        // Must increment to 002 even though unit kerja is 02
        $this->assertStringStartsWith('002/SK-02/MMC/', $preview2);
        $this->assertStringStartsWith('002/SK-02/MMC/', $num2);
    }

    public function test_cabang_pusat_document_numbering_format(): void
    {
        $service = app(DocumentService::class);
        $preview = $service->previewNumber($this->divisionSkrt, $this->skType, $this->branchPusat, null);
        $number = $service->generateId($this->divisionSkrt, $this->skType, $this->branchPusat, null);

        $now = Carbon::now();
        $expectedYear = (string) $now->year;
        $romanMonth = $this->getRomanMonth($now->month);

        // Pusat effective code defaults to Company code (JBM)
        $effectivePusatCode = $this->branchPusat->effective_code; // 'JBM'
        $expectedFormat = sprintf('001/SK/SKRT/%s/%s/%s', $effectivePusatCode, $romanMonth, $expectedYear);

        $this->assertSame($expectedFormat, $number);
        $this->assertSame($expectedFormat, $preview);
    }

    public function test_user_with_unit_kerja_is_verified(): void
    {
        $userWithoutUnitOrDiv = User::factory()->create([
            'system_role' => 'user',
            'division_id' => null,
            'unit_kerja_id' => null,
        ]);
        $userWithoutUnitOrDiv->companies()->sync([$this->company->id]);
        $userWithoutUnitOrDiv->branches()->sync([$this->branchCabang->id]);

        $this->assertFalse($userWithoutUnitOrDiv->isVerified());

        // Assign to Unit Kerja via foreign key
        $userWithoutUnitOrDiv->unit_kerja_id = $this->unitKerja01->id;
        $userWithoutUnitOrDiv->save();
        $userWithoutUnitOrDiv->refresh();

        $this->assertTrue($userWithoutUnitOrDiv->isVerified());

        // Assign to Unit Kerja via pivot table
        $userPivot = User::factory()->create([
            'system_role' => 'user',
            'division_id' => null,
            'unit_kerja_id' => null,
        ]);
        $userPivot->companies()->sync([$this->company->id]);
        $userPivot->branches()->sync([$this->branchCabang->id]);
        $userPivot->unitKerjas()->sync([$this->unitKerja02->id]);
        $userPivot->refresh();

        $this->assertTrue($userPivot->isVerified());
    }

    public function test_document_store_validates_unit_kerja_for_cabang_and_division_for_pusat(): void
    {
        Storage::fake('local');

        $user = User::factory()->create([
            'system_role' => 'user',
            'unit_kerja_id' => $this->unitKerja01->id,
            'division_id' => $this->divisionSkrt->id,
        ]);
        $user->companies()->sync([$this->company->id]);
        $user->branches()->sync([$this->branchCabang->id, $this->branchPusat->id]);

        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

        // Submitting Cabang document WITHOUT unit_kerja_id must fail validation
        $responseCabangFail = $this->actingAs($user)->post(route('documents.store'), [
            'title' => 'Test Cabang Missing UK',
            'document_type_id' => $this->skType->id,
            'branch_id' => $this->branchCabang->id,
            'source_type' => 'upload',
            'file' => $file,
        ]);
        $responseCabangFail->assertSessionHasErrors(['unit_kerja_id']);

        // Submitting Cabang document WITH unit_kerja_id must succeed
        $responseCabangSuccess = $this->actingAs($user)->post(route('documents.store'), [
            'title' => 'Test Cabang With UK',
            'document_type_id' => $this->skType->id,
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

        // Submitting Pusat document WITHOUT division_id must fail validation
        $responsePusatFail = $this->actingAs($user)->post(route('documents.store'), [
            'title' => 'Test Pusat Missing Div',
            'document_type_id' => $this->skType->id,
            'branch_id' => $this->branchPusat->id,
            'source_type' => 'upload',
            'file' => $file,
        ]);
        $responsePusatFail->assertSessionHasErrors(['division_id']);

        // Submitting Pusat document WITH division_id must succeed
        $responsePusatSuccess = $this->actingAs($user)->post(route('documents.store'), [
            'title' => 'Test Pusat With Div',
            'document_type_id' => $this->skType->id,
            'branch_id' => $this->branchPusat->id,
            'division_id' => $this->divisionSkrt->id,
            'source_type' => 'upload',
            'file' => $file,
        ]);
        $responsePusatSuccess->assertRedirect();
        $this->assertDatabaseHas('documents', [
            'title' => 'Test Pusat With Div',
            'division_id' => $this->divisionSkrt->id,
            'branch_id' => $this->branchPusat->id,
        ]);
    }

    public function test_context_switching_between_pusat_and_cabang_with_division_and_unit_kerja(): void
    {
        $user = User::factory()->create([
            'system_role' => 'user',
            'division_id' => $this->divisionSkrt->id,
            'unit_kerja_id' => $this->unitKerja01->id,
        ]);
        $user->companies()->sync([$this->company->id]);
        $user->branches()->sync([$this->branchCabang->id, $this->branchPusat->id]);
        $user->divisions()->sync([$this->divisionSkrt->id]);
        $user->unitKerjas()->sync([$this->unitKerja01->id]);

        // Switch to Cabang PT with unit_kerja_id
        $respCabang = $this->actingAs($user)->post(route('context.switch'), [
            'company_id' => $this->company->id,
            'branch_id' => $this->branchCabang->id,
            'unit_kerja_id' => $this->unitKerja01->id,
        ], ['Accept' => 'application/json']);

        $respCabang->assertJson(['success' => true]);
        $this->assertEquals($this->unitKerja01->id, session('active_unit_kerja_id'));
        $this->assertNull(session('active_division_id'));

        // Switch to Cabang Pusat with division_id
        $respPusat = $this->actingAs($user)->post(route('context.switch'), [
            'company_id' => $this->company->id,
            'branch_id' => $this->branchPusat->id,
            'division_id' => $this->divisionSkrt->id,
        ], ['Accept' => 'application/json']);

        $respPusat->assertJson(['success' => true]);
        $this->assertEquals($this->divisionSkrt->id, session('active_division_id'));
        $this->assertNull(session('active_unit_kerja_id'));
    }

    public function test_admin_user_assignment_validation_for_cabang_and_pusat(): void
    {
        $admin = User::factory()->create([
            'system_role' => 'admin',
        ]);

        // 1. Assigning to Cabang PT without any Unit Kerja must fail
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

        // 2. Assigning to Cabang PT with Unit Kerja must succeed
        $respSuccessCabang = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Cabang User With UK',
            'email' => 'cabang_with_uk@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'system_role' => 'user',
            'company_ids' => [$this->company->id],
            'branch_ids' => [$this->branchCabang->id],
            'division_ids' => [$this->divisionSkrt->id],
            'unit_kerja_ids' => [$this->unitKerja01->id, $this->unitKerja02->id],
        ]);
        $respSuccessCabang->assertRedirect(route('admin.users.index'));
        $createdUser = User::where('email', 'cabang_with_uk@example.com')->first();
        $this->assertNotNull($createdUser);
        $this->assertContains($this->unitKerja01->id, $createdUser->allUnitKerjaIds());
        $this->assertContains($this->unitKerja02->id, $createdUser->allUnitKerjaIds());
        $this->assertContains($this->divisionSkrt->id, $createdUser->allDivisionIds());

        // 3. Assigning to Cabang Pusat without any Division must fail
        $respFailPusat = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Pusat User No Div',
            'email' => 'pusat_no_div@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'system_role' => 'user',
            'company_ids' => [$this->company->id],
            'branch_ids' => [$this->branchPusat->id],
            'division_ids' => [],
        ]);
        $respFailPusat->assertSessionHasErrors(['division_ids']);

        // 4. Assigning to Cabang Pusat with Division must succeed
        $respSuccessPusat = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Pusat User With Div',
            'email' => 'pusat_with_div@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'system_role' => 'user',
            'company_ids' => [$this->company->id],
            'branch_ids' => [$this->branchPusat->id],
            'division_ids' => [$this->divisionSkrt->id],
        ]);
        $respSuccessPusat->assertRedirect(route('admin.users.index'));
        $createdPusatUser = User::where('email', 'pusat_with_div@example.com')->first();
        $this->assertNotNull($createdPusatUser);
        $this->assertContains($this->divisionSkrt->id, $createdPusatUser->allDivisionIds());
        $this->assertEmpty($createdPusatUser->allUnitKerjaIds());

        // 5. Creating Unit Kerja globally without cabang_id succeeds
        $respCreateGlobalUk = $this->actingAs($admin)->post(route('admin.unit-kerja.store'), [
            'kode_unit_kerja' => '99',
            'nama_unit_kerja' => 'Unit Kerja Global Baru',
        ]);
        $respCreateGlobalUk->assertRedirect(route('admin.unit-kerja.index'));
        $this->assertDatabaseHas('unit_kerjas', ['kode_unit_kerja' => '99']);

        // 6. Duplicate kode_unit_kerja is rejected globally
        $respDuplicateUk = $this->actingAs($admin)->post(route('admin.unit-kerja.store'), [
            'kode_unit_kerja' => '99',
            'nama_unit_kerja' => 'Duplikat Unit Kerja',
        ]);
        $respDuplicateUk->assertSessionHasErrors(['kode_unit_kerja']);
    }

    public function test_multi_division_and_multi_unit_kerja_document_creation_selection(): void
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

        // Second division at Pusat for testing multi-division
        $divisionIt = Division::create(['code' => 'IT', 'name' => 'Divisi IT']);

        $user = User::factory()->create([
            'system_role' => 'user',
        ]);
        $user->companies()->sync([$this->company->id]);
        $user->branches()->sync([$this->branchCabang->id, $this->branchPusat->id]);
        $user->divisions()->sync([$this->divisionSkrt->id, $divisionIt->id]);
        $user->unitKerjas()->sync([$this->unitKerja01->id, $this->unitKerja02->id]);

        // 1. Next-number preview for Unit Kerja 01 at Cabang
        $respPreviewUk1 = $this->actingAs($user)->getJson(route('documents.next-number', [
            'document_type_id' => $this->skType->id,
            'branch_id' => $this->branchCabang->id,
            'unit_kerja_id' => $this->unitKerja01->id,
        ]));
        $respPreviewUk1->assertOk();
        $this->assertStringStartsWith('001/SK-01/MMC/', $respPreviewUk1->json('number'));

        // 2. Next-number preview for Unit Kerja 02 at Cabang
        $respPreviewUk2 = $this->actingAs($user)->getJson(route('documents.next-number', [
            'document_type_id' => $this->skType->id,
            'branch_id' => $this->branchCabang->id,
            'unit_kerja_id' => $this->unitKerja02->id,
        ]));
        $respPreviewUk2->assertOk();
        $this->assertStringStartsWith('001/SK-02/MMC/', $respPreviewUk2->json('number'));

        // 3. Next-number preview for Division SKRT at Pusat
        $respPreviewDivSkrt = $this->actingAs($user)->getJson(route('documents.next-number', [
            'document_type_id' => $this->skType->id,
            'branch_id' => $this->branchPusat->id,
            'division_id' => $this->divisionSkrt->id,
        ]));
        $respPreviewDivSkrt->assertOk();
        $this->assertStringStartsWith('001/SK/SKRT/JBM/', $respPreviewDivSkrt->json('number'));

        // 4. Next-number preview for Division IT at Pusat
        $respPreviewDivIt = $this->actingAs($user)->getJson(route('documents.next-number', [
            'document_type_id' => $this->skType->id,
            'branch_id' => $this->branchPusat->id,
            'division_id' => $divisionIt->id,
        ]));
        $respPreviewDivIt->assertOk();
        $this->assertStringStartsWith('001/SK/IT/JBM/', $respPreviewDivIt->json('number'));
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
