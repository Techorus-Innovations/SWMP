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
            $table->dropForeign(['customer_type_id']);
            $table->dropColumn(['customer_type_id', 'tax_category']);
            $table->unsignedBigInteger('industry_id')->onDelete('set null')->nullable()->change();
            // $table->foreignId('industry_id')->constrained('industries')->onDelete('set null')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_partners', function (Blueprint $table) {
            $table->foreignId('customer_type_id')->constrained('customer_types')->onDelete('set null');
            $table->char('tax_category', 1);
            $table->unsignedBigInteger('industry_id')->onDelete('set null')->change();
            
            // $table->foreignId('industry_id')->constrained('industries')->onDelete('set null')->change();
        });
    }
};
