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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('setting_type')->default('string'); // string, integer, boolean, json
            $table->string('category')->default('system'); // system, company, security, inventory
            $table->text('description')->nullable();
            $table->text('change_reason')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            
            $table->index('key');
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
