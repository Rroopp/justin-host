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
        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_id')->constrained('inventory_master')->cascadeOnDelete();
            // We transfer specific batches
            $table->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete(); 
            // If batch_id is null, it might be legacy stock or unspecified? 
            // Ideally should be required for medical. Let's force it nullable for robust data but logic should enforce it.
            $table->integer('quantity');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
    }
};
