<?php

namespace Database\Seeders;

use App\Models\Users\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravolt\Avatar\Facade as Avatar;

class UserSeeder extends Seeder
{
    public function run()
    {
        File::deleteDirectory(storage_path('app/public/' . User::AVATAR_PATH));
        File::ensureDirectoryExists(storage_path('app/public/' . User::AVATAR_PATH));

        $users = [
            // Divsite / Superadmin
            [
                'name' => 'Divsite Superadmin',
                'email' => 'hq.superadmin@nexcity.local',
                'username' => 'divsite.superadmin',
                'role' => 'superadmin',
            ],
            [
                'name' => 'Samsul Hadi',
                'email' => 'samsulhadi@samsulhadiss.com',
                'username' => 'samsulhadi',
                'role' => 'superadmin',
            ],
            [
                'name' => 'Legacy Superadmin',
                'email' => 'superadmin@superadmin.com',
                'username' => 'superadmin',
                'role' => 'superadmin',
            ],

            // Islamic Center Alamanah
            [
                'name' => 'Umar Zuhdi',
                'email' => 'alamanah.superadmin@nexcity.local',
                'username' => 'alamanah.superadmin',
                'role' => 'mosque_admin',
            ],
            [
                'name' => 'Mira Rahma',
                'email' => 'alamanah.finance@nexcity.local',
                'username' => 'alamanah.finance',
                'role' => 'mosque_admin',
            ],
            [
                'name' => 'Dewi Anggraeni',
                'email' => 'alamanah.secretary@nexcity.local',
                'username' => 'alamanah.secretary',
                'role' => 'mosque_admin',
            ],
            [
                'name' => 'Raka Pratama',
                'email' => 'alamanah.officer@nexcity.local',
                'username' => 'alamanah.officer',
                'role' => 'mosque_admin',
            ],
            [
                'name' => 'Fauzan Hakim',
                'email' => 'alamanah.qurban@nexcity.local',
                'username' => 'alamanah.qurban',
                'role' => 'mosque_admin',
            ],
            [
                'name' => 'Alya Putri',
                'email' => 'alamanah.inventory@nexcity.local',
                'username' => 'alamanah.inventory',
                'role' => 'mosque_admin',
            ],
            [
                'name' => 'Nanda Yusuf',
                'email' => 'alamanah.crm@nexcity.local',
                'username' => 'alamanah.crm',
                'role' => 'mosque_admin',
            ],
            [
                'name' => 'Salsa Rahmadani',
                'email' => 'alamanah.humas@nexcity.local',
                'username' => 'alamanah.humas',
                'role' => 'mosque_admin',
            ],

            // Darul Muminin
            [
                'name' => 'Farhan Ridwan',
                'email' => 'darulmuminin.superadmin@nexcity.local',
                'username' => 'darulmuminin.superadmin',
                'role' => 'mosque_admin',
            ],
            [
                'name' => 'Dina Kartika',
                'email' => 'darulmuminin.finance@nexcity.local',
                'username' => 'darulmuminin.finance',
                'role' => 'mosque_admin',
            ],
            [
                'name' => 'Rahmawati Sari',
                'email' => 'darulmuminin.secretary@nexcity.local',
                'username' => 'darulmuminin.secretary',
                'role' => 'mosque_admin',
            ],
            [
                'name' => 'Syifa Hidayah',
                'email' => 'darulmuminin.officer@nexcity.local',
                'username' => 'darulmuminin.officer',
                'role' => 'mosque_admin',
            ],
            [
                'name' => 'Rizki Amalia',
                'email' => 'darulmuminin.qurban@nexcity.local',
                'username' => 'darulmuminin.qurban',
                'role' => 'mosque_admin',
            ],
            [
                'name' => 'Hendra Saputra',
                'email' => 'darulmuminin.inventory@nexcity.local',
                'username' => 'darulmuminin.inventory',
                'role' => 'mosque_admin',
            ],
            [
                'name' => 'Dedi Prakoso',
                'email' => 'darulmuminin.crm@nexcity.local',
                'username' => 'darulmuminin.crm',
                'role' => 'mosque_admin',
            ],
            [
                'name' => 'Nisa Maulida',
                'email' => 'darulmuminin.humas@nexcity.local',
                'username' => 'darulmuminin.humas',
                'role' => 'mosque_admin',
            ],

            // Masjid Al Falah
            [
                'name' => 'Hilmi Rahman',
                'email' => 'alfalah.superadmin@nexcity.local',
                'username' => 'alfalah.superadmin',
                'role' => 'mosque_admin',
            ],
            [
                'name' => 'Nadya Kamilah',
                'email' => 'alfalah.finance@nexcity.local',
                'username' => 'alfalah.finance',
                'role' => 'mosque_admin',
            ],
            [
                'name' => 'Lina Prameswari',
                'email' => 'alfalah.secretary@nexcity.local',
                'username' => 'alfalah.secretary',
                'role' => 'mosque_admin',
            ],
            [
                'name' => 'Bagus Firmansyah',
                'email' => 'alfalah.officer@nexcity.local',
                'username' => 'alfalah.officer',
                'role' => 'mosque_admin',
            ],
            [
                'name' => 'Fikri Rahmat',
                'email' => 'alfalah.qurban@nexcity.local',
                'username' => 'alfalah.qurban',
                'role' => 'mosque_admin',
            ],
            [
                'name' => 'Shinta Nabila',
                'email' => 'alfalah.inventory@nexcity.local',
                'username' => 'alfalah.inventory',
                'role' => 'mosque_admin',
            ],
            [
                'name' => 'Putri Lestari',
                'email' => 'alfalah.crm@nexcity.local',
                'username' => 'alfalah.crm',
                'role' => 'mosque_admin',
            ],
            [
                'name' => 'Hana Amalia',
                'email' => 'alfalah.humas@nexcity.local',
                'username' => 'alfalah.humas',
                'role' => 'mosque_admin',
            ],

            // RT 01
            [
                'name' => 'Ahmad Suryono',
                'email' => 'rt01.superadmin@nexcity.local',
                'username' => 'rt01.superadmin',
                'role' => 'rt_admin',
            ],
            [
                'name' => 'Indah Saputri',
                'email' => 'rt01.finance@nexcity.local',
                'username' => 'rt01.finance',
                'role' => 'rt_admin',
            ],
            [
                'name' => 'Rudi Hartono',
                'email' => 'rt01.secretary@nexcity.local',
                'username' => 'rt01.secretary',
                'role' => 'rt_admin',
            ],
            [
                'name' => 'Taufik Hidayat',
                'email' => 'rt01.field@nexcity.local',
                'username' => 'rt01.field',
                'role' => 'rt_admin',
            ],
            [
                'name' => 'Rani Fadilah',
                'email' => 'rt01.humas@nexcity.local',
                'username' => 'rt01.humas',
                'role' => 'rt_admin',
            ],

            // RT 02
            [
                'name' => 'Budi Santoso',
                'email' => 'rt02.superadmin@nexcity.local',
                'username' => 'rt02.superadmin',
                'role' => 'rt_admin',
            ],
            [
                'name' => 'Laras Widiastuti',
                'email' => 'rt02.finance@nexcity.local',
                'username' => 'rt02.finance',
                'role' => 'rt_admin',
            ],
            [
                'name' => 'Agus Wirawan',
                'email' => 'rt02.secretary@nexcity.local',
                'username' => 'rt02.secretary',
                'role' => 'rt_admin',
            ],
            [
                'name' => 'Doni Pratama',
                'email' => 'rt02.field@nexcity.local',
                'username' => 'rt02.field',
                'role' => 'rt_admin',
            ],
            [
                'name' => 'Siska Andini',
                'email' => 'rt02.humas@nexcity.local',
                'username' => 'rt02.humas',
                'role' => 'rt_admin',
            ],

            // RT 03
            [
                'name' => 'Candra Wijaya',
                'email' => 'rt03.superadmin@nexcity.local',
                'username' => 'rt03.superadmin',
                'role' => 'rt_admin',
            ],
            [
                'name' => 'Nia Anggraini',
                'email' => 'rt03.finance@nexcity.local',
                'username' => 'rt03.finance',
                'role' => 'rt_admin',
            ],
            [
                'name' => 'Yoga Maulana',
                'email' => 'rt03.secretary@nexcity.local',
                'username' => 'rt03.secretary',
                'role' => 'rt_admin',
            ],
            [
                'name' => 'Wahyu Saputra',
                'email' => 'rt03.field@nexcity.local',
                'username' => 'rt03.field',
                'role' => 'rt_admin',
            ],
            [
                'name' => 'Intan Maharani',
                'email' => 'rt03.humas@nexcity.local',
                'username' => 'rt03.humas',
                'role' => 'rt_admin',
            ],

            // RT 04
            [
                'name' => 'Gilang Prabowo',
                'email' => 'rt04.superadmin@nexcity.local',
                'username' => 'rt04.superadmin',
                'role' => 'rt_admin',
            ],
            [
                'name' => 'Wulan Setiawati',
                'email' => 'rt04.finance@nexcity.local',
                'username' => 'rt04.finance',
                'role' => 'rt_admin',
            ],
            [
                'name' => 'Rizal Kurniawan',
                'email' => 'rt04.secretary@nexcity.local',
                'username' => 'rt04.secretary',
                'role' => 'rt_admin',
            ],
            [
                'name' => 'Aditya Putra',
                'email' => 'rt04.field@nexcity.local',
                'username' => 'rt04.field',
                'role' => 'rt_admin',
            ],
            [
                'name' => 'Vina Putri',
                'email' => 'rt04.humas@nexcity.local',
                'username' => 'rt04.humas',
                'role' => 'rt_admin',
            ],

            // RT 05
            [
                'name' => 'Rangga Kurnia',
                'email' => 'rt05.superadmin@nexcity.local',
                'username' => 'rt05.superadmin',
                'role' => 'rt_admin',
            ],
            [
                'name' => 'Mila Shafa',
                'email' => 'rt05.finance@nexcity.local',
                'username' => 'rt05.finance',
                'role' => 'rt_admin',
            ],
            [
                'name' => 'Yusuf Rahman',
                'email' => 'rt05.secretary@nexcity.local',
                'username' => 'rt05.secretary',
                'role' => 'rt_admin',
            ],
            [
                'name' => 'Fikri Yusuf',
                'email' => 'rt05.field@nexcity.local',
                'username' => 'rt05.field',
                'role' => 'rt_admin',
            ],
            [
                'name' => 'Lilis Anggraini',
                'email' => 'rt05.humas@nexcity.local',
                'username' => 'rt05.humas',
                'role' => 'rt_admin',
            ],

            // RT 06
            [
                'name' => 'Ridho Saputra',
                'email' => 'rt06.superadmin@nexcity.local',
                'username' => 'rt06.superadmin',
                'role' => 'rt_admin',
            ],
            [
                'name' => 'Santi Rahmah',
                'email' => 'rt06.finance@nexcity.local',
                'username' => 'rt06.finance',
                'role' => 'rt_admin',
            ],
            [
                'name' => 'Damar Maulana',
                'email' => 'rt06.secretary@nexcity.local',
                'username' => 'rt06.secretary',
                'role' => 'rt_admin',
            ],
            [
                'name' => 'Rahmat Prakoso',
                'email' => 'rt06.field@nexcity.local',
                'username' => 'rt06.field',
                'role' => 'rt_admin',
            ],
            [
                'name' => 'Tasya Nurani',
                'email' => 'rt06.humas@nexcity.local',
                'username' => 'rt06.humas',
                'role' => 'rt_admin',
            ],

            // Residents for RT 01
            [
                'name' => 'Andi Prasetyo',
                'email' => 'resident.rt01.andi@nexcity.local',
                'username' => 'andi.rt01',
                'role' => 'resident',
            ],
            [
                'name' => 'Siti Lestari',
                'email' => 'resident.rt01.siti@nexcity.local',
                'username' => 'siti.rt01',
                'role' => 'resident',
            ],
            [
                'name' => 'Ahmad Suryono',
                'email' => 'resident.rt01.ahmad@nexcity.local',
                'username' => 'ahmad.rt01',
                'role' => 'resident',
            ],

            // Residents for RT 02
            [
                'name' => 'Rudi Prabowo',
                'email' => 'resident.rt02.rudi@nexcity.local',
                'username' => 'rudi.rt02',
                'role' => 'resident',
            ],
            [
                'name' => 'Maya Kusuma',
                'email' => 'resident.rt02.maya@nexcity.local',
                'username' => 'maya.rt02',
                'role' => 'resident',
            ],
            [
                'name' => 'Budi Santoso',
                'email' => 'resident.rt02.budi@nexcity.local',
                'username' => 'budi.rt02',
                'role' => 'resident',
            ],

            // Residents for RT 03
            [
                'name' => 'Fajar Hidayat',
                'email' => 'resident.rt03.fajar@nexcity.local',
                'username' => 'fajar.rt03',
                'role' => 'resident',
            ],
            [
                'name' => 'Rina Pratiwi',
                'email' => 'resident.rt03.rina@nexcity.local',
                'username' => 'rina.rt03',
                'role' => 'resident',
            ],
            [
                'name' => 'Candra Wijaya',
                'email' => 'resident.rt03.candra@nexcity.local',
                'username' => 'candra.rt03',
                'role' => 'resident',
            ],

            // Residents for RT 04
            [
                'name' => 'Eka Nugraha',
                'email' => 'resident.rt04.eka@nexcity.local',
                'username' => 'eka.rt04',
                'role' => 'resident',
            ],
            [
                'name' => 'Lia Puspita',
                'email' => 'resident.rt04.lia@nexcity.local',
                'username' => 'lia.rt04',
                'role' => 'resident',
            ],
            [
                'name' => 'Gilang Prabowo',
                'email' => 'resident.rt04.gilang@nexcity.local',
                'username' => 'gilang.rt04',
                'role' => 'resident',
            ],

            // Residents for RT 05
            [
                'name' => 'Doni Pratama',
                'email' => 'resident.rt05.doni@nexcity.local',
                'username' => 'doni.rt05',
                'role' => 'resident',
            ],
            [
                'name' => 'Nina Maharani',
                'email' => 'resident.rt05.nina@nexcity.local',
                'username' => 'nina.rt05',
                'role' => 'resident',
            ],
            [
                'name' => 'Rangga Kurnia',
                'email' => 'resident.rt05.rangga@nexcity.local',
                'username' => 'rangga.rt05',
                'role' => 'resident',
            ],

            // Residents for RT 06
            [
                'name' => 'Tito Prakoso',
                'email' => 'resident.rt06.tito@nexcity.local',
                'username' => 'tito.rt06',
                'role' => 'resident',
            ],
            [
                'name' => 'Salsa Putri',
                'email' => 'resident.rt06.salsa@nexcity.local',
                'username' => 'salsa.rt06',
                'role' => 'resident',
            ],
            [
                'name' => 'Ridho Saputra',
                'email' => 'resident.rt06.ridho@nexcity.local',
                'username' => 'ridho.rt06',
                'role' => 'resident',
            ],
        ];

        foreach ($users as $userData) {
            if (isset($userData['avatar'])) {
                $fileName = $userData['avatar'];
            } else {
                $fileName = Str::random(30) . '.png';
                Avatar::create($userData['name'])
                    ->save(storage_path('app/public/' . User::AVATAR_PATH . $fileName), 100);
            }

            $user = User::updateOrCreate(
                [
                    'email' => $userData['email'],
                    'username' => $userData['username'],
                ],
                [
                    'name' => $userData['name'],
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                    'avatar' => $fileName,
                    'initial_name' => User::AVATAR_INITIAL_NAME,
                    'remember_token' => Str::random(10),
                ]
            );

            $user->syncRoles([$userData['role']]);
        }
    }
}
