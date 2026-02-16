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
        Schema::table('batches', function (Blueprint $table) {
            // Serial number tracking
            $table->string('serial_number')->nullable()->after('batch_number');
            $table->boolean('is_serialized')->default(false)->after('serial_number');
            
            // Status tracking
            $table->enum('status', ['available', 'sold', 'recalled', 'expired', 'damaged', 'returned'])
                  ->default('available')->after('is_serialized');
            
            // Manufacturer tracking (link to suppliers)
            $table->foreignId('manufacturer_id')->nullable()->after('inventory_id')
                  ->constrained('suppliers')->nullOnDelete();
            
            // Recall tracking
            $table->enum('recall_status', ['none', 'pending', 'active', 'resolved'])
                  ->default('none')->after('status');
            $table->date('recall_date')->nullable()->after('recall_status');
            $table->text('recall_reason')->nullable()->after('recall_date');
            
            // Traceability
            $table->foreignId('sold_to_customer_id')->nullable()->after('recall_reason')
                  ->constrained('customers')->nullOnDelete();
            $table->date('sold_date')->nullable()->after('sold_to_customer_id');
            
            // Add index for serial number lookups
            $table->index('serial_number');
            $table->index('status');
            $table->index('recall_status');
        });

        // Add serialization flag to inventory_master
        Schema::table('inventory_master', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_master', 'requires_serial_tracking')) {
                $table->boolean('requires_serial_tracking')->default(false)->after('is_rentable');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->dropForeign(['manufacturer_id']);
            $table->dropForeign(['sold_to_customer_id']);
            $table->dropIndex(['serial_number']);
            $table->dropIndex(['status']);
            $table->dropIndex(['recall_status']);
            
            $table->dropColumn([
                'serial_number',
                'is_serialized',
                'status',
                'manufacturer_id',
                'recall_status',
                'recall_date',
                'recall_reason',
                'sold_to_customer_id',
                'sold_date',
            ]);
        });

        Schema::table('inventory_master', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_master', 'requires_serial_tracking')) {
                $table->dropColumn('requires_serial_tracking');
            }
        });
    }
};
