<?php

namespace Database\Seeders;

use App\Models\Organizations\Organization;
use App\Models\Organizations\UserLevel;
use App\Models\Users\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravolt\Avatar\Facade as Avatar;

class OrganizationOfficerSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()->find(2);

        if (! $organization) {
            return;
        }

        $levelSlug = 'mosque-officer';
        $roleName = 'mosque_admin';
        $organizationSlug = $organization->slug;

        $names = [
            'Arju', 'Fatia', 'Ina', 'Makis', 'Tata', 'Ayyub', 'Hamdan', 'Ninis', 'Denis', 'Hiban',
            'Ucup', 'Alin', 'Kesya', 'Sule', 'Barka', 'Pussel', 'Ripal', 'Rhafi', 'Piul', 'Erlang',
            'Muamar', 'Cilla', 'Nabil', 'Rifal', 'Ica', 'Syahrul', 'Fadil', 'Damar', 'Agung', 'Azzam',
            'Azka', 'Izzati',
        ];

        foreach ($names as $name) {
            $username = $this->generatePartnerUsername($name, $organizationSlug, $levelSlug);
            $email = Str::slug($name, '.') . '.' . $organizationSlug . '.officer@nexcity.local';

            $fileName = Str::random(30) . '.png';
            Avatar::create($name)->save(storage_path('app/public/' . User::AVATAR_PATH . $fileName), 100);

            $user = User::updateOrCreate(
                [
                    'email' => $email,
                ],
                [
                    'name' => $name,
                    'username' => $username,
                    'password' => Hash::make('password'),
                    'avatar' => $fileName,
                    'initial_name' => User::AVATAR_INITIAL_NAME,
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles([$roleName]);

            $organization->users()->syncWithoutDetaching([
                $user->id => [
                    'role' => $roleName,
                    'level_slug' => $levelSlug,
                    'is_primary' => false,
                    'joined_at' => now(),
                ],
            ]);

            $user->mosqueProfile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'organization_id' => $organization->id,
                    'position' => 'Petugas Zakat',
                ]
            );
        }
    }

    private function generatePartnerUsername(string $name, string $organizationSlug, string $levelSlug): string
    {
        $nameSlug = Str::slug($name, '.');
        $orgSlug = Str::slug($organizationSlug, '.');
        $level = Str::slug($levelSlug, '.');
        $base = trim($nameSlug . '.' . $orgSlug . '.' . $level, '.');
        $suffix = Str::lower(Str::random(4));
        $username = Str::limit($base . '.' . $suffix, 255, '');

        while (User::where('username', $username)->exists()) {
            $suffix = Str::lower(Str::random(4));
            $username = Str::limit($base . '.' . $suffix, 255, '');
        }

        return $username;
    }
}
