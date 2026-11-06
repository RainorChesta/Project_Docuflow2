<?php

namespace Database\Seeders;

use App\Models\ArchiveClassification;
use App\Models\ArchiveNumberingScheme;
use App\Models\ArchiveRetentionPolicy;
use Illuminate\Database\Seeder;

class ArchiveMasterDataSeeder extends Seeder
{
    /**
     * Run the database seeds for standard archive retention policies and classifications.
     */
    public function run(): void
    {
        // 1. Standar Jadwal Retensi Arsip (JRA)
        $policies = [
            [
                'code' => 'JRA-UMUM-5Y',
                'name' => 'Retensi Dokumen Umum & Kesekretariatan (5 Tahun)',
                'description' => 'Surat masuk/keluar, korespondensi umum, dan dokumen administrasi perkantoran non-vital.',
                'trigger_event' => 'registration_date',
                'active_retention_years' => 2,
                'inactive_retention_years' => 3,
                'final_disposition' => 'destroy',
                'requires_approval' => true,
                'is_active' => true,
            ],
            [
                'code' => 'JRA-MED-5Y',
                'name' => 'Retensi Rekam Medis & Pelayanan Pasien (5 Tahun)',
                'description' => 'Berkas rekam medis, tindakan klinis, resep, dan resume medis pasien sesuai Permenkes.',
                'trigger_event' => 'registration_date',
                'active_retention_years' => 2,
                'inactive_retention_years' => 3,
                'final_disposition' => 'review',
                'requires_approval' => true,
                'is_active' => true,
            ],
            [
                'code' => 'JRA-KEU-10Y',
                'name' => 'Retensi Keuangan, Perpajakan & Audit (10 Tahun)',
                'description' => 'Laporan keuangan audited, neraca, SPT pajak, dan bukti transaksi akuntansi.',
                'trigger_event' => 'document_date',
                'active_retention_years' => 5,
                'inactive_retention_years' => 5,
                'final_disposition' => 'review',
                'requires_approval' => true,
                'is_active' => true,
            ],
            [
                'code' => 'JRA-SDM-PERM',
                'name' => 'Retensi Personalia & Riwayat Pegawai (Permanen)',
                'description' => 'Berkas induk kepegawaian, SK pengangkatan/pemberhentian, dan rekam jejak karyawan.',
                'trigger_event' => 'registration_date',
                'active_retention_years' => 5,
                'inactive_retention_years' => 10,
                'final_disposition' => 'permanent',
                'requires_approval' => true,
                'is_active' => true,
            ],
            [
                'code' => 'JRA-HUKUM-PERM',
                'name' => 'Retensi Dokumen Hukum, Akta & Legalitas (Permanen)',
                'description' => 'Akta pendirian perusahaan, izin operasional klinik/faskes, perjanjian vital, dan dokumen HKI.',
                'trigger_event' => 'registration_date',
                'active_retention_years' => 10,
                'inactive_retention_years' => 0,
                'final_disposition' => 'permanent',
                'requires_approval' => true,
                'is_active' => true,
            ],
            [
                'code' => 'JRA-MUTU-SOP',
                'name' => 'Retensi Standar Mutu, Akreditasi & SOP (5 Tahun Reviu)',
                'description' => 'Standar operasional prosedur, pedoman mutu, instrumen akreditasi fasilitas kesehatan.',
                'trigger_event' => 'registration_date',
                'active_retention_years' => 3,
                'inactive_retention_years' => 2,
                'final_disposition' => 'review',
                'requires_approval' => true,
                'is_active' => true,
            ],
        ];

        $policyMap = [];
        foreach ($policies as $p) {
            $policy = ArchiveRetentionPolicy::firstOrCreate(
                ['code' => $p['code'], 'company_id' => null],
                $p
            );
            $policyMap[$p['code']] = $policy->id;
        }

        // 2. Klasifikasi Arsip Standar (Taxonomy Classification Code)
        $classifications = [
            [
                'code' => 'MED.01',
                'name' => 'Pelayanan Medis & Rekam Medis Pasien',
                'description' => 'Arsip riwayat pelayanan tindakan, resume medis rawat inap/jalan, dan odontogram.',
                'confidentiality_level' => 'confidential',
                'requires_approval' => true,
                'default_policy_code' => 'JRA-MED-5Y',
            ],
            [
                'code' => 'MED.02',
                'name' => 'Pelayanan Farmasi & Resep Obat',
                'description' => 'Buku resep, kartu stok obat narkotika/psikotropika, dan telaah resep.',
                'confidentiality_level' => 'internal',
                'requires_approval' => false,
                'default_policy_code' => 'JRA-MED-5Y',
            ],
            [
                'code' => 'MED.03',
                'name' => 'Pelayanan Laboratorium & Diagnostik',
                'description' => 'Hasil pemeriksaan laboratorium darah, patologi, dan rontgen diagnostik.',
                'confidentiality_level' => 'confidential',
                'requires_approval' => true,
                'default_policy_code' => 'JRA-MED-5Y',
            ],
            [
                'code' => 'MUTU.01',
                'name' => 'Standar Operasional Prosedur (SOP) Klinis & Manajerial',
                'description' => 'Baku panduan alur kerja klinis, pelayanan, dan tata kelola fasilitas.',
                'confidentiality_level' => 'internal',
                'requires_approval' => true,
                'default_policy_code' => 'JRA-MUTU-SOP',
            ],
            [
                'code' => 'MUTU.02',
                'name' => 'Dokumen Akreditasi & Sertifikasi Faskes',
                'description' => 'Instrumen akreditasi, laporan survei kepatuhan mutu, dan audit internal.',
                'confidentiality_level' => 'internal',
                'requires_approval' => true,
                'default_policy_code' => 'JRA-MUTU-SOP',
            ],
            [
                'code' => 'KEU.01',
                'name' => 'Laporan Keuangan & Perpajakan',
                'description' => 'Laporan laba rugi, neraca audited, bukti potong pajak, dan dokumen fiskal.',
                'confidentiality_level' => 'confidential',
                'requires_approval' => true,
                'default_policy_code' => 'JRA-KEU-10Y',
            ],
            [
                'code' => 'KEU.02',
                'name' => 'Klaim BPJS & Asuransi Kesehatan',
                'description' => 'Berkas tagihan klaim BPJS Kesehatan, berita acara verifikasi klaim, dan asuransi rekanan.',
                'confidentiality_level' => 'internal',
                'requires_approval' => false,
                'default_policy_code' => 'JRA-KEU-10Y',
            ],
            [
                'code' => 'SDM.01',
                'name' => 'Berkas Induk Kepegawaian (BIP)',
                'description' => 'Riwayat hidup, sertifikat STR/SIP dokter dan nakes, kontrak kerja, dan SK pengangkatan.',
                'confidentiality_level' => 'confidential',
                'requires_approval' => true,
                'default_policy_code' => 'JRA-SDM-PERM',
            ],
            [
                'code' => 'HKM.01',
                'name' => 'Perjanjian Kerjasama (PKS) & Kontrak Vendor',
                'description' => 'MOU, kontrak pengadaan alat medis, perjanjian sewa tempat, dan kerjasama operasional.',
                'confidentiality_level' => 'confidential',
                'requires_approval' => true,
                'default_policy_code' => 'JRA-HUKUM-PERM',
            ],
            [
                'code' => 'HKM.02',
                'name' => 'Perizinan Operasional & Legalitas Institusi',
                'description' => 'Izin operasional klinik, amdal/UKL-UPL, izin radiologi Bapeten, dan sertifikat laik fungsi.',
                'confidentiality_level' => 'restricted',
                'requires_approval' => true,
                'default_policy_code' => 'JRA-HUKUM-PERM',
            ],
            [
                'code' => 'ADM.01',
                'name' => 'Surat Keputusan (SK) Direksi & Pimpinan',
                'description' => 'Surat keputusan pembentukan tim, penetapan tarif, dan kebijakan strategis manajemen.',
                'confidentiality_level' => 'internal',
                'requires_approval' => true,
                'default_policy_code' => 'JRA-HUKUM-PERM',
            ],
            [
                'code' => 'UMM.01',
                'name' => 'Korespondensi & Surat Masuk/Keluar Umum',
                'description' => 'Surat dinas biasa, undangan eksternal, dan arsip kesekretariatan rutin.',
                'confidentiality_level' => 'public',
                'requires_approval' => false,
                'default_policy_code' => 'JRA-UMUM-5Y',
            ],

            // 2b. Klasifikasi Resmi Berdasarkan Dokumen Spesifikasi CV MMC (PENOMORAN CV MMC.xlsx)
            [
                'code' => 'S.ED',
                'name' => 'Surat Edaran',
                'description' => 'Pemberitahuan resmi tertulis kepada seluruh unit kerja, karyawan, atau mitra kerja.',
                'confidentiality_level' => 'internal',
                'requires_approval' => false,
                'default_policy_code' => 'JRA-UMUM-5Y',
            ],
            [
                'code' => 'IM',
                'name' => 'Internal Memo',
                'description' => 'Memo dinas dan komunikasi internal antar divisi atau manajemen operasional.',
                'confidentiality_level' => 'internal',
                'requires_approval' => false,
                'default_policy_code' => 'JRA-UMUM-5Y',
            ],
            [
                'code' => 'ST',
                'name' => 'Surat Tugas',
                'description' => 'Penugasan resmi personil untuk pelaksanaan pekerjaan, dinas, atau mandat operasional.',
                'confidentiality_level' => 'internal',
                'requires_approval' => false,
                'default_policy_code' => 'JRA-UMUM-5Y',
            ],
            [
                'code' => 'I/S.KEL',
                'name' => 'Surat Korespondensi Internal',
                'description' => 'Surat keluar yang ditujukan kepada entitas internal dalam lingkungan korporasi.',
                'confidentiality_level' => 'internal',
                'requires_approval' => false,
                'default_policy_code' => 'JRA-UMUM-5Y',
            ],
            [
                'code' => 'E/S.KEL',
                'name' => 'Surat Korespondensi Eksternal',
                'description' => 'Surat dinas keluar yang ditujukan kepada pihak luar, rekanan, atau instansi terkait.',
                'confidentiality_level' => 'internal',
                'requires_approval' => false,
                'default_policy_code' => 'JRA-UMUM-5Y',
            ],
            [
                'code' => 'PKWT',
                'name' => 'Surat Perjanjian Internal (PKWT)',
                'description' => 'Perjanjian kerja waktu tertentu bagi tenaga medis, paramedis, staf, dan karyawan.',
                'confidentiality_level' => 'confidential',
                'requires_approval' => true,
                'default_policy_code' => 'JRA-SDM-PERM',
            ],
            [
                'code' => 'PKS',
                'name' => 'Surat Perjanjian Eksternal (PKS)',
                'description' => 'Perjanjian kerjasama operasional dengan vendor, asuransi, laboratorium rekanan, dan faskes.',
                'confidentiality_level' => 'confidential',
                'requires_approval' => true,
                'default_policy_code' => 'JRA-HUKUM-PERM',
            ],
            [
                'code' => 'SP',
                'name' => 'Surat Peringatan',
                'description' => 'Surat peringatan kedisiplinan dan pembinaan pegawai (SP 1, SP 2, SP 3).',
                'confidentiality_level' => 'confidential',
                'requires_approval' => true,
                'default_policy_code' => 'JRA-SDM-PERM',
            ],
            [
                'code' => 'S.KU',
                'name' => 'Surat Kuasa',
                'description' => 'Pelimpahan wewenang hukum atau administratif untuk pengurusan urusan tertentu.',
                'confidentiality_level' => 'confidential',
                'requires_approval' => true,
                'default_policy_code' => 'JRA-HUKUM-PERM',
            ],
            [
                'code' => 'BA',
                'name' => 'Berita Acara',
                'description' => 'Berita acara serah terima pekerjaan, inventaris medis, pemusnahan berkas, atau audit.',
                'confidentiality_level' => 'internal',
                'requires_approval' => false,
                'default_policy_code' => 'JRA-UMUM-5Y',
            ],
            [
                'code' => 'S.KET',
                'name' => 'Surat Keterangan',
                'description' => 'Surat keterangan kerja, kesehatan, rekomendasi pengalaman, atau keterangan administratif.',
                'confidentiality_level' => 'internal',
                'requires_approval' => false,
                'default_policy_code' => 'JRA-UMUM-5Y',
            ],
            [
                'code' => 'S.UND',
                'name' => 'Surat Undangan',
                'description' => 'Undangan rapat koordinasi, pertemuan komite medis, seminar, dan acara resmi institusi.',
                'confidentiality_level' => 'public',
                'requires_approval' => false,
                'default_policy_code' => 'JRA-UMUM-5Y',
            ],
            [
                'code' => 'SPPD',
                'name' => 'Surat Perintah Perjalanan Dinas',
                'description' => 'Surat mandat perjalanan dinas beserta pertanggungjawaban operasional dan pembiayaan.',
                'confidentiality_level' => 'internal',
                'requires_approval' => false,
                'default_policy_code' => 'JRA-KEU-10Y',
            ],
            [
                'code' => 'S.PENG',
                'name' => 'Surat Pengantar',
                'description' => 'Surat pengantar penyampaian dokumen, berkas rekam medis, laporan, atau spesimen lab.',
                'confidentiality_level' => 'internal',
                'requires_approval' => false,
                'default_policy_code' => 'JRA-UMUM-5Y',
            ],
            [
                'code' => 'SB',
                'name' => 'Surat Balasan',
                'description' => 'Tanggapan dan konfirmasi resmi atas surat masuk atau permohonan eksternal.',
                'confidentiality_level' => 'internal',
                'requires_approval' => false,
                'default_policy_code' => 'JRA-UMUM-5Y',
            ],
            [
                'code' => 'S.PMH',
                'name' => 'Surat Permohonan',
                'description' => 'Pengajuan izin, bantuan teknis, fasilitas, atau kerjasama kepada instansi eksternal/internal.',
                'confidentiality_level' => 'internal',
                'requires_approval' => false,
                'default_policy_code' => 'JRA-UMUM-5Y',
            ],
            [
                'code' => 'SR',
                'name' => 'Surat Rekomendasi',
                'description' => 'Rekomendasi profesional, izin praktik nakes, kelayakan operasional, atau studi lanjut.',
                'confidentiality_level' => 'internal',
                'requires_approval' => false,
                'default_policy_code' => 'JRA-UMUM-5Y',
            ],
            [
                'code' => 'S.PB',
                'name' => 'Surat Pemberitahuan',
                'description' => 'Pemberitahuan perubahan jadwal layanan klinik, kebijakan faskes, atau informasi umum.',
                'confidentiality_level' => 'internal',
                'requires_approval' => false,
                'default_policy_code' => 'JRA-UMUM-5Y',
            ],
            [
                'code' => 'S.PRT',
                'name' => 'Surat Perintah',
                'description' => 'Instruksi langsung pimpinan atau direksi untuk pelaksanaan tindakan tertentu.',
                'confidentiality_level' => 'internal',
                'requires_approval' => false,
                'default_policy_code' => 'JRA-UMUM-5Y',
            ],
            [
                'code' => 'S.PWN',
                'name' => 'Surat Penawaran',
                'description' => 'Penawaran paket layanan kesehatan kerja (MCU), kerjasama corporate, atau pengadaan barang.',
                'confidentiality_level' => 'internal',
                'requires_approval' => false,
                'default_policy_code' => 'JRA-KEU-10Y',
            ],
            [
                'code' => 'SK',
                'name' => 'Surat Keputusan',
                'description' => 'Ketetapan formal direksi/pimpinan institusi yang berkekuatan hukum mengikat.',
                'confidentiality_level' => 'confidential',
                'requires_approval' => true,
                'default_policy_code' => 'JRA-HUKUM-PERM',
            ],
            [
                'code' => 'FORM',
                'name' => 'Formulir',
                'description' => 'Blanko dan formulir isian baku tata kelola operasional dan administrasi faskes.',
                'confidentiality_level' => 'internal',
                'requires_approval' => false,
                'default_policy_code' => 'JRA-MUTU-SOP',
            ],
            [
                'code' => 'SRT',
                'name' => 'Sertifikat',
                'description' => 'Sertifikat pelatihan, sertifikat kalibrasi alat medis, dan sertifikat mutu fasilitas.',
                'confidentiality_level' => 'confidential',
                'requires_approval' => true,
                'default_policy_code' => 'JRA-HUKUM-PERM',
            ],
        ];

        foreach ($classifications as $c) {
            $policyId = $policyMap[$c['default_policy_code']] ?? null;
            ArchiveClassification::firstOrCreate(
                ['code' => $c['code'], 'company_id' => null],
                [
                    'name' => $c['name'],
                    'description' => $c['description'],
                    'confidentiality_level' => $c['confidentiality_level'],
                    'requires_approval' => $c['requires_approval'],
                    'default_retention_policy_id' => $policyId,
                    'is_active' => true,
                ]
            );
        }

        // 3. Skema Penomoran Arsip Resmi CV Mitra Medical Care (Approach A: D/B/E/F/A)
        ArchiveNumberingScheme::updateOrCreate(
            ['company_id' => null, 'is_active' => true],
            [
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
            ]
        );

        // Update legacy default schemes to adopt the approved Approach A pattern
        ArchiveNumberingScheme::where('prefix', 'ARC')->update([
            'name' => 'Skema Penomoran Arsip CV Mitra Medical Care (D/B/E/F/A)',
            'prefix' => 'JBM',
            'template' => '{PUSAT}/{CLASS}/{ROMAN_MONTH}/{YYYY}/{SEQ:03}',
            'reset_period' => 'monthly',
            'padding' => 3,
        ]);
    }
}

