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
            $table->foreignId('plant_area_id')->nullable()->constrained('plant_areas')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('draft_inbound_material', function (Blueprint $table) {
            $table->dropForeign(['plant_area_id']);
            $table->dropColumn('plant_area_id');
        });
    }
};
