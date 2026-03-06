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
        Schema::table('sppgs', function (Blueprint $table) {
            $table->string('ka_sppg_name')->nullable()->after('name');
            $table->string('accounting_name')->nullable()->after('ka_sppg_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sppgs', function (Blueprint $table) {
            $table->dropColumn(['ka_sppg_name', 'accounting_name']);
        });
    }
};
