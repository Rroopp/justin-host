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
        Schema::create('stock_take_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_take_id')->constrained('stock_takes')->onDelete('cascade');
            $table->foreignId('inventory_id')->constrained('inventory_master')->onDelete('cascade');
            $table->decimal('system_quantity', 10, 2); // Quantity in system at time of stock take
            $table->decimal('physical_quantity', 10, 2)->nullable(); // Actual counted quantity
            $table->decimal('variance', 10, 2)->nullable(); // Difference (physical - system)
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Ensure each inventory item appears only once per stock take
            $table->unique(['stock_take_id', 'inventory_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_take_items');
    }
};
