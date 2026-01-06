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
        Schema::create('saved_carts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(); // Optional human-readable name
            $table->json('cart_data'); // Contains items, customer, discounts
            $table->string('seller_username');
            $table->timestamps();

            $table->index('seller_username');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saved_carts');
    }
};
