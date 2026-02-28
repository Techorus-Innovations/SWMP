<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('business_partners', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedBigInteger('request_id')->unique();
            $table->foreignId('partner_type_id')->nullable()->constrained('partner_types')->onDelete('set null');
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
            $table->foreignId('country_id')->nullable()->constrained('countries')->onDelete('set null');
            $table->foreignId('region_id')->nullable()->constrained('regions')->onDelete('set null');

            // Registration and tax
            $table->string('registration_number', 20);
            $table->string('tax_number', 20);
            $table->foreignId('industry_id')->nullable()->constrained('industries')->onDelete('set null');
            $table->foreignId('customer_type_id')->nullable()->constrained('customer_types')->onDelete('set null');
            $table->char('tax_category', 1);
            $table->string('mobile');
            $table->string('email');
            
            $table->foreignUlid('user_id')->constrained()->onDelete('cascade');
            
            $table->timestamps();
        });
        DB::statement('ALTER TABLE business_partners MODIFY request_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_partners');
    }
};