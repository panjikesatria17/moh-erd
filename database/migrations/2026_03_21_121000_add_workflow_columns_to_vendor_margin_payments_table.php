<?php

use App\Models\VendorMarginPayment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_margin_payments', function (Blueprint $table) {
            $table->string('status', 20)
                ->default(VendorMarginPayment::STATUS_SUBMITTED)
                ->after('notes');
            $table->string('proof_image_path')->nullable()->after('status');
            $table->timestamp('proof_uploaded_at')->nullable()->after('proof_image_path');
            $table->foreignId('approved_by')->nullable()->after('proof_uploaded_at')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('rejection_note')->nullable()->after('approved_at');

            $table->index(['vendor_id', 'status', 'payment_date'], 'vendor_margin_payments_vendor_status_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_margin_payments', function (Blueprint $table) {
            $table->dropIndex('vendor_margin_payments_vendor_status_date_idx');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn([
                'status',
                'proof_image_path',
                'proof_uploaded_at',
                'approved_at',
                'rejection_note',
            ]);
        });
    }
};
