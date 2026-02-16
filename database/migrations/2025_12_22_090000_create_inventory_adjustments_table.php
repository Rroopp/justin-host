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
        Schema::create('inventory_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained('inventory_master')->onDelete('cascade');
            $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->enum('adjustment_type', ['increase', 'decrease', 'set']);
            $table->integer('quantity'); // amount changed (or new value for "set")
            $table->integer('old_quantity');
            $table->integer('new_quantity');
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('inventory_id');
            $table->index('staff_id');
            $table->index('adjustment_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_adjustments');
    }
};


