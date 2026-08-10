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
        try {
            Schema::table('draft_outbound_material', function (Blueprint $table) {
                $table->dropForeign(['draft_outbound_id']);
            });
        } catch (\Throwable $e) {
            // Foreign key does not exist or already dropped
        }

        \Illuminate\Support\Facades\DB::table('draft_outbound_material')->truncate();
        \Illuminate\Support\Facades\DB::table('draft_outbounds')->truncate();

        Schema::table('draft_outbounds', function (Blueprint $table) {
            $table->bigIncrements('id')->change();
        });

        Schema::table('draft_outbound_material', function (Blueprint $table) {
            $table->unsignedBigInteger('draft_outbound_id')->change();
            $table->foreign('draft_outbound_id')->references('id')->on('draft_outbounds')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('draft_outbound_material', function (Blueprint $table) {
            $table->dropForeign(['draft_outbound_id']);
        });

        Schema::table('draft_outbounds', function (Blueprint $table) {
            $table->ulid('id')->change();
        });

        Schema::table('draft_outbound_material', function (Blueprint $table) {
            $table->ulid('draft_outbound_id')->change();
            $table->foreign('draft_outbound_id')->references('id')->on('draft_outbounds')->cascadeOnDelete();
        });
    }
};
