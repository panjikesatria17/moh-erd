<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\FundingRequestStatus;
use App\Models\AuditTrail;
use App\Models\Approval;
use App\Models\AppSetting;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPriceHistory;
use App\Models\PurchaseFundingRequest;
use App\Models\Invoice;
use App\Models\Kwitansi;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Sppg;
use App\Models\User;
use App\Models\Vendor;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProcurementUiFlowTest extends TestCase
{
    use RefreshDatabase;

    private function skipIfGdIsMissing(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD extension is not installed.');
        }
    }

    public function test_ui_dashboard_redirects_guest_to_login(): void
    {
        $response = $this->get(route('ui.dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_ui_dashboard_is_accessible(): void
    {
        $user = User::query()->create([
            'name' => 'Dashboard User',
            'email' => 'dashboard.user@example.com',
            'password' => 'password123',
            'role' => 'owner',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('ui.dashboard'));

        $response->assertOk();
        $response->assertSee('Procurement Platform');
    }

    public function test_owner_dashboard_shows_asset_and_margin_cards(): void
    {
        $owner = User::query()->create([
            'name' => 'Owner Dashboard Metric',
            'email' => 'owner.dashboard.metric@example.com',
            'password' => 'password123',
            'role' => UserRole::OWNER->value,
        ]);

        $this->actingAs($owner);

        $response = $this->get(route('ui.dashboard'));

        $response->assertOk();
        $response->assertSee('Total Aset');
        $response->assertSee('Margin Keuntungan');
        $response->assertSee('Grafik Order Barang Berdasarkan SPPG');
        $response->assertSee('Ekspedisi');
    }

    public function test_purchasing_dashboard_shows_asset_and_chart_but_not_margin(): void
    {
        $purchasing = User::query()->create([
            'name' => 'Purchasing Dashboard Metric',
            'email' => 'purchasing.dashboard.metric@example.com',
            'password' => 'password123',
            'role' => UserRole::PURCHASING->value,
        ]);

        $this->actingAs($purchasing);

        $response = $this->get(route('ui.dashboard'));

        $response->assertOk();
        $response->assertSee('Total Aset');
        $response->assertDontSee('Margin Keuntungan');
        $response->assertSee('Grafik Order Barang Berdasarkan SPPG');
    }

    public function test_finance_and_admin_gudang_dashboard_show_asset_and_chart_but_not_margin(): void
    {
        $roles = [
            UserRole::FINANCE->value,
            UserRole::ADMIN_GUDANG->value,
        ];

        foreach ($roles as $index => $role) {
            $user = User::query()->create([
                'name' => 'Dashboard Role ' . $role,
                'email' => 'dashboard.role.' . $role . '.' . $index . '@example.com',
                'password' => 'password123',
                'role' => $role,
            ]);

            $response = $this->actingAs($user)->get(route('ui.dashboard'));

            $response->assertOk();
            $response->assertSee('Total Aset');
            $response->assertDontSee('Margin Keuntungan');
            $response->assertSee('Grafik Order Barang Berdasarkan SPPG');
        }
    }

    public function test_sppg_dashboard_does_not_show_asset_margin_or_chart(): void
    {
        $sppgUser = User::query()->create([
            'name' => 'SPPG Dashboard Metric',
            'email' => 'sppg.dashboard.metric@example.com',
            'password' => 'password123',
            'role' => UserRole::SPPG_USER->value,
        ]);

        $this->actingAs($sppgUser);

        $response = $this->get(route('ui.dashboard'));

        $response->assertOk();
        $response->assertDontSee('Total Aset');
        $response->assertDontSee('Margin Keuntungan');
        $response->assertDontSee('Grafik Order Barang Berdasarkan SPPG');
    }

    public function test_sppg_user_dashboard_is_scoped_to_own_sppg_data(): void
    {
        $vendorOwn = Vendor::query()->create([
            'code' => 'VN-DSH-OWN',
            'name' => 'Vendor Dashboard Own',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $vendorOther = Vendor::query()->create([
            'code' => 'VN-DSH-OTH',
            'name' => 'Vendor Dashboard Other',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $sppgOwn = Sppg::query()->create([
            'code' => 'SPPG-DSH-OWN',
            'name' => 'SPPG Dashboard Own',
            'default_vendor_id' => $vendorOwn->id,
            'is_active' => true,
        ]);

        $sppgOther = Sppg::query()->create([
            'code' => 'SPPG-DSH-OTH',
            'name' => 'SPPG Dashboard Other',
            'default_vendor_id' => $vendorOther->id,
            'is_active' => true,
        ]);

        $sppgUser = User::query()->create([
            'name' => 'SPPG Dashboard User',
            'email' => 'sppg.dashboard.user@example.com',
            'password' => 'password123',
            'role' => UserRole::SPPG_USER->value,
            'sppg_id' => $sppgOwn->id,
        ]);

        PurchaseRequest::query()->create([
            'number' => 'PR-DSH-OWN',
            'sppg_id' => $sppgOwn->id,
            'requested_by' => $sppgUser->id,
            'request_date' => now()->toDateString(),
            'needed_date' => now()->addDay()->toDateString(),
            'status' => DocumentStatus::SUBMITTED,
            'total_amount' => 100000,
        ]);

        PurchaseRequest::query()->create([
            'number' => 'PR-DSH-OTHER',
            'sppg_id' => $sppgOther->id,
            'requested_by' => null,
            'request_date' => now()->toDateString(),
            'needed_date' => now()->addDay()->toDateString(),
            'status' => DocumentStatus::SUBMITTED,
            'total_amount' => 150000,
        ]);

        PurchaseOrder::query()->create([
            'number' => 'PO-DSH-OWN',
            'purchase_request_id' => null,
            'sppg_id' => $sppgOwn->id,
            'vendor_id' => $vendorOwn->id,
            'ordered_by' => $sppgUser->id,
            'order_date' => now()->toDateString(),
            'status' => DocumentStatus::PROCESSED,
            'is_direct_purchase' => false,
            'total_amount' => 100000,
        ]);

        PurchaseOrder::query()->create([
            'number' => 'PO-DSH-OTHER',
            'purchase_request_id' => null,
            'sppg_id' => $sppgOther->id,
            'vendor_id' => $vendorOther->id,
            'ordered_by' => null,
            'order_date' => now()->toDateString(),
            'status' => DocumentStatus::PROCESSED,
            'is_direct_purchase' => false,
            'total_amount' => 180000,
        ]);

        Invoice::query()->create([
            'number' => 'INV-DSH-OWN',
            'billing_cycle_id' => null,
            'delivery_id' => null,
            'sppg_id' => $sppgOwn->id,
            'vendor_id' => $vendorOwn->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => DocumentStatus::INVOICED,
            'subtotal_amount' => 100000,
            'tax_amount' => 0,
            'total_amount' => 100000,
        ]);

        Invoice::query()->create([
            'number' => 'INV-DSH-OTHER',
            'billing_cycle_id' => null,
            'delivery_id' => null,
            'sppg_id' => $sppgOther->id,
            'vendor_id' => $vendorOther->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => DocumentStatus::INVOICED,
            'subtotal_amount' => 180000,
            'tax_amount' => 0,
            'total_amount' => 180000,
        ]);

        $this->actingAs($sppgUser);

        $response = $this->get(route('ui.dashboard'));

        $response->assertOk();
        $response->assertSee('PR-DSH-OWN');
        $response->assertDontSee('PR-DSH-OTHER');
        $response->assertSee('PO-DSH-OWN');
        $response->assertDontSee('PO-DSH-OTHER');
        $response->assertSee('INV-DSH-OWN');
        $response->assertDontSee('INV-DSH-OTHER');
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

        $actor = User::query()->create([
            'name' => 'UI Actor',
            'email' => 'ui.actor@example.com',
            'password' => 'password123',
            'role' => UserRole::SUPER_ADMIN->value,
        ]);

        $this->actingAs($actor);

        $createResponse = $this->post(route('ui.purchase-requests.store'), [
            'sppg_id' => $sppg->id,
            'needed_date' => now()->addDay()->toDateString(),
            'notes' => 'PR dari test',
            'is_product_review_confirmed' => '1',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 3,
                    'requested_unit_price' => 9000,
                ],
            ],
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

    public function test_store_user_role_auto_scopes_entity_ids_by_role(): void
    {
        $actor = User::query()->create([
            'name' => 'Super Admin Actor',
            'email' => 'superadmin.actor@example.com',
            'password' => 'password123',
            'role' => UserRole::SUPER_ADMIN->value,
        ]);

        $sppg = Sppg::query()->create([
            'code' => 'SPPG-SCOPE-01',
            'name' => 'SPPG Scope 01',
            'is_active' => true,
        ]);

        $vendor = Vendor::query()->create([
            'code' => 'VN-SCOPE-01',
            'name' => 'Vendor Scope 01',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $this->actingAs($actor);

        $response = $this->post(route('ui.users-roles.store'), [
            'name' => 'Finance Scoped',
            'email' => 'finance.scoped@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => UserRole::FINANCE->value,
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendor->id,
        ]);

        $response->assertRedirect(route('ui.users-roles.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'finance.scoped@example.com',
            'role' => UserRole::FINANCE->value,
            'sppg_id' => null,
            'vendor_id' => null,
        ]);
    }

    public function test_update_user_role_requires_scope_for_scoped_roles_and_clears_irrelevant_scope(): void
    {
        $actor = User::query()->create([
            'name' => 'Super Admin Actor 2',
            'email' => 'superadmin.actor2@example.com',
            'password' => 'password123',
            'role' => UserRole::SUPER_ADMIN->value,
        ]);

        $sppg = Sppg::query()->create([
            'code' => 'SPPG-SCOPE-02',
            'name' => 'SPPG Scope 02',
            'is_active' => true,
        ]);

        $vendor = Vendor::query()->create([
            'code' => 'VN-SCOPE-02',
            'name' => 'Vendor Scope 02',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $user = User::query()->create([
            'name' => 'Target User',
            'email' => 'target.user@example.com',
            'password' => 'password123',
            'role' => UserRole::FINANCE->value,
        ]);

        $this->actingAs($actor);

        $invalidResponse = $this->put(route('ui.users-roles.update', $user), [
            'name' => 'Target User',
            'email' => 'target.user@example.com',
            'role' => UserRole::SPPG_USER->value,
            'password' => '',
            'password_confirmation' => '',
        ]);

        $invalidResponse->assertSessionHasErrors(['sppg_id']);

        $validResponse = $this->put(route('ui.users-roles.update', $user), [
            'name' => 'Target User Updated',
            'email' => 'target.user@example.com',
            'role' => UserRole::SPPG_USER->value,
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendor->id,
            'password' => '',
            'password_confirmation' => '',
        ]);

        $validResponse->assertRedirect(route('ui.users-roles.index'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Target User Updated',
            'role' => UserRole::SPPG_USER->value,
            'sppg_id' => $sppg->id,
            'vendor_id' => null,
        ]);
    }

    public function test_owner_cannot_access_users_roles_page(): void
    {
        $owner = User::query()->create([
            'name' => 'Owner Restricted',
            'email' => 'owner.restricted@example.com',
            'password' => 'password123',
            'role' => UserRole::OWNER->value,
        ]);

        $this->actingAs($owner);

        $response = $this->get(route('ui.users-roles.index'));

        $response->assertForbidden();
    }

    public function test_admin_cannot_access_users_roles_page(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin Role Manager',
            'email' => 'admin.role.manager@example.com',
            'password' => 'password123',
            'role' => UserRole::ADMIN->value,
        ]);

        $response = $this->actingAs($admin)->get(route('ui.users-roles.index'));

        $response->assertForbidden();
    }

    public function test_admin_cannot_delete_super_admin_account_from_users_roles_page(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin User Delete Guard',
            'email' => 'admin.user.delete.guard@example.com',
            'password' => 'password123',
            'role' => UserRole::ADMIN->value,
        ]);

        $superAdmin = User::query()->create([
            'name' => 'Super Admin Protected',
            'email' => 'super.admin.protected@example.com',
            'password' => 'password123',
            'role' => UserRole::SUPER_ADMIN->value,
        ]);

        $response = $this->actingAs($admin)->delete(route('ui.users-roles.destroy', $superAdmin));

        $response->assertForbidden();
        $this->assertDatabaseHas('users', [
            'id' => $superAdmin->id,
            'email' => 'super.admin.protected@example.com',
        ]);
    }

    public function test_can_store_sppg_with_signatory_names_from_master_page(): void
    {
        $superAdmin = User::query()->create([
            'name' => 'Super Admin SPPG Store',
            'email' => 'superadmin.sppg.store@example.com',
            'password' => 'password123',
            'role' => UserRole::SUPER_ADMIN->value,
        ]);

        $vendor = Vendor::query()->create([
            'code' => 'VN-SPPG-SIGN-01',
            'name' => 'Vendor SPPG Sign 01',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin);

        $response = $this->post(route('ui.master-data.sppgs.store'), [
            'code' => 'SPPG-SIGN-01',
            'name' => 'SPPG Sign 01',
            'ka_sppg_name' => 'Kepala SPPG Satu',
            'accounting_name' => 'Akuntansi Satu',
            'address' => 'Bogor',
            'default_vendor_id' => $vendor->id,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('ui.master-data.sppgs.index'));

        $this->assertDatabaseHas('sppgs', [
            'code' => 'SPPG-SIGN-01',
            'name' => 'SPPG Sign 01',
            'ka_sppg_name' => 'Kepala SPPG Satu',
            'accounting_name' => 'Akuntansi Satu',
        ]);
    }

    public function test_can_update_sppg_signatory_names_from_master_page(): void
    {
        $superAdmin = User::query()->create([
            'name' => 'Super Admin SPPG Update',
            'email' => 'superadmin.sppg.update@example.com',
            'password' => 'password123',
            'role' => UserRole::SUPER_ADMIN->value,
        ]);

        $vendor = Vendor::query()->create([
            'code' => 'VN-SPPG-SIGN-02',
            'name' => 'Vendor SPPG Sign 02',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $sppg = Sppg::query()->create([
            'code' => 'SPPG-SIGN-02',
            'name' => 'SPPG Sign 02',
            'ka_sppg_name' => 'Kepala Lama',
            'accounting_name' => 'Akuntansi Lama',
            'default_vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin);

        $response = $this->put(route('ui.master-data.sppgs.update', $sppg), [
            'code' => 'SPPG-SIGN-02',
            'name' => 'SPPG Sign 02 Updated',
            'ka_sppg_name' => 'Kepala Baru',
            'accounting_name' => 'Akuntansi Baru',
            'address' => 'Bandung',
            'default_vendor_id' => $vendor->id,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('ui.master-data.sppgs.index'));

        $this->assertDatabaseHas('sppgs', [
            'id' => $sppg->id,
            'name' => 'SPPG Sign 02 Updated',
            'ka_sppg_name' => 'Kepala Baru',
            'accounting_name' => 'Akuntansi Baru',
        ]);
    }

    public function test_can_download_purchase_order_pdf_from_ui(): void
    {
        $this->skipIfGdIsMissing();

        $actor = User::query()->create([
            'name' => 'Owner Download',
            'email' => 'owner.download@example.com',
            'password' => 'password123',
            'role' => UserRole::OWNER->value,
        ]);

        $requester = User::query()->create([
            'name' => 'Requester Download',
            'email' => 'requester.download@example.com',
            'password' => 'password123',
            'role' => UserRole::SPPG_USER->value,
        ]);

        $orderedBy = User::query()->create([
            'name' => 'Purchasing Download',
            'email' => 'purchasing.download@example.com',
            'password' => 'password123',
            'role' => UserRole::PURCHASING->value,
        ]);

        $vendor = Vendor::query()->create([
            'code' => 'VN-DL-01',
            'name' => 'Vendor Download',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $sppg = Sppg::query()->create([
            'code' => 'SPPG-DL-01',
            'name' => 'SPPG Download',
            'default_vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $category = ProductCategory::query()->create([
            'name' => 'Kategori Download',
        ]);

        $product = Product::query()->create([
            'sku' => 'PRD-DL-01',
            'name' => 'Produk Download',
            'product_category_id' => $category->id,
            'unit' => 'kg',
            'government_price_cap' => 10000,
            'minimum_stock_level' => 1,
            'reorder_stock_level' => 2,
            'is_active' => true,
        ]);

        $purchaseRequest = PurchaseRequest::query()->create([
            'number' => 'PR-DL-01',
            'sppg_id' => $sppg->id,
            'requested_by' => $requester->id,
            'request_date' => now()->toDateString(),
            'needed_date' => now()->addDay()->toDateString(),
            'status' => DocumentStatus::APPROVED,
            'total_amount' => 50000,
        ]);

        $purchaseRequest->items()->create([
            'product_id' => $product->id,
            'quantity' => 5,
            'requested_unit_price' => 10000,
            'subtotal' => 50000,
        ]);

        $purchaseOrder = PurchaseOrder::query()->create([
            'number' => 'PO-DL-01',
            'purchase_request_id' => $purchaseRequest->id,
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendor->id,
            'ordered_by' => $orderedBy->id,
            'order_date' => now()->toDateString(),
            'status' => DocumentStatus::PROCESSED,
            'is_direct_purchase' => false,
            'total_amount' => 50000,
        ]);

        $purchaseOrder->items()->create([
            'product_id' => $product->id,
            'quantity' => 5,
            'unit_price' => 10000,
            'subtotal' => 50000,
        ]);

        $this->actingAs($actor);

        $response = $this->get(route('ui.purchase-orders.download', $purchaseOrder));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('attachment; filename=PO-DL-01.pdf', (string) $response->headers->get('content-disposition'));

        $prResponse = $this->get(route('ui.purchase-requests.download', $purchaseRequest));

        $prResponse->assertOk();
        $prResponse->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('attachment; filename=PR-DL-01.pdf', (string) $prResponse->headers->get('content-disposition'));
    }

    public function test_can_download_master_products_pdf_from_ui(): void
    {
        $this->skipIfGdIsMissing();

        $actor = User::query()->create([
            'name' => 'Purchasing Product PDF',
            'email' => 'purchasing.product.pdf@example.com',
            'password' => 'password123',
            'role' => UserRole::PURCHASING->value,
        ]);

        $vendor = Vendor::query()->create([
            'code' => 'VN-PRD-PDF-01',
            'name' => 'Vendor Product PDF',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $category = ProductCategory::query()->create([
            'name' => 'Kategori Product PDF',
        ]);

        Product::query()->create([
            'sku' => 'PRD-PDF-01',
            'name' => 'Produk PDF 01',
            'product_category_id' => $category->id,
            'vendor_id' => $vendor->id,
            'unit' => 'kg',
            'government_price_cap' => 10000,
            'minimum_stock_level' => 1,
            'reorder_stock_level' => 2,
            'is_active' => true,
            'is_ad_hoc' => false,
        ]);

        $this->actingAs($actor);

        $response = $this->get(route('ui.master-data.products.export-pdf'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('attachment; filename=master-products-', (string) $response->headers->get('content-disposition'));
    }

    public function test_can_download_invoice_pdf_from_ui(): void
    {
        $this->skipIfGdIsMissing();

        $actor = User::query()->create([
            'name' => 'Finance Download',
            'email' => 'finance.download@example.com',
            'password' => 'password123',
            'role' => UserRole::FINANCE->value,
        ]);

        $vendor = Vendor::query()->create([
            'code' => 'VN-INV-01',
            'name' => 'Vendor Invoice Download',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $sppg = Sppg::query()->create([
            'code' => 'SPPG-INV-01',
            'name' => 'SPPG Invoice Download',
            'default_vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $invoice = Invoice::query()->create([
            'number' => 'INV-DL-01',
            'billing_cycle_id' => null,
            'delivery_id' => null,
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendor->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => DocumentStatus::INVOICED,
            'subtotal_amount' => 100000,
            'tax_amount' => 11000,
            'total_amount' => 111000,
        ]);

        $this->actingAs($actor);

        $response = $this->get(route('ui.invoices.download', $invoice));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('attachment; filename=INV-DL-01.pdf', (string) $response->headers->get('content-disposition'));
    }

    public function test_invoice_logo_resolution_prefers_vendor_logo_when_available(): void
    {
        $controller = new \App\Http\Controllers\Web\ProcurementUiController();
        $method = new \ReflectionMethod($controller, 'resolveVendorLogoDataUri');
        $method->setAccessible(true);

        $vendorWithLogo = Vendor::query()->create([
            'code' => 'VN-LOGO-01',
            'name' => 'Vendor With Logo',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $vendorWithoutLogo = Vendor::query()->create([
            'code' => 'VN-LOGO-02',
            'name' => 'Vendor Without Logo',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $logoDir = public_path('images/vendors');
        if (! is_dir($logoDir)) {
            mkdir($logoDir, 0777, true);
        }

        $logoPath = $logoDir . '/vn-logo-01.png';
        $tinyPng = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO7Zx5QAAAAASUVORK5CYII=';

        file_put_contents($logoPath, (string) base64_decode($tinyPng));

        try {
            [$customLogoUri, $isCustomLogo] = $method->invoke($controller, $vendorWithLogo, true);
            [$fallbackLogoUri, $isFallbackCustom] = $method->invoke($controller, $vendorWithoutLogo, true);

            $this->assertTrue($isCustomLogo);
            $this->assertIsString($customLogoUri);
            $this->assertStringStartsWith('data:image/png;base64,', $customLogoUri);

            $this->assertFalse($isFallbackCustom);
            $this->assertIsString($fallbackLogoUri);
            $this->assertStringStartsWith('data:image/', $fallbackLogoUri);
        } finally {
            if (is_file($logoPath)) {
                unlink($logoPath);
            }
        }
    }

    public function test_can_create_delivery_from_purchase_order_from_ui(): void
    {
        $actor = User::query()->create([
            'name' => 'Gudang Delivery',
            'email' => 'gudang.delivery@example.com',
            'password' => 'password123',
            'role' => UserRole::ADMIN_GUDANG->value,
        ]);

        $vendor = Vendor::query()->create([
            'code' => 'VN-DLV-01',
            'name' => 'Vendor Delivery',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $sppg = Sppg::query()->create([
            'code' => 'SPPG-DLV-01',
            'name' => 'SPPG Delivery',
            'default_vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $purchaseRequest = PurchaseRequest::query()->create([
            'number' => 'PR-DLV-01',
            'sppg_id' => $sppg->id,
            'requested_by' => null,
            'request_date' => now()->toDateString(),
            'needed_date' => now()->addDay()->toDateString(),
            'status' => DocumentStatus::APPROVED,
            'total_amount' => 150000,
        ]);

        $purchaseOrder = PurchaseOrder::query()->create([
            'number' => 'PO-DLV-01',
            'purchase_request_id' => $purchaseRequest->id,
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendor->id,
            'ordered_by' => null,
            'order_date' => now()->toDateString(),
            'status' => DocumentStatus::PROCESSED,
            'is_direct_purchase' => false,
            'total_amount' => 150000,
        ]);

        $this->actingAs($actor);

        $response = $this->post(route('ui.purchase-orders.create-delivery', $purchaseOrder));

        $response->assertRedirect(route('ui.deliveries.index'));

        $this->assertDatabaseHas('deliveries', [
            'purchase_order_id' => $purchaseOrder->id,
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendor->id,
            'status' => DocumentStatus::PROCESSED->value,
        ]);

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $purchaseOrder->id,
            'status' => DocumentStatus::PROCESSED->value,
        ]);

        $this->assertDatabaseHas('audit_trails', [
            'user_id' => $actor->id,
            'event' => 'delivery.created_from_po',
            'auditable_type' => \App\Models\Delivery::class,
            'ip_address' => '127.0.0.1',
        ]);
    }

    public function test_expedition_can_complete_delivery_with_proofs_and_mark_delivered(): void
    {
        $this->skipIfGdIsMissing();

        Storage::fake('public');

        $vendor = Vendor::query()->create([
            'code' => 'VN-EXP-01',
            'name' => 'Vendor Ekspedisi',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $sppg = Sppg::query()->create([
            'code' => 'SPPG-EXP-01',
            'name' => 'SPPG Ekspedisi',
            'default_vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $expedition = User::query()->create([
            'name' => 'Ekspedisi User',
            'email' => 'ekspedisi.user@example.com',
            'password' => 'password123',
            'role' => UserRole::EXPEDITION->value,
            'vendor_id' => $vendor->id,
        ]);

        $purchaseOrder = PurchaseOrder::query()->create([
            'number' => 'PO-EXP-01',
            'purchase_request_id' => null,
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendor->id,
            'ordered_by' => null,
            'order_date' => now()->toDateString(),
            'status' => DocumentStatus::PROCESSED,
            'is_direct_purchase' => false,
            'total_amount' => 200000,
        ]);

        $delivery = \App\Models\Delivery::query()->create([
            'number' => 'DLV-EXP-01',
            'purchase_order_id' => $purchaseOrder->id,
            'goods_receipt_id' => null,
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendor->id,
            'delivered_by' => null,
            'delivery_date' => now()->toDateString(),
            'status' => DocumentStatus::PROCESSED,
            'total_amount' => 200000,
            'invoiced_po_amount' => 0,
            'notes' => 'On proses ekspedisi',
        ]);

        $this->actingAs($expedition);

        $response = $this->post(route('ui.deliveries.complete', $delivery), [
            'delivery_proof_image' => UploadedFile::fake()->image('proof.jpg'),
            'signed_delivery_note' => UploadedFile::fake()->create('surat-jalan.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect(route('ui.deliveries.index'));

        $delivery->refresh();
        $purchaseOrder->refresh();

        $this->assertEquals(DocumentStatus::DELIVERED, $delivery->status);
        $this->assertNotNull($delivery->delivery_proof_image_path);
        $this->assertNotNull($delivery->signed_delivery_note_path);
        $this->assertNotNull($delivery->proof_uploaded_at);
        $this->assertNotNull($delivery->delivered_at);
        $this->assertEquals($expedition->id, (int) $delivery->delivered_by);
        $this->assertEquals(DocumentStatus::DELIVERED, $purchaseOrder->status);

        Storage::disk('public')->assertExists((string) $delivery->delivery_proof_image_path);
        Storage::disk('public')->assertExists((string) $delivery->signed_delivery_note_path);

        $this->assertDatabaseHas('audit_trails', [
            'user_id' => $expedition->id,
            'event' => 'delivery.completed_by_expedition',
            'auditable_type' => \App\Models\Delivery::class,
            'auditable_id' => $delivery->id,
        ]);
    }

    public function test_audit_trails_page_can_filter_by_event_and_user(): void
    {
        $owner = User::query()->create([
            'name' => 'Owner Audit',
            'email' => 'owner.audit@example.com',
            'password' => 'password123',
            'role' => UserRole::OWNER->value,
        ]);

        $actorOne = User::query()->create([
            'name' => 'Actor One',
            'email' => 'actor.one@example.com',
            'password' => 'password123',
            'role' => UserRole::FINANCE->value,
        ]);

        $actorTwo = User::query()->create([
            'name' => 'Actor Two',
            'email' => 'actor.two@example.com',
            'password' => 'password123',
            'role' => UserRole::PURCHASING->value,
        ]);

        AuditTrail::query()->create([
            'user_id' => $actorOne->id,
            'event' => 'invoice.marked_paid',
            'auditable_type' => Invoice::class,
            'auditable_id' => 101,
            'old_values' => ['status' => 'invoiced'],
            'new_values' => ['status' => 'paid'],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
        ]);

        AuditTrail::query()->create([
            'user_id' => $actorTwo->id,
            'event' => 'delivery.created_from_po',
            'auditable_type' => PurchaseOrder::class,
            'auditable_id' => 202,
            'old_values' => null,
            'new_values' => ['status' => 'delivered'],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
        ]);

        $this->actingAs($owner);

        $response = $this->get(route('ui.audit-trails.index', [
            'event' => 'invoice.marked_paid',
            'user_id' => $actorOne->id,
        ]));

        $response->assertOk();
        $response->assertSee('invoice.marked_paid');
        $response->assertSee('Actor One');
        $response->assertSee('Invoice #101');
        $response->assertDontSee('PurchaseOrder #202');
    }

    public function test_owner_can_approve_item_from_approval_queue(): void
    {
        $owner = User::query()->create([
            'name' => 'Owner Queue',
            'email' => 'owner.queue@example.com',
            'password' => 'password123',
            'role' => UserRole::OWNER->value,
        ]);

        $sppg = Sppg::query()->create([
            'code' => 'SPPG-AQ-01',
            'name' => 'SPPG AQ 01',
            'is_active' => true,
        ]);

        $purchaseRequest = PurchaseRequest::query()->create([
            'number' => 'PR-AQ-01',
            'sppg_id' => $sppg->id,
            'requested_by' => null,
            'request_date' => now()->toDateString(),
            'needed_date' => now()->addDay()->toDateString(),
            'status' => DocumentStatus::SUBMITTED,
            'total_amount' => 250000,
        ]);

        $approval = Approval::query()->create([
            'approvable_type' => PurchaseRequest::class,
            'approvable_id' => $purchaseRequest->id,
            'level' => 1,
            'approver_id' => $owner->id,
            'status' => DocumentStatus::SUBMITTED,
            'note' => null,
            'approved_at' => null,
        ]);

        $this->actingAs($owner);

        $response = $this->post(route('ui.approvals.approve', $approval));

        $response->assertRedirect(route('ui.approvals.index'));

        $approval->refresh();
        $purchaseRequest->refresh();

        $this->assertEquals(DocumentStatus::APPROVED, $approval->status);
        $this->assertNotNull($approval->approved_at);
        $this->assertEquals(DocumentStatus::APPROVED, $purchaseRequest->status);
    }

    public function test_owner_can_reject_item_from_approval_queue(): void
    {
        $owner = User::query()->create([
            'name' => 'Owner Queue Reject',
            'email' => 'owner.queue.reject@example.com',
            'password' => 'password123',
            'role' => UserRole::OWNER->value,
        ]);

        $sppg = Sppg::query()->create([
            'code' => 'SPPG-AQ-02',
            'name' => 'SPPG AQ 02',
            'is_active' => true,
        ]);

        $purchaseRequest = PurchaseRequest::query()->create([
            'number' => 'PR-AQ-02',
            'sppg_id' => $sppg->id,
            'requested_by' => null,
            'request_date' => now()->toDateString(),
            'needed_date' => now()->addDay()->toDateString(),
            'status' => DocumentStatus::SUBMITTED,
            'total_amount' => 300000,
        ]);

        $approval = Approval::query()->create([
            'approvable_type' => PurchaseRequest::class,
            'approvable_id' => $purchaseRequest->id,
            'level' => 1,
            'approver_id' => $owner->id,
            'status' => DocumentStatus::SUBMITTED,
            'note' => null,
            'approved_at' => null,
        ]);

        $this->actingAs($owner);

        $response = $this->post(route('ui.approvals.reject', $approval), [
            'note' => 'Tidak sesuai kebijakan',
        ]);

        $response->assertRedirect(route('ui.approvals.index'));

        $approval->refresh();
        $purchaseRequest->refresh();

        $this->assertEquals(DocumentStatus::REJECTED, $approval->status);
        $this->assertNotNull($approval->approved_at);
        $this->assertEquals('Tidak sesuai kebijakan', $approval->note);
        $this->assertEquals(DocumentStatus::REJECTED, $purchaseRequest->status);
    }

    public function test_purchase_order_above_threshold_requires_owner_approval(): void
    {
        AppSetting::query()->updateOrCreate(
            ['key' => 'po_owner_approval_threshold'],
            ['value' => '5000000']
        );

        $owner = User::query()->create([
            'name' => 'Owner PO Threshold',
            'email' => 'owner.po.threshold@example.com',
            'password' => 'password123',
            'role' => UserRole::OWNER->value,
        ]);

        $purchasing = User::query()->create([
            'name' => 'Purchasing PO Threshold',
            'email' => 'purchasing.po.threshold@example.com',
            'password' => 'password123',
            'role' => UserRole::PURCHASING->value,
        ]);

        $vendor = Vendor::query()->create([
            'code' => 'VN-THR-01',
            'name' => 'Vendor Threshold',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $sppg = Sppg::query()->create([
            'code' => 'SPPG-THR-01',
            'name' => 'SPPG Threshold',
            'default_vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $category = ProductCategory::query()->create([
            'name' => 'Kategori Threshold',
        ]);

        $product = Product::query()->create([
            'sku' => 'PRD-THR-01',
            'name' => 'Produk Threshold',
            'product_category_id' => $category->id,
            'unit' => 'kg',
            'government_price_cap' => 10000,
            'minimum_stock_level' => 1,
            'reorder_stock_level' => 2,
            'is_active' => true,
        ]);

        $purchaseRequest = PurchaseRequest::query()->create([
            'number' => 'PR-THR-01',
            'sppg_id' => $sppg->id,
            'requested_by' => null,
            'request_date' => now()->toDateString(),
            'needed_date' => now()->addDay()->toDateString(),
            'status' => DocumentStatus::APPROVED,
            'total_amount' => 0,
        ]);

        $purchaseRequest->items()->create([
            'product_id' => $product->id,
            'quantity' => 600,
            'requested_unit_price' => 10000,
            'subtotal' => 6000000,
        ]);

        $purchaseRequest->recalculateTotal();

        $this->actingAs($purchasing);

        $response = $this->post(route('ui.purchase-requests.generate-po', $purchaseRequest), [
            'vendor_id' => $vendor->id,
            'expected_date' => now()->addDays(2)->toDateString(),
        ]);

        $response->assertRedirect(route('ui.purchase-orders.index'));

        $po = PurchaseOrder::query()->latest('id')->firstOrFail();

        $this->assertEquals(DocumentStatus::SUBMITTED, $po->status);
        $this->assertDatabaseHas('approvals', [
            'approvable_type' => PurchaseOrder::class,
            'approvable_id' => $po->id,
            'status' => DocumentStatus::SUBMITTED->value,
            'approver_id' => $owner->id,
        ]);
    }

    public function test_super_admin_can_update_po_threshold_from_approval_page(): void
    {
        $superAdmin = User::query()->create([
            'name' => 'Super Admin Threshold Update',
            'email' => 'superadmin.threshold.update@example.com',
            'password' => 'password123',
            'role' => UserRole::SUPER_ADMIN->value,
        ]);

        $this->actingAs($superAdmin);

        $response = $this->post(route('ui.approvals.settings.po-threshold.update'), [
            'po_owner_approval_threshold' => 7000000,
        ]);

        $response->assertRedirect(route('ui.approvals.index'));

        $this->assertDatabaseHas('app_settings', [
            'key' => 'po_owner_approval_threshold',
            'value' => '7000000',
        ]);
    }

    public function test_billing_cycles_page_shows_po_threshold_and_pending_owner_approvals(): void
    {
        AppSetting::query()->updateOrCreate(
            ['key' => 'po_owner_approval_threshold'],
            ['value' => '5500000']
        );

        $owner = User::query()->create([
            'name' => 'Owner Billing',
            'email' => 'owner.billing@example.com',
            'password' => 'password123',
            'role' => UserRole::OWNER->value,
        ]);

        $vendor = Vendor::query()->create([
            'code' => 'VN-BLC-01',
            'name' => 'Vendor Billing',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $sppg = Sppg::query()->create([
            'code' => 'SPPG-BLC-01',
            'name' => 'SPPG Billing',
            'default_vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $purchaseRequest = PurchaseRequest::query()->create([
            'number' => 'PR-BLC-01',
            'sppg_id' => $sppg->id,
            'requested_by' => null,
            'request_date' => now()->toDateString(),
            'needed_date' => now()->addDay()->toDateString(),
            'status' => DocumentStatus::APPROVED,
            'total_amount' => 6000000,
        ]);

        $purchaseOrder = PurchaseOrder::query()->create([
            'number' => 'PO-BLC-01',
            'purchase_request_id' => $purchaseRequest->id,
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendor->id,
            'ordered_by' => null,
            'order_date' => now()->toDateString(),
            'status' => DocumentStatus::SUBMITTED,
            'is_direct_purchase' => false,
            'total_amount' => 6000000,
        ]);

        Approval::query()->create([
            'approvable_type' => PurchaseOrder::class,
            'approvable_id' => $purchaseOrder->id,
            'level' => 1,
            'approver_id' => $owner->id,
            'status' => DocumentStatus::SUBMITTED,
            'note' => 'Menunggu approval owner',
            'approved_at' => null,
        ]);

        $this->actingAs($owner);

        $response = $this->get(route('ui.billing-cycles.index'));

        $response->assertOk();
        $response->assertSee('Aturan Approval PO Aktif');
        $response->assertSee('Rp 5.500.000');
        $response->assertSee('PO menunggu approval owner saat ini: 1');
    }

    public function test_finance_can_create_grouped_kwitansi_from_multiple_invoices(): void
    {
        $finance = User::query()->create([
            'name' => 'Finance Kwitansi',
            'email' => 'finance.kwitansi@example.com',
            'password' => 'password123',
            'role' => UserRole::FINANCE->value,
        ]);

        $vendor = Vendor::query()->create([
            'code' => 'VN-KWT-01',
            'name' => 'Vendor Kwitansi',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $sppg = Sppg::query()->create([
            'code' => 'SPPG-KWT-01',
            'name' => 'SPPG Kwitansi',
            'default_vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $invoiceOne = Invoice::query()->create([
            'number' => 'INV-KWT-01',
            'billing_cycle_id' => null,
            'delivery_id' => null,
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendor->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => DocumentStatus::INVOICED,
            'subtotal_amount' => 200000,
            'tax_amount' => 0,
            'total_amount' => 200000,
        ]);

        $invoiceTwo = Invoice::query()->create([
            'number' => 'INV-KWT-02',
            'billing_cycle_id' => null,
            'delivery_id' => null,
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendor->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => DocumentStatus::INVOICED,
            'subtotal_amount' => 300000,
            'tax_amount' => 0,
            'total_amount' => 300000,
        ]);

        $this->actingAs($finance);

        $response = $this->post(route('ui.kwitansi.store'), [
            'vendor_id' => $vendor->id,
            'receipt_date' => now()->toDateString(),
            'billed_to' => 'SPPG KWITANSI',
            'invoice_ids' => [$invoiceOne->id, $invoiceTwo->id],
            'notes' => 'Tagihan gabungan 2 invoice',
        ]);

        $response->assertRedirect(route('ui.kwitansi.index', ['vendor' => $vendor->id]));

        $this->assertDatabaseCount('kwitansis', 1);
        $this->assertDatabaseHas('kwitansis', [
            'vendor_id' => $vendor->id,
            'billed_to' => 'SPPG KWITANSI',
            'total_amount' => 500000,
        ]);

        $kwitansi = Kwitansi::query()->firstOrFail();
        $this->assertDatabaseHas('kwitansi_invoice', [
            'kwitansi_id' => $kwitansi->id,
            'invoice_id' => $invoiceOne->id,
            'billed_amount' => 200000,
        ]);
        $this->assertDatabaseHas('kwitansi_invoice', [
            'kwitansi_id' => $kwitansi->id,
            'invoice_id' => $invoiceTwo->id,
            'billed_amount' => 300000,
        ]);
    }

    public function test_finance_can_download_kwitansi_pdf(): void
    {
        $this->skipIfGdIsMissing();

        $finance = User::query()->create([
            'name' => 'Finance Kwitansi Download',
            'email' => 'finance.kwitansi.download@example.com',
            'password' => 'password123',
            'role' => UserRole::FINANCE->value,
        ]);

        $vendor = Vendor::query()->create([
            'code' => 'VN-KWT-02',
            'name' => 'Vendor Kwitansi Download',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $sppg = Sppg::query()->create([
            'code' => 'SPPG-KWT-02',
            'name' => 'SPPG Kwitansi Download',
            'default_vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $invoice = Invoice::query()->create([
            'number' => 'INV-KWT-DL-01',
            'billing_cycle_id' => null,
            'delivery_id' => null,
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendor->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => DocumentStatus::INVOICED,
            'subtotal_amount' => 150000,
            'tax_amount' => 0,
            'total_amount' => 150000,
        ]);

        $kwitansi = Kwitansi::query()->create([
            'number' => 'KWT-DL-01',
            'vendor_id' => $vendor->id,
            'billed_to' => 'SPPG DOWNLOAD',
            'receipt_date' => now()->toDateString(),
            'total_amount' => 150000,
            'created_by' => $finance->id,
        ]);

        $kwitansi->invoices()->attach($invoice->id, ['billed_amount' => 150000]);

        $this->actingAs($finance);

        $response = $this->get(route('ui.kwitansi.download', $kwitansi));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('attachment; filename=KWT-DL-01.pdf', (string) $response->headers->get('content-disposition'));
    }

    public function test_ad_hoc_non_catalog_item_can_be_requested_and_requires_owner_approval_on_po(): void
    {
        AppSetting::query()->updateOrCreate(
            ['key' => 'po_owner_approval_threshold'],
            ['value' => '5000000']
        );

        $owner = User::query()->create([
            'name' => 'Owner Adhoc',
            'email' => 'owner.adhoc@example.com',
            'password' => 'password123',
            'role' => UserRole::OWNER->value,
        ]);

        $superAdmin = User::query()->create([
            'name' => 'Super Admin Adhoc',
            'email' => 'superadmin.adhoc@example.com',
            'password' => 'password123',
            'role' => UserRole::SUPER_ADMIN->value,
        ]);

        User::query()->create([
            'name' => 'Purchasing Adhoc',
            'email' => 'purchasing.adhoc@example.com',
            'password' => 'password123',
            'role' => UserRole::PURCHASING->value,
        ]);

        $vendor = Vendor::query()->create([
            'code' => 'VN-ADH-01',
            'name' => 'Vendor Adhoc',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $sppg = Sppg::query()->create([
            'code' => 'SPPG-ADH-01',
            'name' => 'SPPG Adhoc',
            'default_vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin);

        $createPrResponse = $this->post(route('ui.purchase-requests.store'), [
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendor->id,
            'needed_date' => now()->addDays(2)->toDateString(),
            'is_product_review_confirmed' => '1',
            'items' => [
                [
                    'product_id' => null,
                    'ad_hoc_name' => 'Bumbu Rempah Khusus',
                    'ad_hoc_unit' => 'paket',
                    'quantity' => 2,
                    'requested_unit_price' => 750000,
                ],
            ],
        ]);

        $createPrResponse->assertRedirect(route('ui.purchase-requests.index'));

        $adHocProduct = Product::query()->where('name', 'Bumbu Rempah Khusus')->latest('id')->first();
        $this->assertNotNull($adHocProduct);
        $this->assertTrue((bool) $adHocProduct->is_ad_hoc);
        $this->assertFalse((bool) $adHocProduct->is_active);

        $purchaseRequest = PurchaseRequest::query()->latest('id')->firstOrFail();
        $this->assertEquals(DocumentStatus::SUBMITTED, $purchaseRequest->status);

        $approvePrResponse = $this->post(route('ui.purchase-requests.approve', $purchaseRequest));
        $approvePrResponse->assertRedirect(route('ui.purchase-requests.index'));

        $purchaseRequest->refresh();
        $this->assertEquals(DocumentStatus::APPROVED, $purchaseRequest->status);

        $generatePoResponse = $this->post(route('ui.purchase-requests.generate-po', $purchaseRequest), [
            'vendor_id' => $vendor->id,
            'expected_date' => now()->addDays(4)->toDateString(),
        ]);

        $generatePoResponse->assertRedirect(route('ui.purchase-orders.index'));

        $po = PurchaseOrder::query()->latest('id')->firstOrFail();
        $this->assertEquals(DocumentStatus::SUBMITTED, $po->status);

        $this->assertDatabaseHas('approvals', [
            'approvable_type' => PurchaseOrder::class,
            'approvable_id' => $po->id,
            'status' => DocumentStatus::SUBMITTED->value,
            'approver_id' => $owner->id,
        ]);
    }

    public function test_super_admin_can_promote_ad_hoc_product_to_catalog(): void
    {
        $superAdmin = User::query()->create([
            'name' => 'Super Admin Promote',
            'email' => 'superadmin.promote@example.com',
            'password' => 'password123',
            'role' => UserRole::SUPER_ADMIN->value,
        ]);

        $vendor = Vendor::query()->create([
            'code' => 'VN-PROMO-01',
            'name' => 'Vendor Promote',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $categoryTemp = ProductCategory::query()->create([
            'name' => 'Non Katalog Temp',
        ]);

        $categoryFinal = ProductCategory::query()->create([
            'name' => 'Bumbu',
        ]);

        $adHocProduct = Product::query()->create([
            'sku' => 'ADH-PROMO-01',
            'name' => 'Produk Adhoc Promo',
            'product_category_id' => $categoryTemp->id,
            'vendor_id' => null,
            'unit' => 'pack',
            'government_price_cap' => null,
            'minimum_stock_level' => 0,
            'reorder_stock_level' => 0,
            'is_active' => false,
            'is_ad_hoc' => true,
        ]);

        $this->actingAs($superAdmin);

        $response = $this->post(route('ui.master-data.products.promote-ad-hoc', $adHocProduct), [
            'product_category_id' => $categoryFinal->id,
            'vendor_id' => $vendor->id,
            'government_price_cap' => 25000,
        ]);

        $response->assertRedirect(route('ui.master-data.products.index'));

        $this->assertDatabaseHas('products', [
            'id' => $adHocProduct->id,
            'product_category_id' => $categoryFinal->id,
            'vendor_id' => $vendor->id,
            'government_price_cap' => 25000,
            'is_ad_hoc' => false,
            'is_active' => true,
        ]);
    }

    public function test_master_products_scope_filter_can_show_only_non_catalog_products(): void
    {
        $purchasing = User::query()->create([
            'name' => 'Purchasing Filter',
            'email' => 'purchasing.filter@example.com',
            'password' => 'password123',
            'role' => UserRole::PURCHASING->value,
        ]);

        $vendor = Vendor::query()->create([
            'code' => 'VN-FLTR-01',
            'name' => 'Vendor Filter',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $category = ProductCategory::query()->create([
            'name' => 'Kategori Filter',
        ]);

        Product::query()->create([
            'sku' => 'CAT-001',
            'name' => 'Produk Katalog',
            'product_category_id' => $category->id,
            'vendor_id' => $vendor->id,
            'unit' => 'kg',
            'minimum_stock_level' => 0,
            'reorder_stock_level' => 0,
            'is_active' => true,
            'is_ad_hoc' => false,
        ]);

        Product::query()->create([
            'sku' => 'ADH-001',
            'name' => 'Produk Non Katalog',
            'product_category_id' => $category->id,
            'vendor_id' => $vendor->id,
            'unit' => 'pcs',
            'minimum_stock_level' => 0,
            'reorder_stock_level' => 0,
            'is_active' => false,
            'is_ad_hoc' => true,
        ]);

        $this->actingAs($purchasing);

        $response = $this->get(route('ui.master-data.products.index', ['scope' => 'ad_hoc']));

        $response->assertOk();
        $response->assertSee('Produk Non Katalog');
        $response->assertDontSee('Produk Katalog');
    }

    public function test_master_products_asset_value_uses_minimum_stock_when_inventory_zero(): void
    {
        $purchasing = User::query()->create([
            'name' => 'Purchasing Asset Value',
            'email' => 'purchasing.asset.value@example.com',
            'password' => 'password123',
            'role' => UserRole::PURCHASING->value,
        ]);

        $vendor = Vendor::query()->create([
            'code' => 'VN-ASSET-01',
            'name' => 'Vendor Asset',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $category = ProductCategory::query()->create([
            'name' => 'Kategori Asset',
        ]);

        $product = Product::query()->create([
            'sku' => 'PRD-ASSET-01',
            'name' => 'Produk Asset',
            'product_category_id' => $category->id,
            'vendor_id' => $vendor->id,
            'unit' => 'kg',
            'selling_price' => 100,
            'price_variance_percent' => 0,
            'price_variance_amount' => 0,
            'minimum_stock_level' => 10,
            'reorder_stock_level' => 20,
            'is_active' => true,
            'is_ad_hoc' => false,
        ]);

        $this->actingAs($purchasing);

        $response = $this->get(route('ui.master-data.products.index'));

        $response->assertOk();
        $response->assertViewHas('inventoryValueByProduct', function (array $values) use ($product) {
            return abs((float) ($values[$product->id] ?? 0) - 1000.0) < 0.0001;
        });
        $response->assertViewHas('totalAssetValue', fn($value) => abs((float) $value - 1000.0) < 0.0001);
    }

    public function test_purchasing_can_crud_master_products(): void
    {
        $purchasing = User::query()->create([
            'name' => 'Purchasing Product Manager',
            'email' => 'purchasing.product.manager@example.com',
            'password' => 'password123',
            'role' => UserRole::PURCHASING->value,
        ]);

        $vendor = Vendor::query()->create([
            'code' => 'VN-PROD-CRUD-01',
            'name' => 'Vendor Product CRUD',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $category = ProductCategory::query()->create([
            'name' => 'Kategori Product CRUD',
        ]);

        $this->actingAs($purchasing);

        $storeResponse = $this->post(route('ui.master-data.products.store'), [
            'sku' => 'PRD-PUR-CRUD-01',
            'name' => 'Produk Purchasing CRUD',
            'product_category_id' => $category->id,
            'vendor_id' => $vendor->id,
            'unit' => 'kg',
            'government_price_cap' => 12000,
            'price_variance_percent' => 5,
            'price_variance_amount' => 1000,
            'minimum_stock_level' => 10,
            'reorder_stock_level' => 20,
            'is_active' => 1,
        ]);

        $storeResponse->assertRedirect(route('ui.master-data.products.index'));

        $product = Product::query()->where('sku', 'PRD-PUR-CRUD-01')->firstOrFail();

        $updateResponse = $this->put(route('ui.master-data.products.update', $product), [
            'sku' => 'PRD-PUR-CRUD-01',
            'name' => 'Produk Purchasing CRUD Updated',
            'product_category_id' => $category->id,
            'vendor_id' => $vendor->id,
            'unit' => 'kg',
            'government_price_cap' => 15000,
            'price_variance_percent' => 8,
            'price_variance_amount' => 1200,
            'minimum_stock_level' => 12,
            'reorder_stock_level' => 24,
            'is_active' => 1,
        ]);

        $updateResponse->assertRedirect(route('ui.master-data.products.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Produk Purchasing CRUD Updated',
            'government_price_cap' => 15000,
            'price_variance_percent' => 8,
            'price_variance_amount' => 1200,
        ]);

        $deleteResponse = $this->delete(route('ui.master-data.products.destroy', $product));

        $deleteResponse->assertRedirect(route('ui.master-data.products.index'));

        $this->assertSoftDeleted('products', [
            'id' => $product->id,
        ]);
    }

    public function test_purchasing_can_manage_product_price_histories(): void
    {
        $purchasing = User::query()->create([
            'name' => 'Purchasing Price Manager',
            'email' => 'purchasing.price.manager@example.com',
            'password' => 'password123',
            'role' => UserRole::PURCHASING->value,
        ]);

        $vendor = Vendor::query()->create([
            'code' => 'VN-PRICE-CRUD-01',
            'name' => 'Vendor Price CRUD',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $category = ProductCategory::query()->create([
            'name' => 'Kategori Price CRUD',
        ]);

        $product = Product::query()->create([
            'sku' => 'PRD-PRICE-CRUD-01',
            'name' => 'Produk Price CRUD',
            'product_category_id' => $category->id,
            'vendor_id' => $vendor->id,
            'unit' => 'pcs',
            'government_price_cap' => 10000,
            'minimum_stock_level' => 0,
            'reorder_stock_level' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($purchasing);

        $storeResponse = $this->post(route('ui.master-data.price-histories.store'), [
            'product_id' => $product->id,
            'vendor_id' => $vendor->id,
            'price' => 11500,
            'effective_at' => now()->toDateString(),
        ]);

        $storeResponse->assertRedirect(route('ui.master-data.price-histories.index'));

        $priceHistory = ProductPriceHistory::query()->where('product_id', $product->id)->firstOrFail();

        $updateResponse = $this->put(route('ui.master-data.price-histories.update', $priceHistory), [
            'product_id' => $product->id,
            'vendor_id' => $vendor->id,
            'price' => 12345,
            'effective_at' => now()->addDay()->toDateString(),
        ]);

        $updateResponse->assertRedirect(route('ui.master-data.price-histories.index'));

        $this->assertDatabaseHas('product_price_histories', [
            'id' => $priceHistory->id,
            'price' => 12345,
        ]);

        $deleteResponse = $this->delete(route('ui.master-data.price-histories.destroy', $priceHistory));

        $deleteResponse->assertRedirect(route('ui.master-data.price-histories.index'));

        $this->assertSoftDeleted('product_price_histories', [
            'id' => $priceHistory->id,
        ]);
    }

    public function test_price_history_margin_percent_uses_selling_price_as_base(): void
    {
        $purchasing = User::query()->create([
            'name' => 'Purchasing Margin Base',
            'email' => 'purchasing.margin.base@example.com',
            'password' => 'password123',
            'role' => UserRole::PURCHASING->value,
        ]);

        $vendor = Vendor::query()->create([
            'code' => 'VN-MARGIN-BASE-01',
            'name' => 'Vendor Margin Base',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $category = ProductCategory::query()->create([
            'name' => 'Kategori Margin Base',
        ]);

        $product = Product::query()->create([
            'sku' => 'PRD-MARGIN-BASE-01',
            'name' => 'Produk Margin Base',
            'product_category_id' => $category->id,
            'vendor_id' => $vendor->id,
            'unit' => 'pcs',
            'purchase_price' => 15000,
            'selling_price' => 20000,
            'government_price_cap' => 10000,
            'minimum_stock_level' => 0,
            'reorder_stock_level' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($purchasing);

        $response = $this->post(route('ui.master-data.price-histories.store'), [
            'product_id' => $product->id,
            'vendor_id' => $vendor->id,
            'margin_percent' => 10,
            'effective_at' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('ui.master-data.price-histories.index'));

        $this->assertDatabaseHas('product_price_histories', [
            'product_id' => $product->id,
            'vendor_id' => $vendor->id,
            'price' => 22000,
        ]);
    }

    public function test_sppg_user_only_sees_purchase_requests_from_own_sppg(): void
    {
        $sppgOne = Sppg::query()->create([
            'code' => 'SPPG-SCOPE-PR-01',
            'name' => 'SPPG Scope PR 01',
            'is_active' => true,
        ]);

        $sppgTwo = Sppg::query()->create([
            'code' => 'SPPG-SCOPE-PR-02',
            'name' => 'SPPG Scope PR 02',
            'is_active' => true,
        ]);

        $sppgUser = User::query()->create([
            'name' => 'SPPG Scoped User',
            'email' => 'sppg.scoped.user@example.com',
            'password' => 'password123',
            'role' => UserRole::SPPG_USER->value,
            'sppg_id' => $sppgOne->id,
        ]);

        PurchaseRequest::query()->create([
            'number' => 'PR-SCOPE-OWN',
            'sppg_id' => $sppgOne->id,
            'requested_by' => $sppgUser->id,
            'request_date' => now()->toDateString(),
            'needed_date' => now()->addDay()->toDateString(),
            'status' => DocumentStatus::SUBMITTED,
            'total_amount' => 100000,
        ]);

        PurchaseRequest::query()->create([
            'number' => 'PR-SCOPE-OTHER',
            'sppg_id' => $sppgTwo->id,
            'requested_by' => null,
            'request_date' => now()->toDateString(),
            'needed_date' => now()->addDay()->toDateString(),
            'status' => DocumentStatus::SUBMITTED,
            'total_amount' => 200000,
        ]);

        $this->actingAs($sppgUser);

        $response = $this->get(route('ui.purchase-requests.index'));

        $response->assertOk();
        $response->assertSee('PR-SCOPE-OWN');
        $response->assertDontSee('PR-SCOPE-OTHER');
    }

    public function test_sppg_user_can_download_purchase_request_pdf_for_own_sppg(): void
    {
        $this->skipIfGdIsMissing();

        $vendor = Vendor::query()->create([
            'code' => 'VN-PR-PDF-OWN',
            'name' => 'Vendor PR PDF Own',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $sppg = Sppg::query()->create([
            'code' => 'SPPG-PR-PDF-OWN',
            'name' => 'SPPG PR PDF Own',
            'default_vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $sppgUser = User::query()->create([
            'name' => 'SPPG PDF Own User',
            'email' => 'sppg.pdf.own@example.com',
            'password' => 'password123',
            'role' => UserRole::SPPG_USER->value,
            'sppg_id' => $sppg->id,
        ]);

        $category = ProductCategory::query()->create([
            'name' => 'Kategori PR PDF Own',
        ]);

        $product = Product::query()->create([
            'sku' => 'PRD-PR-PDF-OWN',
            'name' => 'Produk PR PDF Own',
            'product_category_id' => $category->id,
            'vendor_id' => $vendor->id,
            'unit' => 'pcs',
            'government_price_cap' => 10000,
            'minimum_stock_level' => 1,
            'reorder_stock_level' => 2,
            'is_active' => true,
            'is_ad_hoc' => false,
        ]);

        $purchaseRequest = PurchaseRequest::query()->create([
            'number' => 'PR-PDF-OWN-01',
            'sppg_id' => $sppg->id,
            'requested_by' => $sppgUser->id,
            'request_date' => now()->toDateString(),
            'needed_date' => now()->addDay()->toDateString(),
            'status' => DocumentStatus::SUBMITTED,
            'total_amount' => 20000,
        ]);

        $purchaseRequest->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'requested_unit_price' => 10000,
            'subtotal' => 20000,
        ]);

        $this->actingAs($sppgUser);

        $response = $this->get(route('ui.purchase-requests.download', $purchaseRequest));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('attachment; filename=PR-PDF-OWN-01.pdf', (string) $response->headers->get('content-disposition'));
    }

    public function test_sppg_user_cannot_download_purchase_request_pdf_from_other_sppg(): void
    {
        $vendor = Vendor::query()->create([
            'code' => 'VN-PR-PDF-OTHER',
            'name' => 'Vendor PR PDF Other',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $sppgOwn = Sppg::query()->create([
            'code' => 'SPPG-PR-PDF-OWN-02',
            'name' => 'SPPG PR PDF Own 02',
            'default_vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $sppgOther = Sppg::query()->create([
            'code' => 'SPPG-PR-PDF-OTHER-02',
            'name' => 'SPPG PR PDF Other 02',
            'default_vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $sppgUser = User::query()->create([
            'name' => 'SPPG PDF Other User',
            'email' => 'sppg.pdf.other@example.com',
            'password' => 'password123',
            'role' => UserRole::SPPG_USER->value,
            'sppg_id' => $sppgOwn->id,
        ]);

        $foreignPurchaseRequest = PurchaseRequest::query()->create([
            'number' => 'PR-PDF-OTHER-01',
            'sppg_id' => $sppgOther->id,
            'requested_by' => null,
            'request_date' => now()->toDateString(),
            'needed_date' => now()->addDay()->toDateString(),
            'status' => DocumentStatus::SUBMITTED,
            'total_amount' => 15000,
        ]);

        $this->actingAs($sppgUser);

        $response = $this->get(route('ui.purchase-requests.download', $foreignPurchaseRequest));

        $response->assertForbidden();
    }

    public function test_sppg_user_cannot_create_purchase_request_for_other_sppg(): void
    {
        $vendor = Vendor::query()->create([
            'code' => 'VN-SCOPE-PR-01',
            'name' => 'Vendor Scope PR',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $sppgOne = Sppg::query()->create([
            'code' => 'SPPG-CREATE-SCOPE-01',
            'name' => 'SPPG Create Scope 01',
            'default_vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $sppgTwo = Sppg::query()->create([
            'code' => 'SPPG-CREATE-SCOPE-02',
            'name' => 'SPPG Create Scope 02',
            'default_vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $sppgUser = User::query()->create([
            'name' => 'SPPG Create Scoped User',
            'email' => 'sppg.create.scoped@example.com',
            'password' => 'password123',
            'role' => UserRole::SPPG_USER->value,
            'sppg_id' => $sppgOne->id,
        ]);

        $category = ProductCategory::query()->create([
            'name' => 'Kategori SPPG Scope',
        ]);

        $product = Product::query()->create([
            'sku' => 'PRD-SCOPE-001',
            'name' => 'Produk Scope',
            'product_category_id' => $category->id,
            'vendor_id' => $vendor->id,
            'unit' => 'kg',
            'government_price_cap' => 10000,
            'minimum_stock_level' => 1,
            'reorder_stock_level' => 2,
            'is_active' => true,
            'is_ad_hoc' => false,
        ]);

        $this->actingAs($sppgUser);

        $response = $this->post(route('ui.purchase-requests.store'), [
            'sppg_id' => $sppgTwo->id,
            'vendor_id' => $vendor->id,
            'needed_date' => now()->addDay()->toDateString(),
            'is_product_review_confirmed' => '1',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'requested_unit_price' => 10000,
                ],
            ],
        ]);

        $response->assertRedirect(route('ui.purchase-requests.index'));
        $response->assertSessionHasErrors('sppg_id');

        $this->assertDatabaseMissing('purchase_requests', [
            'sppg_id' => $sppgTwo->id,
        ]);
    }

    public function test_store_purchase_request_sets_authenticated_user_as_requester(): void
    {
        $vendor = Vendor::query()->create([
            'code' => 'VN-REQ-01',
            'name' => 'Vendor Requester',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $sppg = Sppg::query()->create([
            'code' => 'SPPG-REQ-01',
            'name' => 'SPPG Requester',
            'default_vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $sppgUser = User::query()->create([
            'name' => 'SPPG Requester User',
            'email' => 'sppg.requester.user@example.com',
            'password' => 'password123',
            'role' => UserRole::SPPG_USER->value,
            'sppg_id' => $sppg->id,
        ]);

        $category = ProductCategory::query()->create([
            'name' => 'Kategori Requester',
        ]);

        $product = Product::query()->create([
            'sku' => 'PRD-REQ-01',
            'name' => 'Produk Requester',
            'product_category_id' => $category->id,
            'vendor_id' => $vendor->id,
            'unit' => 'kg',
            'government_price_cap' => 10000,
            'minimum_stock_level' => 0,
            'reorder_stock_level' => 0,
            'is_active' => true,
            'is_ad_hoc' => false,
        ]);

        $this->actingAs($sppgUser);

        $response = $this->post(route('ui.purchase-requests.store'), [
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendor->id,
            'needed_date' => now()->addDay()->toDateString(),
            'is_product_review_confirmed' => '1',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'requested_unit_price' => 10000,
                ],
            ],
        ]);

        $response->assertRedirect(route('ui.purchase-requests.index'));

        $purchaseRequest = PurchaseRequest::query()->latest('id')->firstOrFail();
        $this->assertEquals($sppgUser->id, (int) $purchaseRequest->requested_by);
    }

    public function test_purchasing_cannot_create_purchase_request(): void
    {
        $purchasing = User::query()->create([
            'name' => 'Purchasing PR Blocked',
            'email' => 'purchasing.pr.blocked@example.com',
            'password' => 'password123',
            'role' => UserRole::PURCHASING->value,
        ]);

        $vendor = Vendor::query()->create([
            'code' => 'VN-PR-BLOCK-01',
            'name' => 'Vendor PR Blocked',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $sppg = Sppg::query()->create([
            'code' => 'SPPG-PR-BLOCK-01',
            'name' => 'SPPG PR Blocked',
            'default_vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $category = ProductCategory::query()->create([
            'name' => 'Kategori PR Blocked',
        ]);

        $product = Product::query()->create([
            'sku' => 'PRD-PR-BLOCK-01',
            'name' => 'Produk PR Blocked',
            'product_category_id' => $category->id,
            'unit' => 'kg',
            'government_price_cap' => 12000,
            'minimum_stock_level' => 1,
            'reorder_stock_level' => 2,
            'is_active' => true,
        ]);

        $response = $this->actingAs($purchasing)->post(route('ui.purchase-requests.store'), [
            'sppg_id' => $sppg->id,
            'needed_date' => now()->addDay()->toDateString(),
            'notes' => 'Harus ditolak untuk purchasing',
            'is_product_review_confirmed' => '1',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'requested_unit_price' => 10000,
                ],
            ],
        ]);

        $response->assertForbidden();
    }

    public function test_purchasing_can_assign_requester_to_legacy_purchase_request(): void
    {
        $purchasing = User::query()->create([
            'name' => 'Purchasing Assign',
            'email' => 'purchasing.assign@example.com',
            'password' => 'password123',
            'role' => UserRole::PURCHASING->value,
        ]);

        $sppg = Sppg::query()->create([
            'code' => 'SPPG-ASG-01',
            'name' => 'SPPG Assign 01',
            'is_active' => true,
        ]);

        $sppgUser = User::query()->create([
            'name' => 'SPPG Assignee',
            'email' => 'sppg.assignee@example.com',
            'password' => 'password123',
            'role' => UserRole::SPPG_USER->value,
            'sppg_id' => $sppg->id,
        ]);

        $purchaseRequest = PurchaseRequest::query()->create([
            'number' => 'PR-ASSIGN-01',
            'sppg_id' => $sppg->id,
            'requested_by' => null,
            'request_date' => now()->toDateString(),
            'needed_date' => now()->addDay()->toDateString(),
            'status' => DocumentStatus::SUBMITTED,
            'total_amount' => 100000,
        ]);

        $this->actingAs($purchasing);

        $response = $this->post(route('ui.purchase-requests.assign-requester', $purchaseRequest), [
            'requested_by' => $sppgUser->id,
        ]);

        $response->assertRedirect(route('ui.purchase-requests.index'));

        $this->assertDatabaseHas('purchase_requests', [
            'id' => $purchaseRequest->id,
            'requested_by' => $sppgUser->id,
        ]);
    }

    public function test_assign_requester_rejects_user_from_different_sppg(): void
    {
        $purchasing = User::query()->create([
            'name' => 'Purchasing Assign Reject',
            'email' => 'purchasing.assign.reject@example.com',
            'password' => 'password123',
            'role' => UserRole::PURCHASING->value,
        ]);

        $sppgOne = Sppg::query()->create([
            'code' => 'SPPG-ASG-02',
            'name' => 'SPPG Assign 02',
            'is_active' => true,
        ]);

        $sppgTwo = Sppg::query()->create([
            'code' => 'SPPG-ASG-03',
            'name' => 'SPPG Assign 03',
            'is_active' => true,
        ]);

        $foreignSppgUser = User::query()->create([
            'name' => 'SPPG Foreign Assignee',
            'email' => 'sppg.foreign.assignee@example.com',
            'password' => 'password123',
            'role' => UserRole::SPPG_USER->value,
            'sppg_id' => $sppgTwo->id,
        ]);

        $purchaseRequest = PurchaseRequest::query()->create([
            'number' => 'PR-ASSIGN-02',
            'sppg_id' => $sppgOne->id,
            'requested_by' => null,
            'request_date' => now()->toDateString(),
            'needed_date' => now()->addDay()->toDateString(),
            'status' => DocumentStatus::SUBMITTED,
            'total_amount' => 150000,
        ]);

        $this->actingAs($purchasing);

        $response = $this->post(route('ui.purchase-requests.assign-requester', $purchaseRequest), [
            'requested_by' => $foreignSppgUser->id,
        ]);

        $response->assertRedirect(route('ui.purchase-requests.index'));
        $response->assertSessionHasErrors('requested_by');

        $this->assertDatabaseHas('purchase_requests', [
            'id' => $purchaseRequest->id,
            'requested_by' => null,
        ]);
    }

    public function test_vendor_performance_page_uses_delivery_data_and_filters_by_vendor(): void
    {
        $owner = User::query()->create([
            'name' => 'Owner Analytics Vendor',
            'email' => 'owner.analytics.vendor@example.com',
            'password' => 'password123',
            'role' => UserRole::OWNER->value,
        ]);

        $vendorA = Vendor::query()->create([
            'code' => 'VN-PERF-01',
            'name' => 'Vendor Performa A',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $vendorB = Vendor::query()->create([
            'code' => 'VN-PERF-02',
            'name' => 'Vendor Performa B',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $sppg = Sppg::query()->create([
            'code' => 'SPPG-PERF-01',
            'name' => 'SPPG Analytics 01',
            'default_vendor_id' => $vendorA->id,
            'is_active' => true,
        ]);

        $poA = PurchaseOrder::query()->create([
            'number' => 'PO-PERF-01',
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendorA->id,
            'ordered_by' => $owner->id,
            'order_date' => now()->subDays(10)->toDateString(),
            'expected_date' => now()->subDays(6)->toDateString(),
            'status' => DocumentStatus::PROCESSED,
            'is_direct_purchase' => false,
            'total_amount' => 100000,
        ]);

        $poB = PurchaseOrder::query()->create([
            'number' => 'PO-PERF-02',
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendorB->id,
            'ordered_by' => $owner->id,
            'order_date' => now()->subDays(10)->toDateString(),
            'expected_date' => now()->subDays(7)->toDateString(),
            'status' => DocumentStatus::PROCESSED,
            'is_direct_purchase' => false,
            'total_amount' => 90000,
        ]);

        \App\Models\Delivery::query()->create([
            'number' => 'DLV-PERF-01',
            'purchase_order_id' => $poA->id,
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendorA->id,
            'delivered_by' => $owner->id,
            'delivery_date' => now()->subDays(6)->toDateString(),
            'status' => DocumentStatus::DELIVERED,
            'total_amount' => 100000,
            'notes' => null,
        ]);

        \App\Models\Delivery::query()->create([
            'number' => 'DLV-PERF-02',
            'purchase_order_id' => $poB->id,
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendorB->id,
            'delivered_by' => $owner->id,
            'delivery_date' => now()->subDays(4)->toDateString(),
            'status' => DocumentStatus::DELIVERED,
            'total_amount' => 90000,
            'notes' => 'retur sebagian karena kemasan rusak',
        ]);

        $this->actingAs($owner);

        $response = $this->get(route('ui.vendor-performances.index', [
            'vendor_id' => $vendorA->id,
            'date_from' => now()->subDays(15)->toDateString(),
            'date_to' => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSee('Vendor Performa A</td>', false);
        $response->assertDontSee('Vendor Performa B</td>', false);
    }

    public function test_price_trend_page_uses_price_history_and_filters_by_product(): void
    {
        $owner = User::query()->create([
            'name' => 'Owner Analytics Price',
            'email' => 'owner.analytics.price@example.com',
            'password' => 'password123',
            'role' => UserRole::OWNER->value,
        ]);

        $vendor = Vendor::query()->create([
            'code' => 'VN-PT-01',
            'name' => 'Vendor Price Trend',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $category = ProductCategory::query()->create([
            'name' => 'Kategori Price Trend',
        ]);

        $productA = Product::query()->create([
            'sku' => 'PRD-PT-01',
            'name' => 'Produk Trend A',
            'product_category_id' => $category->id,
            'vendor_id' => $vendor->id,
            'unit' => 'kg',
            'government_price_cap' => 10000,
            'minimum_stock_level' => 1,
            'reorder_stock_level' => 2,
            'is_active' => true,
            'is_ad_hoc' => false,
        ]);

        $productB = Product::query()->create([
            'sku' => 'PRD-PT-02',
            'name' => 'Produk Trend B',
            'product_category_id' => $category->id,
            'vendor_id' => $vendor->id,
            'unit' => 'kg',
            'government_price_cap' => 12000,
            'minimum_stock_level' => 1,
            'reorder_stock_level' => 2,
            'is_active' => true,
            'is_ad_hoc' => false,
        ]);

        \App\Models\ProductPriceHistory::query()->create([
            'product_id' => $productA->id,
            'vendor_id' => $vendor->id,
            'price' => 10000,
            'effective_at' => now()->subMonths(2)->toDateString(),
            'created_by' => $owner->id,
        ]);

        \App\Models\ProductPriceHistory::query()->create([
            'product_id' => $productA->id,
            'vendor_id' => $vendor->id,
            'price' => 11500,
            'effective_at' => now()->subMonth()->toDateString(),
            'created_by' => $owner->id,
        ]);

        \App\Models\ProductPriceHistory::query()->create([
            'product_id' => $productB->id,
            'vendor_id' => $vendor->id,
            'price' => 9000,
            'effective_at' => now()->subMonth()->toDateString(),
            'created_by' => $owner->id,
        ]);

        $this->actingAs($owner);

        $response = $this->get(route('ui.price-trends.index', [
            'product_id' => $productA->id,
            'date_from' => now()->subMonths(3)->toDateString(),
            'date_to' => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSee('<div class="font-medium">Produk Trend A</div>', false);
        $response->assertDontSee('<div class="font-medium">Produk Trend B</div>', false);
    }

    public function test_vendor_admin_can_view_only_own_vendor_data_on_po_delivery_and_invoice_pages(): void
    {
        $vendorOwn = Vendor::query()->create([
            'code' => 'VN-VA-OWN-01',
            'name' => 'Vendor Admin Own',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $vendorOther = Vendor::query()->create([
            'code' => 'VN-VA-OTH-01',
            'name' => 'Vendor Admin Other',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $sppg = Sppg::query()->create([
            'code' => 'SPPG-VA-01',
            'name' => 'SPPG Vendor Admin',
            'default_vendor_id' => $vendorOwn->id,
            'is_active' => true,
        ]);

        $vendorAdmin = User::query()->create([
            'name' => 'Vendor Admin Scoped',
            'email' => 'vendor.admin.scoped@example.com',
            'password' => 'password123',
            'role' => UserRole::VENDOR_ADMIN->value,
            'vendor_id' => $vendorOwn->id,
        ]);

        $poOwn = PurchaseOrder::query()->create([
            'number' => 'PO-VA-OWN-01',
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendorOwn->id,
            'ordered_by' => null,
            'order_date' => now()->toDateString(),
            'status' => DocumentStatus::PROCESSED,
            'is_direct_purchase' => false,
            'total_amount' => 120000,
        ]);

        $poOther = PurchaseOrder::query()->create([
            'number' => 'PO-VA-OTH-01',
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendorOther->id,
            'ordered_by' => null,
            'order_date' => now()->toDateString(),
            'status' => DocumentStatus::PROCESSED,
            'is_direct_purchase' => false,
            'total_amount' => 130000,
        ]);

        \App\Models\Delivery::query()->create([
            'number' => 'DLV-VA-OWN-01',
            'purchase_order_id' => $poOwn->id,
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendorOwn->id,
            'delivered_by' => $vendorAdmin->id,
            'delivery_date' => now()->toDateString(),
            'status' => DocumentStatus::DELIVERED,
            'total_amount' => 120000,
            'notes' => null,
        ]);

        \App\Models\Delivery::query()->create([
            'number' => 'DLV-VA-OTH-01',
            'purchase_order_id' => $poOther->id,
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendorOther->id,
            'delivered_by' => $vendorAdmin->id,
            'delivery_date' => now()->toDateString(),
            'status' => DocumentStatus::DELIVERED,
            'total_amount' => 130000,
            'notes' => null,
        ]);

        Invoice::query()->create([
            'number' => 'INV-VA-OWN-01',
            'billing_cycle_id' => null,
            'delivery_id' => null,
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendorOwn->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => DocumentStatus::INVOICED,
            'subtotal_amount' => 120000,
            'tax_amount' => 0,
            'total_amount' => 120000,
        ]);

        Invoice::query()->create([
            'number' => 'INV-VA-OTH-01',
            'billing_cycle_id' => null,
            'delivery_id' => null,
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendorOther->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => DocumentStatus::INVOICED,
            'subtotal_amount' => 130000,
            'tax_amount' => 0,
            'total_amount' => 130000,
        ]);

        $this->actingAs($vendorAdmin);

        $poResponse = $this->get(route('ui.purchase-orders.index'));
        $poResponse->assertOk();
        $poResponse->assertSee('PO-VA-OWN-01');
        $poResponse->assertDontSee('PO-VA-OTH-01');

        $deliveryResponse = $this->get(route('ui.deliveries.index'));
        $deliveryResponse->assertOk();
        $deliveryResponse->assertSee('DLV-VA-OWN-01');
        $deliveryResponse->assertDontSee('DLV-VA-OTH-01');

        $invoiceResponse = $this->get(route('ui.invoices.index'));
        $invoiceResponse->assertOk();
        $invoiceResponse->assertSee('INV-VA-OWN-01');
        $invoiceResponse->assertDontSee('INV-VA-OTH-01');
    }

    public function test_vendor_admin_cannot_download_other_vendor_purchase_order_and_invoice(): void
    {
        $this->skipIfGdIsMissing();

        $vendorOwn = Vendor::query()->create([
            'code' => 'VN-VA-DL-OWN',
            'name' => 'Vendor Admin Download Own',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $vendorOther = Vendor::query()->create([
            'code' => 'VN-VA-DL-OTH',
            'name' => 'Vendor Admin Download Other',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $sppg = Sppg::query()->create([
            'code' => 'SPPG-VA-DL-01',
            'name' => 'SPPG Vendor Admin Download',
            'default_vendor_id' => $vendorOwn->id,
            'is_active' => true,
        ]);

        $vendorAdmin = User::query()->create([
            'name' => 'Vendor Admin Download Scoped',
            'email' => 'vendor.admin.download.scoped@example.com',
            'password' => 'password123',
            'role' => UserRole::VENDOR_ADMIN->value,
            'vendor_id' => $vendorOwn->id,
        ]);

        $poOwn = PurchaseOrder::query()->create([
            'number' => 'PO-VA-DL-OWN',
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendorOwn->id,
            'ordered_by' => null,
            'order_date' => now()->toDateString(),
            'status' => DocumentStatus::PROCESSED,
            'is_direct_purchase' => false,
            'total_amount' => 50000,
        ]);

        $poOther = PurchaseOrder::query()->create([
            'number' => 'PO-VA-DL-OTH',
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendorOther->id,
            'ordered_by' => null,
            'order_date' => now()->toDateString(),
            'status' => DocumentStatus::PROCESSED,
            'is_direct_purchase' => false,
            'total_amount' => 55000,
        ]);

        $invoiceOwn = Invoice::query()->create([
            'number' => 'INV-VA-DL-OWN',
            'billing_cycle_id' => null,
            'delivery_id' => null,
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendorOwn->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => DocumentStatus::INVOICED,
            'subtotal_amount' => 50000,
            'tax_amount' => 0,
            'total_amount' => 50000,
        ]);

        $invoiceOther = Invoice::query()->create([
            'number' => 'INV-VA-DL-OTH',
            'billing_cycle_id' => null,
            'delivery_id' => null,
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendorOther->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => DocumentStatus::INVOICED,
            'subtotal_amount' => 55000,
            'tax_amount' => 0,
            'total_amount' => 55000,
        ]);

        $this->actingAs($vendorAdmin);

        $this->get(route('ui.purchase-orders.download', $poOwn))->assertOk();
        $this->get(route('ui.invoices.download', $invoiceOwn))->assertOk();

        $this->get(route('ui.purchase-orders.download', $poOther))->assertForbidden();
        $this->get(route('ui.invoices.download', $invoiceOther))->assertForbidden();
    }

    public function test_admin_gudang_can_store_rejected_item_with_image(): void
    {
        $this->skipIfGdIsMissing();

        Storage::fake('public');

        $adminGudang = User::query()->create([
            'name' => 'Admin Gudang Reject',
            'email' => 'admin.gudang.reject@example.com',
            'password' => 'password123',
            'role' => UserRole::ADMIN_GUDANG->value,
        ]);

        $vendor = Vendor::query()->create([
            'code' => 'VN-RJ-01',
            'name' => 'Vendor Reject 01',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $sppg = Sppg::query()->create([
            'code' => 'SPPG-RJ-01',
            'name' => 'SPPG Reject 01',
            'default_vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $category = ProductCategory::query()->create([
            'name' => 'Kategori Reject',
        ]);

        $product = Product::query()->create([
            'sku' => 'PRD-RJ-01',
            'name' => 'Produk Reject 01',
            'product_category_id' => $category->id,
            'vendor_id' => $vendor->id,
            'unit' => 'kg',
            'government_price_cap' => 10000,
            'minimum_stock_level' => 1,
            'reorder_stock_level' => 2,
            'is_active' => true,
            'is_ad_hoc' => false,
        ]);

        $purchaseOrder = PurchaseOrder::query()->create([
            'number' => 'PO-RJ-01',
            'purchase_request_id' => null,
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendor->id,
            'ordered_by' => null,
            'order_date' => now()->toDateString(),
            'status' => DocumentStatus::PROCESSED,
            'is_direct_purchase' => false,
            'total_amount' => 30000,
        ]);

        $poItem = $purchaseOrder->items()->create([
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 10000,
            'subtotal' => 30000,
        ]);

        $delivery = \App\Models\Delivery::query()->create([
            'number' => 'DLV-RJ-01',
            'purchase_order_id' => $purchaseOrder->id,
            'goods_receipt_id' => null,
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendor->id,
            'delivered_by' => $adminGudang->id,
            'delivery_date' => now()->toDateString(),
            'status' => DocumentStatus::DELIVERED,
            'total_amount' => 30000,
            'notes' => null,
        ]);

        $this->actingAs($adminGudang);

        $image = UploadedFile::fake()->image('reject.jpg');

        $response = $this->post(route('ui.rejected-items.store'), [
            'delivery_id' => $delivery->id,
            'purchase_order_item_id' => $poItem->id,
            'quantity' => 1,
            'reason' => 'Kemasan rusak',
            'reported_at' => now()->toDateString(),
            'evidence_image' => $image,
        ]);

        $response->assertRedirect(route('ui.rejected-items.index', ['delivery_id' => $delivery->id]));

        $this->assertDatabaseHas('rejected_items', [
            'delivery_id' => $delivery->id,
            'purchase_order_item_id' => $poItem->id,
            'product_id' => $product->id,
            'reported_by' => $adminGudang->id,
            'quantity' => '1.00',
            'reason' => 'Kemasan rusak',
        ]);

        $saved = \App\Models\RejectedItem::query()->latest('id')->firstOrFail();
        $this->assertNotNull($saved->evidence_image_path);
        $this->assertTrue(Storage::disk('public')->exists((string) $saved->evidence_image_path));
    }

    public function test_rejected_item_must_match_selected_delivery_po_item(): void
    {
        $adminGudang = User::query()->create([
            'name' => 'Admin Gudang Reject Guard',
            'email' => 'admin.gudang.reject.guard@example.com',
            'password' => 'password123',
            'role' => UserRole::ADMIN_GUDANG->value,
        ]);

        $vendor = Vendor::query()->create([
            'code' => 'VN-RJ-02',
            'name' => 'Vendor Reject 02',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $sppg = Sppg::query()->create([
            'code' => 'SPPG-RJ-02',
            'name' => 'SPPG Reject 02',
            'default_vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $category = ProductCategory::query()->create([
            'name' => 'Kategori Reject Guard',
        ]);

        $productA = Product::query()->create([
            'sku' => 'PRD-RJ-A',
            'name' => 'Produk Reject A',
            'product_category_id' => $category->id,
            'vendor_id' => $vendor->id,
            'unit' => 'kg',
            'government_price_cap' => 10000,
            'minimum_stock_level' => 1,
            'reorder_stock_level' => 2,
            'is_active' => true,
            'is_ad_hoc' => false,
        ]);

        $productB = Product::query()->create([
            'sku' => 'PRD-RJ-B',
            'name' => 'Produk Reject B',
            'product_category_id' => $category->id,
            'vendor_id' => $vendor->id,
            'unit' => 'kg',
            'government_price_cap' => 12000,
            'minimum_stock_level' => 1,
            'reorder_stock_level' => 2,
            'is_active' => true,
            'is_ad_hoc' => false,
        ]);

        $poA = PurchaseOrder::query()->create([
            'number' => 'PO-RJ-A',
            'purchase_request_id' => null,
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendor->id,
            'ordered_by' => null,
            'order_date' => now()->toDateString(),
            'status' => DocumentStatus::PROCESSED,
            'is_direct_purchase' => false,
            'total_amount' => 10000,
        ]);

        $poB = PurchaseOrder::query()->create([
            'number' => 'PO-RJ-B',
            'purchase_request_id' => null,
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendor->id,
            'ordered_by' => null,
            'order_date' => now()->toDateString(),
            'status' => DocumentStatus::PROCESSED,
            'is_direct_purchase' => false,
            'total_amount' => 12000,
        ]);

        $poA->items()->create([
            'product_id' => $productA->id,
            'quantity' => 1,
            'unit_price' => 10000,
            'subtotal' => 10000,
        ]);

        $foreignItem = $poB->items()->create([
            'product_id' => $productB->id,
            'quantity' => 1,
            'unit_price' => 12000,
            'subtotal' => 12000,
        ]);

        $deliveryA = \App\Models\Delivery::query()->create([
            'number' => 'DLV-RJ-A',
            'purchase_order_id' => $poA->id,
            'goods_receipt_id' => null,
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendor->id,
            'delivered_by' => $adminGudang->id,
            'delivery_date' => now()->toDateString(),
            'status' => DocumentStatus::DELIVERED,
            'total_amount' => 10000,
            'notes' => null,
        ]);

        $this->actingAs($adminGudang);

        $response = $this->post(route('ui.rejected-items.store'), [
            'delivery_id' => $deliveryA->id,
            'purchase_order_item_id' => $foreignItem->id,
            'quantity' => 1,
            'reason' => 'Item salah PO',
            'reported_at' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors('purchase_order_item_id');
        $this->assertDatabaseCount('rejected_items', 0);
    }

    public function test_rejected_items_page_renders_dynamic_hooks_and_preview_elements(): void
    {
        $adminGudang = User::query()->create([
            'name' => 'Admin Gudang Reject Page',
            'email' => 'admin.gudang.reject.page@example.com',
            'password' => 'password123',
            'role' => UserRole::ADMIN_GUDANG->value,
        ]);

        $vendor = Vendor::query()->create([
            'code' => 'VN-RJ-03',
            'name' => 'Vendor Reject 03',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $sppg = Sppg::query()->create([
            'code' => 'SPPG-RJ-03',
            'name' => 'SPPG Reject 03',
            'default_vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $category = ProductCategory::query()->create([
            'name' => 'Kategori Reject Page',
        ]);

        $product = Product::query()->create([
            'sku' => 'PRD-RJ-03',
            'name' => 'Produk Reject 03',
            'product_category_id' => $category->id,
            'vendor_id' => $vendor->id,
            'unit' => 'kg',
            'government_price_cap' => 10000,
            'minimum_stock_level' => 1,
            'reorder_stock_level' => 2,
            'is_active' => true,
            'is_ad_hoc' => false,
        ]);

        $purchaseOrder = PurchaseOrder::query()->create([
            'number' => 'PO-RJ-03',
            'purchase_request_id' => null,
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendor->id,
            'ordered_by' => null,
            'order_date' => now()->toDateString(),
            'status' => DocumentStatus::PROCESSED,
            'is_direct_purchase' => false,
            'total_amount' => 20000,
        ]);

        $purchaseOrder->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 10000,
            'subtotal' => 20000,
        ]);

        \App\Models\Delivery::query()->create([
            'number' => 'DLV-RJ-03',
            'purchase_order_id' => $purchaseOrder->id,
            'goods_receipt_id' => null,
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendor->id,
            'delivered_by' => $adminGudang->id,
            'delivery_date' => now()->toDateString(),
            'status' => DocumentStatus::DELIVERED,
            'total_amount' => 20000,
            'notes' => null,
        ]);

        $this->actingAs($adminGudang);

        $response = $this->get(route('ui.rejected-items.index'));

        $response->assertOk();
        $response->assertSee('id="delivery_id"', false);
        $response->assertSee('id="purchase_order_item_id"', false);
        $response->assertSee('id="evidence_image"', false);
        $response->assertSee('id="image_preview"', false);
        $response->assertSee('const deliveryItemsMap =', false);
    }

    public function test_rejected_item_quantity_cannot_exceed_po_item_quantity(): void
    {
        $adminGudang = User::query()->create([
            'name' => 'Admin Gudang Reject Qty',
            'email' => 'admin.gudang.reject.qty@example.com',
            'password' => 'password123',
            'role' => UserRole::ADMIN_GUDANG->value,
        ]);

        $vendor = Vendor::query()->create([
            'code' => 'VN-RJ-04',
            'name' => 'Vendor Reject 04',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $sppg = Sppg::query()->create([
            'code' => 'SPPG-RJ-04',
            'name' => 'SPPG Reject 04',
            'default_vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $category = ProductCategory::query()->create([
            'name' => 'Kategori Reject Qty',
        ]);

        $product = Product::query()->create([
            'sku' => 'PRD-RJ-04',
            'name' => 'Produk Reject 04',
            'product_category_id' => $category->id,
            'vendor_id' => $vendor->id,
            'unit' => 'kg',
            'government_price_cap' => 10000,
            'minimum_stock_level' => 1,
            'reorder_stock_level' => 2,
            'is_active' => true,
            'is_ad_hoc' => false,
        ]);

        $purchaseOrder = PurchaseOrder::query()->create([
            'number' => 'PO-RJ-04',
            'purchase_request_id' => null,
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendor->id,
            'ordered_by' => null,
            'order_date' => now()->toDateString(),
            'status' => DocumentStatus::PROCESSED,
            'is_direct_purchase' => false,
            'total_amount' => 20000,
        ]);

        $poItem = $purchaseOrder->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 10000,
            'subtotal' => 20000,
        ]);

        $delivery = \App\Models\Delivery::query()->create([
            'number' => 'DLV-RJ-04',
            'purchase_order_id' => $purchaseOrder->id,
            'goods_receipt_id' => null,
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendor->id,
            'delivered_by' => $adminGudang->id,
            'delivery_date' => now()->toDateString(),
            'status' => DocumentStatus::DELIVERED,
            'total_amount' => 20000,
            'notes' => null,
        ]);

        $this->actingAs($adminGudang);

        $response = $this->post(route('ui.rejected-items.store'), [
            'delivery_id' => $delivery->id,
            'purchase_order_item_id' => $poItem->id,
            'quantity' => 3,
            'reason' => 'Qty melebihi pesanan',
            'reported_at' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors('quantity');
        $this->assertDatabaseCount('rejected_items', 0);
    }

    public function test_sppg_user_rejected_items_index_is_scoped_to_own_sppg_deliveries(): void
    {
        $vendor = Vendor::query()->create([
            'code' => 'VN-RJ-05',
            'name' => 'Vendor Reject 05',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $sppgOwn = Sppg::query()->create([
            'code' => 'SPPG-RJ-OWN',
            'name' => 'SPPG Reject Own',
            'default_vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $sppgOther = Sppg::query()->create([
            'code' => 'SPPG-RJ-OTH',
            'name' => 'SPPG Reject Other',
            'default_vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $sppgUser = User::query()->create([
            'name' => 'SPPG User Reject Scope',
            'email' => 'sppg.user.reject.scope@example.com',
            'password' => 'password123',
            'role' => UserRole::SPPG_USER->value,
            'sppg_id' => $sppgOwn->id,
        ]);

        $category = ProductCategory::query()->create([
            'name' => 'Kategori Reject Scope',
        ]);

        $product = Product::query()->create([
            'sku' => 'PRD-RJ-05',
            'name' => 'Produk Reject 05',
            'product_category_id' => $category->id,
            'vendor_id' => $vendor->id,
            'unit' => 'kg',
            'government_price_cap' => 10000,
            'minimum_stock_level' => 1,
            'reorder_stock_level' => 2,
            'is_active' => true,
            'is_ad_hoc' => false,
        ]);

        $poOwn = PurchaseOrder::query()->create([
            'number' => 'PO-RJ-OWN',
            'purchase_request_id' => null,
            'sppg_id' => $sppgOwn->id,
            'vendor_id' => $vendor->id,
            'ordered_by' => null,
            'order_date' => now()->toDateString(),
            'status' => DocumentStatus::PROCESSED,
            'is_direct_purchase' => false,
            'total_amount' => 10000,
        ]);

        $poOther = PurchaseOrder::query()->create([
            'number' => 'PO-RJ-OTH',
            'purchase_request_id' => null,
            'sppg_id' => $sppgOther->id,
            'vendor_id' => $vendor->id,
            'ordered_by' => null,
            'order_date' => now()->toDateString(),
            'status' => DocumentStatus::PROCESSED,
            'is_direct_purchase' => false,
            'total_amount' => 10000,
        ]);

        $poOwn->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 10000,
            'subtotal' => 10000,
        ]);

        $poOther->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 10000,
            'subtotal' => 10000,
        ]);

        \App\Models\Delivery::query()->create([
            'number' => 'DLV-RJ-OWN',
            'purchase_order_id' => $poOwn->id,
            'goods_receipt_id' => null,
            'sppg_id' => $sppgOwn->id,
            'vendor_id' => $vendor->id,
            'delivered_by' => $sppgUser->id,
            'delivery_date' => now()->toDateString(),
            'status' => DocumentStatus::DELIVERED,
            'total_amount' => 10000,
            'notes' => null,
        ]);

        \App\Models\Delivery::query()->create([
            'number' => 'DLV-RJ-OTH',
            'purchase_order_id' => $poOther->id,
            'goods_receipt_id' => null,
            'sppg_id' => $sppgOther->id,
            'vendor_id' => $vendor->id,
            'delivered_by' => $sppgUser->id,
            'delivery_date' => now()->toDateString(),
            'status' => DocumentStatus::DELIVERED,
            'total_amount' => 10000,
            'notes' => null,
        ]);

        $this->actingAs($sppgUser);

        $response = $this->get(route('ui.rejected-items.index'));

        $response->assertOk();
        $response->assertSee('DLV-RJ-OWN');
        $response->assertDontSee('DLV-RJ-OTH');
    }

    public function test_purchasing_can_view_rejected_items_report_but_cannot_store(): void
    {
        $purchasing = User::query()->create([
            'name' => 'Purchasing Reject View',
            'email' => 'purchasing.reject.view@example.com',
            'password' => 'password123',
            'role' => UserRole::PURCHASING->value,
        ]);

        $vendor = Vendor::query()->create([
            'code' => 'VN-RJ-PRC-01',
            'name' => 'Vendor Reject Purchasing',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $sppg = Sppg::query()->create([
            'code' => 'SPPG-RJ-PRC-01',
            'name' => 'SPPG Reject Purchasing',
            'default_vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $productCategory = ProductCategory::query()->create([
            'name' => 'Kategori Reject Purchasing',
        ]);

        $product = Product::query()->create([
            'sku' => 'PRD-RJ-PRC-01',
            'name' => 'Produk Reject Purchasing',
            'product_category_id' => $productCategory->id,
            'unit' => 'kg',
            'government_price_cap' => 15000,
            'minimum_stock_level' => 1,
            'reorder_stock_level' => 2,
            'is_active' => true,
        ]);

        $purchaseOrder = PurchaseOrder::query()->create([
            'number' => 'PO-RJ-PRC-01',
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendor->id,
            'order_date' => now()->toDateString(),
            'status' => DocumentStatus::PROCESSED,
            'is_direct_purchase' => false,
            'total_amount' => 30000,
        ]);

        $purchaseOrderItem = $purchaseOrder->items()->create([
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 10000,
            'subtotal' => 30000,
        ]);

        $delivery = \App\Models\Delivery::query()->create([
            'number' => 'DLV-RJ-PRC-01',
            'purchase_order_id' => $purchaseOrder->id,
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendor->id,
            'delivery_date' => now()->toDateString(),
            'status' => DocumentStatus::DELIVERED,
            'total_amount' => 30000,
        ]);

        \App\Models\RejectedItem::query()->create([
            'delivery_id' => $delivery->id,
            'purchase_order_item_id' => $purchaseOrderItem->id,
            'product_id' => $product->id,
            'reported_by' => null,
            'quantity' => 1,
            'reason' => 'Kemasan sobek',
            'evidence_image_path' => 'rejected-items/evidence/sample.jpg',
            'reported_at' => now()->toDateString(),
        ]);

        $indexResponse = $this->actingAs($purchasing)
            ->get(route('ui.rejected-items.index', ['delivery_id' => $delivery->id]));

        $indexResponse->assertOk();
        $indexResponse->assertSee('DLV-RJ-PRC-01');
        $indexResponse->assertSee('Lihat');
        $indexResponse->assertDontSee('Input Barang Reject');

        $storeResponse = $this->actingAs($purchasing)
            ->post(route('ui.rejected-items.store'), [
                'delivery_id' => $delivery->id,
                'purchase_order_item_id' => $purchaseOrderItem->id,
                'quantity' => 1,
                'reason' => 'Harus ditolak',
                'reported_at' => now()->toDateString(),
            ]);

        $storeResponse->assertForbidden();
    }

    public function test_sppg_user_can_upload_payment_proof_for_own_sppg_payment(): void
    {
        $this->skipIfGdIsMissing();

        Storage::fake('public');

        $vendor = Vendor::query()->create([
            'code' => 'VN-PROOF-01',
            'name' => 'Vendor Proof 01',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $sppg = Sppg::query()->create([
            'code' => 'SPPG-PROOF-01',
            'name' => 'SPPG Proof 01',
            'default_vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $sppgUser = User::query()->create([
            'name' => 'SPPG Payment Uploader',
            'email' => 'sppg.payment.uploader@example.com',
            'password' => 'password123',
            'role' => UserRole::SPPG_USER->value,
            'sppg_id' => $sppg->id,
        ]);

        $invoice = Invoice::query()->create([
            'number' => 'INV-PROOF-01',
            'billing_cycle_id' => null,
            'delivery_id' => null,
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendor->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => DocumentStatus::INVOICED,
            'subtotal_amount' => 100000,
            'tax_amount' => 0,
            'total_amount' => 100000,
        ]);

        $payment = Payment::query()->create([
            'number' => 'PAY-PROOF-01',
            'invoice_id' => $invoice->id,
            'payment_date' => null,
            'amount' => 100000,
            'status' => \App\Enums\PaymentStatus::DRAFT,
            'payment_method' => null,
            'reference_no' => null,
            'paid_by' => null,
            'notes' => null,
        ]);

        $this->actingAs($sppgUser);

        $response = $this->post(route('ui.payments.upload-proof', $payment), [
            'payment_date' => now()->toDateString(),
            'payment_method' => 'Transfer',
            'reference_no' => 'REF-PROOF-01',
            'proof_image' => UploadedFile::fake()->image('proof.jpg'),
            'notes' => 'Bukti transfer dari SPPG.',
        ]);

        $response->assertRedirect(route('ui.payments.index'));

        $payment->refresh();
        $this->assertEquals('submitted', $payment->status?->value);
        $this->assertNotNull($payment->proof_image_path);
        $this->assertEquals($sppgUser->id, (int) $payment->proof_uploaded_by);
        $this->assertTrue(Storage::disk('public')->exists((string) $payment->proof_image_path));
    }

    public function test_finance_can_approve_submitted_payment_and_mark_invoice_paid(): void
    {
        $finance = User::query()->create([
            'name' => 'Finance Approver Payment',
            'email' => 'finance.approver.payment@example.com',
            'password' => 'password123',
            'role' => UserRole::FINANCE->value,
        ]);

        $vendor = Vendor::query()->create([
            'code' => 'VN-PROOF-02',
            'name' => 'Vendor Proof 02',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $sppg = Sppg::query()->create([
            'code' => 'SPPG-PROOF-02',
            'name' => 'SPPG Proof 02',
            'default_vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $invoice = Invoice::query()->create([
            'number' => 'INV-PROOF-02',
            'billing_cycle_id' => null,
            'delivery_id' => null,
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendor->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => DocumentStatus::INVOICED,
            'subtotal_amount' => 150000,
            'tax_amount' => 0,
            'total_amount' => 150000,
        ]);

        $payment = Payment::query()->create([
            'number' => 'PAY-PROOF-02',
            'invoice_id' => $invoice->id,
            'payment_date' => now()->toDateString(),
            'amount' => 150000,
            'status' => \App\Enums\PaymentStatus::SUBMITTED,
            'payment_method' => 'Transfer',
            'reference_no' => 'REF-PROOF-02',
            'proof_image_path' => 'payment-proofs/proof-02.jpg',
            'proof_uploaded_by' => null,
            'proof_uploaded_at' => now(),
            'approved_by' => null,
            'approved_at' => null,
            'paid_by' => null,
            'notes' => null,
        ]);

        $this->actingAs($finance);

        $response = $this->post(route('ui.payments.approve', $payment));

        $response->assertRedirect(route('ui.payments.index'));

        $payment->refresh();
        $invoice->refresh();

        $this->assertEquals('paid', $payment->status?->value);
        $this->assertNotNull($payment->approved_at);
        $this->assertEquals($finance->id, (int) $payment->approved_by);
        $this->assertEquals($finance->id, (int) $payment->paid_by);
        $this->assertEquals(DocumentStatus::PAID, $invoice->status);
    }

    public function test_sppg_user_payments_page_is_scoped_to_own_sppg_only(): void
    {
        $vendor = Vendor::query()->create([
            'code' => 'VN-PROOF-03',
            'name' => 'Vendor Proof 03',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $sppgOwn = Sppg::query()->create([
            'code' => 'SPPG-PROOF-OWN',
            'name' => 'SPPG Proof Own',
            'default_vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $sppgOther = Sppg::query()->create([
            'code' => 'SPPG-PROOF-OTH',
            'name' => 'SPPG Proof Other',
            'default_vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $sppgUser = User::query()->create([
            'name' => 'SPPG Payment Scoped User',
            'email' => 'sppg.payment.scoped@example.com',
            'password' => 'password123',
            'role' => UserRole::SPPG_USER->value,
            'sppg_id' => $sppgOwn->id,
        ]);

        $invoiceOwn = Invoice::query()->create([
            'number' => 'INV-PROOF-OWN',
            'billing_cycle_id' => null,
            'delivery_id' => null,
            'sppg_id' => $sppgOwn->id,
            'vendor_id' => $vendor->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => DocumentStatus::INVOICED,
            'subtotal_amount' => 70000,
            'tax_amount' => 0,
            'total_amount' => 70000,
        ]);

        $invoiceOther = Invoice::query()->create([
            'number' => 'INV-PROOF-OTH',
            'billing_cycle_id' => null,
            'delivery_id' => null,
            'sppg_id' => $sppgOther->id,
            'vendor_id' => $vendor->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => DocumentStatus::INVOICED,
            'subtotal_amount' => 80000,
            'tax_amount' => 0,
            'total_amount' => 80000,
        ]);

        Payment::query()->create([
            'number' => 'PAY-PROOF-OWN',
            'invoice_id' => $invoiceOwn->id,
            'payment_date' => null,
            'amount' => 70000,
            'status' => \App\Enums\PaymentStatus::DRAFT,
            'payment_method' => null,
            'reference_no' => null,
            'paid_by' => null,
            'notes' => null,
        ]);

        Payment::query()->create([
            'number' => 'PAY-PROOF-OTH',
            'invoice_id' => $invoiceOther->id,
            'payment_date' => null,
            'amount' => 80000,
            'status' => \App\Enums\PaymentStatus::DRAFT,
            'payment_method' => null,
            'reference_no' => null,
            'paid_by' => null,
            'notes' => null,
        ]);

        $this->actingAs($sppgUser);

        $response = $this->get(route('ui.payments.index'));

        $response->assertOk();
        $response->assertSee('PAY-PROOF-OWN');
        $response->assertDontSee('PAY-PROOF-OTH');
    }

    public function test_owner_can_access_payments_page(): void
    {
        $owner = User::query()->create([
            'name' => 'Owner Payments Access',
            'email' => 'owner.payments.access@example.com',
            'password' => 'password123',
            'role' => UserRole::OWNER->value,
        ]);

        $this->actingAs($owner);

        $response = $this->get(route('ui.payments.index'));

        $response->assertOk();
    }

    public function test_non_allowed_roles_cannot_access_payments_page(): void
    {
        $purchasing = User::query()->create([
            'name' => 'Purchasing Payments Blocked',
            'email' => 'purchasing.payments.blocked@example.com',
            'password' => 'password123',
            'role' => UserRole::PURCHASING->value,
        ]);

        $vendor = Vendor::query()->create([
            'code' => 'VN-PAY-BLOCK-01',
            'name' => 'Vendor Pay Block 01',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $vendorAdmin = User::query()->create([
            'name' => 'Vendor Admin Payments Blocked',
            'email' => 'vendoradmin.payments.blocked@example.com',
            'password' => 'password123',
            'role' => UserRole::VENDOR_ADMIN->value,
            'vendor_id' => $vendor->id,
        ]);

        $adminGudang = User::query()->create([
            'name' => 'Gudang Payments Blocked',
            'email' => 'gudang.payments.blocked@example.com',
            'password' => 'password123',
            'role' => UserRole::ADMIN_GUDANG->value,
        ]);

        $this->actingAs($purchasing);
        $this->get(route('ui.payments.index'))->assertForbidden();

        $this->actingAs($vendorAdmin);
        $this->get(route('ui.payments.index'))->assertForbidden();

        $this->actingAs($adminGudang);
        $this->get(route('ui.payments.index'))->assertForbidden();
    }

    public function test_finance_owner_and_super_admin_can_access_purchase_funding_requests_page(): void
    {
        $roles = [
            UserRole::SUPER_ADMIN->value,
            UserRole::FINANCE->value,
            UserRole::OWNER->value,
        ];

        foreach ($roles as $index => $role) {
            $user = User::query()->create([
                'name' => 'Funding Access ' . $role,
                'email' => 'funding.access.' . $role . '.' . $index . '@example.com',
                'password' => 'password123',
                'role' => $role,
            ]);

            $response = $this->actingAs($user)->get(route('ui.purchase-funding-requests.index'));
            $response->assertOk();
            $response->assertSee('Pengajuan Dana Pembelian');
        }
    }

    public function test_non_allowed_roles_cannot_access_purchase_funding_requests_page(): void
    {
        $purchasing = User::query()->create([
            'name' => 'Purchasing Funding Blocked',
            'email' => 'purchasing.funding.blocked@example.com',
            'password' => 'password123',
            'role' => UserRole::PURCHASING->value,
        ]);

        $sppgUser = User::query()->create([
            'name' => 'SPPG Funding Blocked',
            'email' => 'sppg.funding.blocked@example.com',
            'password' => 'password123',
            'role' => UserRole::SPPG_USER->value,
        ]);

        $this->actingAs($purchasing)
            ->get(route('ui.purchase-funding-requests.index'))
            ->assertForbidden();

        $this->actingAs($sppgUser)
            ->get(route('ui.purchase-funding-requests.index'))
            ->assertForbidden();
    }

    public function test_finance_to_owner_funding_workflow_can_be_completed_until_settlement(): void
    {
        $this->skipIfGdIsMissing();

        AppSetting::query()->updateOrCreate(
            ['key' => 'purchase_funding_owner_approval_threshold'],
            ['value' => '100000']
        );

        Storage::fake('public');

        $vendor = Vendor::query()->create([
            'code' => 'VN-FUND-WF-01',
            'name' => 'Vendor Funding Workflow',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $sppg = Sppg::query()->create([
            'code' => 'SPPG-FUND-WF',
            'name' => 'SPPG Funding Workflow',
            'default_vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $finance = User::query()->create([
            'name' => 'Finance Funding Workflow',
            'email' => 'finance.funding.workflow@example.com',
            'password' => 'password123',
            'role' => UserRole::FINANCE->value,
        ]);

        $owner = User::query()->create([
            'name' => 'Owner Funding Workflow',
            'email' => 'owner.funding.workflow@example.com',
            'password' => 'password123',
            'role' => UserRole::OWNER->value,
        ]);

        $purchaseOrder = PurchaseOrder::query()->create([
            'number' => 'PO-FUND-WF-01',
            'purchase_request_id' => null,
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendor->id,
            'ordered_by' => $finance->id,
            'order_date' => now()->toDateString(),
            'status' => DocumentStatus::APPROVED,
            'is_direct_purchase' => false,
            'total_amount' => 150000,
        ]);

        $createResponse = $this
            ->actingAs($finance)
            ->post(route('ui.purchase-funding-requests.store'), [
                'purchase_order_id' => $purchaseOrder->id,
                'fund_source' => 'petty_cash',
                'requested_amount' => 120000,
                'title' => 'Dana pembelian mingguan',
                'notes' => 'Rincian pembelian kebutuhan dapur.',
            ]);

        $createResponse->assertRedirect(route('ui.purchase-funding-requests.index', ['fund_source' => 'petty_cash']));

        $fundingRequest = PurchaseFundingRequest::query()->first();
        $this->assertNotNull($fundingRequest);
        $this->assertSame(FundingRequestStatus::SUBMITTED, $fundingRequest->status);

        $this
            ->actingAs($finance)
            ->post(route('ui.purchase-funding-requests.review', $fundingRequest), [
                'reviewed_amount' => 118000,
                'finance_notes' => 'Nominal disesuaikan harga pasar.',
            ])
            ->assertRedirect(route('ui.purchase-funding-requests.index'));

        $fundingRequest->refresh();
        $this->assertSame(FundingRequestStatus::REVIEWED, $fundingRequest->status);
        $this->assertSame('118000.00', (string) $fundingRequest->reviewed_amount);

        $this
            ->actingAs($owner)
            ->post(route('ui.purchase-funding-requests.approve', $fundingRequest), [
                'approved_amount' => 118000,
                'owner_notes' => 'Approved untuk kebutuhan minggu ini.',
            ])
            ->assertRedirect(route('ui.purchase-funding-requests.index'));

        $fundingRequest->refresh();
        $this->assertSame(FundingRequestStatus::APPROVED, $fundingRequest->status);
        $this->assertSame('118000.00', (string) $fundingRequest->approved_amount);

        $this
            ->actingAs($finance)
            ->post(route('ui.purchase-funding-requests.disburse', $fundingRequest), [
                'disbursed_amount' => 118000,
                'finance_notes' => 'Dana sudah ditransfer ke PIC.',
            ])
            ->assertRedirect(route('ui.purchase-funding-requests.index'));

        $fundingRequest->refresh();
        $this->assertSame(FundingRequestStatus::DISBURSED, $fundingRequest->status);
        $this->assertSame('118000.00', (string) $fundingRequest->disbursed_amount);

        $this
            ->actingAs($finance)
            ->post(route('ui.purchase-funding-requests.settle', $fundingRequest), [
                'spent_amount' => 118000,
                'finance_notes' => 'Settlement lengkap dengan bukti belanja.',
                'settlement_proof' => UploadedFile::fake()->image('nota-settlement.jpg'),
            ])
            ->assertRedirect(route('ui.purchase-funding-requests.index'));

        $fundingRequest->refresh();
        $this->assertSame(FundingRequestStatus::SETTLED, $fundingRequest->status);
        $this->assertSame('118000.00', (string) $fundingRequest->spent_amount);
        $this->assertNotNull($fundingRequest->settled_at);
        $this->assertNotNull($fundingRequest->settlement_proof_path);
        Storage::disk('public')->assertExists($fundingRequest->settlement_proof_path);
    }

    public function test_finance_review_auto_approves_funding_request_below_owner_threshold(): void
    {
        AppSetting::query()->updateOrCreate(
            ['key' => 'purchase_funding_owner_approval_threshold'],
            ['value' => '500000']
        );

        $vendor = Vendor::query()->create([
            'code' => 'VN-FUND-AUTO-01',
            'name' => 'Vendor Funding Auto',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $sppg = Sppg::query()->create([
            'code' => 'SPPG-FUND-AUTO',
            'name' => 'SPPG Funding Auto',
            'default_vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $finance = User::query()->create([
            'name' => 'Finance Funding Auto',
            'email' => 'finance.funding.auto@example.com',
            'password' => 'password123',
            'role' => UserRole::FINANCE->value,
        ]);

        $purchaseOrder = PurchaseOrder::query()->create([
            'number' => 'PO-FUND-AUTO-01',
            'purchase_request_id' => null,
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendor->id,
            'ordered_by' => $finance->id,
            'order_date' => now()->toDateString(),
            'status' => DocumentStatus::APPROVED,
            'is_direct_purchase' => false,
            'total_amount' => 200000,
        ]);

        $this
            ->actingAs($finance)
            ->post(route('ui.purchase-funding-requests.store'), [
                'purchase_order_id' => $purchaseOrder->id,
                'fund_source' => 'petty_cash',
                'requested_amount' => 180000,
                'title' => 'Dana petty cash harian',
            ])
            ->assertRedirect(route('ui.purchase-funding-requests.index', ['fund_source' => 'petty_cash']));

        $fundingRequest = PurchaseFundingRequest::query()->first();
        $this->assertNotNull($fundingRequest);

        $this
            ->actingAs($finance)
            ->post(route('ui.purchase-funding-requests.review', $fundingRequest), [
                'reviewed_amount' => 180000,
                'finance_notes' => 'Nominal di bawah threshold owner.',
            ])
            ->assertRedirect(route('ui.purchase-funding-requests.index'));

        $fundingRequest->refresh();
        $this->assertSame(FundingRequestStatus::APPROVED, $fundingRequest->status);
        $this->assertSame('180000.00', (string) $fundingRequest->approved_amount);
        $this->assertSame($finance->id, $fundingRequest->approved_by);
        $this->assertNotNull($fundingRequest->approved_at);
    }

    public function test_finance_review_above_threshold_creates_owner_notification(): void
    {
        AppSetting::query()->updateOrCreate(
            ['key' => 'purchase_funding_owner_approval_threshold'],
            ['value' => '100000']
        );

        $vendor = Vendor::query()->create([
            'code' => 'VN-FUND-NTF-01',
            'name' => 'Vendor Funding Notification',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $sppg = Sppg::query()->create([
            'code' => 'SPPG-FUND-NTF',
            'name' => 'SPPG Funding Notification',
            'default_vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $finance = User::query()->create([
            'name' => 'Finance Funding Notification',
            'email' => 'finance.funding.notification@example.com',
            'password' => 'password123',
            'role' => UserRole::FINANCE->value,
        ]);

        $owner = User::query()->create([
            'name' => 'Owner Funding Notification',
            'email' => 'owner.funding.notification@example.com',
            'password' => 'password123',
            'role' => UserRole::OWNER->value,
        ]);

        $purchaseOrder = PurchaseOrder::query()->create([
            'number' => 'PO-FUND-NTF-01',
            'purchase_request_id' => null,
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendor->id,
            'ordered_by' => $finance->id,
            'order_date' => now()->toDateString(),
            'status' => DocumentStatus::APPROVED,
            'is_direct_purchase' => false,
            'total_amount' => 150000,
        ]);

        $this
            ->actingAs($finance)
            ->post(route('ui.purchase-funding-requests.store'), [
                'purchase_order_id' => $purchaseOrder->id,
                'fund_source' => 'petty_cash',
                'requested_amount' => 120000,
                'title' => 'Dana pembelian notifikasi owner',
            ])
            ->assertRedirect(route('ui.purchase-funding-requests.index', ['fund_source' => 'petty_cash']));

        $fundingRequest = PurchaseFundingRequest::query()->firstOrFail();

        $this
            ->actingAs($finance)
            ->post(route('ui.purchase-funding-requests.review', $fundingRequest), [
                'reviewed_amount' => 120000,
                'finance_notes' => 'Di atas threshold.',
            ])
            ->assertRedirect(route('ui.purchase-funding-requests.index'));

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $owner->id,
            'type' => \App\Notifications\PurchaseFundingNeedsOwnerApproval::class,
        ]);
    }

    public function test_open_notification_marks_it_as_read_and_redirects(): void
    {
        AppSetting::query()->updateOrCreate(
            ['key' => 'purchase_funding_owner_approval_threshold'],
            ['value' => '100000']
        );

        $vendor = Vendor::query()->create([
            'code' => 'VN-FUND-NTF-OPEN',
            'name' => 'Vendor Funding Notification Open',
            'is_affiliate' => false,
            'is_active' => true,
        ]);

        $sppg = Sppg::query()->create([
            'code' => 'SPPG-FUND-NTF-OPEN',
            'name' => 'SPPG Funding Notification Open',
            'default_vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $finance = User::query()->create([
            'name' => 'Finance Funding Notification Open',
            'email' => 'finance.funding.notification.open@example.com',
            'password' => 'password123',
            'role' => UserRole::FINANCE->value,
        ]);

        $owner = User::query()->create([
            'name' => 'Owner Funding Notification Open',
            'email' => 'owner.funding.notification.open@example.com',
            'password' => 'password123',
            'role' => UserRole::OWNER->value,
        ]);

        $purchaseOrder = PurchaseOrder::query()->create([
            'number' => 'PO-FUND-NTF-OPEN',
            'purchase_request_id' => null,
            'sppg_id' => $sppg->id,
            'vendor_id' => $vendor->id,
            'ordered_by' => $finance->id,
            'order_date' => now()->toDateString(),
            'status' => DocumentStatus::APPROVED,
            'is_direct_purchase' => false,
            'total_amount' => 150000,
        ]);

        $this
            ->actingAs($finance)
            ->post(route('ui.purchase-funding-requests.store'), [
                'purchase_order_id' => $purchaseOrder->id,
                'fund_source' => 'petty_cash',
                'requested_amount' => 120000,
                'title' => 'Dana pembelian notifikasi owner open',
            ])
            ->assertRedirect(route('ui.purchase-funding-requests.index', ['fund_source' => 'petty_cash']));

        $fundingRequest = PurchaseFundingRequest::query()->firstOrFail();

        $this
            ->actingAs($finance)
            ->post(route('ui.purchase-funding-requests.review', $fundingRequest), [
                'reviewed_amount' => 120000,
                'finance_notes' => 'Di atas threshold.',
            ])
            ->assertRedirect(route('ui.purchase-funding-requests.index'));

        $notification = $owner->fresh()->notifications()->latest()->firstOrFail();

        $this
            ->actingAs($owner)
            ->get(route('ui.notifications.open', $notification->id))
            ->assertRedirect(route('ui.purchase-funding-requests.index', ['status' => 'reviewed']));

        $this->assertNotNull($owner->fresh()->notifications()->findOrFail($notification->id)->read_at);
    }

    public function test_finance_can_export_purchase_funding_reports_to_excel_and_pdf(): void
    {
        $finance = User::query()->create([
            'name' => 'Finance Funding Export',
            'email' => 'finance.funding.export@example.com',
            'password' => 'password123',
            'role' => UserRole::FINANCE->value,
        ]);

        PurchaseFundingRequest::query()->create([
            'number' => 'FND-EXP-01',
            'purchase_order_id' => null,
            'title' => 'Funding export sample',
            'vendor_id' => null,
            'sppg_id' => null,
            'fund_source' => 'petty_cash',
            'requested_amount' => 100000,
            'reviewed_amount' => 100000,
            'approved_amount' => 100000,
            'disbursed_amount' => 90000,
            'spent_amount' => 70000,
            'status' => FundingRequestStatus::DISBURSED,
            'submitted_by' => $finance->id,
        ]);

        $excelResponse = $this
            ->actingAs($finance)
            ->get(route('ui.purchase-funding-requests.export', ['fund_source' => 'petty_cash']));

        $excelResponse->assertOk();
        $excelResponse->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $pdfResponse = $this
            ->actingAs($finance)
            ->get(route('ui.purchase-funding-requests.export-pdf', ['fund_source' => 'petty_cash']));

        $pdfResponse->assertOk();
        $pdfResponse->assertHeader('content-type', 'application/pdf');
    }

    public function test_funding_settlement_rejects_unsafe_upload_file(): void
    {
        $finance = User::query()->create([
            'name' => 'Finance Funding Security',
            'email' => 'finance.funding.security@example.com',
            'password' => 'password123',
            'role' => UserRole::FINANCE->value,
        ]);

        $fundingRequest = PurchaseFundingRequest::query()->create([
            'number' => 'FND-SEC-01',
            'purchase_order_id' => null,
            'title' => 'Funding security test',
            'vendor_id' => null,
            'sppg_id' => null,
            'fund_source' => 'petty_cash',
            'requested_amount' => 100000,
            'approved_amount' => 100000,
            'disbursed_amount' => 100000,
            'spent_amount' => 0,
            'status' => FundingRequestStatus::DISBURSED,
            'submitted_by' => $finance->id,
        ]);

        $response = $this
            ->actingAs($finance)
            ->post(route('ui.purchase-funding-requests.settle', $fundingRequest), [
                'spent_amount' => 50000,
                'finance_notes' => 'Testing dangerous upload',
                'settlement_proof' => UploadedFile::fake()->create('dangerous.php', 20, 'application/x-php'),
            ]);

        $response->assertSessionHasErrors('settlement_proof');
    }
}
