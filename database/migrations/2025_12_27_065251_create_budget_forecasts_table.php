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
        Schema::create('budget_forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained('budgets')->onDelete('cascade');
            $table->string('category');
            $table->json('historical_data')->nullable(); // Store past trends
            $table->decimal('projected_amount', 15, 2)->default(0);
            $table->decimal('confidence_level', 5, 2)->default(0); // 0-100%
            $table->decimal('growth_rate', 5, 2)->default(0); // Percentage
            $table->decimal('seasonality_factor', 5, 2)->default(1); // Multiplier
            $table->timestamps();
            
            $table->index(['budget_id', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_forecasts');
    }
};
