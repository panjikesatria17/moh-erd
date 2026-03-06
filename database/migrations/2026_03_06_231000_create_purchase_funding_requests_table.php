<?php

use App\Enums\FundingRequestStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_funding_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('number', 50)->unique();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('sppg_id')->nullable()->constrained('sppgs')->nullOnDelete();
            $table->string('title', 255);
            $table->string('fund_source', 30)->default('petty_cash');
            $table->decimal('requested_amount', 15, 2);
            $table->decimal('reviewed_amount', 15, 2)->nullable();
            $table->decimal('approved_amount', 15, 2)->nullable();
            $table->decimal('disbursed_amount', 15, 2)->nullable();
            $table->decimal('spent_amount', 15, 2)->default(0);
            $table->string('status', 30)->default(FundingRequestStatus::SUBMITTED->value);

            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('disbursed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('disbursed_at')->nullable();
            $table->foreignId('settled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('settled_at')->nullable();

            $table->text('notes')->nullable();
            $table->text('finance_notes')->nullable();
            $table->text('owner_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'fund_source']);
            $table->index(['purchase_order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_funding_requests');
    }
};
