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
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('total_gross', 10, 2)->default(0);
            $table->decimal('total_tax', 10, 2)->default(0);
            $table->decimal('total_net', 10, 2)->default(0);
            $table->enum('status', ['DRAFT', 'COMPLETED', 'CANCELLED'])->default('DRAFT');
            $table->string('created_by');
            $table->timestamps();
            
            $table->index('period_start');
            $table->index('period_end');
            $table->index('status');
        });
        
        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('payroll_runs')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('staff')->onDelete('cascade');
            $table->decimal('gross_pay', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('net_pay', 10, 2);
            $table->timestamps();
            
            $table->index('run_id');
            $table->index('employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('payroll_runs');
    }
};
