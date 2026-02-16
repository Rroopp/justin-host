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
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            
            // Core Movement Data
            $table->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();
            $table->foreignId('inventory_id')->constrained('inventory_master')->cascadeOnDelete();
            
            // Movement Type
            $table->enum('movement_type', [
                'receipt',          // Goods received from supplier
                'transfer',         // Stock transfer between locations
                'reservation',      // Reserved for surgery/case
                'usage',           // Used in surgery/procedure
                'sale',            // Sold to customer
                'return',          // Returned from customer/location
                'adjustment',      // Manual adjustment (count correction)
                'write_off',       // Damaged/lost/expired write-off
                'consignment_out', // Sent on consignment
                'consignment_return', // Returned from consignment
                'consignment_sale'    // Sold from consignment
            ]);
            
            // Quantity
            $table->integer('quantity')->comment('Positive for additions, negative for reductions');
            $table->integer('quantity_before')->nullable()->comment('Quantity before this movement');
            $table->integer('quantity_after')->nullable()->comment('Quantity after this movement');
            
            // Location Tracking
            $table->foreignId('from_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('to_location_id')->nullable()->constrained('locations')->nullOnDelete();
            
            // Reference to Source Document
            $table->string('reference_type')->nullable()->comment('purchase_order, stock_transfer, pos_sale, etc.');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('ID of the source document');
            
            // Additional Context
            $table->text('reason')->nullable()->comment('Reason for adjustment/write-off');
            $table->text('notes')->nullable();
            
            // Cost Tracking
            $table->decimal('unit_cost', 10, 2)->nullable()->comment('Cost per unit at time of movement');
            $table->decimal('total_value', 12, 2)->nullable()->comment('Total value of movement');
            
            // Audit Trail
            $table->foreignId('performed_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index('movement_type');
            $table->index('reference_type');
            $table->index(['reference_type', 'reference_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
