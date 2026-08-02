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
            // Drop foreign keys first to avoid MySQL index constraint errors
            $table->dropForeign(['draft_inbound_id']);
            $table->dropForeign(['material_id']);
            
            // Drop primary key
            $table->dropPrimary();

            // Add new auto-increment ID and unique constraint
            // $table->id();
            $table->unique(['draft_inbound_id', 'material_id', 'batch', 'plant_area_id'], 'draft_inbound_material_unique');
            
            // Re-add foreign keys
            $table->foreign('draft_inbound_id')->references('id')->on('draft_inbounds')->cascadeOnDelete();
            $table->foreign('material_id')->references('id')->on('materials')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('draft_inbound_material', function (Blueprint $table) {
            // $table->dropForeign(['draft_inbound_id']);
            // $table->dropForeign(['material_id']);
            
            // $table->dropUnique('draft_inbound_material_unique');
            // $table->dropColumn('id');
            
            $table->primary(['draft_inbound_id', 'material_id']);
            
            $table->foreign('draft_inbound_id')->references('id')->on('draft_inbounds')->cascadeOnDelete();
            $table->foreign('material_id')->references('id')->on('materials')->cascadeOnDelete();
        });
    }
};
