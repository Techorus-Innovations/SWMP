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
            $table->string('quantity_unit')->after('max_quantity');
            $table->string('inbound_request_item_code')->after('inbound_request_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('draft_outbound_material', function (Blueprint $table) {
            $table->dropColumn(['quantity_unit', 'inbound_request_item_code']);
        });
    }
};
