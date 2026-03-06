<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('proof_image_path')->nullable()->after('reference_no');
            $table->foreignId('proof_uploaded_by')->nullable()->after('proof_image_path')->constrained('users')->nullOnDelete();
            $table->timestamp('proof_uploaded_at')->nullable()->after('proof_uploaded_by');
            $table->foreignId('approved_by')->nullable()->after('proof_uploaded_at')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');

            $table->index(['status', 'proof_uploaded_at']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['status', 'proof_uploaded_at']);
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn('approved_at');
            $table->dropConstrainedForeignId('proof_uploaded_by');
            $table->dropColumn('proof_uploaded_at');
            $table->dropColumn('proof_image_path');
        });
    }
};
