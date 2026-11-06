<?php

namespace Tests\Feature\Archiving;

use App\Models\ArchiveClassification;
use App\Models\ArchiveNumberingScheme;
use App\Models\Branch;
use App\Models\Company;
use App\Models\UnitKerja;
use App\Models\User;
use App\Services\ArchiveNumberingService;
use App\Services\ArchiveRegistryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArchiveNumberingConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected ArchiveNumberingService $numberingService;
    protected Company $companyA;
    protected Company $companyB;
    protected Branch $branchA;
    protected UnitKerja $unitA;
    protected ArchiveClassification $classification;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('id');

        $this->numberingService = app(ArchiveNumberingService::class);

        $this->companyA = Company::create(['name' => 'PT DOKUFLOW SATU', 'code' => 'DKF']);
        $this->companyB = Company::create(['name' => 'PT DOKUFLOW DUA', 'code' => 'MED']);

        $this->branchA = Branch::create([
            'company_id' => $this->companyA->id,
            'name' => 'KANTOR PUSAT',
            'code' => 'PST',
            'is_pusat' => true,
        ]);

        $this->unitA = UnitKerja::create([
            'nama_unit_kerja' => 'SUMBER DAYA MANUSIA',
            'kode_unit_kerja' => 'SDM',
        ]);

        $this->classification = ArchiveClassification::create([
            'company_id' => $this->companyA->id,
            'code' => 'SOP-01',
            'name' => 'Standar Operasional Prosedur',
            'confidentiality_level' => 'internal',
            'requires_approval' => false,
        ]);
    }

    public function test_sequential_number_allocation(): void
    {
        $alloc1 = $this->numberingService->allocateNumber(
            company: $this->companyA,
            branch: $this->branchA,
            unit: $this->unitA,
            classification: $this->classification
        );

        $alloc2 = $this->numberingService->allocateNumber(
            company: $this->companyA,
            branch: $this->branchA,
            unit: $this->unitA,
            classification: $this->classification
        );

        $this->assertEquals(1, $alloc1['sequence']);
        $this->assertEquals(2, $alloc2['sequence']);

        $year = now()->format('Y');
        $romanMonths = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'];
        $romanMonth = $romanMonths[(int) now()->format('n')];

        $this->assertEquals("JBM/SOP-01/{$romanMonth}/{$year}/001", $alloc1['number']);
        $this->assertEquals("JBM/SOP-01/{$romanMonth}/{$year}/002", $alloc2['number']);
    }

    public function test_batch_allocation_no_duplicates(): void
    {
        $allocatedNumbers = [];

        for ($i = 0; $i < 50; $i++) {
            $alloc = $this->numberingService->allocateNumber(
                company: $this->companyA,
                branch: $this->branchA,
                unit: $this->unitA,
                classification: $this->classification
            );
            $allocatedNumbers[] = $alloc['number'];
        }

        $this->assertCount(50, $allocatedNumbers);
        $this->assertCount(50, array_unique($allocatedNumbers), 'Duplicate archive numbers were generated in batch!');

        $year = now()->format('Y');
        $this->assertStringEndsWith("/{$year}/001", $allocatedNumbers[0]);
        $this->assertStringEndsWith("/{$year}/050", $allocatedNumbers[49]);
    }

    public function test_cross_tenant_counter_isolation(): void
    {
        $classB = ArchiveClassification::create([
            'company_id' => $this->companyB->id,
            'code' => 'KEU-01',
            'name' => 'Keuangan',
            'confidentiality_level' => 'internal',
        ]);

        $allocA1 = $this->numberingService->allocateNumber(
            company: $this->companyA,
            branch: $this->branchA,
            unit: $this->unitA,
            classification: $this->classification
        );

        $allocB1 = $this->numberingService->allocateNumber(
            company: $this->companyB,
            branch: null,
            unit: null,
            classification: $classB
        );

        $allocA2 = $this->numberingService->allocateNumber(
            company: $this->companyA,
            branch: $this->branchA,
            unit: $this->unitA,
            classification: $this->classification
        );

        $allocB2 = $this->numberingService->allocateNumber(
            company: $this->companyB,
            branch: null,
            unit: null,
            classification: $classB
        );

        $this->assertEquals(1, $allocA1['sequence']);
        $this->assertEquals(2, $allocA2['sequence']);

        $this->assertEquals(1, $allocB1['sequence']);
        $this->assertEquals(2, $allocB2['sequence']);

        $this->assertStringContainsString('SOP-01', $allocA1['number']);
        $this->assertStringContainsString('KEU-01', $allocB1['number']);
    }

    public function test_preview_number_does_not_reserve_or_increment_counter(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $preview = $this->numberingService->previewNumber(
                company: $this->companyA,
                branch: $this->branchA,
                unit: $this->unitA,
                classification: $this->classification
            );
            $this->assertTrue($preview['is_preview']);
            $this->assertEquals(1, $preview['sequence']);
        }

        // First real allocation must still be 1
        $actual = $this->numberingService->allocateNumber(
            company: $this->companyA,
            branch: $this->branchA,
            unit: $this->unitA,
            classification: $this->classification
        );

        $this->assertFalse($actual['is_preview']);
        $this->assertEquals(1, $actual['sequence']);
    }

    public function test_voided_archive_numbers_are_never_reused(): void
    {
        $alloc1 = $this->numberingService->allocateNumber(
            company: $this->companyA,
            branch: $this->branchA,
            unit: $this->unitA,
            classification: $this->classification
        );

        // Record void record manually to simulate voiding
        \App\Models\ArchiveVoidRecord::create([
            'company_id' => $this->companyA->id,
            'archive_number' => $alloc1['number'],
            'normalized_number' => $alloc1['normalized'],
            'void_reason' => 'Testing void collision guard',
            'voided_at' => now(),
        ]);

        // Attempting to allocate next should produce sequence 2
        $alloc2 = $this->numberingService->allocateNumber(
            company: $this->companyA,
            branch: $this->branchA,
            unit: $this->unitA,
            classification: $this->classification
        );

        $this->assertEquals(2, $alloc2['sequence']);
        $this->assertNotEquals($alloc1['number'], $alloc2['number']);
    }
}

