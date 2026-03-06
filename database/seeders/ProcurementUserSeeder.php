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
        $vendorDefault = Vendor::query()->where('code', 'VN-HO-01')->first();
        $vendorPdMitra = Vendor::query()->where('code', 'VN-PDMU-01')->first();
        $vendorPtSudirman = Vendor::query()->where('code', 'VN-PSGM-01')->first();

        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@ho.local',
                'role' => UserRole::SUPER_ADMIN,
                'sppg_id' => null,
                'vendor_id' => null,
            ],
            [
                'name' => 'Owner',
                'email' => 'owner@ho.local',
                'role' => UserRole::OWNER,
                'sppg_id' => null,
                'vendor_id' => null,
            ],
            [
                'name' => 'Finance',
                'email' => 'finance@ho.local',
                'role' => UserRole::FINANCE,
                'sppg_id' => null,
                'vendor_id' => null,
            ],
            [
                'name' => 'Purchasing',
                'email' => 'purchasing@ho.local',
                'role' => UserRole::PURCHASING,
                'sppg_id' => null,
                'vendor_id' => null,
            ],
            [
                'name' => 'Admin Gudang',
                'email' => 'gudang@ho.local',
                'role' => UserRole::ADMIN_GUDANG,
                'sppg_id' => null,
                'vendor_id' => null,
            ],
            [
                'name' => 'Ekspedisi',
                'email' => 'ekspedisi@ho.local',
                'role' => UserRole::EXPEDITION,
                'sppg_id' => null,
                'vendor_id' => $vendorDefault?->id,
            ],
            [
                'name' => 'SPPG User Jakarta 01',
                'email' => 'sppg.jkt01@ho.local',
                'role' => UserRole::SPPG_USER,
                'sppg_id' => $sppg?->id,
                'vendor_id' => null,
            ],
            [
                'name' => 'Vendor Admin',
                'email' => 'vendor.admin@ho.local',
                'role' => UserRole::VENDOR_ADMIN,
                'sppg_id' => null,
                'vendor_id' => $vendorDefault?->id,
            ],
            [
                'name' => 'Vendor Admin PD Mitra',
                'email' => 'vendor.pdmitra@ho.local',
                'role' => UserRole::VENDOR_ADMIN,
                'sppg_id' => null,
                'vendor_id' => $vendorPdMitra?->id,
            ],
            [
                'name' => 'Vendor Admin PT Sudirman',
                'email' => 'vendor.sudirman@ho.local',
                'role' => UserRole::VENDOR_ADMIN,
                'sppg_id' => null,
                'vendor_id' => $vendorPtSudirman?->id,
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
