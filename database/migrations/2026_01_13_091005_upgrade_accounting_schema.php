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
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('chart_of_accounts', 'sub_type')) {
                $table->string('sub_type')->nullable()->after('account_type');
            }
            if (!Schema::hasColumn('chart_of_accounts', 'normal_balance')) {
                $table->string('normal_balance')->default('DEBIT')->after('sub_type');
            }
            if (!Schema::hasColumn('chart_of_accounts', 'is_system')) {
                $table->boolean('is_system')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('chart_of_accounts', 'is_locked')) {
                $table->boolean('is_locked')->default(false)->after('is_system');
            }
            if (!Schema::hasColumn('chart_of_accounts', 'currency')) {
                $table->string('currency', 3)->default('KES')->after('name');
            }
        });

        Schema::table('accounting_periods', function (Blueprint $table) {
             if (!Schema::hasColumn('accounting_periods', 'status')) {
                $table->string('status')->default('OPEN')->after('end_date'); // Enum simulation
            }
            if (!Schema::hasColumn('accounting_periods', 'vat_locked')) {
                $table->boolean('vat_locked')->default(false)->after('status');
            }
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            if (!Schema::hasColumn('journal_entries', 'period_id')) {
                $table->foreignId('period_id')->nullable()->constrained('accounting_periods')->nullOnDelete();
            }
            if (!Schema::hasColumn('journal_entries', 'reversed_entry_id')) {
                $table->foreignId('reversed_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            }
            if (!Schema::hasColumn('journal_entries', 'is_locked')) {
                $table->boolean('is_locked')->default(false);
            }
            // Rename columns to match PRD "Source" terminology if they exist
            if (Schema::hasColumn('journal_entries', 'reference_type')) {
                $table->renameColumn('reference_type', 'source');
            }
            if (Schema::hasColumn('journal_entries', 'reference_id')) {
                 $table->renameColumn('reference_id', 'source_id');
            }
        });

        Schema::table('journal_entry_lines', function (Blueprint $table) {
             if (!Schema::hasColumn('journal_entry_lines', 'currency')) {
                $table->string('currency', 3)->default('KES');
            }
            if (!Schema::hasColumn('journal_entry_lines', 'exchange_rate')) {
                $table->decimal('exchange_rate', 18, 6)->default(1);
            }
            if (!Schema::hasColumn('journal_entry_lines', 'debit_base')) {
                $table->decimal('debit_base', 20, 4)->default(0);
            }
            if (!Schema::hasColumn('journal_entry_lines', 'credit_base')) {
                 $table->decimal('credit_base', 20, 4)->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_entry_lines', function (Blueprint $table) {
            $table->dropColumn(['currency', 'exchange_rate', 'debit_base', 'credit_base']);
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->renameColumn('source', 'reference_type');
            $table->renameColumn('source_id', 'reference_id');
            $table->dropColumn(['period_id', 'reversed_entry_id', 'is_locked']); // FKs might need explicit drop
        });

        Schema::table('accounting_periods', function (Blueprint $table) {
            $table->dropColumn(['status', 'vat_locked']);
        });

        Schema::table('chart_of_accounts', function (Blueprint $table) {
             $table->dropColumn(['sub_type', 'normal_balance', 'is_system', 'is_locked', 'currency']);
        });
    }
};
