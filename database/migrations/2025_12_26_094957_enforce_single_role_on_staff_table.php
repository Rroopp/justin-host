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
        Schema::table('staff', function (Blueprint $table) {
            // Drop JSON roles array
            if (Schema::hasColumn('staff', 'roles')) {
                $table->dropColumn('roles');
            }
            
            // Rename primary_role to role if it exists (assuming it does) or create if not?
            // Migration says rename, but let's be safe.
            // If we strictly rename, we assume primary_role has the correct value.
            if (Schema::hasColumn('staff', 'primary_role')) {
                $table->renameColumn('primary_role', 'role');
            } else {
                 if (!Schema::hasColumn('staff', 'role')) {
                    $table->string('role')->default('staff')->after('full_name');
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            // Rename role back to primary_role
             if (Schema::hasColumn('staff', 'role')) {
                $table->renameColumn('role', 'primary_role');
            }
            
            // Re-create roles array (empty initially)
            if (!Schema::hasColumn('staff', 'roles')) {
                $table->json('roles')->nullable()->after('full_name');
            }
        });
    }
};
