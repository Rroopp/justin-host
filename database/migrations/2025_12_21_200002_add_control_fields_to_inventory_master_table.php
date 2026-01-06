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
        Schema::table('inventory_master', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_master', 'min_stock_level')) {
                $table->integer('min_stock_level')->default(10)->after('quantity_in_stock');
            }
            if (!Schema::hasColumn('inventory_master', 'max_stock')) {
                $table->integer('max_stock')->default(100)->after('min_stock_level');
            }
            if (!Schema::hasColumn('inventory_master', 'reorder_threshold')) {
                $table->integer('reorder_threshold')->default(20)->after('max_stock');
            }
            if (!Schema::hasColumn('inventory_master', 'expiry_date')) {
                $table->date('expiry_date')->nullable()->after('reorder_threshold');
            }
            if (!Schema::hasColumn('inventory_master', 'batch_number')) {
                $table->string('batch_number')->nullable()->after('expiry_date');
            }
            if (!Schema::hasColumn('inventory_master', 'country_of_manufacture')) {
                $table->string('country_of_manufacture')->nullable()->after('batch_number');
            }
            if (!Schema::hasColumn('inventory_master', 'packaging_unit')) {
                $table->string('packaging_unit')->nullable()->after('country_of_manufacture');
            }
            // Flexible storage for subcategory-specific fields (matches old FastAPI subcategory tables)
            if (!Schema::hasColumn('inventory_master', 'attributes')) {
                $table->json('attributes')->nullable()->after('description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_master', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_master', 'attributes')) {
                $table->dropColumn('attributes');
            }
            if (Schema::hasColumn('inventory_master', 'packaging_unit')) {
                $table->dropColumn('packaging_unit');
            }
            if (Schema::hasColumn('inventory_master', 'country_of_manufacture')) {
                $table->dropColumn('country_of_manufacture');
            }
            if (Schema::hasColumn('inventory_master', 'batch_number')) {
                $table->dropColumn('batch_number');
            }
            if (Schema::hasColumn('inventory_master', 'expiry_date')) {
                $table->dropColumn('expiry_date');
            }
            if (Schema::hasColumn('inventory_master', 'reorder_threshold')) {
                $table->dropColumn('reorder_threshold');
            }
            if (Schema::hasColumn('inventory_master', 'max_stock')) {
                $table->dropColumn('max_stock');
            }
            if (Schema::hasColumn('inventory_master', 'min_stock_level')) {
                $table->dropColumn('min_stock_level');
            }
        });
    }
};


