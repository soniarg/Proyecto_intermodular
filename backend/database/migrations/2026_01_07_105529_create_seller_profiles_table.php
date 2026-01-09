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
        Schema::create('seller_profiles', function (Blueprint $table) {
            // seller_id es PK y FK a la vez
            $table->unsignedBigInteger('seller_id')->primary(); 
            $table->string('store_name');
            $table->text('description')->nullable();
            $table->char('nif', 9);
            $table->string('banner_url')->nullable();
            $table->timestamps();

            // Relación: seller_id DEBE existir en la tabla users (columna id)
            $table->foreign('seller_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seller_profiles');
    }
};
