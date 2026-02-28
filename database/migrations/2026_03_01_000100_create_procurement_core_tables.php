<?php

use App\Enums\DocumentStatus;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_affiliate')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sppgs', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('address')->nullable();
            $table->foreignId('default_vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('name');
            $table->foreignId('product_category_id')->constrained('product_categories')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('unit', 30);
            $table->decimal('government_price_cap', 15, 2)->nullable();
            $table->decimal('minimum_stock_level', 15, 2)->default(0);
            $table->decimal('reorder_stock_level', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('billing_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sppg_id')->constrained('sppgs')->cascadeOnUpdate()->restrictOnDelete();
            $table->date('week_start_date');
            $table->date('week_end_date');
            $table->enum('status', DocumentStatus::values())->default(DocumentStatus::DRAFT->value);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['sppg_id', 'week_start_date', 'week_end_date']);
        });

        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('sppg_id')->constrained('sppgs')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('request_date');
            $table->date('needed_date')->nullable();
            $table->enum('status', DocumentStatus::values())->default(DocumentStatus::DRAFT->value);
            $table->text('notes')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['sppg_id', 'request_date']);
            $table->index(['status']);
        });

        Schema::create('purchase_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained('purchase_requests')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('quantity', 15, 2);
            $table->decimal('requested_unit_price', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->morphs('approvable');
            $table->unsignedInteger('level')->default(1);
            $table->foreignId('approver_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->enum('status', DocumentStatus::values())->default(DocumentStatus::SUBMITTED->value);
            $table->text('note')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['approvable_type', 'approvable_id', 'level']);
            $table->index(['approver_id', 'status']);
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('purchase_request_id')->nullable()->constrained('purchase_requests')->nullOnDelete();
            $table->foreignId('sppg_id')->constrained('sppgs')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('ordered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('order_date');
            $table->date('expected_date')->nullable();
            $table->enum('status', DocumentStatus::values())->default(DocumentStatus::DRAFT->value);
            $table->boolean('is_direct_purchase')->default(false);
            $table->text('notes')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['vendor_id', 'order_date']);
            $table->index(['status']);
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('received_date');
            $table->enum('status', DocumentStatus::values())->default(DocumentStatus::PROCESSED->value);
            $table->text('inspection_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->foreignId('goods_receipt_id')->nullable()->constrained('goods_receipts')->nullOnDelete();
            $table->foreignId('sppg_id')->constrained('sppgs')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('delivered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('delivery_date');
            $table->enum('status', DocumentStatus::values())->default(DocumentStatus::DELIVERED->value);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('billing_cycle_id')->nullable()->constrained('billing_cycles')->nullOnDelete();
            $table->foreignId('delivery_id')->nullable()->constrained('deliveries')->nullOnDelete();
            $table->foreignId('sppg_id')->constrained('sppgs')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnUpdate()->restrictOnDelete();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->enum('status', DocumentStatus::values())->default(DocumentStatus::INVOICED->value);
            $table->decimal('subtotal_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['sppg_id', 'vendor_id', 'invoice_date']);
            $table->index(['status']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnUpdate()->restrictOnDelete();
            $table->date('payment_date')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->enum('status', PaymentStatus::values())->default(PaymentStatus::DRAFT->value);
            $table->string('payment_method', 100)->nullable();
            $table->string('reference_no')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'payment_date']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnUpdate()->restrictOnDelete();
            $table->enum('type', StockMovementType::values());
            $table->decimal('quantity', 15, 2);
            $table->decimal('balance_after', 15, 2)->default(0);
            $table->morphs('reference');
            $table->date('movement_date');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['warehouse_id', 'product_id', 'movement_date']);
        });

        Schema::create('product_price_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->decimal('price', 15, 2);
            $table->date('effective_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['product_id', 'vendor_id', 'effective_at']);
        });

        Schema::create('stock_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('current_balance', 15, 2);
            $table->decimal('minimum_stock_level', 15, 2);
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['warehouse_id', 'is_resolved']);
        });

        Schema::create('vendor_performances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnUpdate()->restrictOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedInteger('on_time_delivery_count')->default(0);
            $table->unsignedInteger('late_delivery_count')->default(0);
            $table->unsignedInteger('quality_issue_count')->default(0);
            $table->decimal('average_lead_time_days', 8, 2)->default(0);
            $table->decimal('score', 8, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['vendor_id', 'period_start', 'period_end']);
        });

        Schema::create('audit_trails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 120);
            $table->morphs('auditable');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['event', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_trails');
        Schema::dropIfExists('vendor_performances');
        Schema::dropIfExists('stock_alerts');
        Schema::dropIfExists('product_price_histories');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('deliveries');
        Schema::dropIfExists('goods_receipts');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('approvals');
        Schema::dropIfExists('purchase_request_items');
        Schema::dropIfExists('purchase_requests');
        Schema::dropIfExists('billing_cycles');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('sppgs');
        Schema::dropIfExists('vendors');
    }
};
