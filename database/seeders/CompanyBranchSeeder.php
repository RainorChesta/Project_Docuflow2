<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanyBranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = [
            [
                'code' => 'KCMC',
                'name' => 'PT KARUNIA CAHAYA MEDICALCARE',
                'branches' => [
                    [
                        'name' => 'LUMIERA LUZELLE',
                        'code' => 'LML',
                    ],
                ],
            ],
            [
                'code' => 'JBM',
                'name' => 'PT JAYA BHAKTI MANDIRI',
                'branches' => [
                    [
                        'name' => 'KLINIK UTAMA CAHAYA DIAGNOSTIC CENTRE',
                        'code' => 'MMC-CDC',
                    ],
                    [
                        'name' => 'KLINIK PRATAMA MITRAMEDICARE SURABAYA',
                        'code' => 'MMC-DHU',
                    ],
                    [
                        'name' => 'KLINIK PRATAMA MITRAMEDICARE DRIYOREJO',
                        'code' => 'MMC-DRY',
                    ],
                    [
                        'name' => 'KLINIK PRATAMA MITRAMEDICARE JATIUWUNG',
                        'code' => 'MMC-JTU',
                    ],
                    [
                        'name' => 'KLINIK PRATAMA MITRAMEDICARE KARAWANG',
                        'code' => 'MMC-KRW',
                    ],
                    [
                        'name' => 'KLINIK PRATAMA MITRAMEDICARE MANYAR',
                        'code' => 'MMC-MYR',
                    ],
                    [
                        'name' => 'KLINIK PRATAMA MITRAMEDICARE PASAR KEMIS',
                        'code' => 'MMC-PK',
                    ],
                    [
                        'name' => 'KLINIK PRATAMA MITRAMEDICARE RANCAEKEK',
                        'code' => 'MMC-RCK',
                    ],
                    [
                        'name' => 'KLINIK PRATAMA MITRAMEDICARE SUCI',
                        'code' => 'MMC-SCI',
                    ],
                ],
            ],
            [
                'code' => 'BAS',
                'name' => 'PT BINTANG ALAM SEMESTA',
                'branches' => [],
            ],
            [
                'code' => 'CKMH',
                'name' => 'PT CAHAYA KLINIK MEDIKA HUSADA',
                'branches' => [
                    [
                        'name' => 'APOTEK',
                        'code' => 'APT',
                    ],
                    [
                        'name' => 'MITRA MEDICARE BOYOLALI',
                        'code' => 'MMC-BYL',
                    ],
                ],
            ],
            [
                'code' => 'CMH',
                'name' => 'PT CAHAYA MEDIKA HEALTHCARE',
                'branches' => [],
            ],
            [
                'code' => 'CMSB',
                'name' => 'PT CAHAYA MITRA SATYA BERSAMA',
                'branches' => [],
            ],
            [
                'code' => 'PNI',
                'name' => 'PT PERSADA NUANSA INDAH',
                'branches' => [],
            ],
            [
                'code' => 'MMC',
                'name' => 'CV MITRA MEDICALCARE',
                'branches' => [],
            ],
        ];

        foreach ($companies as $data) {
            $company = Company::updateOrCreate(
                ['code' => $data['code']],
                ['name' => $data['name']]
            );

            // Pastikan kantor pusat default selalu ada untuk setiap entitas/perusahaan
            Branch::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'is_pusat' => true,
                ],
                [
                    'name' => 'PUSAT',
                    'code' => null,
                ]
            );

            // Buat / perbarui cabang operasional
            foreach ($data['branches'] as $branchData) {
                Branch::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'code' => $branchData['code'],
                    ],
                    [
                        'name' => $branchData['name'],
                        'is_pusat' => false,
                    ]
                );
            }
        }
    }
}
