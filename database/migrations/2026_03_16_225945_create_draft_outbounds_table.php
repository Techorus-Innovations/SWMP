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
        Schema::create('draft_outbounds', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('ship_to_party')->nullable();
            $table->foreignUlid('business_partner_id')->constrained('business_partners')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('draft_outbounds');
    }
};
