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
        Schema::create('consignment_stock_levels', function (Blueprint $table) {
            $table->id();
            
            // Location and inventory
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->foreignId('inventory_id')->constrained('inventory_master')->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();
            
            // Stock levels
            $table->integer('quantity_placed')->default(0); // Total placed at location
            $table->integer('quantity_used')->default(0);   // Total used/billed
            $table->integer('quantity_available')->default(0); // placed - used
            
            // Tracking dates
            $table->date('last_placed_date')->nullable();
            $table->date('last_used_date')->nullable();
            $table->integer('days_at_location')->default(0); // Calculated field
            
            $table->timestamps();
            
            // Unique constraint - one record per location/inventory/batch combination
            $table->unique(['location_id', 'inventory_id', 'batch_id']);
            
            // Indexes
            $table->index('quantity_available');
            $table->index('days_at_location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consignment_stock_levels');
    }
};
