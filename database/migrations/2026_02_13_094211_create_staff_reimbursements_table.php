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
        Schema::create('staff_reimbursements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->onDelete('cascade');
            $table->string('reference_number')->unique(); // REI-YYYY-XXXX
            $table->string('description');
            $table->string('category')->nullable(); // Travel, Meals, Supplies, Fuel, Other
            $table->decimal('amount', 10, 2);
            $table->date('expense_date');
            $table->string('receipt_file_path')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'paid'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('staff')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('staff')->onDelete('set null');
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method')->nullable(); // cash, bank_transfer, payroll
            $table->foreignId('payroll_run_id')->nullable()->constrained('payroll_runs')->onDelete('set null');
            $table->foreignId('payment_account_id')->nullable()->constrained('chart_of_accounts')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['staff_id', 'status']);
            $table->index('expense_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_reimbursements');
    }
};
