<?php

namespace Database\Seeders;

use App\Models\Masters\Education;
use App\Models\Masters\EducationMajor;
use App\Models\Masters\DistributionClass;
use App\Models\Masters\DistributionType;
use App\Models\Masters\MaritalStatus;
use App\Models\Masters\OwnershipStatus;
use App\Models\Masters\Religion;
use App\Models\Masters\ResidenceStatus;
use App\Models\Masters\ResidentStatus;
use App\Models\Masters\WorkStatus;
use App\Models\Masters\CharityType;
use App\Models\Masters\Bank;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $residenceStatuses = [
            ['name' => 'Milik Sendiri', 'slug' => 'owned', 'sort_order' => 1],
            ['name' => 'Kontrak', 'slug' => 'rent', 'sort_order' => 2],
            ['name' => 'Keluarga', 'slug' => 'family', 'sort_order' => 3],
            ['name' => 'Dinas', 'slug' => 'official', 'sort_order' => 4],
            ['name' => 'Lainnya', 'slug' => 'other', 'sort_order' => 5],
        ];

        foreach ($residenceStatuses as $item) {
            ResidenceStatus::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'sort_order' => $item['sort_order'] ?? 0,
                    'is_active' => true,
                ]
            );
        }

        $maritalStatuses = [
            ['name' => 'Belum Menikah', 'slug' => 'single', 'sort_order' => 1],
            ['name' => 'Menikah', 'slug' => 'married', 'sort_order' => 2],
            ['name' => 'Cerai Hidup', 'slug' => 'divorced', 'sort_order' => 3],
            ['name' => 'Cerai Mati', 'slug' => 'widowed', 'sort_order' => 4],
        ];

        foreach ($maritalStatuses as $item) {
            MaritalStatus::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'sort_order' => $item['sort_order'] ?? 0,
                    'is_active' => true,
                ]
            );
        }

        $educations = [
            ['name' => 'Tidak Sekolah', 'slug' => 'none', 'sort_order' => 1],
            ['name' => 'SD / Sederajat', 'slug' => 'sd', 'sort_order' => 2],
            ['name' => 'SMP / Sederajat', 'slug' => 'smp', 'sort_order' => 3],
            ['name' => 'SMA / Sederajat', 'slug' => 'sma', 'sort_order' => 4],
            ['name' => 'D1', 'slug' => 'd1', 'sort_order' => 5],
            ['name' => 'D2', 'slug' => 'd2', 'sort_order' => 6],
            ['name' => 'D3', 'slug' => 'd3', 'sort_order' => 7],
            ['name' => 'S1', 'slug' => 's1', 'sort_order' => 8],
            ['name' => 'S2', 'slug' => 's2', 'sort_order' => 9],
            ['name' => 'S3', 'slug' => 's3', 'sort_order' => 10],
        ];

        $educationMap = [];
        foreach ($educations as $item) {
            $education = Education::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'sort_order' => $item['sort_order'] ?? 0,
                    'is_active' => true,
                ]
            );
            $educationMap[$item['slug']] = $education;
        }

        $majors = [
            ['name' => 'Umum', 'slug' => 'general', 'education_slug' => 'sma', 'sort_order' => 1],
            ['name' => 'IPA', 'slug' => 'ipa', 'education_slug' => 'sma', 'sort_order' => 2],
            ['name' => 'IPS', 'slug' => 'ips', 'education_slug' => 'sma', 'sort_order' => 3],
            ['name' => 'TKJ', 'slug' => 'tkj', 'education_slug' => 'sma', 'sort_order' => 4],
            ['name' => 'Akuntansi', 'slug' => 'accounting', 'education_slug' => 'd3', 'sort_order' => 5],
            ['name' => 'Manajemen', 'slug' => 'management', 'education_slug' => 's1', 'sort_order' => 6],
            ['name' => 'Teknik Informatika', 'slug' => 'informatics', 'education_slug' => 's1', 'sort_order' => 7],
            ['name' => 'Pendidikan', 'slug' => 'education', 'education_slug' => 's1', 'sort_order' => 8],
        ];

        foreach ($majors as $item) {
            $education = $educationMap[$item['education_slug']] ?? $educationMap['sma'] ?? null;

            EducationMajor::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'education_id' => $education?->id,
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'sort_order' => $item['sort_order'] ?? 0,
                    'is_active' => true,
                ]
            );
        }

        $religions = [
            ['name' => 'Islam', 'slug' => 'islam', 'sort_order' => 1],
            ['name' => 'Kristen', 'slug' => 'christian', 'sort_order' => 2],
            ['name' => 'Katolik', 'slug' => 'catholic', 'sort_order' => 3],
            ['name' => 'Hindu', 'slug' => 'hindu', 'sort_order' => 4],
            ['name' => 'Buddha', 'slug' => 'buddha', 'sort_order' => 5],
            ['name' => 'Konghucu', 'slug' => 'confucian', 'sort_order' => 6],
        ];

        foreach ($religions as $item) {
            Religion::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'sort_order' => $item['sort_order'] ?? 0,
                    'is_active' => true,
                ]
            );
        }

        $ownershipStatuses = [
            ['name' => 'Wakaf', 'slug' => 'wakaf', 'sort_order' => 1],
            ['name' => 'Hak Milik', 'slug' => 'owned', 'sort_order' => 2],
            ['name' => 'Sewa', 'slug' => 'rent', 'sort_order' => 3],
            ['name' => 'Pinjam Pakai', 'slug' => 'borrowed', 'sort_order' => 4],
            ['name' => 'Lainnya', 'slug' => 'other', 'sort_order' => 5],
        ];

        foreach ($ownershipStatuses as $item) {
            OwnershipStatus::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'sort_order' => $item['sort_order'] ?? 0,
                    'is_active' => true,
                ]
            );
        }

        $workStatuses = [
            ['name' => 'Bekerja', 'slug' => 'working', 'sort_order' => 1],
            ['name' => 'Tidak Bekerja', 'slug' => 'not-working', 'sort_order' => 2],
            ['name' => 'Pelajar / Mahasiswa', 'slug' => 'student', 'sort_order' => 3],
            ['name' => 'Wiraswasta', 'slug' => 'entrepreneur', 'sort_order' => 4],
            ['name' => 'Pensiun', 'slug' => 'retired', 'sort_order' => 5],
            ['name' => 'Ibu Rumah Tangga', 'slug' => 'housewife', 'sort_order' => 6],
        ];

        foreach ($workStatuses as $item) {
            WorkStatus::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'sort_order' => $item['sort_order'] ?? 0,
                    'is_active' => true,
                ]
            );
        }

        $residentStatuses = [
            ['name' => 'Warga Tetap', 'slug' => 'permanent', 'sort_order' => 1],
            ['name' => 'Warga Sementara', 'slug' => 'temporary', 'sort_order' => 2],
            ['name' => 'Pendatang', 'slug' => 'migrant', 'sort_order' => 3],
            ['name' => 'Warga Asli', 'slug' => 'native', 'sort_order' => 4],
        ];

        foreach ($residentStatuses as $item) {
            ResidentStatus::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'sort_order' => $item['sort_order'] ?? 0,
                    'is_active' => true,
                ]
            );
        }

        $distributionTypes = [
            ['name' => 'Zakat', 'slug' => 'zakat', 'sort_order' => 1],
            ['name' => 'Qurban', 'slug' => 'qurban', 'sort_order' => 2],
        ];

        foreach ($distributionTypes as $item) {
            DistributionType::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'sort_order' => $item['sort_order'] ?? 0,
                    'is_active' => true,
                ]
            );
        }

        $distributionClasses = [
            ['name' => 'Amil 1', 'slug' => 'amil-1', 'year' => 2026, 'get_money' => 150000, 'get_rice' => 1, 'sort_order' => 1],
            ['name' => 'Amil 2', 'slug' => 'amil-2', 'year' => 2026, 'get_money' => 200000, 'get_rice' => 2, 'sort_order' => 2],
            ['name' => 'Amil 3', 'slug' => 'amil-3', 'year' => 2026, 'get_money' => 100000, 'get_rice' => 1, 'sort_order' => 3],
            ['name' => 'Fakir Miskin Gol 1', 'slug' => 'fakir-miskin-1', 'year' => 2026, 'get_money' => 100000, 'get_rice' => 1, 'sort_order' => 4],
            ['name' => 'Fakir Miskin Gol 2', 'slug' => 'fakir-miskin-2', 'year' => 2026, 'get_money' => 100000, 'get_rice' => 1, 'sort_order' => 5],
            ['name' => 'Yatim/Piatu', 'slug' => 'yatim-piatu', 'year' => 2026, 'get_money' => 50000, 'get_rice' => 1, 'sort_order' => 6],
        ];

        foreach ($distributionClasses as $item) {
            DistributionClass::updateOrCreate(
                ['slug' => $item['slug'], 'year' => $item['year']],
                [
                    'name' => $item['name'],
                    'get_money' => $item['get_money'] ?? null,
                    'get_rice' => $item['get_rice'] ?? null,
                    'description' => $item['description'] ?? null,
                    'sort_order' => $item['sort_order'] ?? 0,
                    'is_active' => true,
                ]
            );
        }

        $charityTypes = [
            [
                'name' => 'Zakat Fitrah',
                'slug' => 'zakat-fitrah',
                'year' => 2026,
                'min_amount' => 40000,
                'max_amount' => 50000,
                'is_rice' => false,
                'total_rice' => 35,
                'package_amount' => 32000,
                'sort_order' => 1,
            ],
            [
                'name' => 'Fidyah',
                'slug' => 'fidyah',
                'year' => 2026,
                'min_amount' => 40000,
                'max_amount' => 60000,
                'is_rice' => false,
                'total_rice' => 25,
                'package_amount' => null,
                'sort_order' => 2,
            ],
            [
                'name' => 'Infaq',
                'slug' => 'infaq',
                'year' => 2026,
                'min_amount' => null,
                'max_amount' => null,
                'is_rice' => false,
                'total_rice' => null,
                'package_amount' => null,
                'sort_order' => 3,
            ],
            [
                'name' => 'Sodaqoh',
                'slug' => 'sodaqoh',
                'year' => 2026,
                'min_amount' => null,
                'max_amount' => null,
                'is_rice' => false,
                'total_rice' => null,
                'package_amount' => null,
                'sort_order' => 4,
            ],
            [
                'name' => 'Zakat Mal',
                'slug' => 'zakat-mal',
                'year' => 2026,
                'min_amount' => null,
                'max_amount' => null,
                'is_rice' => false,
                'total_rice' => null,
                'package_amount' => null,
                'sort_order' => 5,
            ],
            [
                'name' => 'Waqaf',
                'slug' => 'waqaf',
                'year' => 2026,
                'min_amount' => null,
                'max_amount' => null,
                'is_rice' => false,
                'total_rice' => null,
                'package_amount' => null,
                'sort_order' => 6,
            ],
        ];

        foreach ($charityTypes as $item) {
            CharityType::updateOrCreate(
                ['slug' => $item['slug'], 'year' => $item['year']],
                [
                    'name' => $item['name'],
                    'min_amount' => $item['min_amount'],
                    'max_amount' => $item['max_amount'],
                    'is_rice' => $item['is_rice'],
                    'total_rice' => $item['total_rice'],
                    'package_amount' => $item['package_amount'],
                    'description' => $item['description'] ?? null,
                    'sort_order' => $item['sort_order'] ?? 0,
                    'is_active' => true,
                ]
            );
        }

        $banks = [
            ['name' => 'Bank BCA', 'slug' => 'bca', 'code' => '014', 'sort_order' => 1],
            ['name' => 'Bank BRI', 'slug' => 'bri', 'code' => '002', 'sort_order' => 2],
            ['name' => 'Bank BNI', 'slug' => 'bni', 'code' => '009', 'sort_order' => 3],
            ['name' => 'Bank Mandiri', 'slug' => 'mandiri', 'code' => '008', 'sort_order' => 4],
            ['name' => 'Bank Syariah Indonesia', 'slug' => 'bsi', 'code' => '451', 'sort_order' => 5],
        ];

        foreach ($banks as $item) {
            Bank::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'code' => $item['code'] ?? null,
                    'description' => $item['description'] ?? null,
                    'sort_order' => $item['sort_order'] ?? 0,
                    'is_active' => true,
                ]
            );
        }
    }
}
