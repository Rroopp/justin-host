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
        Schema::table('payroll_earnings', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_earnings', 'reimbursements')) {
                $table->decimal('reimbursements', 10, 2)->default(0)->after('bonuses');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_earnings', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_earnings', 'reimbursements')) {
                $table->dropColumn('reimbursements');
            }
        });
    }
};
