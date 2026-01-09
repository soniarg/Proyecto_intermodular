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
        Schema::create('orders', function (Blueprint $table) {
            $table->id(); // order_id
            $table->unsignedBigInteger('buyer_id');
            $table->unsignedBigInteger('seller_id');
            $table->unsignedBigInteger('pickup_id')->nullable();
            
            $table->enum('status', ['draft', 'pending', 'weight_adjusted', 'ready', 'completed', 'rejected'])->default('draft');
            $table->decimal('total_price', 10, 2);
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            // Foreign Keys
            $table->foreign('buyer_id')->references('id')->on('users'); // Comprador es un User
            $table->foreign('seller_id')->references('seller_id')->on('seller_profiles');
            $table->foreign('pickup_id')->references('id')->on('pickup_points');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
