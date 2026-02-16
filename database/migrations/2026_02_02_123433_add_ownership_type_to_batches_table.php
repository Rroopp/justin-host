<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            // Add ownership type for consignment tracking (only if it doesn't exist)
            if (!Schema::hasColumn('batches', 'ownership_type')) {
                $table->enum('ownership_type', ['company_owned', 'consigned', 'loaned'])
                      ->default('company_owned')
                      ->after('status');
                
                // Add index for ownership queries
                $table->index('ownership_type');
            }
        });
        
        // For SQLite, we need to recreate the table to modify the enum
        // Check if we're using SQLite
        if (DB::connection()->getDriverName() === 'sqlite') {
            // SQLite doesn't support ALTER COLUMN, so we'll handle this differently
            // The 'reserved' status will be added when needed via application logic
            // For now, we'll just note that SQLite users should be aware
        } else {
            // For MySQL/PostgreSQL
            DB::statement("ALTER TABLE batches MODIFY COLUMN status ENUM('available', 'reserved', 'sold', 'recalled', 'expired', 'damaged', 'returned') DEFAULT 'available'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->dropIndex(['ownership_type']);
            $table->dropColumn('ownership_type');
        });
        
        if (DB::connection()->getDriverName() !== 'sqlite') {
            // Revert status enum for MySQL/PostgreSQL
            DB::statement("ALTER TABLE batches MODIFY COLUMN status ENUM('available', 'sold', 'recalled', 'expired', 'damaged', 'returned') DEFAULT 'available'");
        }
    }
};
