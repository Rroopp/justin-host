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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('payee');
            $table->text('description');
            $table->decimal('amount', 10, 2);
            $table->date('expense_date');
            $table->foreignId('category_id')->nullable()->constrained('chart_of_accounts')->onDelete('set null');
            $table->foreignId('payment_account_id')->nullable()->constrained('chart_of_accounts')->onDelete('set null');
            $table->string('created_by');
            $table->timestamps();
            
            $table->index('expense_date');
            $table->index('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
