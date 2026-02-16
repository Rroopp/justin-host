<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class VerifyAccounting extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:verify-accounting';
    protected $description = 'Verify that POS Sales trigger Journal Entries';

    public function handle()
    {
        try {
            $this->info('Starting Accounting Verification...');

            // ... (rest of code) ...
            
            // 0. Seed Accounts
            $accounts = [
                ['code' => '4000', 'name' => 'Sales Revenue', 'account_type' => 'Income'], // Corrected from Revenue
                ['code' => '5000', 'name' => 'Cost of Goods Sold', 'account_type' => 'Expense'],
                ['code' => '2000', 'name' => 'VAT Payable', 'account_type' => 'Liability'],
                ['code' => '1200', 'name' => 'Inventory Asset', 'account_type' => 'Asset'],
                ['code' => '1000', 'name' => 'Cash', 'account_type' => 'Asset'],
                ['code' => '1010', 'name' => 'Bank', 'account_type' => 'Asset'],
            ];

            foreach ($accounts as $acc) {
                // Ensure unique name too? Or just code. Setup might fail if name is duplicates?
                // Using updateOrCreate to be safer if types changed
                \App\Models\ChartOfAccount::updateOrCreate(
                    ['code' => $acc['code']],
                    ['name' => $acc['name'], 'account_type' => $acc['account_type']]
                );
            }
            $this->info('Accounts Seeded.');

            // 1. Create Prod & Sale
            $this->info('Creating Inventory Item...');
            $timestamp = time();
            $product = \App\Models\Inventory::create([
                'code' => 'TEST-VERIFY-' . $timestamp,
                'product_name' => 'Verification Product ' . $timestamp, 
                'price' => 500, 
                'selling_price' => 1000, 
                'quantity_in_stock' => 100,
                'size_unit' => 'pcs' // Required field
            ]);

            $sale = new \App\Models\PosSale();
            $sale->invoice_number = 'VERIFY-' . $timestamp;
            $sale->document_type = 'receipt'; // Required field
            $sale->total = 1160;
            $sale->subtotal = 1000;
            $sale->vat = 160;
            $sale->discount_amount = 0;
            $sale->payment_method = 'Cash';
            $sale->customer_name = 'Verify Bot';
            $sale->seller_username = 'bot';
            $sale->timestamp = now();
            $sale->sale_items = [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'price' => 1160,
                    'total' => 1160,
                    'product_snapshot' => [
                        'id' => $product->id,
                        'price' => $product->price,
                        'product_name' => $product->product_name
                    ]
                ]
            ];
            $sale->save();
            $this->info("Sale #{$sale->id} Created.");

            // 2. Count Before
            $countBefore = \App\Models\JournalEntry::count();

            // 3. Fire Event manually
            $this->info('Firing SaleCompleted Event...');
            \App\Events\SaleCompleted::dispatch($sale);

            // 4. Count After
            $countAfter = \App\Models\JournalEntry::count();
            $diff = $countAfter - $countBefore;

            if ($diff >= 1) {
                $this->info("SUCCESS: Created {$diff} Journal Entries.");
                $sale->delete();
                return 0;
            } else {
                $this->error("FAILURE: No Journal Entries created.");
                return 1;
            }

        } catch (\Exception $e) {
            $this->error("CRASH: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}
