<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->boolean('is_additional')->default(false)->after('notes');
            $table->foreignId('additional_to_po_id')
                ->nullable()
                ->after('is_additional')
                ->constrained('purchase_orders')
                ->nullOnDelete();

            $table->index(['is_additional', 'additional_to_po_id']);
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropIndex(['is_additional', 'additional_to_po_id']);
            $table->dropConstrainedForeignId('additional_to_po_id');
            $table->dropColumn('is_additional');
        });
    }
};
