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
        if (Schema::hasTable('rentals') && !Schema::hasColumn('rentals', 'pos_sale_id')) {
            Schema::table('rentals', function (Blueprint $table) {
                $table->foreignId('pos_sale_id')->nullable()->after('customer_id')->constrained('pos_sales')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('rentals') && Schema::hasColumn('rentals', 'pos_sale_id')) {
            Schema::table('rentals', function (Blueprint $table) {
                $table->dropForeign(['pos_sale_id']);
                $table->dropColumn('pos_sale_id');
            });
        }
    }
};
