<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // CORREGIDO
            
            $table->string('title');
            $table->decimal('price', 10, 2);
            $table->enum('unit', ['unit', 'kg', 'box']);
            $table->decimal('estimated_weight', 10, 2);
            $table->decimal('stock', 14, 3);
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            // Apunta a user_id en seller_profiles
            $table->foreign('user_id')->references('user_id')->on('seller_profiles')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};