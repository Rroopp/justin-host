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
            // Add 'set' to the type enum
            $table->enum('type', ['store', 'hospital', 'consignment', 'set'])->default('store')->change();
            
            // Link sets to assets
            $table->foreignId('asset_id')->nullable()->constrained('assets')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropForeign(['asset_id']);
            $table->dropColumn('asset_id');
            $table->enum('type', ['store', 'hospital', 'consignment'])->default('store')->change();
        });
    }
};
