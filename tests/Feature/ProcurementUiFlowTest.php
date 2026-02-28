<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Sppg;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcurementUiFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_ui_dashboard_is_accessible(): void
    {
        $response = $this->get(route('ui.dashboard'));

        $response->assertOk();
        $response->assertSee('HO Procurement Platform');
    }

    public function test_can_create_approve_and_generate_po_from_ui(): void
    {
        $vendor = Vendor::query()->create([
            'code' => 'VN-TEST-01',
            'name' => 'Vendor Test',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $sppg = Sppg::query()->create([
            'code' => 'SPPG-TEST-01',
            'name' => 'SPPG Test',
            'default_vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        ProductCategory::query()->create([
            'name' => 'Kategori Test',
        ]);

        $category = ProductCategory::query()->firstOrFail();

        $product = Product::query()->create([
            'sku' => 'PRD-TEST-01',
            'name' => 'Produk Test',
            'product_category_id' => $category->id,
            'unit' => 'kg',
            'government_price_cap' => 10000,
            'minimum_stock_level' => 5,
            'reorder_stock_level' => 10,
            'is_active' => true,
        ]);

        User::query()->create([
            'name' => 'Owner Test',
            'email' => 'owner.test@example.com',
            'password' => 'password123',
            'role' => 'owner',
        ]);

        User::query()->create([
            'name' => 'Purchasing Test',
            'email' => 'purchasing.test@example.com',
            'password' => 'password123',
            'role' => 'purchasing',
        ]);

        User::query()->create([
            'name' => 'SPPG User Test',
            'email' => 'sppg.test@example.com',
            'password' => 'password123',
            'role' => 'sppg_user',
            'sppg_id' => $sppg->id,
        ]);

        $createResponse = $this->post(route('ui.purchase-requests.store'), [
            'sppg_id' => $sppg->id,
            'needed_date' => now()->addDay()->toDateString(),
            'notes' => 'PR dari test',
            'product_id' => $product->id,
            'quantity' => 3,
            'requested_unit_price' => 9000,
        ]);

        $createResponse->assertRedirect(route('ui.purchase-requests.index'));

        $purchaseRequest = \App\Models\PurchaseRequest::query()->firstOrFail();
        $this->assertEquals(DocumentStatus::SUBMITTED, $purchaseRequest->status);

        $approveResponse = $this->post(route('ui.purchase-requests.approve', $purchaseRequest));
        $approveResponse->assertRedirect(route('ui.purchase-requests.index'));

        $purchaseRequest->refresh();
        $this->assertEquals(DocumentStatus::APPROVED, $purchaseRequest->status);

        $poResponse = $this->post(route('ui.purchase-requests.generate-po', $purchaseRequest), [
            'vendor_id' => $vendor->id,
            'expected_date' => now()->addDays(2)->toDateString(),
        ]);

        $poResponse->assertRedirect(route('ui.purchase-orders.index'));

        $this->assertDatabaseCount('purchase_orders', 1);
        $this->assertDatabaseCount('purchase_order_items', 1);

        $purchaseRequest->refresh();
        $this->assertEquals(DocumentStatus::PROCESSED, $purchaseRequest->status);
    }
}
