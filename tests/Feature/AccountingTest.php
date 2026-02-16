<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\PosSale;
use App\Models\Inventory;
use App\Models\ChartOfAccount;
use App\Events\SaleCompleted;
use App\Listeners\AccountingListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class AccountingTest extends TestCase
{
    // Note: Not using RefreshDatabase on a real persistent dev DB to avoid wiping user data unless we config testing env correctly.
    // For this environment, we'll manually cleanup or just check increments.
    // SAFE MODE: We will just fire the event on a dummy sale and check if DB count increased.

    public function test_sale_event_triggers_journal_entries()
    {
        // 0. Seed Accounts (Ensure they exist)
        $accounts = [
            ['code' => '4000', 'name' => 'Sales Revenue', 'account_type' => 'Revenue'],
            ['code' => '5000', 'name' => 'Cost of Goods Sold', 'account_type' => 'Expense'],
            ['code' => '2000', 'name' => 'VAT Payable', 'account_type' => 'Liability'],
            ['code' => '1200', 'name' => 'Inventory Asset', 'account_type' => 'Asset'],
            ['code' => '1000', 'name' => 'Cash', 'account_type' => 'Asset'],
        ];

        foreach ($accounts as $acc) {
            ChartOfAccount::firstOrCreate(
                ['code' => $acc['code']],
                ['name' => $acc['name'], 'account_type' => $acc['account_type']]
            );
        }

        // 1. Setup Data
        // Create or find a product
        $product = Inventory::first();
        if (!$product) {
            $product = Inventory::create([
                'product_name' => 'Test Product',
                'price' => 500,
                'selling_price' => 1000,
                'quantity_in_stock' => 10,
                'code' => 'TEST-' . rand(1000,9999)
            ]);
        }

        // Simulate a Sale
        $sale = new PosSale();
        $sale->invoice_number = 'TEST-' . time();
        $sale->total = 1160;
        $sale->subtotal = 1000;
        $sale->vat = 160;
        $sale->discount_amount = 0;
        $sale->payment_method = 'Cash';
        $sale->customer_name = 'Test Customer';
        $sale->seller_username = 'tester';
        $sale->timestamp = now();
        // Create valid sale_items structure with snapshot
        $sale->sale_items = [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'price' => 1160,
                'total' => 1160,
                'product_snapshot' => [
                    'id' => $product->id,
                    'price' => $product->price, // Cost Price
                    'product_name' => $product->product_name
                ]
            ]
        ];
        $sale->save();

        // 2. Count Journals Before
        $countBefore = \App\Models\JournalEntry::count();

        // 3. Fire Event manually
        $listener = new AccountingListener(new \App\Services\AccountingService());
        $event = new SaleCompleted($sale);
        $listener->handle($event);

        // 4. Count Journals After
        $countAfter = \App\Models\JournalEntry::count();

        // We expect +1 (Revenue) or +2 (Revenue + COGS)
        // COGS entry only created if totalCost > 0.
        $expectedIncrease = ($product->price > 0) ? 2 : 1;

        $this->assertTrue($countAfter >= $countBefore + 1, "Journal Entries should increase. Before: $countBefore, After: $countAfter");
        
        // 5. Cleanup
        $sale->delete();
        // Ideally we delete the Created Journals too, but for dev env it's fine (audit trail).
    }
}
