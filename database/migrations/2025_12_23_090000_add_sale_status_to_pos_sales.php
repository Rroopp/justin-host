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
        Schema::table('pos_sales', function (Blueprint $table) {
            // Add sale_status column with 'completed' as default for existing sales
            $table->enum('sale_status', ['completed', 'consignment', 'returned'])
                  ->default('completed')
                  ->after('payment_status');
            
            // Index for faster queries on status
            $table->index('sale_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_sales', function (Blueprint $table) {
            $table->dropColumn('sale_status');
        });
    }
};
