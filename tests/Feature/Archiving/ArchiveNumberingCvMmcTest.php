<?php

namespace Tests\Feature\Archiving;

use App\Models\ArchiveClassification;
use App\Models\ArchiveNumberingScheme;
use App\Models\Company;
use App\Models\Branch;
use App\Models\UnitKerja;
use App\Services\ArchiveNumberingService;
use Carbon\Carbon;
use Database\Seeders\ArchiveMasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArchiveNumberingCvMmcTest extends TestCase
{
    use RefreshDatabase;

    protected ArchiveNumberingService $numberingService;
    protected Company $companyMmc;
    protected Branch $branchPusat;
    protected UnitKerja $unitKerja;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('id');

        $this->numberingService = app(ArchiveNumberingService::class);

        $this->companyMmc = Company::create([
            'name' => 'CV MITRA MEDICAL CARE',
            'code' => 'MMC',
        ]);

        $this->branchPusat = Branch::create([
            'company_id' => $this->companyMmc->id,
            'name' => 'PUSAT',
            'code' => 'PST',
            'is_pusat' => true,
        ]);

        $this->unitKerja = UnitKerja::create([
            'nama_unit_kerja' => 'DIREKSI & UMUM',
            'kode_unit_kerja' => 'DIR',
        ]);
    }

    /**
     * Test Approach A numbering: D / B / E / F / A
     * [KODE PUSAT] / [KODE DOKUMEN] / [BULAN ROMAWI] / [TAHUN] / [NOMOR:03]
     */
    public function test_cv_mmc_approach_a_numbering_for_standard_document_types(): void
    {
        $testCases = [
            ['code' => 'S.ED', 'month' => 1, 'expectedMonth' => 'I'],
            ['code' => 'IM', 'month' => 2, 'expectedMonth' => 'II'],
            ['code' => 'ST', 'month' => 3, 'expectedMonth' => 'III'],
            ['code' => 'PKS', 'month' => 7, 'expectedMonth' => 'VII'],
            ['code' => 'SP', 'month' => 8, 'expectedMonth' => 'VIII'],
            ['code' => 'SK', 'month' => 9, 'expectedMonth' => 'IX'],
            ['code' => 'BA', 'month' => 10, 'expectedMonth' => 'X'],
            ['code' => 'PKWT', 'month' => 12, 'expectedMonth' => 'XII'],
        ];

        foreach ($testCases as $case) {
            $classification = ArchiveClassification::create([
                'company_id' => $this->companyMmc->id,
                'code' => $case['code'],
                'name' => 'Test ' . $case['code'],
                'confidentiality_level' => 'internal',
            ]);

            $date = Carbon::create(2026, $case['month'], 15, 10, 0, 0, 'Asia/Jakarta');

            $alloc = $this->numberingService->allocateNumber(
                company: $this->companyMmc,
                branch: $this->branchPusat,
                unit: $this->unitKerja,
                classification: $classification,
                date: $date
            );

            $expected = "JBM/{$case['code']}/{$case['expectedMonth']}/2026/001";
            $this->assertEquals($expected, $alloc['number'], "Failed for document type {$case['code']}");
            $this->assertEquals(1, $alloc['sequence']);
        }
    }

    /**
     * Test classification codes with forward slashes (I/S.KEL and E/S.KEL).
     */
    public function test_cv_mmc_classification_codes_with_slashes(): void
    {
        $internalKeluar = ArchiveClassification::create([
            'company_id' => $this->companyMmc->id,
            'code' => 'I/S.KEL',
            'name' => 'Surat Korespondensi Internal',
            'confidentiality_level' => 'internal',
        ]);

        $eksternalKeluar = ArchiveClassification::create([
            'company_id' => $this->companyMmc->id,
            'code' => 'E/S.KEL',
            'name' => 'Surat Korespondensi Eksternal',
            'confidentiality_level' => 'internal',
        ]);

        $dateApril = Carbon::create(2026, 4, 10, 9, 0, 0, 'Asia/Jakarta');
        $dateMay = Carbon::create(2026, 5, 20, 14, 0, 0, 'Asia/Jakarta');

        $alloc1 = $this->numberingService->allocateNumber(
            company: $this->companyMmc,
            branch: $this->branchPusat,
            unit: $this->unitKerja,
            classification: $internalKeluar,
            date: $dateApril
        );

        $alloc2 = $this->numberingService->allocateNumber(
            company: $this->companyMmc,
            branch: $this->branchPusat,
            unit: $this->unitKerja,
            classification: $eksternalKeluar,
            date: $dateMay
        );

        $this->assertEquals('JBM/I/S.KEL/IV/2026/001', $alloc1['number']);
        $this->assertEquals('JBM/E/S.KEL/V/2026/001', $alloc2['number']);
    }

    /**
     * Test that monthly sequence reset (reset_period = 'monthly') restarts at 001 each month.
     */
    public function test_monthly_reset_period_counter_rollover(): void
    {
        $classification = ArchiveClassification::create([
            'company_id' => $this->companyMmc->id,
            'code' => 'S.ED',
            'name' => 'Surat Edaran',
            'confidentiality_level' => 'internal',
        ]);

        // January 2026: allocate 2 items
        $dateJan1 = Carbon::create(2026, 1, 10, 9, 0, 0, 'Asia/Jakarta');
        $dateJan2 = Carbon::create(2026, 1, 25, 11, 0, 0, 'Asia/Jakarta');

        $allocJan1 = $this->numberingService->allocateNumber(
            company: $this->companyMmc,
            branch: $this->branchPusat,
            unit: $this->unitKerja,
            classification: $classification,
            date: $dateJan1
        );

        $allocJan2 = $this->numberingService->allocateNumber(
            company: $this->companyMmc,
            branch: $this->branchPusat,
            unit: $this->unitKerja,
            classification: $classification,
            date: $dateJan2
        );

        $this->assertEquals('JBM/S.ED/I/2026/001', $allocJan1['number']);
        $this->assertEquals(1, $allocJan1['sequence']);

        $this->assertEquals('JBM/S.ED/I/2026/002', $allocJan2['number']);
        $this->assertEquals(2, $allocJan2['sequence']);

        // February 2026: sequence counter must reset to 001!
        $dateFeb1 = Carbon::create(2026, 2, 2, 8, 30, 0, 'Asia/Jakarta');

        $allocFeb1 = $this->numberingService->allocateNumber(
            company: $this->companyMmc,
            branch: $this->branchPusat,
            unit: $this->unitKerja,
            classification: $classification,
            date: $dateFeb1
        );

        $this->assertEquals('JBM/S.ED/II/2026/001', $allocFeb1['number']);
        $this->assertEquals(1, $allocFeb1['sequence']);
    }

    /**
     * Test template token substitutions including column short codes {D}/{B}/{E}/{F}/{A}.
     */
    public function test_template_token_substitutions_and_column_aliases(): void
    {
        $classification = ArchiveClassification::create([
            'company_id' => $this->companyMmc->id,
            'code' => 'S.ED',
            'name' => 'Surat Edaran',
            'confidentiality_level' => 'internal',
        ]);

        $date = Carbon::create(2026, 1, 15, 12, 0, 0, 'Asia/Jakarta');

        // Scheme 1: Standard Approach A descriptive tokens
        $schemeDescriptive = new ArchiveNumberingScheme([
            'template' => '{PUSAT}/{CLASS}/{ROMAN_MONTH}/{YYYY}/{SEQ:03}',
            'prefix' => 'JBM',
            'padding' => 3,
        ]);

        // Scheme 2: Exact column formula from PDF (D/B/E/F/A)
        $schemeColumnCodes = new ArchiveNumberingScheme([
            'template' => '{D}/{B}/{E}/{F}/{A:03}',
            'prefix' => 'JBM',
            'padding' => 3,
        ]);

        // Scheme 3: Indonesian naming tokens
        $schemeIndonesian = new ArchiveNumberingScheme([
            'template' => '{KODE_PUSAT}/{KODE_DOKUMEN}/{BULAN}/{TAHUN}/{NOMOR:03}',
            'prefix' => 'JBM',
            'padding' => 3,
        ]);

        $rendered1 = $this->numberingService->renderTemplate(
            scheme: $schemeDescriptive,
            sequence: 5,
            date: $date,
            companyCode: 'MMC',
            branchCode: 'PST',
            unitCode: 'DIR',
            classCode: 'S.ED',
            pusatCode: 'JBM'
        );

        $rendered2 = $this->numberingService->renderTemplate(
            scheme: $schemeColumnCodes,
            sequence: 5,
            date: $date,
            companyCode: 'MMC',
            branchCode: 'PST',
            unitCode: 'DIR',
            classCode: 'S.ED',
            pusatCode: 'JBM'
        );

        $rendered3 = $this->numberingService->renderTemplate(
            scheme: $schemeIndonesian,
            sequence: 5,
            date: $date,
            companyCode: 'MMC',
            branchCode: 'PST',
            unitCode: 'DIR',
            classCode: 'S.ED',
            pusatCode: 'JBM'
        );

        $expected = 'JBM/S.ED/I/2026/005';
        $this->assertEquals($expected, $rendered1);
        $this->assertEquals($expected, $rendered2);
        $this->assertEquals($expected, $rendered3);
    }

    /**
     * Test Pusat resolution: MMC and JBM companies both resolve to JBM custody.
     */
    public function test_pusat_custody_resolution(): void
    {
        $scheme = new ArchiveNumberingScheme(['prefix' => 'JBM']);

        // Company MMC -> JBM
        $this->assertEquals('JBM', $this->numberingService->resolvePusatCode($this->companyMmc, null, $scheme));

        // Company JBM -> JBM
        $compJbm = new Company(['name' => 'PT JAYA BHAKTI MANDIRI', 'code' => 'JBM']);
        $this->assertEquals('JBM', $this->numberingService->resolvePusatCode($compJbm, null, $scheme));

        // Custom prefix on scheme overrides
        $customScheme = new ArchiveNumberingScheme(['prefix' => 'HO']);
        $this->assertEquals('HO', $this->numberingService->resolvePusatCode($this->companyMmc, null, $customScheme));
    }

    /**
     * Test that all 22+ official CV MMC classifications are seeded properly via ArchiveMasterDataSeeder.
     */
    public function test_all_22_official_cv_mmc_classifications_are_seeded(): void
    {
        $this->seed(ArchiveMasterDataSeeder::class);

        $officialCodes = [
            'S.ED', 'IM', 'ST', 'I/S.KEL', 'E/S.KEL', 'PKWT', 'PKS',
            'SP', 'S.KU', 'BA', 'S.KET', 'S.UND', 'SPPD', 'S.PENG',
            'SB', 'S.PMH', 'SR', 'S.PB', 'S.PRT', 'S.PWN', 'SK', 'FORM', 'SRT'
        ];

        foreach ($officialCodes as $code) {
            $class = ArchiveClassification::where('code', $code)->first();
            $this->assertNotNull($class, "Official classification code {$code} was not seeded!");
            $this->assertTrue($class->is_active);
            $this->assertNotEmpty($class->name);
            $this->assertNotNull($class->defaultRetentionPolicy);
        }

        // Verify active numbering scheme seeded
        $scheme = ArchiveNumberingScheme::whereNull('company_id')->where('is_active', true)->first();
        $this->assertNotNull($scheme);
        $this->assertEquals('{PUSAT}/{CLASS}/{ROMAN_MONTH}/{YYYY}/{SEQ:03}', $scheme->template);
        $this->assertEquals('monthly', $scheme->reset_period);
        $this->assertEquals(3, $scheme->padding);
    }
}

