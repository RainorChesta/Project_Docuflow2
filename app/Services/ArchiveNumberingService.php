<?php

namespace App\Services;

use App\Models\ArchiveClassification;
use App\Models\ArchiveEntry;
use App\Models\ArchiveNumberCounter;
use App\Models\ArchiveNumberingScheme;
use App\Models\ArchiveVoidRecord;
use App\Models\Branch;
use App\Models\Company;
use App\Models\UnitKerja;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ArchiveNumberingService
{
    /**
     * Roman numeral representations for months 1-12.
     */
    protected const ROMAN_MONTHS = [
        1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
        7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
    ];

    /**
     * Get or create the active numbering scheme for a company.
     */
    public function getActiveScheme(?Company $company): ArchiveNumberingScheme
    {
        $companyId = $company?->id;

        $scheme = ArchiveNumberingScheme::where('company_id', $companyId)
            ->where('is_active', true)
            ->latest('id')
            ->first();

        if ($scheme) {
            return $scheme;
        }

        // Global fallback scheme
        $globalScheme = ArchiveNumberingScheme::whereNull('company_id')
            ->where('is_active', true)
            ->latest('id')
            ->first();

        if ($globalScheme) {
            return $globalScheme;
        }

        // Auto-provision default scheme for company (CV MMC Approach A: D/B/E/F/A)
        return ArchiveNumberingScheme::create([
            'company_id' => $companyId,
            'version' => 1,
            'name' => 'Skema Penomoran Arsip CV Mitra Medical Care (D/B/E/F/A)',
            'prefix' => 'JBM',
            'template' => '{PUSAT}/{CLASS}/{ROMAN_MONTH}/{YYYY}/{SEQ:03}',
            'separator' => '/',
            'sequence_scope' => 'company_year',
            'reset_period' => 'monthly',
            'padding' => 3,
            'start_value' => 1,
            'timezone' => 'Asia/Jakarta',
            'is_active' => true,
        ]);
    }

    /**
     * Preview an archive number without locking, incrementing, or reserving sequences.
     */
    public function previewNumber(
        ?Company $company,
        ?Branch $branch,
        ?UnitKerja $unit,
        ?ArchiveClassification $classification,
        ?\DateTimeInterface $date = null,
        ?ArchiveNumberingScheme $customScheme = null
    ): array {
        $scheme = $customScheme ?? $this->getActiveScheme($company);
        $tz = $scheme->timezone ?: 'Asia/Jakarta';
        $now = $date ? Carbon::parse($date)->setTimezone($tz) : Carbon::now($tz);

        $scopeKey = $this->resolveScopeKey($scheme, $company?->id, $branch?->id);
        $periodKey = $this->resolvePeriodKey($scheme, $now);

        $counter = ArchiveNumberCounter::where('scheme_id', $scheme->id)
            ->where('company_id', $company?->id)
            ->where('scope_key', $scopeKey)
            ->where('period_key', $periodKey)
            ->first();

        $previewSeq = $counter ? ($counter->last_value + 1) : $scheme->start_value;
        $companyCode = $company?->code ?: 'CMP';
        $branchCode = $branch?->code ?: ($branch?->effective_code ?: 'PST');
        $unitCode = $unit?->kode_unit_kerja ?: 'GEN';
        $classCode = $classification?->code ?: 'ARC';
        $pusatCode = $this->resolvePusatCode($company, $branch, $scheme, $companyCode, $branchCode);

        $number = $this->renderTemplate(
            scheme: $scheme,
            sequence: $previewSeq,
            date: $now,
            companyCode: $companyCode,
            branchCode: $branchCode,
            unitCode: $unitCode,
            classCode: $classCode,
            pusatCode: $pusatCode
        );

        return [
            'number' => $number,
            'normalized' => strtoupper(trim($number)),
            'sequence' => $previewSeq,
            'scope_key' => $scopeKey,
            'period_key' => $periodKey,
            'scheme_id' => $scheme->id,
            'scheme_version' => $scheme->version,
            'is_preview' => true,
        ];
    }

    /**
     * Atomically allocate the next unique official archive number.
     * MUST be called inside a DB transaction.
     */
    public function allocateNumber(
        ?Company $company,
        ?Branch $branch,
        ?UnitKerja $unit,
        ArchiveClassification $classification,
        ?\DateTimeInterface $date = null
    ): array {
        $scheme = $this->getActiveScheme($company);
        $tz = $scheme->timezone ?: 'Asia/Jakarta';
        $now = $date ? Carbon::parse($date)->setTimezone($tz) : Carbon::now($tz);

        $companyId = $company?->id;
        $scopeKey = $this->resolveScopeKey($scheme, $companyId, $branch?->id);
        $periodKey = $this->resolvePeriodKey($scheme, $now);

        $companyCode = $company?->code ?: 'CMP';
        $branchCode = $branch?->code ?: ($branch?->effective_code ?: 'PST');
        $unitCode = $unit?->kode_unit_kerja ?: 'GEN';
        $classCode = $classification->code ?: 'ARC';
        $pusatCode = $this->resolvePusatCode($company, $branch, $scheme, $companyCode, $branchCode);

        // Perform atomic increment loop to guarantee absolute uniqueness
        $maxAttempts = 50;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            $attempt++;

            // Lock counter row for update
            $counter = ArchiveNumberCounter::lockForUpdate()
                ->where('scheme_id', $scheme->id)
                ->where('company_id', $companyId)
                ->where('scope_key', $scopeKey)
                ->where('period_key', $periodKey)
                ->first();

            if (!$counter) {
                // First sequence in this scope and period
                $initialVal = max(0, $scheme->start_value - 1);
                $counter = ArchiveNumberCounter::create([
                    'scheme_id' => $scheme->id,
                    'company_id' => $companyId,
                    'scope_key' => $scopeKey,
                    'period_key' => $periodKey,
                    'last_value' => $initialVal,
                ]);

                // Re-lock
                $counter = ArchiveNumberCounter::lockForUpdate()->find($counter->id);
            }

            $nextSeq = $counter->last_value + 1;
            if ($nextSeq < $scheme->start_value) {
                $nextSeq = $scheme->start_value;
            }

            $counter->last_value = $nextSeq;
            $counter->save();

            $formatted = $this->renderTemplate(
                scheme: $scheme,
                sequence: $nextSeq,
                date: $now,
                companyCode: $companyCode,
                branchCode: $branchCode,
                unitCode: $unitCode,
                classCode: $classCode,
                pusatCode: $pusatCode
            );

            $normalized = strtoupper(trim($formatted));

            // Verify number collision against archive_entries and archive_void_records
            $existsInEntries = ArchiveEntry::where('company_id', $companyId)
                ->where('normalized_number', $normalized)
                ->exists();

            $existsInVoid = ArchiveVoidRecord::where('company_id', $companyId)
                ->where('normalized_number', $normalized)
                ->exists();

            if (!$existsInEntries && !$existsInVoid) {
                return [
                    'number' => $formatted,
                    'normalized' => $normalized,
                    'sequence' => $nextSeq,
                    'scope_key' => $scopeKey,
                    'period_key' => $periodKey,
                    'scheme_id' => $scheme->id,
                    'scheme_version' => $scheme->version,
                    'registration_period' => $periodKey,
                    'is_preview' => false,
                ];
            }
        }

        throw new \RuntimeException("Unable to allocate unique archive number after {$maxAttempts} attempts due to collisions.");
    }

    /**
     * Resolve the designated Pusat (custody entity) code according to CV MMC specification.
     */
    public function resolvePusatCode(
        ?Company $company,
        ?Branch $branch,
        ArchiveNumberingScheme $scheme,
        ?string $companyCode = null,
        ?string $branchCode = null
    ): string {
        // 1. If scheme prefix is explicitly set and not generic 'ARC', use it (e.g. 'JBM')
        if (!empty($scheme->prefix) && strtoupper($scheme->prefix) !== 'ARC') {
            return strtoupper($scheme->prefix);
        }

        // 2. If company code is MMC or JBM, CV MMC spec designates 'JBM' as Pusat
        $cCode = strtoupper($companyCode ?? $company?->code ?? '');
        if ($cCode === 'MMC' || $cCode === 'JBM') {
            return 'JBM';
        }

        // 3. If branch is designated pusat with a non-generic code
        if ($branch && $branch->is_pusat && !empty($branch->code) && strtoupper($branch->code) !== 'PST') {
            return strtoupper($branch->code);
        }

        // 4. Default to company code if available and valid
        if (!empty($cCode) && $cCode !== 'CMP') {
            return $cCode;
        }

        // 5. Default fallback to JBM per CV MMC specification
        return 'JBM';
    }

    /**
     * Render the template string with substituted tokens.
     */
    public function renderTemplate(
        ArchiveNumberingScheme $scheme,
        int $sequence,
        Carbon $date,
        string $companyCode,
        string $branchCode,
        string $unitCode,
        string $classCode,
        ?string $pusatCode = null
    ): string {
        $template = $scheme->template ?: '{PUSAT}/{CLASS}/{ROMAN_MONTH}/{YYYY}/{SEQ:03}';
        $prefix = $scheme->prefix ?: 'JBM';
        $padding = $scheme->padding ?: 3;
        $resolvedPusat = $pusatCode ?: $this->resolvePusatCode(null, null, $scheme, $companyCode, $branchCode);

        $romanMonth = self::ROMAN_MONTHS[(int) $date->format('n')] ?? 'I';

        $tokens = [
            '{PREFIX}' => strtoupper($prefix),
            '{COMPANY}' => strtoupper($companyCode),
            '{BRANCH}' => strtoupper($branchCode),
            '{UNIT}' => strtoupper($unitCode),
            '{CLASS}' => strtoupper($classCode),
            '{KODE_DOKUMEN}' => strtoupper($classCode),
            '{PUSAT}' => strtoupper($resolvedPusat),
            '{KODE_PUSAT}' => strtoupper($resolvedPusat),
            '{YYYY}' => $date->format('Y'),
            '{YEAR}' => $date->format('Y'),
            '{TAHUN}' => $date->format('Y'),
            '{YY}' => $date->format('y'),
            '{MM}' => $date->format('m'),
            '{ROMAN_MONTH}' => $romanMonth,
            '{BULAN}' => $romanMonth,
            '{MONTH}' => $romanMonth,
            // Column short codes per CV MMC reference (D/B/E/F)
            '{D}' => strtoupper($resolvedPusat),
            '{B}' => strtoupper($classCode),
            '{E}' => $romanMonth,
            '{F}' => $date->format('Y'),
        ];

        $rendered = str_replace(array_keys($tokens), array_values($tokens), $template);

        // Replace padded sequences like {SEQ:03}, {SEQ:04}, or bare {SEQ}, and aliases {A:03}, {A}, {NOMOR:03}, {NOMOR}
        $rendered = preg_replace_callback('/\{(?:SEQ|A|NOMOR)(?::(\d+))?\}/i', function ($matches) use ($sequence, $padding) {
            $padLen = !empty($matches[1]) ? (int) $matches[1] : $padding;
            return str_pad((string) $sequence, $padLen, '0', STR_PAD_LEFT);
        }, $rendered);

        return $rendered;
    }

    protected function resolveScopeKey(ArchiveNumberingScheme $scheme, ?int $companyId, ?int $branchId): string
    {
        return match ($scheme->sequence_scope) {
            'branch_year' => "comp_{$companyId}_br_{$branchId}",
            'company_never' => "comp_{$companyId}_all",
            default => "comp_{$companyId}",
        };
    }

    protected function resolvePeriodKey(ArchiveNumberingScheme $scheme, Carbon $date): string
    {
        return match ($scheme->reset_period) {
            'monthly' => $date->format('Y-m'),
            'never' => 'all',
            default => $date->format('Y'),
        };
    }
}

