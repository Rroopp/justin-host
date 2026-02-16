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
            if (!Schema::hasColumn('inventory_master', 'moving_average_cost')) {
                $table->decimal('moving_average_cost', 15, 2)->default(0.00)->after('price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_master', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_master', 'moving_average_cost')) {
                $table->dropColumn('moving_average_cost');
            }
        });
    }
};
