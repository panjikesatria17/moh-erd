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
                'owner_name' => 'Rudi Hartono',
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
                'owner_name' => 'Santi Pratiwi',
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
                'owner_name' => 'Teguh Winata',
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
                'owner_name' => 'Hendra Saputra',
                'email' => 'vendor.ext@ho.local',
                'phone' => '022-555-0111',
                'address' => 'Cimahi',
                'is_affiliate' => false,
                'is_active' => true,
            ]
        );

        Vendor::query()->updateOrCreate(
            ['code' => 'VN-PDMU-01'],
            [
                'name' => 'PD Mitra Utama',
                'owner_name' => 'Turino Junaidy',
                'email' => 'pdmitra.utama@ho.local',
                'phone' => '0251-777-0101',
                'address' => 'Jl. Jend. Sudirman No 10A, Kelurahan Sempur, Kecamatan Bogor Tengah, Kota Bogor',
                'is_affiliate' => false,
                'is_active' => true,
            ]
        );

        Vendor::query()->updateOrCreate(
            ['code' => 'VN-PSGM-01'],
            [
                'name' => 'PT Sudirman Global Mandiri',
                'owner_name' => 'Rangga Sudirman',
                'email' => 'sudirman.global@ho.local',
                'phone' => '0251-777-0201',
                'address' => 'Jl. Raya Pemda No 5, RT 001, RW 010, Kedunghalang, Bogor Utara, Kota Bogor, Jawa Barat, 16158',
                'is_affiliate' => false,
                'is_active' => true,
            ]
        );

        Sppg::query()->updateOrCreate(
            ['code' => 'SPPG-JKT-01'],
            [
                'name' => 'SPPG Jakarta 01',
                'ka_sppg_name' => 'Agus Pratama',
                'accounting_name' => 'Rina Wulandari',
                'address' => 'Jakarta Timur',
                'default_vendor_id' => $vendorA->id,
                'is_active' => true,
            ]
        );

        Sppg::query()->updateOrCreate(
            ['code' => 'SPPG-JKT-02'],
            [
                'name' => 'SPPG Jakarta 02',
                'ka_sppg_name' => 'Deni Saputra',
                'accounting_name' => 'Maya Lestari',
                'address' => 'Jakarta Barat',
                'default_vendor_id' => $vendorB->id,
                'is_active' => true,
            ]
        );

        Sppg::query()->updateOrCreate(
            ['code' => 'SPPG-BDG-01'],
            [
                'name' => 'SPPG Bandung 01',
                'ka_sppg_name' => 'Andri Kurniawan',
                'accounting_name' => 'Fitri Handayani',
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
