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
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_sale_id')->constrained('pos_sales')->onDelete('cascade');
            $table->string('refund_number')->unique();
            $table->enum('refund_type', ['full', 'partial'])->default('full');
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending');
            $table->decimal('refund_amount', 10, 2);
            $table->json('refund_items'); // Items being refunded with quantities
            $table->text('reason');
            $table->text('admin_notes')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('staff')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('staff')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->enum('refund_method', ['Cash', 'M-Pesa', 'Bank', 'Credit Note'])->nullable();
            $table->string('reference_number')->nullable(); // M-Pesa/Bank reference
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->onDelete('set null');
            $table->boolean('inventory_restored')->default(false);
            $table->boolean('accounting_reversed')->default(false);
            $table->timestamps();
            
            $table->index('pos_sale_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
