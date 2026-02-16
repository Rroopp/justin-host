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
        Schema::create('case_reservation_surgical_set', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_reservation_id')->constrained('case_reservations')->cascadeOnDelete();
            $table->foreignId('surgical_set_id')->constrained('surgical_sets')->cascadeOnDelete();
            
            $table->enum('status', ['reserved', 'dispatched', 'returned', 'completed'])->default('reserved');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_reservation_surgical_set');
    }
};
