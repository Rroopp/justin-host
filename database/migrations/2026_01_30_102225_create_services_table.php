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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->enum('service_type', ['medical', 'equipment'])->default('medical');
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('service_categories')->onDelete('set null');
            $table->decimal('base_price', 10, 2)->default(0);
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->onDelete('set null');
            $table->integer('duration_minutes')->default(30); // For scheduling
            $table->boolean('requires_equipment')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
