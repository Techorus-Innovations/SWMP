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
        Schema::table('draft_inbound_material', function (Blueprint $table) {
            $table->string('batch', 10)->nullable();
            $table->date('manufacture_date')->nullable();
            $table->date('expiration_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('draft_inbound_material', function (Blueprint $table) {
            $table->dropColumn(['batch', 'manufacture_date', 'expiration_date']);
        });
    }
};
