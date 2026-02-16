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
        Schema::create('case_reservations', function (Blueprint $table) {
            $table->id();
            $table->string('case_number')->unique();
            $table->string('patient_name');
            $table->string('patient_id')->nullable();
            $table->string('surgeon_name')->nullable();
            $table->string('procedure_name')->nullable();
            $table->dateTime('surgery_date');
            
            // Link to location where surgery will happen / stock reserved from
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            
            // Status workflow: draft -> confirmed (stock reserved) -> completed (usage recorded) -> cancelled (stock returned)
            $table->enum('status', ['draft', 'confirmed', 'completed', 'cancelled'])->default('draft');
            
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('case_reservation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_reservation_id')->constrained('case_reservations')->cascadeOnDelete();
            $table->foreignId('inventory_id')->constrained('inventory_master')->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();
            
            $table->integer('quantity_reserved');
            $table->integer('quantity_used')->default(0);
            
            // Item status: pending (reserved), used (consumed), returned (unused), partial (some used, some returned)
            $table->enum('status', ['pending', 'used', 'returned', 'partial'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_reservation_items');
        Schema::dropIfExists('case_reservations');
    }
};
