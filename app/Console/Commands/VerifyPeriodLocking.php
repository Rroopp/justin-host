<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class VerifyPeriodLocking extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:verify-period-locking';
    protected $description = 'Verify that closed accounting periods block transactions';

    public function handle()
    {
        $this->info('Starting Period Locking Verification...');
        
        // 1. Create a Closed Period for "Last Year"
        $start = \Carbon\Carbon::now()->subYear()->startOfYear();
        $end = \Carbon\Carbon::now()->subYear()->endOfYear();
        
        \App\Models\AccountingPeriod::create([
            'period_name' => 'Closed Year ' . $start->year,
            'start_date' => $start,
            'end_date' => $end,
            'is_closed' => true,
        ]);
        
        $this->info("Created Closed Period: {$start->toDateString()} to {$end->toDateString()}");

        // 2. Attempt to create a Sale in that period
        $sale = new \App\Models\PosSale();
        $sale->invoice_number = 'BACKDATED-TEST';
        $sale->total = 1000;
        $sale->vat = 160;
        $sale->created_at = $start->copy()->addMonth(); // Date inside closed period
        // ... (other required fields mock)
        $sale->payment_method = 'Cash'; 
        
        // We don't save the sale because the check is in postSaleJournal which takes a sale object.
        // Actually, normally the sale is saved first THEN posted. 
        // But postSaleJournal checks the period before DB transaction.
        
        $service = new \App\Services\AccountingService();
        
        $this->info("Attempting to post journal for date: {$sale->created_at->toDateString()}");
        
        // Capture output/logs? Or just check result.
        // The service returns null/void on failure and logs error.
        
        $countBefore = \App\Models\JournalEntry::count();
        $service->postSaleJournal($sale);
        $countAfter = \App\Models\JournalEntry::count();
        
        if ($countBefore === $countAfter) {
            $this->info("SUCCESS: Transaction was BLOCKED.");
            return 0;
        } else {
            $this->error("FAILURE: Transaction was POSTED.");
            return 1;
        }
    }
}
