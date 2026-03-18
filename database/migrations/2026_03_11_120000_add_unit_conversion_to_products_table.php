<?php

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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('pcs_per_box', 15, 4)->nullable()->after('unit');
            $table->decimal('pcs_per_pack', 15, 4)->nullable()->after('pcs_per_box');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['pcs_per_box', 'pcs_per_pack']);
        });
    }
};
