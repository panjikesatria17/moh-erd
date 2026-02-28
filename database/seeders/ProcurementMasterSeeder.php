<?php

namespace Database\Seeders;

use App\Models\Sppg;
use App\Models\Vendor;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class ProcurementMasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vendorA = Vendor::query()->updateOrCreate(
            ['code' => 'VN-HO-01'],
            [
                'name' => 'PT Holding Pangan Prima',
                'email' => 'vendor1@ho.local',
                'phone' => '021-555-0101',
                'address' => 'Jakarta Pusat',
                'is_affiliate' => true,
                'is_active' => true,
            ]
        );

        $vendorB = Vendor::query()->updateOrCreate(
            ['code' => 'VN-HO-02'],
            [
                'name' => 'PT Distribusi Sehat Nusantara',
                'email' => 'vendor2@ho.local',
                'phone' => '021-555-0102',
                'address' => 'Jakarta Selatan',
                'is_affiliate' => true,
                'is_active' => true,
            ]
        );

        $vendorC = Vendor::query()->updateOrCreate(
            ['code' => 'VN-HO-03'],
            [
                'name' => 'PT Sumber Gizi Anak',
                'email' => 'vendor3@ho.local',
                'phone' => '021-555-0103',
                'address' => 'Bandung',
                'is_affiliate' => true,
                'is_active' => true,
            ]
        );

        Vendor::query()->updateOrCreate(
            ['code' => 'VN-EXT-01'],
            [
                'name' => 'CV Mitra Sayur Lokal',
                'email' => 'vendor.ext@ho.local',
                'phone' => '022-555-0111',
                'address' => 'Cimahi',
                'is_affiliate' => false,
                'is_active' => true,
            ]
        );

        Sppg::query()->updateOrCreate(
            ['code' => 'SPPG-JKT-01'],
            [
                'name' => 'SPPG Jakarta 01',
                'address' => 'Jakarta Timur',
                'default_vendor_id' => $vendorA->id,
                'is_active' => true,
            ]
        );

        Sppg::query()->updateOrCreate(
            ['code' => 'SPPG-JKT-02'],
            [
                'name' => 'SPPG Jakarta 02',
                'address' => 'Jakarta Barat',
                'default_vendor_id' => $vendorB->id,
                'is_active' => true,
            ]
        );

        Sppg::query()->updateOrCreate(
            ['code' => 'SPPG-BDG-01'],
            [
                'name' => 'SPPG Bandung 01',
                'address' => 'Bandung Kota',
                'default_vendor_id' => $vendorC->id,
                'is_active' => true,
            ]
        );

        Warehouse::query()->updateOrCreate(
            ['code' => 'WH-HO-01'],
            [
                'name' => 'Gudang Pusat HO',
                'address' => 'Jakarta Pusat',
                'is_active' => true,
            ]
        );

        Warehouse::query()->updateOrCreate(
            ['code' => 'WH-BDG-01'],
            [
                'name' => 'Gudang Regional Bandung',
                'address' => 'Bandung',
                'is_active' => true,
            ]
        );
    }
}
