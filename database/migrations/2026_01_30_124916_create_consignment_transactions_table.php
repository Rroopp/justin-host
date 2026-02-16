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
        Schema::create('consignment_transactions', function (Blueprint $table) {
            $table->id();
            
            // Location and inventory
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->foreignId('inventory_id')->constrained('inventory_master')->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();
            
            // Transaction details
            $table->enum('transaction_type', ['placed', 'used', 'returned', 'expired', 'damaged'])->default('placed');
            $table->integer('quantity');
            $table->date('transaction_date');
            
            // Reference to source (polymorphic)
            $table->string('reference_type')->nullable(); // PosSale, SurgeryUsage, Manual
            $table->unsignedBigInteger('reference_id')->nullable();
            
            // Billing tracking
            $table->boolean('billed')->default(false);
            $table->date('billed_date')->nullable();
            $table->string('billing_reference')->nullable(); // Invoice number
            
            // Additional info
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('staff')->cascadeOnDelete();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['location_id', 'inventory_id']);
            $table->index('transaction_type');
            $table->index('billed');
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consignment_transactions');
    }
};
