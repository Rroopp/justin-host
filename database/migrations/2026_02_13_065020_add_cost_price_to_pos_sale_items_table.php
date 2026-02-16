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
        if (Schema::hasTable('pos_sale_items')) {
            Schema::table('pos_sale_items', function (Blueprint $table) {
                if (!Schema::hasColumn('pos_sale_items', 'cost_price')) {
                    $table->decimal('cost_price', 10, 2)->default(0)->after('price');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_sale_items', function (Blueprint $table) {
            $table->dropColumn('cost_price');
        });
    }
};
