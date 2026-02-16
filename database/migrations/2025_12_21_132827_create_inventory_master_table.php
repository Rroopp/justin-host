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
        Schema::create('inventory_master', function (Blueprint $table) {
            $table->id();
            $table->string('product_name');
            $table->string('category')->nullable();
            $table->string('subcategory')->nullable();
            $table->string('code')->nullable()->unique();
            $table->string('unit')->default('pcs');
            $table->integer('quantity_in_stock')->default(0);
            $table->decimal('price', 10, 2)->default(0); // Buying price
            $table->decimal('selling_price', 10, 2)->default(0);
            $table->decimal('profit', 10, 2)->nullable();
            $table->string('manufacturer')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index('category');
            $table->index('subcategory');
            $table->index('code');
            $table->index('quantity_in_stock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_master');
    }
};
