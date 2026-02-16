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
        Schema::create('lpos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers');
            $table->string('lpo_number');
            $table->decimal('amount', 15, 2)->default(0); // Total LPO Value
            $table->decimal('remaining_balance', 15, 2)->default(0); // For drawdown
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->text('description')->nullable();
            $table->string('document_path')->nullable(); // File path
            $table->enum('status', ['active', 'completed', 'expired'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('lpo_number');
            $table->index('customer_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lpos');
    }
};
