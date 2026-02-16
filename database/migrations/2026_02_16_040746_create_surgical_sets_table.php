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
        Schema::create('surgical_sets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete(); // The "Mobile Store" location
            
            $table->enum('status', ['available', 'in_surgery', 'in_transit', 'maintenance', 'incomplete'])->default('available');
            $table->enum('sterilization_status', ['sterile', 'non_sterile', 'expired'])->default('non_sterile');
            $table->date('last_service_date')->nullable();
            
            $table->foreignId('responsible_staff_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surgical_sets');
    }
};
