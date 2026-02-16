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
        Schema::create('set_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surgical_set_id')->constrained('surgical_sets')->cascadeOnDelete();
            $table->foreignId('case_reservation_id')->nullable()->constrained('case_reservations')->nullOnDelete();
            
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete(); // Destination (e.g. Hospital)
            
            $table->timestamp('dispatched_at')->useCurrent();
            $table->timestamp('returned_at')->nullable();
            
            $table->foreignId('dispatched_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->enum('status', ['dispatched', 'returned', 'reconciled'])->default('dispatched');
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('set_movements');
    }
};
