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
        // 1. Attributes Definition
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Material", "Size"
            $table->string('slug')->unique(); // e.g., "material", "size"
            $table->string('type')->default('text'); // text, select, number, boolean, date
            $table->string('unit')->nullable(); // e.g., "mm", "kg"
            $table->boolean('is_required')->default(false);
            $table->timestamps();
        });

        // 2. Options for 'select' type attributes
        Schema::create('product_attribute_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_attribute_id')->constrained()->cascadeOnDelete();
            $table->string('value'); // e.g., "Titanium", "Stainless Steel"
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 3. Link Attributes to Categories (Many-to-Many)
        // Note: Assuming 'categories' table exists. 
        // If not, we might need to reference 'product_categories' if that's what it's called, 
        // but analysis showed App\Models\Category exists.
        Schema::create('category_product_attribute', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_attribute_id')->constrained()->cascadeOnDelete();
            $table->integer('sort_order')->default(0); // To optimize form ordering
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_product_attribute');
        Schema::dropIfExists('product_attribute_options');
        Schema::dropIfExists('product_attributes');
    }
};
