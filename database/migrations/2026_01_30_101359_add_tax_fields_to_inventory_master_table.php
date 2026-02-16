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
            $table->boolean('is_vatable')->default(false)->after('selling_price');
            $table->foreignId('tax_rate_id')->nullable()->after('is_vatable')->constrained('tax_rates')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_master', function (Blueprint $table) {
            $table->dropForeign(['tax_rate_id']);
            $table->dropColumn(['is_vatable', 'tax_rate_id']);
        });
    }
};
