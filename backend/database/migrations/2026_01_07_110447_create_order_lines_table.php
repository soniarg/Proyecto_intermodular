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
        Schema::create('order_lines', function (Blueprint $table) {
            $table->id(); // order_line_id
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            
            $table->decimal('quantity', 10, 2);
            $table->decimal('weight_at_moment', 10, 2);
            $table->decimal('real_weight', 10, 2)->nullable();
            $table->decimal('price_at_moment', 10, 2);
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_lines');
    }
};
