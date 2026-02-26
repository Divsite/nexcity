<?php

namespace Database\Seeders;

use App\Models\Organizations\OrganizationWhatsappGroup;
use Illuminate\Database\Seeder;

class OrganizationWhatsappGroupSeeder extends Seeder
{
    public function run(): void
    {
        $records = [
            [
                'organization_id' => 2,
                'name' => 'daily report charities',
                'jid' => '120363404435170845@g.us',
            ],
            [
                'organization_id' => 3,
                'name' => 'daily report charities',
                'jid' => '120363424853607651@g.us',
            ],
        ];

        foreach ($records as $record) {
            OrganizationWhatsappGroup::query()->updateOrCreate(
                [
                    'jid' => $record['jid'],
                ],
                [
                    'organization_id' => $record['organization_id'],
                    'name' => $record['name'],
                    'is_active' => true,
                ]
            );
        }
    }
}
