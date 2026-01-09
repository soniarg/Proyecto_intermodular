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
        Schema::create('products', function (Blueprint $table) {
            $table->id(); // product_id
            $table->unsignedBigInteger('seller_id');
            $table->string('title');
            $table->decimal('price', 10, 2);
            $table->enum('unit', ['unit', 'kg', 'box']);
            $table->decimal('estimated_weight', 10, 2);
            $table->decimal('stock', 14, 3);
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->foreign('seller_id')->references('seller_id')->on('seller_profiles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
