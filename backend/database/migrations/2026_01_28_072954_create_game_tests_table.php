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
        Schema::create('game_tests', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nombre del juego
            $table->decimal('cost', 8, 2); // Precio (ej: 50.00)
            $table->string('image')->nullable(); // Foto (opcional)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_tests');
    }
};
