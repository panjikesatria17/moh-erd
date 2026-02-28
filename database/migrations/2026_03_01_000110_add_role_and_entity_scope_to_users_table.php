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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 50)->default('sppg_user')->after('password');
            $table->foreignId('sppg_id')->nullable()->after('role')->constrained('sppgs')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->after('sppg_id')->constrained('vendors')->nullOnDelete();

            $table->index('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vendor_id');
            $table->dropConstrainedForeignId('sppg_id');
            $table->dropIndex(['role']);
            $table->dropColumn('role');
        });
    }
};
