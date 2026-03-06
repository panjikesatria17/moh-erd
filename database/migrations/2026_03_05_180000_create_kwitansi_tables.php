<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kwitansis', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('billed_to', 255);
            $table->date('receipt_date');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['vendor_id', 'receipt_date']);
        });

        Schema::create('kwitansi_invoice', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kwitansi_id')->constrained('kwitansis')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('billed_amount', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['kwitansi_id', 'invoice_id']);
            $table->index(['invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kwitansi_invoice');
        Schema::dropIfExists('kwitansis');
    }
};
