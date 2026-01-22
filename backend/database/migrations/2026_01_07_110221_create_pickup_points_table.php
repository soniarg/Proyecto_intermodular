<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickup_points', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seller_id'); // Aquí guardaremos el User ID
            
            // COORDENADAS
            $table->decimal('latitude', 11, 8)->nullable(); // Nullable por si falla la API
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('address');
            $table->string('city');        // <--- NUEVO
            $table->string('postal_code'); // <--- NUEVO

            $table->timestamps();

            // Apunta a seller_id en seller_profiles
            $table->foreign('seller_id')->references('seller_id')->on('seller_profiles')->onDelete('cascade');
            // CORRECCIÓN IMPORTANTE:
            // Apuntamos a la tabla 'users' porque Auth::id() nos da un User ID.
            // Si el usuario se borra, se borran sus puntos.
            $table->foreign('seller_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_points');
    }
};