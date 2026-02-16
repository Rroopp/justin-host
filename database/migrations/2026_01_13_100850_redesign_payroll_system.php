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
        // 1. Create deduction types master table
        Schema::create('payroll_deduction_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // PAYE, NSSF_EE, NHIF_EE, LOAN, ADVANCE
            $table->string('name');
            $table->enum('type', ['STATUTORY', 'LOAN', 'ADVANCE', 'OTHER'])->default('OTHER');
            $table->boolean('is_statutory')->default(false);
            $table->foreignId('liability_account_id')->nullable()->constrained('chart_of_accounts');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Create employer contribution types
        Schema::create('payroll_contribution_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // NSSF_ER, NHIF_ER
            $table->string('name');
            $table->foreignId('expense_account_id')->nullable()->constrained('chart_of_accounts');
            $table->foreignId('liability_account_id')->nullable()->constrained('chart_of_accounts');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Modify payroll_runs
        Schema::table('payroll_runs', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_runs', 'journal_entry_id')) {
                $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            }
            if (!Schema::hasColumn('payroll_runs', 'payment_journal_entry_id')) {
                $table->foreignId('payment_journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            }
            if (!Schema::hasColumn('payroll_runs', 'total_deductions')) {
                $table->decimal('total_deductions', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('payroll_runs', 'total_employer_contributions')) {
                $table->decimal('total_employer_contributions', 12, 2)->default(0);
            }
            // Expand status enum if needed (SQLite limitation - will use string)
            if (Schema::hasColumn('payroll_runs', 'status')) {
                $table->string('status_new')->default('DRAFT')->after('status');
            }
        });

        // 4. Create payroll earnings table
        Schema::create('payroll_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->onDelete('cascade');
            $table->foreignId('staff_id')->constrained('staff')->onDelete('cascade');
            $table->decimal('basic_salary', 10, 2)->default(0);
            $table->decimal('allowances', 10, 2)->default(0);
            $table->decimal('overtime', 10, 2)->default(0);
            $table->decimal('bonuses', 10, 2)->default(0);
            $table->decimal('gross_pay', 10, 2)->default(0); // Calculated
            $table->timestamps();
            
            $table->index(['payroll_run_id', 'staff_id']);
        });

        // 5. Create payroll deductions table
        Schema::create('payroll_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->onDelete('cascade');
            $table->foreignId('staff_id')->constrained('staff')->onDelete('cascade');
            $table->foreignId('deduction_type_id')->constrained('payroll_deduction_types')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->boolean('is_statutory')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['payroll_run_id', 'staff_id']);
        });

        // 6. Create employer contributions table
        Schema::create('payroll_employer_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->onDelete('cascade');
            $table->foreignId('staff_id')->constrained('staff')->onDelete('cascade');
            $table->foreignId('contribution_type_id')->constrained('payroll_contribution_types')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->timestamps();
            
            $table->index(['payroll_run_id', 'staff_id']);
        });

        // 7. Modify payroll_items to add summary fields
        Schema::table('payroll_items', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_items', 'total_deductions')) {
                $table->decimal('total_deductions', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('payroll_items', 'total_employer_contributions')) {
                $table->decimal('total_employer_contributions', 10, 2)->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_items', function (Blueprint $table) {
            $table->dropColumn(['total_deductions', 'total_employer_contributions']);
        });
        
        Schema::dropIfExists('payroll_employer_contributions');
        Schema::dropIfExists('payroll_deductions');
        Schema::dropIfExists('payroll_earnings');
        
        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->dropColumn([
                'journal_entry_id', 
                'payment_journal_entry_id',
                'total_deductions',
                'total_employer_contributions',
                'status_new'
            ]);
        });
        
        Schema::dropIfExists('payroll_contribution_types');
        Schema::dropIfExists('payroll_deduction_types');
    }
};
