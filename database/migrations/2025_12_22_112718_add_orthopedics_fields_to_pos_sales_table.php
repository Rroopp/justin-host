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
        Schema::table('pos_sales', function (Blueprint $table) {
            $table->string('patient_name')->nullable()->after('customer_id');
            $table->string('patient_number')->nullable()->after('patient_name');
            $table->string('facility_name')->nullable()->after('patient_number');
            $table->string('surgeon_name')->nullable()->after('facility_name');
            $table->string('nurse_name')->nullable()->after('surgeon_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_sales', function (Blueprint $table) {
            $table->dropColumn([
                'patient_name',
                'patient_number',
                'facility_name',
                'surgeon_name',
                'nurse_name'
            ]);
        });
    }
};
