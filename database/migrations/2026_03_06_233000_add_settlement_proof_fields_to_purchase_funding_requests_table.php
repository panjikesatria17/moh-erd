<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_funding_requests', function (Blueprint $table): void {
            $table->string('settlement_proof_path')->nullable()->after('owner_notes');
            $table->timestamp('settlement_proof_uploaded_at')->nullable()->after('settlement_proof_path');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_funding_requests', function (Blueprint $table): void {
            $table->dropColumn([
                'settlement_proof_path',
                'settlement_proof_uploaded_at',
            ]);
        });
    }
};
