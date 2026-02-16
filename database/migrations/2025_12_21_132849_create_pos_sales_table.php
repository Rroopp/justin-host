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
        Schema::create('pos_sales', function (Blueprint $table) {
            $table->id();
            $table->json('sale_items'); // Array of sale items
            $table->enum('payment_method', ['Cash', 'M-Pesa', 'Bank', 'Cheque'])->default('Cash');
            $table->enum('payment_status', ['paid', 'pending', 'partial'])->default('paid');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('vat', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->json('customer_snapshot')->nullable(); // Full customer data at time of sale
            $table->string('seller_username');
            $table->string('timestamp');
            $table->boolean('receipt_generated')->default(true);
            $table->text('receipt_data')->nullable(); // JSON receipt/invoice data
            $table->enum('document_type', ['receipt', 'invoice', 'delivery_note'])->default('receipt');
            $table->string('invoice_number')->nullable()->unique();
            $table->date('due_date')->nullable();
            $table->string('lpo_number')->nullable();
            $table->enum('patient_type', ['Inpatient', 'Outpatient'])->nullable();
            $table->boolean('delivery_note_generated')->default(false);
            $table->text('delivery_note_data')->nullable();
            $table->date('payment_date')->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('payment_notes')->nullable();
            $table->timestamps();
            
            $table->index('seller_username');
            $table->index('customer_id');
            $table->index('document_type');
            $table->index('payment_status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_sales');
    }
};
