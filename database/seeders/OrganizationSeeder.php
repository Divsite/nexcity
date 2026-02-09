<?php

namespace Database\Seeders;

use App\Models\Locations\CitizensAssociation;
use App\Models\Locations\City;
use App\Models\Locations\District;
use App\Models\Locations\NeighborhoodAssociation;
use App\Models\Locations\Province;
use App\Models\Locations\Village;
use App\Models\Masters\Education;
use App\Models\Masters\EducationMajor;
use App\Models\Masters\MaritalStatus;
use App\Models\Masters\OwnershipStatus;
use App\Models\Masters\Religion;
use App\Models\Masters\ResidenceStatus;
use App\Models\Organizations\Organization;
use App\Models\Organizations\OrganizationCategory;
use App\Models\Organizations\OrganizationCorporateProfile;
use App\Models\Organizations\OrganizationInstitutionProfile;
use App\Models\Organizations\OrganizationMosqueProfile;
use App\Models\Organizations\OrganizationRtProfile;
use App\Models\Organizations\OrganizationUmkmProfile;
use App\Models\Organizations\UserLevel;
use App\Models\Organizations\UserLevelPermission;
use App\Models\Profiles\UserMosqueProfile;
use App\Models\Profiles\UserResidentProfile;
use App\Models\Profiles\UserRtProfile;
use App\Models\Users\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $province = Province::where('code', '36')->first();
        $city = City::where('code', '3674')->first();
        $district = District::where('code', '367404')->first();
        $village = Village::where('code', '3674041004')->first();
        $rw03 = CitizensAssociation::where('village_id', $village?->id)->where('slug', 'rw003')->first();

        if (! $province || ! $city || ! $district || ! $village || ! $rw03) {
            $this->command?->warn('Location master data missing. Run LocationSeeder first.');

            return;
        }

        $neighborhoods = NeighborhoodAssociation::where('citizens_association_id', $rw03->id)
            ->get()
            ->keyBy('number');

        $residenceStatuses = ResidenceStatus::all()->keyBy('slug');
        $maritalStatuses = MaritalStatus::all()->keyBy('slug');
        $educations = Education::all()->keyBy('slug');
        $educationMajors = EducationMajor::all()->keyBy('slug');
        $religions = Religion::all()->keyBy('slug');
        $ownershipStatuses = OwnershipStatus::all()->keyBy('slug');

        $defaultResidenceStatusId = $residenceStatuses->get('owned')?->id ?? $residenceStatuses->first()?->id;
        $defaultMaritalStatusId = $maritalStatuses->get('married')?->id ?? $maritalStatuses->first()?->id;
        $defaultEducationId = $educations->get('sma')?->id ?? $educations->first()?->id;
        $defaultEducationMajorId = $educationMajors->get('general')?->id ?? $educationMajors->first()?->id;
        $defaultReligionId = $religions->get('islam')?->id ?? $religions->first()?->id;
        $defaultOwnershipStatusId = $ownershipStatuses->get('wakaf')?->id ?? $ownershipStatuses->first()?->id;

        if (! $defaultResidenceStatusId || ! $defaultMaritalStatusId || ! $defaultEducationId || ! $defaultEducationMajorId || ! $defaultReligionId || ! $defaultOwnershipStatusId) {
            $this->command?->warn('Master data missing. Run MasterDataSeeder first.');

            return;
        }

        $resolveMasterId = function ($collection, ?string $slug, ?int $fallbackId) {
            if ($slug && $collection->has($slug)) {
                return $collection->get($slug)->id;
            }

            return $fallbackId;
        };

        $categoryMap = collect([
            'corporate' => [
                'name' => 'Corporate',
                'description' => 'Divsite Teknologi dan internal superadmin.',
            ],
            'mosque' => [
                'name' => 'Masjid',
                'description' => 'Kemitraan DKM dan lembaga masjid.',
            ],
            'residential' => [
                'name' => 'Lingkungan RT/RW',
                'description' => 'Kemitraan RT/RW dan warga.',
            ],
            'umkm' => [
                'name' => 'UMKM',
                'description' => 'Kemitraan pelaku usaha kecil dan menengah.',
            ],
            'institution' => [
                'name' => 'Institusi',
                'description' => 'Kemitraan institusi pendidikan dan lembaga formal.',
            ],
        ])->mapWithKeys(function ($data, $slug) {
            $record = OrganizationCategory::updateOrCreate(
                ['slug' => $slug],
                ['name' => $data['name'], 'description' => $data['description']]
            );

            return [$slug => $record];
        });

        $locationIds = function (?string $rtCode) use ($province, $city, $district, $village, $rw03, $neighborhoods) {
            return [
                'country_id' => $province->country_id,
                'province_id' => $province->id,
                'city_id' => $city->id,
                'district_id' => $district->id,
                'village_id' => $village->id,
                'citizens_association_id' => $rw03->id,
                'neighborhood_association_id' => $rtCode && $neighborhoods->has($rtCode)
                    ? $neighborhoods->get($rtCode)->id
                    : null,
            ];
        };

        $addressData = function (string $addressLine) {
            return [
                'address_line' => $addressLine,
            ];
        };

        $residentProfile = function (string $rtCode, string $addressLine, array $extra = []) use (
            $locationIds,
            $addressData,
            $resolveMasterId,
            $residenceStatuses,
            $maritalStatuses,
            $educations,
            $educationMajors,
            $religions,
            $defaultResidenceStatusId,
            $defaultMaritalStatusId,
            $defaultEducationId,
            $defaultEducationMajorId,
            $defaultReligionId
        ) {
            $locations = $locationIds($rtCode);

            $residenceSlug = $extra['residence_status_slug'] ?? null;
            $maritalSlug = $extra['marital_status_slug'] ?? null;
            $educationSlug = $extra['education_slug'] ?? null;
            $educationMajorSlug = $extra['education_major_slug'] ?? null;
            $religionSlug = $extra['religion_slug'] ?? null;

            unset(
                $extra['residence_status_slug'],
                $extra['marital_status_slug'],
                $extra['education_slug'],
                $extra['education_major_slug'],
                $extra['religion_slug']
            );

            $masterData = [
                'residence_status_id' => $resolveMasterId($residenceStatuses, $residenceSlug, $defaultResidenceStatusId),
                'marital_status_id' => $resolveMasterId($maritalStatuses, $maritalSlug, $defaultMaritalStatusId),
                'education_id' => $resolveMasterId($educations, $educationSlug, $defaultEducationId),
                'education_major_id' => $resolveMasterId($educationMajors, $educationMajorSlug, $defaultEducationMajorId),
                'religion_id' => $resolveMasterId($religions, $religionSlug, $defaultReligionId),
            ];

            return array_merge(
                $locations,
                $addressData($addressLine),
                $masterData,
                $extra
            );
        };

        $organizationProfileData = function (string $rtCode, string $addressLine, array $extra = []) use ($addressData) {
            return array_merge(
                $addressData($addressLine),
                $extra
            );
        };

        $mosqueProfileData = function (array $extra = []) use ($resolveMasterId, $ownershipStatuses, $defaultOwnershipStatusId) {
            $ownershipSlug = $extra['ownership_status_slug'] ?? null;
            unset($extra['ownership_status_slug']);

            return array_merge(
                [
                    'ownership_status_id' => $resolveMasterId($ownershipStatuses, $ownershipSlug, $defaultOwnershipStatusId),
                ],
                $extra
            );
        };

        $rtProfileData = function (array $extra = []) {
            return array_merge(
                [
                    'period_start_date' => now()->startOfYear()->toDateString(),
                    'period_end_date' => now()->addYears(4)->endOfYear()->toDateString(),
                ],
                $extra
            );
        };

        $umkmProfileData = function (array $extra = []) {
            return array_merge(
                [
                    'business_type' => 'Retail',
                    'owner_name' => 'Owner UMKM',
                    'established_year' => now()->subYears(5)->format('Y'),
                    'employee_count' => 3,
                ],
                $extra
            );
        };

        $corporateProfileData = function (array $extra = []) {
            return array_merge(
                [
                    'industry' => 'Technology',
                    'established_year' => now()->subYears(8)->format('Y'),
                    'employee_count' => 12,
                    'hr_contact_name' => 'Divsite HR',
                    'hr_contact_email' => 'hr@divsite.local',
                ],
                $extra
            );
        };

        $institutionProfileData = function (array $extra = []) {
            return array_merge(
                [
                    'institution_type' => 'School',
                    'accreditation_status' => 'A',
                    'established_year' => now()->subYears(15)->format('Y'),
                    'student_count' => 250,
                ],
                $extra
            );
        };

        $mosqueLevels = [
            ['name' => 'Ketua DKM', 'slug' => 'mosque-superadmin', 'description' => 'Akses penuh modul masjid'],
            ['name' => 'Sekretaris Masjid', 'slug' => 'mosque-secretary', 'description' => 'Administrasi, laporan, dan data jamaah'],
            ['name' => 'Keuangan Masjid', 'slug' => 'mosque-finance', 'description' => 'Kelola zakat & laporan'],
            ['name' => 'Petugas Zakat', 'slug' => 'mosque-officer', 'description' => 'Distribusi & scan barcode'],
            ['name' => 'Petugas Qurban', 'slug' => 'mosque-qurban', 'description' => 'Kelola kupon qurban & scan'],
            ['name' => 'Petugas Inventaris', 'slug' => 'mosque-inventory', 'description' => 'Kelola inventaris masjid'],
            ['name' => 'Petugas Donasi/CRM', 'slug' => 'mosque-crm', 'description' => 'Kelola donatur & CRM'],
            ['name' => 'Humas/Media', 'slug' => 'mosque-humas', 'description' => 'Publikasi kegiatan & komunikasi jamaah'],
        ];

        $rtLevels = [
            ['name' => 'Ketua RT', 'slug' => 'rt-superadmin', 'description' => 'Akses penuh modul RT'],
            ['name' => 'Bendahara RT', 'slug' => 'rt-finance', 'description' => 'Kelola kas & iuran'],
            ['name' => 'Sekretaris RT', 'slug' => 'rt-secretary', 'description' => 'Administrasi program kerja'],
            ['name' => 'Petugas Lapangan', 'slug' => 'rt-field-officer', 'description' => 'Distribusi & scan QR warga'],
            ['name' => 'Humas/Media', 'slug' => 'rt-humas', 'description' => 'Publikasi kegiatan & komunikasi warga'],
        ];

        $mosqueLevelPermissions = [
            'mosque-superadmin' => [
                'browse-zakat', 'read-zakat', 'edit-zakat', 'add-zakat', 'delete-zakat',
                'browse-qurban', 'read-qurban', 'edit-qurban', 'add-qurban', 'delete-qurban',
                'browse-mosque-distributions', 'read-mosque-distributions', 'edit-mosque-distributions', 'add-mosque-distributions', 'delete-mosque-distributions',
                'browse-mosque-charity-reports', 'browse-mosque-distribution-reports',
                'browse-mosque-inventory', 'read-mosque-inventory', 'edit-mosque-inventory', 'add-mosque-inventory', 'delete-mosque-inventory',
                'browse-mosque-crm', 'read-mosque-crm', 'edit-mosque-crm', 'add-mosque-crm', 'delete-mosque-crm',
                'scan-qurban-coupon', 'scan-zakat-coupon',
            ],
            'mosque-secretary' => [
                'browse-zakat', 'read-zakat',
                'browse-mosque-distributions', 'read-mosque-distributions',
                'browse-mosque-charity-reports', 'browse-mosque-distribution-reports',
            ],
            'mosque-finance' => [
                'browse-zakat', 'read-zakat', 'edit-zakat', 'add-zakat',
                'browse-mosque-distributions', 'read-mosque-distributions',
                'browse-mosque-charity-reports', 'browse-mosque-distribution-reports',
            ],
            'mosque-officer' => [
                'browse-qurban', 'read-qurban',
                'browse-mosque-distributions', 'read-mosque-distributions',
                'scan-qurban-coupon', 'scan-zakat-coupon',
            ],
            'mosque-qurban' => [
                'browse-qurban', 'read-qurban', 'edit-qurban', 'add-qurban',
                'scan-qurban-coupon',
            ],
            'mosque-inventory' => [
                'browse-mosque-inventory', 'read-mosque-inventory', 'edit-mosque-inventory', 'add-mosque-inventory',
            ],
            'mosque-crm' => [
                'browse-mosque-crm', 'read-mosque-crm', 'edit-mosque-crm', 'add-mosque-crm',
            ],
            'mosque-humas' => [
                'browse-mosque-crm', 'read-mosque-crm',
                'browse-mosque-charity-reports', 'browse-mosque-distribution-reports',
            ],
        ];

        $rtLevelPermissions = [
            'rt-superadmin' => [
                'browse-rt-residents', 'read-rt-residents', 'edit-rt-residents', 'add-rt-residents', 'delete-rt-residents',
                'browse-rt-dues', 'read-rt-dues', 'edit-rt-dues', 'add-rt-dues', 'delete-rt-dues',
                'browse-rt-events', 'browse-rt-news', 'browse-rt-feedback', 'browse-rt-inventory', 'browse-rt-membership',
                'browse-rt-reports',
                'scan-resident-qr',
            ],
            'rt-secretary' => [
                'browse-rt-residents', 'read-rt-residents', 'edit-rt-residents', 'add-rt-residents',
                'browse-rt-events', 'browse-rt-news', 'browse-rt-feedback', 'browse-rt-membership',
                'browse-rt-reports',
            ],
            'rt-finance' => [
                'browse-rt-dues', 'read-rt-dues', 'edit-rt-dues', 'add-rt-dues',
                'browse-rt-reports',
            ],
            'rt-field-officer' => [
                'scan-resident-qr',
            ],
            'rt-humas' => [
                'browse-rt-events', 'browse-rt-news', 'browse-rt-feedback',
                'browse-rt-reports',
            ],
        ];

        $organizations = [
            [
                'name' => 'Divsite Teknologi',
                'slug' => 'divsite-teknologi',
                'type' => Organization::TYPE_INSTITUTION,
                'category_slug' => 'corporate',
                'status' => 'active',
                'email' => 'hello@divsite.local',
                'phone' => '+62 811 2222 3333',
                'timezone' => 'Asia/Jakarta',
                'location' => $locationIds('001'),
                'profile' => $organizationProfileData('001', 'Jl. H. Jamat No. 12', [
                    'description' => 'Divsite Teknologi memegang kendali penuh sebagai superadmin Nexcity.',
                ]),
                'corporate_profile' => $corporateProfileData([
                    'industry' => 'Technology Consulting',
                    'employee_count' => 18,
                ]),
                'levels' => [
                    ['name' => 'Divsite Superadmin', 'slug' => 'divsite-superadmin', 'description' => 'Kontrol penuh Nexcity', 'is_global' => true],
                    ['name' => 'Divsite Operations', 'slug' => 'divsite-ops', 'description' => 'Operasional dan dukungan mitra', 'is_global' => true],
                ],
                'members' => [
                    ['email' => 'hq.superadmin@nexcity.local', 'role' => 'superadmin', 'level_slug' => 'divsite-superadmin', 'is_primary' => true],
                    ['email' => 'samsulhadi@samsulhadiss.com', 'role' => 'superadmin', 'level_slug' => 'divsite-superadmin'],
                    ['email' => 'superadmin@superadmin.com', 'role' => 'superadmin', 'level_slug' => 'divsite-ops'],
                ],
            ],
            [
                'name' => 'Islamic Center Alamanah',
                'slug' => 'islamic-center-alamanah',
                'type' => Organization::TYPE_MOSQUE,
                'category_slug' => 'mosque',
                'status' => 'active',
                'email' => 'alamanah@nexcity.local',
                'phone' => '+62 813 2222 1111',
                'timezone' => 'Asia/Jakarta',
                'location' => $locationIds('004'),
                'profile' => $organizationProfileData('004', 'Jl. Kenanga No. 21', [
                    'description' => 'Islamic Center Alamanah fokus pada digitalisasi zakat, qurban, dan distribusi.',
                ]),
                'mosque_profile' => $mosqueProfileData([
                    'ownership_status_slug' => 'wakaf',
                    'built_year' => 1998,
                    'floor_area' => 450.50,
                    'floor_count' => 2,
                    'latitude' => -6.2671234,
                    'longitude' => 106.7455678,
                    'owner_name' => 'Yayasan Alamanah',
                    'property_value' => 3500000000,
                    'certification_number' => 'DKM-ALAMANAH-1998',
                    'certification_status' => 'verified',
                ]),
                'levels' => $mosqueLevels,
                'members' => [
                    [
                        'email' => 'alamanah.superadmin@nexcity.local',
                        'role' => 'mosque_admin',
                        'level_slug' => 'mosque-superadmin',
                        'is_primary' => true,
                        'mosque_profile' => [
                            'position' => 'Ketua DKM',
                            'responsibility_area' => 'Strategi masjid & koordinasi program',
                            'service_start_date' => now()->subYears(3)->toDateString(),
                            'phone' => '+62 813 1234 1111',
                        ],
                    ],
                    [
                        'email' => 'alamanah.finance@nexcity.local',
                        'role' => 'mosque_admin',
                        'level_slug' => 'mosque-finance',
                        'mosque_profile' => [
                            'position' => 'Bendahara',
                            'responsibility_area' => 'Pengelolaan donasi dan laporan amal',
                            'service_start_date' => now()->subYears(2)->toDateString(),
                            'phone' => '+62 812 0000 2222',
                        ],
                    ],
                    [
                        'email' => 'alamanah.secretary@nexcity.local',
                        'role' => 'mosque_admin',
                        'level_slug' => 'mosque-secretary',
                        'mosque_profile' => [
                            'position' => 'Sekretaris',
                            'responsibility_area' => 'Administrasi dan pelaporan masjid',
                            'service_start_date' => now()->subYears(2)->toDateString(),
                            'phone' => '+62 813 2222 9999',
                        ],
                    ],
                    [
                        'email' => 'alamanah.officer@nexcity.local',
                        'role' => 'mosque_admin',
                        'level_slug' => 'mosque-officer',
                        'mosque_profile' => [
                            'position' => 'Petugas Zakat',
                            'responsibility_area' => 'Distribusi, pencatatan, dan scan barcode warga',
                            'service_start_date' => now()->subYear()->toDateString(),
                            'phone' => '+62 819 0000 5555',
                        ],
                    ],
                    [
                        'email' => 'alamanah.qurban@nexcity.local',
                        'role' => 'mosque_admin',
                        'level_slug' => 'mosque-qurban',
                        'mosque_profile' => [
                            'position' => 'Petugas Qurban',
                            'responsibility_area' => 'Kupon qurban dan distribusi daging',
                            'service_start_date' => now()->subYear()->toDateString(),
                            'phone' => '+62 813 7777 1111',
                        ],
                    ],
                    [
                        'email' => 'alamanah.inventory@nexcity.local',
                        'role' => 'mosque_admin',
                        'level_slug' => 'mosque-inventory',
                        'mosque_profile' => [
                            'position' => 'Petugas Inventaris',
                            'responsibility_area' => 'Pendataan dan perawatan aset masjid',
                            'service_start_date' => now()->subMonths(9)->toDateString(),
                            'phone' => '+62 813 5555 0001',
                        ],
                    ],
                    [
                        'email' => 'alamanah.crm@nexcity.local',
                        'role' => 'mosque_admin',
                        'level_slug' => 'mosque-crm',
                        'mosque_profile' => [
                            'position' => 'Petugas Donasi',
                            'responsibility_area' => 'Relasi donatur dan CRM',
                            'service_start_date' => now()->subMonths(6)->toDateString(),
                            'phone' => '+62 813 5555 0002',
                        ],
                    ],
                    [
                        'email' => 'alamanah.humas@nexcity.local',
                        'role' => 'mosque_admin',
                        'level_slug' => 'mosque-humas',
                        'mosque_profile' => [
                            'position' => 'Humas/Media',
                            'responsibility_area' => 'Publikasi kegiatan masjid dan komunikasi jamaah',
                            'service_start_date' => now()->subMonths(4)->toDateString(),
                            'phone' => '+62 813 5555 0003',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Darul Muminin',
                'slug' => 'darul-muminin',
                'type' => Organization::TYPE_MOSQUE,
                'category_slug' => 'mosque',
                'status' => 'active',
                'email' => 'darulmuminin@nexcity.local',
                'phone' => '+62 813 5555 6666',
                'timezone' => 'Asia/Jakarta',
                'location' => $locationIds('003'),
                'profile' => $organizationProfileData('003', 'Jl. Dahlia No. 2', [
                    'description' => 'Darul Muminin melayani jamaah & program sosial di wilayah RT 03.',
                ]),
                'mosque_profile' => $mosqueProfileData([
                    'ownership_status_slug' => 'owned',
                    'built_year' => 2005,
                    'floor_area' => 320.75,
                    'floor_count' => 1,
                    'latitude' => -6.2683321,
                    'longitude' => 106.7461134,
                    'owner_name' => 'DKM Darul Muminin',
                    'property_value' => 2200000000,
                    'certification_number' => 'DKM-DM-2005',
                    'certification_status' => 'verified',
                ]),
                'levels' => $mosqueLevels,
                'members' => [
                    [
                        'email' => 'darulmuminin.superadmin@nexcity.local',
                        'role' => 'mosque_admin',
                        'level_slug' => 'mosque-superadmin',
                        'is_primary' => true,
                        'mosque_profile' => [
                            'position' => 'Ketua Yayasan',
                            'responsibility_area' => 'Pengembangan layanan jamaah & kemitraan',
                            'service_start_date' => now()->subYears(4)->toDateString(),
                            'phone' => '+62 813 5678 2222',
                        ],
                    ],
                    [
                        'email' => 'darulmuminin.finance@nexcity.local',
                        'role' => 'mosque_admin',
                        'level_slug' => 'mosque-finance',
                        'mosque_profile' => [
                            'position' => 'Bendahara',
                            'responsibility_area' => 'Keuangan amal & qurban',
                            'service_start_date' => now()->subYears(2)->toDateString(),
                            'phone' => '+62 812 3333 4444',
                        ],
                    ],
                    [
                        'email' => 'darulmuminin.secretary@nexcity.local',
                        'role' => 'mosque_admin',
                        'level_slug' => 'mosque-secretary',
                        'mosque_profile' => [
                            'position' => 'Sekretaris',
                            'responsibility_area' => 'Administrasi dan pelaporan masjid',
                            'service_start_date' => now()->subYears(2)->toDateString(),
                            'phone' => '+62 813 7777 2222',
                        ],
                    ],
                    [
                        'email' => 'darulmuminin.officer@nexcity.local',
                        'role' => 'mosque_admin',
                        'level_slug' => 'mosque-officer',
                        'mosque_profile' => [
                            'position' => 'Petugas Operasional',
                            'responsibility_area' => 'Distribusi logistik & barcode warga',
                            'service_start_date' => now()->subMonths(10)->toDateString(),
                            'phone' => '+62 819 6666 7777',
                        ],
                    ],
                    [
                        'email' => 'darulmuminin.qurban@nexcity.local',
                        'role' => 'mosque_admin',
                        'level_slug' => 'mosque-qurban',
                        'mosque_profile' => [
                            'position' => 'Petugas Qurban',
                            'responsibility_area' => 'Kupon qurban dan distribusi daging',
                            'service_start_date' => now()->subYear()->toDateString(),
                            'phone' => '+62 813 7777 3333',
                        ],
                    ],
                    [
                        'email' => 'darulmuminin.inventory@nexcity.local',
                        'role' => 'mosque_admin',
                        'level_slug' => 'mosque-inventory',
                        'mosque_profile' => [
                            'position' => 'Petugas Inventaris',
                            'responsibility_area' => 'Pendataan dan perawatan aset masjid',
                            'service_start_date' => now()->subMonths(9)->toDateString(),
                            'phone' => '+62 813 7777 4444',
                        ],
                    ],
                    [
                        'email' => 'darulmuminin.crm@nexcity.local',
                        'role' => 'mosque_admin',
                        'level_slug' => 'mosque-crm',
                        'mosque_profile' => [
                            'position' => 'Petugas Donasi',
                            'responsibility_area' => 'Relasi donatur dan CRM',
                            'service_start_date' => now()->subMonths(6)->toDateString(),
                            'phone' => '+62 813 7777 5555',
                        ],
                    ],
                    [
                        'email' => 'darulmuminin.humas@nexcity.local',
                        'role' => 'mosque_admin',
                        'level_slug' => 'mosque-humas',
                        'mosque_profile' => [
                            'position' => 'Humas/Media',
                            'responsibility_area' => 'Publikasi kegiatan masjid dan komunikasi jamaah',
                            'service_start_date' => now()->subMonths(4)->toDateString(),
                            'phone' => '+62 813 7777 6666',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Masjid Al Falah',
                'slug' => 'masjid-al-falah',
                'type' => Organization::TYPE_MOSQUE,
                'category_slug' => 'mosque',
                'status' => 'active',
                'email' => 'alfalah@nexcity.local',
                'phone' => '+62 813 7654 1212',
                'timezone' => 'Asia/Jakarta',
                'location' => $locationIds('005'),
                'profile' => $organizationProfileData('005', 'Jl. Anggrek No. 7', [
                    'description' => 'Masjid Al Falah melayani wilayah RT 05 & fokus pada pelaporan transparan.',
                ]),
                'mosque_profile' => $mosqueProfileData([
                    'ownership_status_slug' => 'wakaf',
                    'built_year' => 2010,
                    'floor_area' => 280.25,
                    'floor_count' => 2,
                    'latitude' => -6.2667789,
                    'longitude' => 106.7445566,
                    'owner_name' => 'Yayasan Al Falah',
                    'property_value' => 1800000000,
                    'certification_number' => 'DKM-ALF-2010',
                    'certification_status' => 'verified',
                ]),
                'levels' => $mosqueLevels,
                'members' => [
                    [
                        'email' => 'alfalah.superadmin@nexcity.local',
                        'role' => 'mosque_admin',
                        'level_slug' => 'mosque-superadmin',
                        'is_primary' => true,
                        'mosque_profile' => [
                            'position' => 'Ketua DKM',
                            'responsibility_area' => 'Koordinasi program utama Masjid Al Falah',
                            'service_start_date' => now()->subYears(5)->toDateString(),
                            'phone' => '+62 813 8765 9988',
                        ],
                    ],
                    [
                        'email' => 'alfalah.finance@nexcity.local',
                        'role' => 'mosque_admin',
                        'level_slug' => 'mosque-finance',
                        'mosque_profile' => [
                            'position' => 'Bendahara',
                            'responsibility_area' => 'Pelaporan dana dan manajemen kas',
                            'service_start_date' => now()->subYears(3)->toDateString(),
                            'phone' => '+62 812 4455 2211',
                        ],
                    ],
                    [
                        'email' => 'alfalah.secretary@nexcity.local',
                        'role' => 'mosque_admin',
                        'level_slug' => 'mosque-secretary',
                        'mosque_profile' => [
                            'position' => 'Sekretaris',
                            'responsibility_area' => 'Administrasi dan pelaporan masjid',
                            'service_start_date' => now()->subYears(2)->toDateString(),
                            'phone' => '+62 813 8888 2222',
                        ],
                    ],
                    [
                        'email' => 'alfalah.officer@nexcity.local',
                        'role' => 'mosque_admin',
                        'level_slug' => 'mosque-officer',
                        'mosque_profile' => [
                            'position' => 'Petugas Lapangan',
                            'responsibility_area' => 'Pelaksanaan distribusi & pemindaian barcode',
                            'service_start_date' => now()->subMonths(8)->toDateString(),
                            'phone' => '+62 819 8899 0011',
                        ],
                    ],
                    [
                        'email' => 'alfalah.qurban@nexcity.local',
                        'role' => 'mosque_admin',
                        'level_slug' => 'mosque-qurban',
                        'mosque_profile' => [
                            'position' => 'Petugas Qurban',
                            'responsibility_area' => 'Kupon qurban dan distribusi daging',
                            'service_start_date' => now()->subYear()->toDateString(),
                            'phone' => '+62 813 8888 3333',
                        ],
                    ],
                    [
                        'email' => 'alfalah.inventory@nexcity.local',
                        'role' => 'mosque_admin',
                        'level_slug' => 'mosque-inventory',
                        'mosque_profile' => [
                            'position' => 'Petugas Inventaris',
                            'responsibility_area' => 'Pendataan dan perawatan aset masjid',
                            'service_start_date' => now()->subMonths(9)->toDateString(),
                            'phone' => '+62 813 8888 4444',
                        ],
                    ],
                    [
                        'email' => 'alfalah.crm@nexcity.local',
                        'role' => 'mosque_admin',
                        'level_slug' => 'mosque-crm',
                        'mosque_profile' => [
                            'position' => 'Petugas Donasi',
                            'responsibility_area' => 'Relasi donatur dan CRM',
                            'service_start_date' => now()->subMonths(6)->toDateString(),
                            'phone' => '+62 813 8888 5555',
                        ],
                    ],
                    [
                        'email' => 'alfalah.humas@nexcity.local',
                        'role' => 'mosque_admin',
                        'level_slug' => 'mosque-humas',
                        'mosque_profile' => [
                            'position' => 'Humas/Media',
                            'responsibility_area' => 'Publikasi kegiatan masjid dan komunikasi jamaah',
                            'service_start_date' => now()->subMonths(4)->toDateString(),
                            'phone' => '+62 813 8888 6666',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'RT 01 RW 03 Jurang Mangu Barat',
                'slug' => 'rt-01-rw03-jurang-mangu-barat',
                'type' => Organization::TYPE_RT,
                'category_slug' => 'residential',
                'status' => 'active',
                'email' => 'rt01@nexcity.local',
                'phone' => '+62 812 9000 1001',
                'timezone' => 'Asia/Jakarta',
                'location' => $locationIds('001'),
                'profile' => $organizationProfileData('001', 'Posko RT 01 - Jl. Melati No. 5', [
                    'description' => 'RT 01 RW 03 fokus pada digitalisasi iuran dan layanan warga.',
                ]),
                'rt_profile' => $rtProfileData([
                    'office_phone' => '+62 812 9000 1001',
                    'office_address' => 'Posko RT 01 - Jl. Melati No. 5',
                    'notes' => 'Periode kepengurusan RT 01 RW 03 Jurang Mangu Barat.',
                ]),
                'levels' => $rtLevels,
                'members' => [
                    [
                        'email' => 'rt01.superadmin@nexcity.local',
                        'role' => 'rt_admin',
                        'level_slug' => 'rt-superadmin',
                        'is_primary' => true,
                        'rt_profile' => [
                            'position' => 'Ketua RT',
                            'responsibility_area' => 'Koordinasi program RT dan pelayanan warga',
                            'service_start_date' => now()->subYears(2)->toDateString(),
                            'phone' => '+62 811 0000 1111',
                        ],
                    ],
                    [
                        'email' => 'rt01.finance@nexcity.local',
                        'role' => 'rt_admin',
                        'level_slug' => 'rt-finance',
                        'rt_profile' => [
                            'position' => 'Bendahara',
                            'responsibility_area' => 'Kas RT dan laporan iuran warga',
                            'service_start_date' => now()->subYears(2)->toDateString(),
                            'phone' => '+62 811 0000 2222',
                        ],
                    ],
                    [
                        'email' => 'rt01.secretary@nexcity.local',
                        'role' => 'rt_admin',
                        'level_slug' => 'rt-secretary',
                        'rt_profile' => [
                            'position' => 'Sekretaris',
                            'responsibility_area' => 'Administrasi RT dan dokumentasi program',
                            'service_start_date' => now()->subYears(2)->toDateString(),
                            'phone' => '+62 811 0000 3333',
                        ],
                    ],
                    [
                        'email' => 'rt01.field@nexcity.local',
                        'role' => 'rt_admin',
                        'level_slug' => 'rt-field-officer',
                        'rt_profile' => [
                            'position' => 'Petugas Lapangan',
                            'responsibility_area' => 'Distribusi & scan QR warga',
                            'service_start_date' => now()->subYear()->toDateString(),
                            'phone' => '+62 811 0000 4444',
                        ],
                    ],
                    [
                        'email' => 'rt01.humas@nexcity.local',
                        'role' => 'rt_admin',
                        'level_slug' => 'rt-humas',
                        'rt_profile' => [
                            'position' => 'Humas/Media',
                            'responsibility_area' => 'Publikasi kegiatan & komunikasi warga',
                            'service_start_date' => now()->subYear()->toDateString(),
                            'phone' => '+62 811 0000 4555',
                        ],
                    ],
                    [
                        'email' => 'resident.rt01.ahmad@nexcity.local',
                        'role' => 'resident',
                        'resident_profile' => $residentProfile('001', 'Jl. Melati No. 5', [
                            'family_card_number' => '36740410040010008',
                            'national_id_number' => '3674040808080008',
                            'birth_place' => 'Tangerang Selatan',
                            'birth_date' => now()->subYears(45)->toDateString(),
                            'gender' => 'male',
                            'marital_status_slug' => 'married',
                            'occupation' => 'Wiraswasta',
                        ]),
                    ],
                    [
                        'email' => 'resident.rt01.andi@nexcity.local',
                        'role' => 'resident',
                        'resident_profile' => $residentProfile('001', 'Jl. Melati No. 11', [
                            'family_card_number' => '36740410040010004',
                            'national_id_number' => '3674040404040004',
                            'birth_place' => 'Tangerang Selatan',
                            'birth_date' => now()->subYears(29)->toDateString(),
                            'gender' => 'male',
                            'marital_status_slug' => 'married',
                            'occupation' => 'Teknisi',
                        ]),
                    ],
                    [
                        'email' => 'resident.rt01.siti@nexcity.local',
                        'role' => 'resident',
                        'resident_profile' => $residentProfile('001', 'Jl. Melati No. 13', [
                            'family_card_number' => '36740410040010005',
                            'national_id_number' => '3674040505050005',
                            'birth_place' => 'Tangerang Selatan',
                            'birth_date' => now()->subYears(27)->toDateString(),
                            'gender' => 'female',
                            'marital_status_slug' => 'married',
                            'occupation' => 'Guru TK',
                        ]),
                    ],
                ],
            ],
            [
                'name' => 'RT 02 RW 03 Jurang Mangu Barat',
                'slug' => 'rt-02-rw03-jurang-mangu-barat',
                'type' => Organization::TYPE_RT,
                'category_slug' => 'residential',
                'status' => 'active',
                'email' => 'rt02@nexcity.local',
                'phone' => '+62 812 9000 1002',
                'timezone' => 'Asia/Jakarta',
                'location' => $locationIds('002'),
                'profile' => $organizationProfileData('002', 'Posko RT 02 - Jl. Cemara No. 3', [
                    'description' => 'RT 02 RW 03 menata program kerja dan inventaris digital.',
                ]),
                'rt_profile' => $rtProfileData([
                    'office_phone' => '+62 812 9000 1002',
                    'office_address' => 'Posko RT 02 - Jl. Cemara No. 3',
                    'notes' => 'Periode kepengurusan RT 02 RW 03 Jurang Mangu Barat.',
                ]),
                'levels' => $rtLevels,
                'members' => [
                    [
                        'email' => 'rt02.superadmin@nexcity.local',
                        'role' => 'rt_admin',
                        'level_slug' => 'rt-superadmin',
                        'is_primary' => true,
                        'rt_profile' => [
                            'position' => 'Ketua RT',
                            'responsibility_area' => 'Koordinasi program RT dan pelayanan warga',
                            'service_start_date' => now()->subYears(2)->toDateString(),
                            'phone' => '+62 811 0000 4444',
                        ],
                    ],
                    [
                        'email' => 'rt02.finance@nexcity.local',
                        'role' => 'rt_admin',
                        'level_slug' => 'rt-finance',
                        'rt_profile' => [
                            'position' => 'Bendahara',
                            'responsibility_area' => 'Kas RT dan laporan iuran warga',
                            'service_start_date' => now()->subYears(2)->toDateString(),
                            'phone' => '+62 811 0000 5555',
                        ],
                    ],
                    [
                        'email' => 'rt02.secretary@nexcity.local',
                        'role' => 'rt_admin',
                        'level_slug' => 'rt-secretary',
                        'rt_profile' => [
                            'position' => 'Sekretaris',
                            'responsibility_area' => 'Administrasi RT dan dokumentasi program',
                            'service_start_date' => now()->subYears(2)->toDateString(),
                            'phone' => '+62 811 0000 6666',
                        ],
                    ],
                    [
                        'email' => 'rt02.field@nexcity.local',
                        'role' => 'rt_admin',
                        'level_slug' => 'rt-field-officer',
                        'rt_profile' => [
                            'position' => 'Petugas Lapangan',
                            'responsibility_area' => 'Distribusi & scan QR warga',
                            'service_start_date' => now()->subYear()->toDateString(),
                            'phone' => '+62 811 0000 7777',
                        ],
                    ],
                    [
                        'email' => 'rt02.humas@nexcity.local',
                        'role' => 'rt_admin',
                        'level_slug' => 'rt-humas',
                        'rt_profile' => [
                            'position' => 'Humas/Media',
                            'responsibility_area' => 'Publikasi kegiatan & komunikasi warga',
                            'service_start_date' => now()->subYear()->toDateString(),
                            'phone' => '+62 811 0000 7888',
                        ],
                    ],
                    [
                        'email' => 'resident.rt02.budi@nexcity.local',
                        'role' => 'resident',
                        'resident_profile' => $residentProfile('002', 'Jl. Cemara No. 3', [
                            'family_card_number' => '36740410040020008',
                            'national_id_number' => '3674041818180018',
                            'birth_place' => 'Tangerang Selatan',
                            'birth_date' => now()->subYears(42)->toDateString(),
                            'gender' => 'male',
                            'marital_status_slug' => 'married',
                            'occupation' => 'Wiraswasta',
                        ]),
                    ],
                    [
                        'email' => 'resident.rt02.rudi@nexcity.local',
                        'role' => 'resident',
                        'resident_profile' => $residentProfile('002', 'Jl. Cemara No. 11', [
                            'family_card_number' => '36740410040020004',
                            'national_id_number' => '3674040909090009',
                            'birth_place' => 'Tangerang Selatan',
                            'birth_date' => now()->subYears(28)->toDateString(),
                            'gender' => 'male',
                            'marital_status_slug' => 'married',
                            'occupation' => 'Wiraswasta',
                        ]),
                    ],
                    [
                        'email' => 'resident.rt02.maya@nexcity.local',
                        'role' => 'resident',
                        'resident_profile' => $residentProfile('002', 'Jl. Cemara No. 13', [
                            'family_card_number' => '36740410040020005',
                            'national_id_number' => '3674041010100010',
                            'birth_place' => 'Tangerang Selatan',
                            'birth_date' => now()->subYears(26)->toDateString(),
                            'gender' => 'female',
                            'marital_status_slug' => 'single',
                            'occupation' => 'Content Creator',
                        ]),
                    ],
                ],
            ],
            [
                'name' => 'RT 03 RW 03 Jurang Mangu Barat',
                'slug' => 'rt-03-rw03-jurang-mangu-barat',
                'type' => Organization::TYPE_RT,
                'category_slug' => 'residential',
                'status' => 'active',
                'email' => 'rt03@nexcity.local',
                'phone' => '+62 812 9000 1003',
                'timezone' => 'Asia/Jakarta',
                'location' => $locationIds('003'),
                'profile' => $organizationProfileData('003', 'Posko RT 03 - Jl. Dahlia No. 2', [
                    'description' => 'RT 03 RW 03 memperkuat publikasi, event, dan laporan transparan.',
                ]),
                'rt_profile' => $rtProfileData([
                    'office_phone' => '+62 812 9000 1003',
                    'office_address' => 'Posko RT 03 - Jl. Dahlia No. 2',
                    'notes' => 'Periode kepengurusan RT 03 RW 03 Jurang Mangu Barat.',
                ]),
                'levels' => $rtLevels,
                'members' => [
                    [
                        'email' => 'rt03.superadmin@nexcity.local',
                        'role' => 'rt_admin',
                        'level_slug' => 'rt-superadmin',
                        'is_primary' => true,
                        'rt_profile' => [
                            'position' => 'Ketua RT',
                            'responsibility_area' => 'Koordinasi program RT dan pelayanan warga',
                            'service_start_date' => now()->subYears(2)->toDateString(),
                            'phone' => '+62 811 0000 7777',
                        ],
                    ],
                    [
                        'email' => 'rt03.finance@nexcity.local',
                        'role' => 'rt_admin',
                        'level_slug' => 'rt-finance',
                        'rt_profile' => [
                            'position' => 'Bendahara',
                            'responsibility_area' => 'Kas RT dan laporan iuran warga',
                            'service_start_date' => now()->subYears(2)->toDateString(),
                            'phone' => '+62 811 0000 8888',
                        ],
                    ],
                    [
                        'email' => 'rt03.secretary@nexcity.local',
                        'role' => 'rt_admin',
                        'level_slug' => 'rt-secretary',
                        'rt_profile' => [
                            'position' => 'Sekretaris',
                            'responsibility_area' => 'Administrasi RT dan dokumentasi program',
                            'service_start_date' => now()->subYears(2)->toDateString(),
                            'phone' => '+62 811 0000 9999',
                        ],
                    ],
                    [
                        'email' => 'rt03.field@nexcity.local',
                        'role' => 'rt_admin',
                        'level_slug' => 'rt-field-officer',
                        'rt_profile' => [
                            'position' => 'Petugas Lapangan',
                            'responsibility_area' => 'Distribusi & scan QR warga',
                            'service_start_date' => now()->subYear()->toDateString(),
                            'phone' => '+62 811 0001 0001',
                        ],
                    ],
                    [
                        'email' => 'rt03.humas@nexcity.local',
                        'role' => 'rt_admin',
                        'level_slug' => 'rt-humas',
                        'rt_profile' => [
                            'position' => 'Humas/Media',
                            'responsibility_area' => 'Publikasi kegiatan & komunikasi warga',
                            'service_start_date' => now()->subYear()->toDateString(),
                            'phone' => '+62 811 0001 0111',
                        ],
                    ],
                    [
                        'email' => 'resident.rt03.candra@nexcity.local',
                        'role' => 'resident',
                        'resident_profile' => $residentProfile('003', 'Jl. Dahlia No. 2', [
                            'family_card_number' => '36740410040030008',
                            'national_id_number' => '3674041919190019',
                            'birth_place' => 'Tangerang Selatan',
                            'birth_date' => now()->subYears(44)->toDateString(),
                            'gender' => 'male',
                            'marital_status_slug' => 'married',
                            'occupation' => 'Wiraswasta',
                        ]),
                    ],
                    [
                        'email' => 'resident.rt03.fajar@nexcity.local',
                        'role' => 'resident',
                        'resident_profile' => $residentProfile('003', 'Jl. Dahlia No. 10', [
                            'family_card_number' => '36740410040030004',
                            'national_id_number' => '3674041414140014',
                            'birth_place' => 'Tangerang Selatan',
                            'birth_date' => now()->subYears(30)->toDateString(),
                            'gender' => 'male',
                            'marital_status_slug' => 'single',
                            'occupation' => 'Desainer Grafis',
                        ]),
                    ],
                    [
                        'email' => 'resident.rt03.rina@nexcity.local',
                        'role' => 'resident',
                        'resident_profile' => $residentProfile('003', 'Jl. Dahlia No. 12', [
                            'family_card_number' => '36740410040030005',
                            'national_id_number' => '3674041515150015',
                            'birth_place' => 'Tangerang Selatan',
                            'birth_date' => now()->subYears(24)->toDateString(),
                            'gender' => 'female',
                            'marital_status_slug' => 'single',
                            'occupation' => 'Mahasiswi',
                        ]),
                    ],
                ],
            ],
            [
                'name' => 'RT 04 RW 03 Jurang Mangu Barat',
                'slug' => 'rt-04-rw03-jurang-mangu-barat',
                'type' => Organization::TYPE_RT,
                'category_slug' => 'residential',
                'status' => 'active',
                'email' => 'rt04@nexcity.local',
                'phone' => '+62 812 9000 1004',
                'timezone' => 'Asia/Jakarta',
                'location' => $locationIds('004'),
                'profile' => $organizationProfileData('004', 'Posko RT 04 - Jl. Kenanga No. 8', [
                    'description' => 'RT 04 RW 03 fokus pada administrasi warga dan layanan digital.',
                ]),
                'rt_profile' => $rtProfileData([
                    'office_phone' => '+62 812 9000 1004',
                    'office_address' => 'Posko RT 04 - Jl. Kenanga No. 8',
                    'notes' => 'Periode kepengurusan RT 04 RW 03 Jurang Mangu Barat.',
                ]),
                'levels' => $rtLevels,
                'members' => [
                    [
                        'email' => 'rt04.superadmin@nexcity.local',
                        'role' => 'rt_admin',
                        'level_slug' => 'rt-superadmin',
                        'is_primary' => true,
                        'rt_profile' => [
                            'position' => 'Ketua RT',
                            'responsibility_area' => 'Koordinasi program RT dan pelayanan warga',
                            'service_start_date' => now()->subYears(2)->toDateString(),
                            'phone' => '+62 811 0001 1111',
                        ],
                    ],
                    [
                        'email' => 'rt04.finance@nexcity.local',
                        'role' => 'rt_admin',
                        'level_slug' => 'rt-finance',
                        'rt_profile' => [
                            'position' => 'Bendahara',
                            'responsibility_area' => 'Kas RT dan laporan iuran warga',
                            'service_start_date' => now()->subYears(2)->toDateString(),
                            'phone' => '+62 811 0001 2222',
                        ],
                    ],
                    [
                        'email' => 'rt04.secretary@nexcity.local',
                        'role' => 'rt_admin',
                        'level_slug' => 'rt-secretary',
                        'rt_profile' => [
                            'position' => 'Sekretaris',
                            'responsibility_area' => 'Administrasi RT dan dokumentasi program',
                            'service_start_date' => now()->subYears(2)->toDateString(),
                            'phone' => '+62 811 0001 3333',
                        ],
                    ],
                    [
                        'email' => 'rt04.field@nexcity.local',
                        'role' => 'rt_admin',
                        'level_slug' => 'rt-field-officer',
                        'rt_profile' => [
                            'position' => 'Petugas Lapangan',
                            'responsibility_area' => 'Distribusi & scan QR warga',
                            'service_start_date' => now()->subYear()->toDateString(),
                            'phone' => '+62 811 0001 4444',
                        ],
                    ],
                    [
                        'email' => 'rt04.humas@nexcity.local',
                        'role' => 'rt_admin',
                        'level_slug' => 'rt-humas',
                        'rt_profile' => [
                            'position' => 'Humas/Media',
                            'responsibility_area' => 'Publikasi kegiatan & komunikasi warga',
                            'service_start_date' => now()->subYear()->toDateString(),
                            'phone' => '+62 811 0001 4555',
                        ],
                    ],
                    [
                        'email' => 'resident.rt04.gilang@nexcity.local',
                        'role' => 'resident',
                        'resident_profile' => $residentProfile('004', 'Jl. Kenanga No. 8', [
                            'family_card_number' => '36740410040040008',
                            'national_id_number' => '3674042020200020',
                            'birth_place' => 'Tangerang Selatan',
                            'birth_date' => now()->subYears(43)->toDateString(),
                            'gender' => 'male',
                            'marital_status_slug' => 'married',
                            'occupation' => 'Wiraswasta',
                        ]),
                    ],
                    [
                        'email' => 'resident.rt04.eka@nexcity.local',
                        'role' => 'resident',
                        'resident_profile' => $residentProfile('004', 'Jl. Kenanga No. 18', [
                            'family_card_number' => '36740410040040004',
                            'national_id_number' => '3674042424240024',
                            'birth_place' => 'Tangerang Selatan',
                            'birth_date' => now()->subYears(30)->toDateString(),
                            'gender' => 'male',
                            'marital_status_slug' => 'married',
                            'occupation' => 'Teknisi',
                        ]),
                    ],
                    [
                        'email' => 'resident.rt04.lia@nexcity.local',
                        'role' => 'resident',
                        'resident_profile' => $residentProfile('004', 'Jl. Kenanga No. 20', [
                            'family_card_number' => '36740410040040005',
                            'national_id_number' => '3674042525250025',
                            'birth_place' => 'Tangerang Selatan',
                            'birth_date' => now()->subYears(26)->toDateString(),
                            'gender' => 'female',
                            'marital_status_slug' => 'married',
                            'occupation' => 'Guru',
                        ]),
                    ],
                ],
            ],
            [
                'name' => 'RT 05 RW 03 Jurang Mangu Barat',
                'slug' => 'rt-05-rw03-jurang-mangu-barat',
                'type' => Organization::TYPE_RT,
                'category_slug' => 'residential',
                'status' => 'active',
                'email' => 'rt05@nexcity.local',
                'phone' => '+62 812 9000 1005',
                'timezone' => 'Asia/Jakarta',
                'location' => $locationIds('005'),
                'profile' => $organizationProfileData('005', 'Posko RT 05 - Jl. Anggrek No. 4', [
                    'description' => 'RT 05 RW 03 fokus pada program kerja dan iuran warga.',
                ]),
                'rt_profile' => $rtProfileData([
                    'office_phone' => '+62 812 9000 1005',
                    'office_address' => 'Posko RT 05 - Jl. Anggrek No. 4',
                    'notes' => 'Periode kepengurusan RT 05 RW 03 Jurang Mangu Barat.',
                ]),
                'levels' => $rtLevels,
                'members' => [
                    [
                        'email' => 'rt05.superadmin@nexcity.local',
                        'role' => 'rt_admin',
                        'level_slug' => 'rt-superadmin',
                        'is_primary' => true,
                        'rt_profile' => [
                            'position' => 'Ketua RT',
                            'responsibility_area' => 'Koordinasi program RT dan pelayanan warga',
                            'service_start_date' => now()->subYears(2)->toDateString(),
                            'phone' => '+62 811 0001 5555',
                        ],
                    ],
                    [
                        'email' => 'rt05.finance@nexcity.local',
                        'role' => 'rt_admin',
                        'level_slug' => 'rt-finance',
                        'rt_profile' => [
                            'position' => 'Bendahara',
                            'responsibility_area' => 'Kas RT dan laporan iuran warga',
                            'service_start_date' => now()->subYears(2)->toDateString(),
                            'phone' => '+62 811 0001 6666',
                        ],
                    ],
                    [
                        'email' => 'rt05.secretary@nexcity.local',
                        'role' => 'rt_admin',
                        'level_slug' => 'rt-secretary',
                        'rt_profile' => [
                            'position' => 'Sekretaris',
                            'responsibility_area' => 'Administrasi RT dan dokumentasi program',
                            'service_start_date' => now()->subYears(2)->toDateString(),
                            'phone' => '+62 811 0001 7777',
                        ],
                    ],
                    [
                        'email' => 'rt05.field@nexcity.local',
                        'role' => 'rt_admin',
                        'level_slug' => 'rt-field-officer',
                        'rt_profile' => [
                            'position' => 'Petugas Lapangan',
                            'responsibility_area' => 'Distribusi & scan QR warga',
                            'service_start_date' => now()->subYear()->toDateString(),
                            'phone' => '+62 811 0001 8888',
                        ],
                    ],
                    [
                        'email' => 'rt05.humas@nexcity.local',
                        'role' => 'rt_admin',
                        'level_slug' => 'rt-humas',
                        'rt_profile' => [
                            'position' => 'Humas/Media',
                            'responsibility_area' => 'Publikasi kegiatan & komunikasi warga',
                            'service_start_date' => now()->subYear()->toDateString(),
                            'phone' => '+62 811 0001 8999',
                        ],
                    ],
                    [
                        'email' => 'resident.rt05.rangga@nexcity.local',
                        'role' => 'resident',
                        'resident_profile' => $residentProfile('005', 'Jl. Anggrek No. 4', [
                            'family_card_number' => '36740410040050008',
                            'national_id_number' => '3674042121210021',
                            'birth_place' => 'Tangerang Selatan',
                            'birth_date' => now()->subYears(41)->toDateString(),
                            'gender' => 'male',
                            'marital_status_slug' => 'married',
                            'occupation' => 'Wiraswasta',
                        ]),
                    ],
                    [
                        'email' => 'resident.rt05.doni@nexcity.local',
                        'role' => 'resident',
                        'resident_profile' => $residentProfile('005', 'Jl. Anggrek No. 14', [
                            'family_card_number' => '36740410040050004',
                            'national_id_number' => '3674043131310031',
                            'birth_place' => 'Tangerang Selatan',
                            'birth_date' => now()->subYears(29)->toDateString(),
                            'gender' => 'male',
                            'marital_status_slug' => 'married',
                            'occupation' => 'Karyawan Swasta',
                        ]),
                    ],
                    [
                        'email' => 'resident.rt05.nina@nexcity.local',
                        'role' => 'resident',
                        'resident_profile' => $residentProfile('005', 'Jl. Anggrek No. 16', [
                            'family_card_number' => '36740410040050005',
                            'national_id_number' => '3674043232320032',
                            'birth_place' => 'Tangerang Selatan',
                            'birth_date' => now()->subYears(25)->toDateString(),
                            'gender' => 'female',
                            'marital_status_slug' => 'single',
                            'occupation' => 'Wirausaha',
                        ]),
                    ],
                ],
            ],
            [
                'name' => 'RT 06 RW 03 Jurang Mangu Barat',
                'slug' => 'rt-06-rw03-jurang-mangu-barat',
                'type' => Organization::TYPE_RT,
                'category_slug' => 'residential',
                'status' => 'active',
                'email' => 'rt06@nexcity.local',
                'phone' => '+62 812 9000 1006',
                'timezone' => 'Asia/Jakarta',
                'location' => $locationIds('006'),
                'profile' => $organizationProfileData('006', 'Posko RT 06 - Jl. Melur No. 1', [
                    'description' => 'RT 06 RW 03 fokus pada pelayanan warga dan pelaporan transparan.',
                ]),
                'rt_profile' => $rtProfileData([
                    'office_phone' => '+62 812 9000 1006',
                    'office_address' => 'Posko RT 06 - Jl. Melur No. 1',
                    'notes' => 'Periode kepengurusan RT 06 RW 03 Jurang Mangu Barat.',
                ]),
                'levels' => $rtLevels,
                'members' => [
                    [
                        'email' => 'rt06.superadmin@nexcity.local',
                        'role' => 'rt_admin',
                        'level_slug' => 'rt-superadmin',
                        'is_primary' => true,
                        'rt_profile' => [
                            'position' => 'Ketua RT',
                            'responsibility_area' => 'Koordinasi program RT dan pelayanan warga',
                            'service_start_date' => now()->subYears(2)->toDateString(),
                            'phone' => '+62 811 0001 9999',
                        ],
                    ],
                    [
                        'email' => 'rt06.finance@nexcity.local',
                        'role' => 'rt_admin',
                        'level_slug' => 'rt-finance',
                        'rt_profile' => [
                            'position' => 'Bendahara',
                            'responsibility_area' => 'Kas RT dan laporan iuran warga',
                            'service_start_date' => now()->subYears(2)->toDateString(),
                            'phone' => '+62 811 0002 0001',
                        ],
                    ],
                    [
                        'email' => 'rt06.secretary@nexcity.local',
                        'role' => 'rt_admin',
                        'level_slug' => 'rt-secretary',
                        'rt_profile' => [
                            'position' => 'Sekretaris',
                            'responsibility_area' => 'Administrasi RT dan dokumentasi program',
                            'service_start_date' => now()->subYears(2)->toDateString(),
                            'phone' => '+62 811 0002 0002',
                        ],
                    ],
                    [
                        'email' => 'rt06.field@nexcity.local',
                        'role' => 'rt_admin',
                        'level_slug' => 'rt-field-officer',
                        'rt_profile' => [
                            'position' => 'Petugas Lapangan',
                            'responsibility_area' => 'Distribusi & scan QR warga',
                            'service_start_date' => now()->subYear()->toDateString(),
                            'phone' => '+62 811 0002 0003',
                        ],
                    ],
                    [
                        'email' => 'rt06.humas@nexcity.local',
                        'role' => 'rt_admin',
                        'level_slug' => 'rt-humas',
                        'rt_profile' => [
                            'position' => 'Humas/Media',
                            'responsibility_area' => 'Publikasi kegiatan & komunikasi warga',
                            'service_start_date' => now()->subYear()->toDateString(),
                            'phone' => '+62 811 0002 0004',
                        ],
                    ],
                    [
                        'email' => 'resident.rt06.ridho@nexcity.local',
                        'role' => 'resident',
                        'resident_profile' => $residentProfile('006', 'Jl. Melur No. 1', [
                            'family_card_number' => '36740410040060008',
                            'national_id_number' => '3674042222220022',
                            'birth_place' => 'Tangerang Selatan',
                            'birth_date' => now()->subYears(40)->toDateString(),
                            'gender' => 'male',
                            'marital_status_slug' => 'married',
                            'occupation' => 'Wiraswasta',
                        ]),
                    ],
                    [
                        'email' => 'resident.rt06.tito@nexcity.local',
                        'role' => 'resident',
                        'resident_profile' => $residentProfile('006', 'Jl. Melur No. 11', [
                            'family_card_number' => '36740410040060004',
                            'national_id_number' => '3674043838380038',
                            'birth_place' => 'Tangerang Selatan',
                            'birth_date' => now()->subYears(29)->toDateString(),
                            'gender' => 'male',
                            'marital_status_slug' => 'married',
                            'occupation' => 'Driver',
                        ]),
                    ],
                    [
                        'email' => 'resident.rt06.salsa@nexcity.local',
                        'role' => 'resident',
                        'resident_profile' => $residentProfile('006', 'Jl. Melur No. 13', [
                            'family_card_number' => '36740410040060005',
                            'national_id_number' => '3674043939390039',
                            'birth_place' => 'Tangerang Selatan',
                            'birth_date' => now()->subYears(24)->toDateString(),
                            'gender' => 'female',
                            'marital_status_slug' => 'single',
                            'occupation' => 'Kasir',
                        ]),
                    ],
                ],
            ],
        ];

        foreach ($organizations as $data) {
            $profileData = Arr::pull($data, 'profile', []);
            $mosqueProfileData = Arr::pull($data, 'mosque_profile', []);
            $rtProfileData = Arr::pull($data, 'rt_profile', []);
            $umkmProfileData = Arr::pull($data, 'umkm_profile', []);
            $corporateProfileData = Arr::pull($data, 'corporate_profile', []);
            $institutionProfileData = Arr::pull($data, 'institution_profile', []);
            $levels = Arr::pull($data, 'levels', []);
            $members = Arr::pull($data, 'members', []);
            $categorySlug = Arr::pull($data, 'category_slug');
            $location = Arr::pull($data, 'location', []);

            $organization = Organization::updateOrCreate(
                ['slug' => $data['slug']],
                array_merge(
                    $data,
                    $location,
                    [
                        'organization_category_id' => $categorySlug && isset($categoryMap[$categorySlug])
                            ? $categoryMap[$categorySlug]->id
                            : null,
                    ]
                )
            );

            if (! empty($profileData)) {
                $organization->profile()->updateOrCreate(
                    ['organization_id' => $organization->id],
                    $profileData
                );
            }

            if (! empty($mosqueProfileData)) {
                OrganizationMosqueProfile::updateOrCreate(
                    ['organization_id' => $organization->id],
                    $mosqueProfileData
                );
            }

            if (! empty($rtProfileData)) {
                OrganizationRtProfile::updateOrCreate(
                    ['organization_id' => $organization->id],
                    $rtProfileData
                );
            }

            if (! empty($umkmProfileData)) {
                OrganizationUmkmProfile::updateOrCreate(
                    ['organization_id' => $organization->id],
                    $umkmProfileData
                );
            }

            if (! empty($corporateProfileData)) {
                OrganizationCorporateProfile::updateOrCreate(
                    ['organization_id' => $organization->id],
                    $corporateProfileData
                );
            }

            if (! empty($institutionProfileData)) {
                OrganizationInstitutionProfile::updateOrCreate(
                    ['organization_id' => $organization->id],
                    $institutionProfileData
                );
            }

            foreach ($levels as $level) {
                $levelModel = UserLevel::updateOrCreate(
                    [
                        'organization_id' => $organization->id,
                        'slug' => $level['slug'],
                    ],
                    [
                        'name' => $level['name'],
                        'description' => $level['description'] ?? null,
                        'is_global' => $level['is_global'] ?? false,
                    ]
                );

                $permissionSource = match ($organization->type) {
                    Organization::TYPE_MOSQUE => $mosqueLevelPermissions,
                    Organization::TYPE_RT => $rtLevelPermissions,
                    default => [],
                };

                $permissionNames = $permissionSource[$level['slug']] ?? [];
                if (! empty($permissionNames)) {
                    UserLevelPermission::query()
                        ->where('user_level_id', $levelModel->id)
                        ->delete();

                    $rows = collect($permissionNames)
                        ->map(fn ($name) => [
                            'user_level_id' => $levelModel->id,
                            'permission_name' => $name,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ])
                        ->toArray();

                    UserLevelPermission::query()->insert($rows);
                }
            }

            foreach ($members as $memberData) {
                $user = User::where('email', $memberData['email'])->first();

                if (! $user) {
                    continue;
                }

                $organization->users()->syncWithoutDetaching([
                    $user->id => [
                        'role' => $memberData['role'] ?? 'member',
                        'level_slug' => $memberData['level_slug'] ?? null,
                        'is_primary' => $memberData['is_primary'] ?? false,
                        'joined_at' => $memberData['joined_at'] ?? now(),
                    ],
                ]);

                if (isset($memberData['resident_profile'])) {
                    UserResidentProfile::updateOrCreate(
                        ['user_id' => $user->id],
                        array_merge(
                            $memberData['resident_profile'],
                            ['organization_id' => $organization->id]
                        )
                    );
                }

                if (isset($memberData['mosque_profile'])) {
                    UserMosqueProfile::updateOrCreate(
                        ['user_id' => $user->id],
                        array_merge(
                            $memberData['mosque_profile'],
                            ['organization_id' => $organization->id]
                        )
                    );
                }

                if (isset($memberData['rt_profile'])) {
                    UserRtProfile::updateOrCreate(
                        ['user_id' => $user->id],
                        array_merge(
                            $memberData['rt_profile'],
                            ['organization_id' => $organization->id]
                        )
                    );
                }
            }
        }
    }
}
