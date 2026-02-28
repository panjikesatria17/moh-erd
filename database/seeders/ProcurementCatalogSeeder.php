<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPriceHistory;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class ProcurementCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $protein = ProductCategory::query()->updateOrCreate(
            ['name' => 'Protein'],
            ['description' => 'Sumber protein hewani dan nabati']
        );

        $vegetable = ProductCategory::query()->updateOrCreate(
            ['name' => 'Sayuran'],
            ['description' => 'Sayuran segar untuk menu harian']
        );

        $carbohydrate = ProductCategory::query()->updateOrCreate(
            ['name' => 'Karbohidrat'],
            ['description' => 'Bahan makanan sumber karbohidrat']
        );

        $products = [
            [
                'sku' => 'PRD-001',
                'name' => 'Dada Ayam Fillet',
                'product_category_id' => $protein->id,
                'unit' => 'kg',
                'government_price_cap' => 42000,
                'minimum_stock_level' => 50,
                'reorder_stock_level' => 80,
                'is_active' => true,
            ],
            [
                'sku' => 'PRD-002',
                'name' => 'Telur Ayam',
                'product_category_id' => $protein->id,
                'unit' => 'kg',
                'government_price_cap' => 32000,
                'minimum_stock_level' => 40,
                'reorder_stock_level' => 60,
                'is_active' => true,
            ],
            [
                'sku' => 'PRD-003',
                'name' => 'Beras Medium',
                'product_category_id' => $carbohydrate->id,
                'unit' => 'kg',
                'government_price_cap' => 15000,
                'minimum_stock_level' => 100,
                'reorder_stock_level' => 150,
                'is_active' => true,
            ],
            [
                'sku' => 'PRD-004',
                'name' => 'Wortel',
                'product_category_id' => $vegetable->id,
                'unit' => 'kg',
                'government_price_cap' => 14000,
                'minimum_stock_level' => 35,
                'reorder_stock_level' => 50,
                'is_active' => true,
            ],
        ];

        foreach ($products as $payload) {
            Product::query()->updateOrCreate(['sku' => $payload['sku']], $payload);
        }

        $creatorId = User::query()->where('email', 'purchasing@ho.local')->value('id');
        $vendorId = Vendor::query()->where('code', 'VN-HO-01')->value('id');

        $priceMap = [
            'PRD-001' => 40000,
            'PRD-002' => 30000,
            'PRD-003' => 14500,
            'PRD-004' => 12000,
        ];

        foreach ($priceMap as $sku => $price) {
            $productId = Product::query()->where('sku', $sku)->value('id');

            if (! $productId) {
                continue;
            }

            ProductPriceHistory::query()->updateOrCreate(
                [
                    'product_id' => $productId,
                    'vendor_id' => $vendorId,
                    'effective_at' => now()->subWeek()->toDateString(),
                ],
                [
                    'price' => $price,
                    'created_by' => $creatorId,
                ]
            );
        }
    }
}
