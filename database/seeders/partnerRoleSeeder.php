<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PartnerRole;
class partnerRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $permissions = [
            [
                'name' => 'create_inbounds'
            ],
            [
                'name' => 'view_inbounds'
            ],
            [
                'name' => 'cancel_inbounds'
            ],
            [
                'name' => 'manage_users'
            ],
            [
                'name' => 'view_stock'
            ],
            [
                'name' => 'create_outbounds'
            ],
            [
                'name' => 'view_outbounds'
            ],
            [
                'name' => 'cancel_outbounds'
            ]
        ];

        foreach ($permissions as $permission) {
            PartnerRole::create($permission);
        }

       
    }
}
