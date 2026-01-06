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
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'customer_code')) {
                $table->string('customer_code')->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('customers', 'customer_type')) {
                $table->string('customer_type')->default('individual')->after('customer_code');
            }
            if (!Schema::hasColumn('customers', 'contact_person')) {
                $table->string('contact_person')->nullable()->after('facility');
            }
            if (!Schema::hasColumn('customers', 'city')) {
                $table->string('city')->nullable()->after('address');
            }
            if (!Schema::hasColumn('customers', 'postal_code')) {
                $table->string('postal_code')->nullable()->after('city');
            }
            if (!Schema::hasColumn('customers', 'country')) {
                $table->string('country')->default('Kenya')->after('postal_code');
            }
            if (!Schema::hasColumn('customers', 'tax_number')) {
                $table->string('tax_number')->nullable()->after('country');
            }
            if (!Schema::hasColumn('customers', 'payment_terms')) {
                $table->text('payment_terms')->nullable()->after('tax_number');
            }
            if (!Schema::hasColumn('customers', 'credit_limit')) {
                $table->decimal('credit_limit', 10, 2)->default(0)->after('payment_terms');
            }
            if (!Schema::hasColumn('customers', 'current_balance')) {
                $table->decimal('current_balance', 10, 2)->default(0)->after('credit_limit');
            }
            if (!Schema::hasColumn('customers', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('current_balance');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'is_active')) {
                $table->dropColumn('is_active');
            }
            if (Schema::hasColumn('customers', 'current_balance')) {
                $table->dropColumn('current_balance');
            }
            if (Schema::hasColumn('customers', 'credit_limit')) {
                $table->dropColumn('credit_limit');
            }
            if (Schema::hasColumn('customers', 'payment_terms')) {
                $table->dropColumn('payment_terms');
            }
            if (Schema::hasColumn('customers', 'tax_number')) {
                $table->dropColumn('tax_number');
            }
            if (Schema::hasColumn('customers', 'country')) {
                $table->dropColumn('country');
            }
            if (Schema::hasColumn('customers', 'postal_code')) {
                $table->dropColumn('postal_code');
            }
            if (Schema::hasColumn('customers', 'city')) {
                $table->dropColumn('city');
            }
            if (Schema::hasColumn('customers', 'contact_person')) {
                $table->dropColumn('contact_person');
            }
            if (Schema::hasColumn('customers', 'customer_type')) {
                $table->dropColumn('customer_type');
            }
            if (Schema::hasColumn('customers', 'customer_code')) {
                $table->dropUnique(['customer_code']);
                $table->dropColumn('customer_code');
            }
        });
    }
};


