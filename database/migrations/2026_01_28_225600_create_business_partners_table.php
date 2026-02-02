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
        Schema::create('business_partners', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('request_id', 40)->unique();
            $table->foreignId('partner_type_id')->constrained('partner_types')->onDelete('set null');
            $table->char('type', 1);
            
            // GLN fields
            $table->char('gln1', 7);
            $table->char('gln2', 5);
            $table->char('ckgdigit', 1);
            
            // Name fields
            $table->string('name1', 40);
            $table->string('name2', 40)->nullable();
            
            // Address fields
            $table->string('city', 40);
            $table->string('street', 60);
            $table->string('building', 10);
            $table->string('postal_code', 16);
            $table->foreignId('country_id')->constrained('countries')->onDelete('set null');
            $table->foreignId('region_id')->constrained('regions')->onDelete('set null');

            // Registration and tax
            $table->string('registration_number', 20);
            $table->string('tax_number', 20);
            $table->foreignId('industry_id')->constrained('industries')->onDelete('set null');
            $table->foreignId('customer_type_id')->constrained('customer_types')->onDelete('set null');
            $table->char('tax_category', 1);
            $table->string('mobile');
            $table->string('email');
            
            $table->foreignUlid('user_id')->constrained()->onDelete('cascade');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_partners');
    }
};
