<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class VerifyWAC extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:verify-wac';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify Weighted Average Cost (WAC) Logic';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting WAC Verification...');
        
        // Setup Service
        $inventoryService = new \App\Services\InventoryService();

        // 1. Create Fresh Product
        $timestamp = time();
        $product = \App\Models\Inventory::create([
            'code' => 'WAC-TEST-' . $timestamp,
            'product_name' => 'WAC Product ' . $timestamp, 
            'price' => 100, // Initial "Buying Price"
            'selling_price' => 200, 
            'quantity_in_stock' => 0,
            'moving_average_cost' => 0,
            'size_unit' => 'pcs'
        ]);

        $this->info("Created Product {$product->code}. Initial Stock: 0, WAC: 0");

        // TEST CASE 1: Receive 10 @ 100
        $inventoryService->receiveStock($product, 10, 100);
        $product->refresh();
        
        $this->info("Step 1: Received 10 @ 100. New Stock: {$product->quantity_in_stock}, WAC: {$product->moving_average_cost}");
        
        if ($product->moving_average_cost != 100) {
            $this->error("FAILURE: Expected WAC 100, got {$product->moving_average_cost}");
            return 1;
        }

        // TEST CASE 2: Receive 10 @ 200
        // ((10 * 100) + (10 * 200)) / 20 = (1000 + 2000) / 20 = 150
        $inventoryService->receiveStock($product, 10, 200);
        $product->refresh();

        $this->info("Step 2: Received 10 @ 200. New Stock: {$product->quantity_in_stock}, WAC: {$product->moving_average_cost}");

        if ($product->moving_average_cost != 150) {
            $this->error("FAILURE: Expected WAC 150, got {$product->moving_average_cost}");
            return 1;
        }

        // TEST CASE 3: Receive 20 @ 50
        // ((20 * 150) + (20 * 50)) / 40 = (3000 + 1000) / 40 = 100
        $inventoryService->receiveStock($product, 20, 50);
        $product->refresh();

        $this->info("Step 3: Received 20 @ 50.  New Stock: {$product->quantity_in_stock}, WAC: {$product->moving_average_cost}");

        if ($product->moving_average_cost != 100) {
            $this->error("FAILURE: Expected WAC 100, got {$product->moving_average_cost}");
            return 1;
        }

        $this->info("SUCCESS: WAC Calculation Verified Correctly.");
        $product->delete();
        return 0;
    }
}
