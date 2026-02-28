<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Sppg;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProcurementUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sppg = Sppg::query()->where('code', 'SPPG-JKT-01')->first();
        $vendor = Vendor::query()->where('code', 'VN-HO-01')->first();

        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@ho.local',
                'role' => UserRole::SUPER_ADMIN,
                'sppg_id' => null,
                'vendor_id' => null,
            ],
            [
                'name' => 'Owner Direksi',
                'email' => 'owner@ho.local',
                'role' => UserRole::OWNER,
                'sppg_id' => null,
                'vendor_id' => null,
            ],
            [
                'name' => 'Finance HO',
                'email' => 'finance@ho.local',
                'role' => UserRole::FINANCE,
                'sppg_id' => null,
                'vendor_id' => null,
            ],
            [
                'name' => 'Purchasing HO',
                'email' => 'purchasing@ho.local',
                'role' => UserRole::PURCHASING,
                'sppg_id' => null,
                'vendor_id' => null,
            ],
            [
                'name' => 'Admin Gudang HO',
                'email' => 'gudang@ho.local',
                'role' => UserRole::ADMIN_GUDANG,
                'sppg_id' => null,
                'vendor_id' => null,
            ],
            [
                'name' => 'User SPPG Jakarta 01',
                'email' => 'sppg.jkt01@ho.local',
                'role' => UserRole::SPPG_USER,
                'sppg_id' => $sppg?->id,
                'vendor_id' => null,
            ],
            [
                'name' => 'Vendor Admin A',
                'email' => 'vendor.admin@ho.local',
                'role' => UserRole::VENDOR_ADMIN,
                'sppg_id' => null,
                'vendor_id' => $vendor?->id,
            ],
        ];

        foreach ($users as $payload) {
            User::query()->updateOrCreate(
                ['email' => $payload['email']],
                [
                    'name' => $payload['name'],
                    'password' => Hash::make('password123'),
                    'role' => $payload['role'],
                    'sppg_id' => $payload['sppg_id'],
                    'vendor_id' => $payload['vendor_id'],
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
