<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\ChartOfAccount;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix Cash Account
        ChartOfAccount::updateOrCreate(
            ['code' => 'CASH'],
            [
                'name' => 'Cash on Hand',
                'account_type' => 'Asset',
                'sub_type' => 'Current Asset',
                'normal_balance' => 'DEBIT',
                'is_system' => true,
                'is_active' => true,
            ]
        );

        // Fix M-Pesa Account
        ChartOfAccount::updateOrCreate(
            ['code' => '1020'],
            [
                'name' => 'Mobile Money (M-Pesa)',
                'account_type' => 'Asset',
                'sub_type' => 'Current Asset',
                'normal_balance' => 'DEBIT',
                'is_system' => false,
                'is_active' => true,
            ]
        );

        // Fix Bank Account
        ChartOfAccount::updateOrCreate(
            ['code' => '1010'],
            [
                'name' => 'Bank Account',
                'account_type' => 'Asset',
                'sub_type' => 'Current Asset',
                'normal_balance' => 'DEBIT',
                'is_system' => false,
                'is_active' => true,
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse needed as we just want to ensure they exist
    }
};
