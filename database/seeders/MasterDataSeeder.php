<?php

namespace Database\Seeders;

use App\Models\Masters\Bank;
use App\Models\CharityTypes\CharityType;
use App\Models\CharityTypeSources\CharityTypeSource;
use App\Models\DistributionClasses\DistributionClass;
use App\Models\DistributionClassSources\DistributionClassSource;
use App\Models\DistributionTypes\DistributionType;
use App\Models\Masters\Education;
use App\Models\Masters\EducationMajor;
use App\Models\Masters\MaritalStatus;
use App\Models\Masters\OwnershipStatus;
use App\Models\Masters\Religion;
use App\Models\Masters\ResidenceStatus;
use App\Models\Masters\ResidentStatus;
use App\Models\Masters\WorkStatus;
use App\Models\Organizations\Organization;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $residenceStatuses = [
            ['name' => 'Milik Sendiri', 'slug' => 'owned'],
            ['name' => 'Kontrak', 'slug' => 'rent'],
            ['name' => 'Keluarga', 'slug' => 'family'],
            ['name' => 'Dinas', 'slug' => 'official'],
            ['name' => 'Lainnya', 'slug' => 'other'],
        ];

        foreach ($residenceStatuses as $item) {
            ResidenceStatus::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'is_active' => true,
                ]
            );
        }

        $maritalStatuses = [
            ['name' => 'Belum Menikah', 'slug' => 'single'],
            ['name' => 'Menikah', 'slug' => 'married'],
            ['name' => 'Cerai Hidup', 'slug' => 'divorced'],
            ['name' => 'Cerai Mati', 'slug' => 'widowed'],
        ];

        foreach ($maritalStatuses as $item) {
            MaritalStatus::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'is_active' => true,
                ]
            );
        }

        $educations = [
            ['name' => 'Tidak Sekolah', 'slug' => 'none'],
            ['name' => 'SD / Sederajat', 'slug' => 'sd'],
            ['name' => 'SMP / Sederajat', 'slug' => 'smp'],
            ['name' => 'SMA / Sederajat', 'slug' => 'sma'],
            ['name' => 'D1', 'slug' => 'd1'],
            ['name' => 'D2', 'slug' => 'd2'],
            ['name' => 'D3', 'slug' => 'd3'],
            ['name' => 'S1', 'slug' => 's1'],
            ['name' => 'S2', 'slug' => 's2'],
            ['name' => 'S3', 'slug' => 's3'],
        ];

        $educationMap = [];
        foreach ($educations as $item) {
            $education = Education::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'is_active' => true,
                ]
            );
            $educationMap[$item['slug']] = $education;
        }

        $majors = [
            ['name' => 'Umum', 'slug' => 'general', 'education_slug' => 'sma'],
            ['name' => 'IPA', 'slug' => 'ipa', 'education_slug' => 'sma'],
            ['name' => 'IPS', 'slug' => 'ips', 'education_slug' => 'sma'],
            ['name' => 'TKJ', 'slug' => 'tkj', 'education_slug' => 'sma'],
            ['name' => 'Akuntansi', 'slug' => 'accounting', 'education_slug' => 'd3'],
            ['name' => 'Manajemen', 'slug' => 'management', 'education_slug' => 's1'],
            ['name' => 'Teknik Informatika', 'slug' => 'informatics', 'education_slug' => 's1'],
            ['name' => 'Pendidikan', 'slug' => 'education', 'education_slug' => 's1'],
        ];

        foreach ($majors as $item) {
            $education = $educationMap[$item['education_slug']] ?? $educationMap['sma'] ?? null;

            EducationMajor::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'education_id' => $education?->id,
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'is_active' => true,
                ]
            );
        }

        $religions = [
            ['name' => 'Islam', 'slug' => 'islam'],
            ['name' => 'Kristen', 'slug' => 'christian'],
            ['name' => 'Katolik', 'slug' => 'catholic'],
            ['name' => 'Hindu', 'slug' => 'hindu'],
            ['name' => 'Buddha', 'slug' => 'buddha'],
            ['name' => 'Konghucu', 'slug' => 'confucian'],
        ];

        foreach ($religions as $item) {
            Religion::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'is_active' => true,
                ]
            );
        }

        $ownershipStatuses = [
            ['name' => 'Wakaf', 'slug' => 'wakaf'],
            ['name' => 'Hak Milik', 'slug' => 'owned'],
            ['name' => 'Sewa', 'slug' => 'rent'],
            ['name' => 'Pinjam Pakai', 'slug' => 'borrowed'],
            ['name' => 'Lainnya', 'slug' => 'other'],
        ];

        foreach ($ownershipStatuses as $item) {
            OwnershipStatus::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'is_active' => true,
                ]
            );
        }

        $workStatuses = [
            ['name' => 'Bekerja', 'slug' => 'working'],
            ['name' => 'Tidak Bekerja', 'slug' => 'not-working'],
            ['name' => 'Pelajar / Mahasiswa', 'slug' => 'student'],
            ['name' => 'Wiraswasta', 'slug' => 'entrepreneur'],
            ['name' => 'Pensiun', 'slug' => 'retired'],
            ['name' => 'Ibu Rumah Tangga', 'slug' => 'housewife'],
        ];

        foreach ($workStatuses as $item) {
            WorkStatus::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'is_active' => true,
                ]
            );
        }

        $residentStatuses = [
            ['name' => 'Warga Tetap', 'slug' => 'permanent'],
            ['name' => 'Warga Sementara', 'slug' => 'temporary'],
            ['name' => 'Pendatang', 'slug' => 'migrant'],
            ['name' => 'Warga Asli', 'slug' => 'native'],
        ];

        foreach ($residentStatuses as $item) {
            ResidentStatus::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'is_active' => true,
                ]
            );
        }

        $distributionTypes = [
            ['name' => 'Zakat', 'slug' => 'zakat'],
            ['name' => 'Qurban', 'slug' => 'qurban'],
        ];

        foreach ($distributionTypes as $item) {
            DistributionType::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'is_active' => true,
                ]
            );
        }

        $distributionClassSources = [
            ['name' => 'Amil', 'slug' => 'amil'],
            ['name' => 'Fakir Miskin', 'slug' => 'fakir-miskin'],
            ['name' => 'Yatim/Piatu', 'slug' => 'yatim-piatu'],
        ];

        foreach ($distributionClassSources as $item) {
            DistributionClassSource::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'is_active' => true,
                ]
            );
        }

        $distributionClasses = [
            ['source' => 'amil', 'year' => 2026, 'get_money' => 150000, 'get_rice' => 1],
            ['source' => 'fakir-miskin', 'year' => 2026, 'get_money' => 100000, 'get_rice' => 1],
            ['source' => 'yatim-piatu', 'year' => 2026, 'get_money' => 50000, 'get_rice' => 1],
        ];

        $organizations = Organization::query()
            ->whereIn('slug', ['islamic-center-alamanah', 'darul-muminin'])
            ->get()
            ->keyBy('slug');

        $sourceMap = DistributionClassSource::query()
            ->whereIn('slug', collect($distributionClassSources)->pluck('slug'))
            ->get()
            ->keyBy('slug');

        foreach ($distributionClasses as $item) {
            $source = $sourceMap->get($item['source']);
            if (! $source) {
                continue;
            }

            foreach ($organizations as $organization) {
                DistributionClass::updateOrCreate(
                    [
                        'organization_id' => $organization->id,
                        'distribution_class_source_id' => $source->id,
                        'year' => $item['year'],
                    ],
                    [
                        'get_money' => $item['get_money'] ?? null,
                        'get_rice' => $item['get_rice'] ?? null,
                        'description' => $item['description'] ?? null,
                        'is_active' => true,
                    ]
                );
            }
        }

        $charityTypeSources = [
            ['name' => 'Zakat Fitrah', 'slug' => 'zakat-fitrah'],
            ['name' => 'Zakat Mal', 'slug' => 'zakat-mal'],
            ['name' => 'Fidyah', 'slug' => 'fidyah'],
            ['name' => 'Infaq', 'slug' => 'infaq'],
            ['name' => 'Sedekah', 'slug' => 'sedekah'],
            ['name' => 'Waqf', 'slug' => 'waqf'],
        ];

        foreach ($charityTypeSources as $item) {
            CharityTypeSource::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'is_active' => true,
                ]
            );
        }

        $sourceMap = CharityTypeSource::query()
            ->whereIn('slug', collect($charityTypeSources)->pluck('slug'))
            ->get()
            ->keyBy('slug');

        $charitySettings2026 = [
            'islamic-center-alamanah' => [
                ['source' => 'zakat-fitrah', 'min_amount' => 40000, 'max_amount' => 50000, 'is_rice' => true, 'total_rice' => 2.0, 'package_amount' => 32000],
                ['source' => 'fidyah', 'min_amount' => 40000, 'max_amount' => 60000, 'is_rice' => true, 'total_rice' => 2.5],
                ['source' => 'zakat-mal'],
                ['source' => 'infaq'],
                ['source' => 'sedekah'],
                ['source' => 'waqf'],
            ],
            'darul-muminin' => [
                ['source' => 'zakat-fitrah', 'min_amount' => 45000, 'max_amount' => 55000, 'is_rice' => true, 'total_rice' => 2.5, 'package_amount' => 35000],
                ['source' => 'fidyah', 'min_amount' => 45000, 'max_amount' => 65000, 'is_rice' => true, 'total_rice' => 2.0],
                ['source' => 'zakat-mal'],
                ['source' => 'infaq'],
                ['source' => 'sedekah'],
                ['source' => 'waqf'],
            ],
        ];

        foreach ($charitySettings2026 as $orgSlug => $settings) {
            $organization = $organizations->get($orgSlug);
            if (! $organization) {
                continue;
            }

            foreach ($settings as $item) {
                $source = $sourceMap->get($item['source']);
                if (! $source) {
                    continue;
                }

                CharityType::updateOrCreate(
                    [
                        'organization_id' => $organization->id,
                        'charity_type_source_id' => $source->id,
                        'year' => 2026,
                    ],
                    [
                        'min_amount' => $item['min_amount'] ?? null,
                        'max_amount' => $item['max_amount'] ?? null,
                        'is_rice' => $item['is_rice'] ?? false,
                        'total_rice' => $item['total_rice'] ?? null,
                        'package_amount' => $item['package_amount'] ?? null,
                        'description' => $item['description'] ?? null,
                        'is_active' => true,
                    ]
                );
            }
        }

        $banks = [
            ['name' => 'Bank BCA', 'slug' => 'bca', 'code' => '014'],
            ['name' => 'Bank BRI', 'slug' => 'bri', 'code' => '002'],
            ['name' => 'Bank BNI', 'slug' => 'bni', 'code' => '009'],
            ['name' => 'Bank Mandiri', 'slug' => 'mandiri', 'code' => '008'],
            ['name' => 'Bank Syariah Indonesia', 'slug' => 'bsi', 'code' => '451'],
        ];

        foreach ($banks as $item) {
            Bank::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'code' => $item['code'] ?? null,
                    'description' => $item['description'] ?? null,
                    'is_active' => true,
                ]
            );
        }
    }
}
