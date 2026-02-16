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
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained('inventory_master')->cascadeOnDelete();
            $table->string('batch_number');
            $table->date('expiry_date')->nullable();
            $table->integer('quantity'); // Current quantity in this batch
            $table->decimal('cost_price', 10, 2)->default(0); // Cost for this specific batch
            $table->decimal('selling_price', 10, 2)->nullable(); // Optional override selling price
            
            // Phase 3 Prep: Location Support
            // $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete(); 
            // We'll add location_id in Phase 3 migration to handle 'locations' table dependency cleanly
            
            $table->timestamps();
            
            // Unique constraint: A batch number should be unique per product? 
            // Or globally? Usually per product.
            // But sometimes same batch number is used for multiple products (rare).
            // Let's enforce uniqueness on (inventory_id, batch_number) for now.
            $table->unique(['inventory_id', 'batch_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
