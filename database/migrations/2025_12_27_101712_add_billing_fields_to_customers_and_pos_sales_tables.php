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
            $table->enum('billing_preference', ['itemised', 'package', 'hybrid'])
                  ->default('itemised')
                  ->after('customer_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe check or empty to allow rollback of empty migration
        if (Schema::hasColumn('customers', 'billing_preference')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('billing_preference');
            });
        }
    }
};
