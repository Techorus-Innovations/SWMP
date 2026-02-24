<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Schema::table('business_partners', function (Blueprint $table) {
            //
            $table->unique('request_id');
        });
        DB::statement('ALTER TABLE business_partners MODIFY request_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_partners', function (Blueprint $table) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            // To reverse: remove auto-increment first, then drop the index
            DB::statement('ALTER TABLE your_table_name MODIFY request_id BIGINT UNSIGNED NOT NULL');

            Schema::table('business_partners', function (Blueprint $table) {
                $table->dropUnique(['request_id']);
            });
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        });
    }
};
