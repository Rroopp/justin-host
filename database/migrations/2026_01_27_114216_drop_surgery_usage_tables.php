<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('surgery_usage_items');
        Schema::dropIfExists('surgery_usage');
    }

    public function down(): void
    {
        // Recreate if needed (not recommended)
        Schema::create('surgery_usage', function (Blueprint $table) {
            $table->id();
            $table->date('surgery_date');
            $table->string('patient_name')->nullable();
            $table->string('patient_number')->nullable();
            $table->string('surgeon_name')->nullable();
            $table->string('facility_name')->nullable();
            $table->foreignId('set_location_id')->nullable()->constrained('locations')->onDelete('set null');
            $table->foreignId('user_id')->constrained('staff')->onDelete('cascade');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('surgery_usage_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surgery_usage_id')->constrained('surgery_usage')->onDelete('cascade');
            $table->foreignId('inventory_id')->constrained('inventory_master')->onDelete('cascade');
            $table->foreignId('batch_id')->nullable()->constrained('batches')->onDelete('set null');
            $table->integer('quantity');
            $table->boolean('from_set')->default(true);
            $table->timestamps();
        });
    }
};
