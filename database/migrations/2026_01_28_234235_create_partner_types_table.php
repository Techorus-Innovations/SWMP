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
        Schema::create('partner_types', function (Blueprint $table) {
            $table->id();
            $table->string('grouping_code');
            $table->string('name_en');
            $table->string('name_ar');
            $table->char('type', 1);
            $table->foreignId('cust_type_id')->nullable()->constrained('customer_types')->onDelete('set null');
            $table->char('tax_cat', 1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_types');
    }
};
