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
        Schema::create('draft_outbound_material', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('draft_outbound_id')->constrained('draft_outbounds')->cascadeOnDelete();
            $table->string('material_code');
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('max_quantity');
            $table->unsignedInteger('inbound_request_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('draft_outbound_material');
    }
};
