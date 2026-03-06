<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_ad_hoc')->default(false)->after('is_active');
            $table->index(['is_ad_hoc', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_ad_hoc', 'is_active']);
            $table->dropColumn('is_ad_hoc');
        });
    }
};
