<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_profiles', function (Blueprint $table) {
            // CORREGIDO: Usamos user_id como Clave Primaria y Foránea
            $table->unsignedBigInteger('seller_id')->primary(); 
            $table->string('store_name');
            $table->text('description')->nullable();
            $table->char('nif', 9);
            $table->string('banner_url')->nullable();
            $table->timestamps();
            $table->foreign('seller_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_profiles');
    }
};