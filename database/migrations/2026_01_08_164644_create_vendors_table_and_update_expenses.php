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
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('kra_pin')->nullable()->comment('Tax ID');
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('vendor_id')->nullable()->after('category_id')->constrained('vendors')->onDelete('set null');
            $table->string('status')->default('paid')->after('amount')->comment('paid, unpaid, partial');
            $table->date('due_date')->nullable()->after('expense_date');
            $table->string('reference_number')->nullable()->after('payee')->comment('Invoice Number');
            
            // Allow payment_account_id to be null for UNPAID bills
            $table->unsignedBigInteger('payment_account_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
            $table->dropColumn(['vendor_id', 'status', 'due_date', 'reference_number']);
            // We cannot easily revert payment_account_id to not null if there are null values, 
            // but strictly speaking we should reverse the change.
        });

        Schema::dropIfExists('vendors');
    }
};
