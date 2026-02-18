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
        Schema::table('surgical_sets', function (Blueprint $table) {
            // Change status from enum to string to remove the check constraint
            // This allows values like 'dispatched', 'dirty', etc. defined in the model constants
            $table->string('status')->default('available')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surgical_sets', function (Blueprint $table) {
            // Revert back to enum - Note: This will fail if there are values in database 
            // that don't match the allowed enum values (like 'dispatched')
            // So we might choose not to revert or handle data migration strictly.
            // For now, we try to revert but it's risky.
            // $table->enum('status', ['available', 'in_surgery', 'in_transit', 'maintenance', 'incomplete'])->default('available')->change();
        });
    }
};
