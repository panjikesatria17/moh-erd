<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->string('delivery_proof_image_path')->nullable()->after('delivery_date');
            $table->string('signed_delivery_note_path')->nullable()->after('delivery_proof_image_path');
            $table->timestamp('proof_uploaded_at')->nullable()->after('signed_delivery_note_path');
            $table->timestamp('delivered_at')->nullable()->after('proof_uploaded_at');

            $table->index(['status', 'proof_uploaded_at']);
        });
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropIndex(['status', 'proof_uploaded_at']);
            $table->dropColumn([
                'delivery_proof_image_path',
                'signed_delivery_note_path',
                'proof_uploaded_at',
                'delivered_at',
            ]);
        });
    }
};
