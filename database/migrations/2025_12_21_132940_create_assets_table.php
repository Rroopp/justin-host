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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category');
            $table->decimal('purchase_price', 10, 2);
            $table->date('purchase_date');
            $table->enum('depreciation_method', ['straight_line', 'declining_balance', 'sum_of_years', 'units_of_production'])->default('straight_line');
            $table->decimal('useful_life_years', 5, 2);
            $table->decimal('salvage_value', 10, 2)->default(0);
            $table->text('description')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('location')->nullable();
            $table->string('allocated_to')->nullable();
            $table->string('allocated_to_type')->nullable(); // staff, department, etc.
            $table->date('allocation_date')->nullable();
            $table->timestamps();
            
            $table->index('category');
            $table->index('allocated_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
