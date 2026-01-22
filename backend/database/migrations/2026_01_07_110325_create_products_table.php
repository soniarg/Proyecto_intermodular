<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ... imports ...

return new class extends Migration
{
    public function up(): void
    {
    Schema::create('products', function (Blueprint $table) {
            $table->id();
            // Usamos 'seller_id' para referirnos al usuario vendedor
            $table->unsignedBigInteger('seller_id'); 
            
            $table->string('title');
            $table->decimal('price', 10, 2);
            $table->enum('unit', ['unit', 'kg', 'box']);
            $table->decimal('estimated_weight', 10, 2)->default(1.0);
            $table->decimal('stock', 14, 3);
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // RELACIÓN: Un producto pertenece a un Usuario (que es vendedor)
            $table->foreign('seller_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};