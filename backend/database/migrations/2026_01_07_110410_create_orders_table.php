<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            
            // COMPRADOR (El que está logueado)
            $table->unsignedBigInteger('buyer_id'); 
            
            // VENDEDOR (El dueño del producto)
            // Lo renombramos a 'seller_id' para que sea más claro
            $table->unsignedBigInteger('seller_id'); 

            // PUNTO DE RECOGIDA (Puede ser null si es a domicilio en el futuro)
            $table->unsignedBigInteger('pickup_id')->nullable();
            
            $table->enum('status', ['draft', 'pending', 'weight_adjusted', 'ready', 'completed', 'rejected'])->default('draft');
            $table->decimal('total_price', 10, 2); // He cambiado total_price a total para coincidir con el modelo estándar, pero puedes usar el que quieras.
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            // --- RELACIONES ---
            
            // 1. El comprador es un usuario
            $table->foreign('buyer_id')->references('id')->on('users')->onDelete('cascade');

            // 2. El vendedor TAMBIÉN es un usuario (mejor apuntar a users que a profiles para evitar líos de IDs)
            $table->foreign('seller_id')->references('id')->on('users')->onDelete('cascade');

            // 3. El punto de recogida
            // IMPORTANTE: 'set null'. Si se borra el punto, no queremos borrar el pedido, solo que el campo se quede vacío.
            $table->foreign('pickup_id')->references('id')->on('pickup_points')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};