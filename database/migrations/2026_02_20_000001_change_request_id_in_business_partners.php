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
        Schema::table('business_partners', function (Blueprint $table) {
            // $table->dropColumn('request_id');
            $table->unsignedBigInteger('request_id');
            // $table->unsignedBigInteger('industry_id')->onDelete('set null')->nullable()->change();
            // $table->foreignId('industry_id')->constrained('industries')->onDelete('set null')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_partners', function (Blueprint $table) {
            $table->dropColumn('request_id');
            $table->string('request_id', 40)->unique();
            
            // $table->foreignId('industry_id')->constrained('industries')->onDelete('set null')->change();
        });
    }
};
