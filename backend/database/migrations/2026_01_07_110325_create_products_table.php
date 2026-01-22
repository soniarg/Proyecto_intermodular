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
            $table->unsignedBigInteger('user_id'); // El vendedor (User)
            
            $table->string('title');
            $table->decimal('price', 10, 2);
            $table->enum('unit', ['unit', 'kg', 'box']);
            $table->decimal('estimated_weight', 10, 2)->default(1.0); // Ponemos default por si acaso
            $table->decimal('stock', 14, 3);
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true); // Default true para que se vean al crearlos
            $table->timestamps();

            // CAMBIO CLAVE: Apuntamos directamente a 'users' para evitar problemas hoy
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};