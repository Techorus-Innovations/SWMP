<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('draft_outbound_material', function (Blueprint $table) {
            $table->dropColumn(['inbound_request_id','batch', 'manufacture_date', 'expiration_date','location','inbound_request_item_code']);
            $table->string('batch_code')->after('material_code');
            $table->string('location_code')->after('batch_code');
            $table->date('manufacture_date')->nullable()->after('quantity_unit');
            $table->date('expiration_date')->nullable()->after('manufacture_date');
            $table->string('material_code')->change();
        });
    }

    public function down(): void
    {
        Schema::table('draft_outbound_material', function (Blueprint $table) {
            $table->dropColumn(['batch_code', 'location_code', 'manufacture_date', 'expiration_date']);
            $table->string('inbound_request_id')->after('max_quantity');
        });
    }
};