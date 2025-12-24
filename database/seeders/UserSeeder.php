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
        File::deleteDirectory(storage_path('app/' . User::AVATAR_PATH));
        File::ensureDirectoryExists(storage_path('app/' . User::AVATAR_PATH));

        $users = [
            [
                'name' => 'Superadmin',
                'email' => 'superadmin@superadmin.com',
                'username' => 'superadmin',
                'role' => 'superadmin',
            ],
            [
                'name' => 'Samsul Hadi',
                'email' => 'samsulhadi@samsulhadiss.com',
                'username' => 'samsulhadi',
                'role' => 'superadmin',
                'avatar' => 'samsulhadi_ss.png',
            ],
            [
                'name' => 'Syuhada Rantisi',
                'email' => 'syuhada@student.com',
                'username' => 'syuhada',
                'role' => 'student',
            ],
            [
                'name' => 'Erwin Haryono',
                'email' => 'erwin@client.com',
                'username' => 'erwin',
                'role' => 'client',
            ],
            [
                'name' => 'Albertina Suryaningsih',
                'email' => 'albertina@client.com',
                'username' => 'albertina',
                'role' => 'client',
            ],
            [
                'name' => 'Indra Prasetyo',
                'email' => 'indra@client.com',
                'username' => 'indra',
                'role' => 'client',
            ],
            [
                'name' => 'Muhammad Ujang',
                'email' => 'ujang@partner.com',
                'username' => 'ujang',
                'role' => 'partner',
            ],
            [
                'name' => 'M Humam Afifi',
                'email' => 'humam@instructor.com',
                'username' => 'humam',
                'role' => 'instructor',
            ],
            [
                'name' => 'Fahri',
                'email' => 'fahri@instructor.com',
                'username' => 'fahri',
                'role' => 'instructor',
            ],
            [
                'name' => 'nizomudin',
                'email' => 'nizomudin@instructor.com',
                'username' => 'nizomudin',
                'role' => 'instructor',
            ],
            [
                'name' => 'Sandora',
                'email' => 'sandora@samsulhadiss.com',
                'username' => 'sandora',
                'role' => 'superadmin',
                'avatar' => 'sandora_ss.png',
            ],
        ];

        foreach ($users as $userData) {
            if (isset($userData['avatar'])) {
                $fileName = $userData['avatar'];
            } else {
                $fileName = Str::random(30) . '.png';
                Avatar::create($userData['name'])
                    ->save(storage_path('app/' . User::AVATAR_PATH . $fileName), 100);
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

            $user->assignRole($userData['role']);
        }
    }
}
