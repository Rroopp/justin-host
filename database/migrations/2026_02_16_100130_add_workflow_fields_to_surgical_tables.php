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
        Schema::table('set_instruments', function (Blueprint $table) {
            // Enhanced lifecycle tracking
            if (!Schema::hasColumn('set_instruments', 'status')) {
                $table->string('status')->default('good')->after('quantity'); // good, damaged, missing, maintenance
            }
            if (!Schema::hasColumn('set_instruments', 'current_location_id')) {
                $table->foreignId('current_location_id')->nullable()->constrained('locations')->nullOnDelete()->after('surgical_set_id');
            }
        });

        Schema::table('inventory_master', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_master', 'asset_profile_id')) {
                $table->foreignId('asset_profile_id')->nullable()->constrained('assets')->nullOnDelete()->after('type');
            }
        });
        
        // Modify surgical_sets status enum to include new workflow states
        // Using raw statement for Enum modification (Only for MySQL/MariaDB)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE surgical_sets MODIFY COLUMN status ENUM('available', 'in_surgery', 'in_transit', 'maintenance', 'incomplete', 'dispatched', 'dirty', 'sterilizing', 'in_use') DEFAULT 'available'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('set_instruments', function (Blueprint $table) {
            $table->dropForeign(['current_location_id']);
            $table->dropColumn(['current_location_id', 'status']);
        });

        Schema::table('inventory_master', function (Blueprint $table) {
            $table->dropForeign(['asset_profile_id']);
            $table->dropColumn(['asset_profile_id']);
        });
    }
};
