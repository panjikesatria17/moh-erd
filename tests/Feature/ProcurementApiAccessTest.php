<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\UserRole;
use App\Models\Delivery;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\Sppg;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProcurementApiAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_admin_api_reads_are_scoped_to_own_vendor(): void
    {
        $vendorOwn = Vendor::query()->create([
            'code' => 'VN-API-OWN-01',
            'name' => 'Vendor API Own',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $vendorOther = Vendor::query()->create([
            'code' => 'VN-API-OTH-01',
            'name' => 'Vendor API Other',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $sppg = Sppg::query()->create([
            'code' => 'SPPG-API-01',
            'name' => 'SPPG API',
            'default_vendor_id' => $vendorOwn->id,
            'is_active' => true,
        ]);

        $vendorAdmin = User::query()->create([
            'name' => 'Vendor Admin API',
            'email' => 'vendor.admin.api@example.com',
            'password' => 'password123',
            'role' => UserRole::VENDOR_ADMIN->value,
            'vendor_id' => $vendorOwn->id,
        ]);

        $poOwn = PurchaseOrder::query()->create([
            'number' => 'PO-API-OWN-01',
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendorOwn->id,
            'ordered_by' => null,
            'order_date' => now()->toDateString(),
            'status' => DocumentStatus::PROCESSED,
            'is_direct_purchase' => false,
            'total_amount' => 100000,
        ]);

        $poOther = PurchaseOrder::query()->create([
            'number' => 'PO-API-OTH-01',
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendorOther->id,
            'ordered_by' => null,
            'order_date' => now()->toDateString(),
            'status' => DocumentStatus::PROCESSED,
            'is_direct_purchase' => false,
            'total_amount' => 110000,
        ]);

        Delivery::query()->create([
            'number' => 'DLV-API-OWN-01',
            'purchase_order_id' => $poOwn->id,
            'goods_receipt_id' => null,
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendorOwn->id,
            'delivered_by' => $vendorAdmin->id,
            'delivery_date' => now()->toDateString(),
            'status' => DocumentStatus::DELIVERED,
            'total_amount' => 100000,
            'notes' => null,
        ]);

        Delivery::query()->create([
            'number' => 'DLV-API-OTH-01',
            'purchase_order_id' => $poOther->id,
            'goods_receipt_id' => null,
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendorOther->id,
            'delivered_by' => $vendorAdmin->id,
            'delivery_date' => now()->toDateString(),
            'status' => DocumentStatus::DELIVERED,
            'total_amount' => 110000,
            'notes' => null,
        ]);

        Invoice::query()->create([
            'number' => 'INV-API-OWN-01',
            'billing_cycle_id' => null,
            'delivery_id' => null,
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendorOwn->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => DocumentStatus::INVOICED,
            'subtotal_amount' => 100000,
            'tax_amount' => 0,
            'total_amount' => 100000,
        ]);

        Invoice::query()->create([
            'number' => 'INV-API-OTH-01',
            'billing_cycle_id' => null,
            'delivery_id' => null,
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendorOther->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => DocumentStatus::INVOICED,
            'subtotal_amount' => 110000,
            'tax_amount' => 0,
            'total_amount' => 110000,
        ]);

        Sanctum::actingAs($vendorAdmin);

        $ordersResponse = $this->getJson('/api/v1/purchase-orders');
        $ordersResponse->assertOk();
        $ordersResponse->assertJsonFragment(['number' => 'PO-API-OWN-01']);
        $ordersResponse->assertJsonMissing(['number' => 'PO-API-OTH-01']);

        $deliveriesResponse = $this->getJson('/api/v1/deliveries');
        $deliveriesResponse->assertOk();
        $deliveriesResponse->assertJsonFragment(['number' => 'DLV-API-OWN-01']);
        $deliveriesResponse->assertJsonMissing(['number' => 'DLV-API-OTH-01']);

        $invoicesResponse = $this->getJson('/api/v1/invoices');
        $invoicesResponse->assertOk();
        $invoicesResponse->assertJsonFragment(['number' => 'INV-API-OWN-01']);
        $invoicesResponse->assertJsonMissing(['number' => 'INV-API-OTH-01']);
    }

    public function test_vendor_admin_cannot_access_purchase_requests_api_resource(): void
    {
        $vendor = Vendor::query()->create([
            'code' => 'VN-API-LOCK-01',
            'name' => 'Vendor API Lock',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $vendorAdmin = User::query()->create([
            'name' => 'Vendor Admin API Lock',
            'email' => 'vendor.admin.api.lock@example.com',
            'password' => 'password123',
            'role' => UserRole::VENDOR_ADMIN->value,
            'vendor_id' => $vendor->id,
        ]);

        Sanctum::actingAs($vendorAdmin);

        $response = $this->getJson('/api/v1/purchase-requests');

        $response->assertForbidden();
    }
}
