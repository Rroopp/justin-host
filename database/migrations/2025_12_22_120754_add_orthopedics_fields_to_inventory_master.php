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
            if (!Schema::hasColumn('inventory_master', 'batch_number')) {
                $table->string('batch_number')->nullable()->after('manufacturer');
            }
            if (!Schema::hasColumn('inventory_master', 'expiry_date')) {
                $table->date('expiry_date')->nullable()->after('batch_number');
            }
            if (!Schema::hasColumn('inventory_master', 'is_rentable')) {
                $table->boolean('is_rentable')->default(false)->after('expiry_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_master', function (Blueprint $table) {
            $table->dropColumn(['batch_number', 'expiry_date', 'is_rentable']);
        });
    }
};
