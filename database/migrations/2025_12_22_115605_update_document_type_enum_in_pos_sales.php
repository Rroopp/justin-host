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
            // Changing enum to string is the safest way to support new types without dropping column
            // Note: In standard SQL 'CHANGE' or 'MODIFY' is needed. Laravel's change() needs doctrine/dbal.
            // Assuming MySQL/MariaDB environment.
            $table->string('document_type')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_sales', function (Blueprint $table) {
             // Reverting is tricky if new data exists. We can just leave it as string or revert to enum.
             // For safety in dev, we'll try to revert.
            //$table->enum('document_type', ['receipt', 'invoice', 'delivery_note'])->default('receipt')->change();
        });
    }
};
