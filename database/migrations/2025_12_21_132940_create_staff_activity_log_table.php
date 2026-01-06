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
        Schema::create('staff_activity_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->onDelete('cascade');
            $table->string('username');
            $table->string('activity_type'); // login, sale, inventory_update, etc.
            $table->text('description');
            $table->string('ip_address')->nullable();
            $table->text('metadata')->nullable(); // JSON additional data
            $table->timestamps();
            
            $table->index('staff_id');
            $table->index('username');
            $table->index('activity_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_activity_log');
    }
};
