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
        Schema::create('set_instruments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surgical_set_id')->constrained('surgical_sets')->cascadeOnDelete();
            
            $table->string('name');
            $table->foreignId('inventory_id')->nullable()->constrained('inventory_master')->nullOnDelete(); // Link to Product Definition if exists
            
            $table->string('serial_number')->nullable();
            $table->integer('quantity')->default(1);
            
            $table->enum('condition', ['good', 'damaged', 'missing', 'maintenance'])->default('good');
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
        Schema::dropIfExists('set_instruments');
    }
};
