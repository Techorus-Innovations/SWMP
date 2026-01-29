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
        Schema::create('partner_role_user', function (Blueprint $table) {
                $table->id();
                $table->foreignUlid('user_id')->nullable()->constrained('users')->onDelete('cascade');
                $table->foreignUlid('partner_role_id')->nullable()->constrained('partner_roles')->onDelete('cascade');
                $table->timestamps();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_role_user');
    }
};
