<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->string('preference_key');
            $table->text('preference_value')->nullable(); // JSON or string
            $table->timestamps();

            $table->unique(['staff_id', 'preference_key']);
            $table->index('staff_id');
            $table->index('preference_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};





