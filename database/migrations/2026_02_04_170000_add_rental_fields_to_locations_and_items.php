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
        Schema::table('locations', function (Blueprint $table) {
            if (!Schema::hasColumn('locations', 'rental_price')) {
                $table->decimal('rental_price', 15, 2)->default(0)->after('type');
            }
        });

        Schema::table('case_reservation_items', function (Blueprint $table) {
            if (!Schema::hasColumn('case_reservation_items', 'custom_price')) {
                $table->decimal('custom_price', 15, 2)->nullable()->after('inventory_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('rental_price');
        });

        Schema::table('case_reservation_items', function (Blueprint $table) {
            $table->dropColumn('custom_price');
        });
    }
};
