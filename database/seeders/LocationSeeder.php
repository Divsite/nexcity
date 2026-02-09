<?php

namespace Database\Seeders;

use App\Models\Locations\CitizensAssociation;
use App\Models\Locations\City;
use App\Models\Locations\Country;
use App\Models\Locations\District;
use App\Models\Locations\NeighborhoodAssociation;
use App\Models\Locations\Province;
use App\Models\Locations\Village;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $indonesia = Country::updateOrCreate(
            ['code' => 'ID'],
            ['name' => 'Indonesia']
        );

        $banten = Province::updateOrCreate(
            ['code' => '36'],
            ['name' => 'Banten', 'country_id' => $indonesia->id]
        );

        $tangsel = City::updateOrCreate(
            ['code' => '3674'],
            ['name' => 'Kota Tangerang Selatan', 'province_id' => $banten->id]
        );

        $pondokAren = District::updateOrCreate(
            ['code' => '367404'],
            ['name' => 'Pondok Aren', 'city_id' => $tangsel->id]
        );

        $villages = [
            ['code' => '3674041001', 'name' => 'Pondok Jaya', 'postal_code' => '15220'],
            ['code' => '3674041002', 'name' => 'Pondok Betung', 'postal_code' => '15221'],
            ['code' => '3674041003', 'name' => 'Jurang Mangu Timur', 'postal_code' => '15222'],
            ['code' => '3674041004', 'name' => 'Jurang Mangu Barat', 'postal_code' => '15223'],
            ['code' => '3674041005', 'name' => 'Pondok Aren', 'postal_code' => '15224'],
            ['code' => '3674041006', 'name' => 'Pondok Karya', 'postal_code' => '15225'],
            ['code' => '3674041007', 'name' => 'Pondok Kacang Barat', 'postal_code' => '15226'],
            ['code' => '3674041008', 'name' => 'Perigi Lama', 'postal_code' => '15227'],
            ['code' => '3674041009', 'name' => 'Perigi Baru', 'postal_code' => '15228'],
            ['code' => '3674041010', 'name' => 'Pondok Pucung', 'postal_code' => '15229'],
        ];

        $villageModels = [];
        foreach ($villages as $village) {
            $villageModels[$village['code']] = Village::updateOrCreate(
                ['code' => $village['code']],
                [
                    'name' => $village['name'],
                    'postal_code' => $village['postal_code'],
                    'district_id' => $pondokAren->id,
                ]
            );
        }

        $jmb = $villageModels['3674041004'];

        $rw3 = CitizensAssociation::updateOrCreate(
            [
                'village_id' => $jmb->id,
                'slug' => 'rw003',
            ],
            [
                'code' => sprintf('rw-%s-003', $jmb->code),
                'number' => '003',
                'name' => 'RW 003',
                'slug' => 'rw003',
                'leader_name' => 'Komarudin',
                'leader_phone' => '0897676767',
                'start_period' => '2025-01-01',
                'end_period' => '2029-12-31',
            ]
        );

        $rts = [
            ['code' => '001', 'leader' => 'Suryono'],
            ['code' => '002', 'leader' => 'Arif'],
            ['code' => '003', 'leader' => 'Zahriar'],
            ['code' => '004', 'leader' => 'Abdul Karim'],
            ['code' => '005', 'leader' => 'Rouf'],
            ['code' => '006', 'leader' => 'Syadeli'],
        ];

        foreach ($rts as $rt) {
            NeighborhoodAssociation::updateOrCreate(
                [
                    'citizens_association_id' => $rw3->id,
                    'slug' => 'rt' . $rt['code'],
                ],
                [
                    'code' => sprintf('rt-%s-%s', $rw3->code, $rt['code']),
                    'number' => $rt['code'],
                    'name' => 'RT ' . $rt['code'],
                    'slug' => 'rt' . $rt['code'],
                    'leader_name' => $rt['leader'],
                    'leader_phone' => '0897676767',
                    'start_period' => '2025-01-01',
                    'end_period' => '2029-12-31',
                ]
            );
        }
    }
}
