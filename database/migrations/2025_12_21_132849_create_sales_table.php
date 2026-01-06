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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained('inventory_master')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('inventory_master')->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('total', 10, 2);
            $table->string('seller_username');
            $table->text('product_snapshot'); // JSON product data at time of sale
            $table->date('date')->default(now());
            $table->timestamps();
            
            $table->index('inventory_id');
            $table->index('seller_username');
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
