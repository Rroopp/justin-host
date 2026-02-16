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
        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->enum('template_type', ['receipt', 'invoice', 'delivery_note']);
            $table->string('template_name');
            $table->text('template_data'); // JSON template configuration
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            
            $table->index('template_type');
            $table->index('is_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_templates');
    }
};
