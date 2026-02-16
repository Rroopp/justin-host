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
        Schema::create('set_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations')->onDelete('cascade');
            $table->foreignId('inventory_id')->constrained('inventory_master')->onDelete('cascade');
            $table->integer('standard_quantity');
            $table->text('notes')->nullable();
            $table->unique(['location_id', 'inventory_id']); // Prevent duplicate items in same set
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('set_contents');
    }
};
