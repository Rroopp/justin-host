<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_sale_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_sale_id')->constrained('pos_sales')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('payment_method', 20)->default('Cash');
            $table->date('payment_date')->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('payment_notes')->nullable();
            $table->string('received_by')->nullable();
            $table->timestamps();

            $table->index('pos_sale_id');
            $table->index('payment_date');
            $table->index('payment_method');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_sale_payments');
    }
};


