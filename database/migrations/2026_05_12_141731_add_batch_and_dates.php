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
        Schema::table('draft_outbound_material', function (Blueprint $table) {
            $table->string('expiration_date')->nullable();
            $table->string('manufacture_date')->nullable();
            $table->string('batch')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('draft_outbound_material', function (Blueprint $table) {
            $table->dropColumn(['expiration_date', 'manufacture_date', 'batch']);
        });
    }
};
