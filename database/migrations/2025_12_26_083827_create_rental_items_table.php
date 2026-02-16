<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained('rentals')->onDelete('cascade');
            $table->foreignId('inventory_id')->constrained('inventory_master')->onDelete('restrict');
            $table->integer('quantity')->default(1);
            $table->string('condition_out')->nullable(); // Condition when rented out
            $table->string('condition_in')->nullable(); // Condition when returned
            $table->decimal('price_at_rental', 10, 2)->nullable(); // Rental price at time of rental
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_items');
    }
};
