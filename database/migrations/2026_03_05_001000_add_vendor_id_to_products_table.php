<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('vendor_id')
                ->nullable()
                ->after('product_category_id')
                ->constrained('vendors')
                ->nullOnDelete();

            $table->index(['vendor_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['vendor_id', 'name']);
            $table->dropConstrainedForeignId('vendor_id');
        });
    }
};
