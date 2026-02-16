<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Inventory;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\Batch;

class AuditDatabaseLines extends Command
{
    protected $signature = 'audit:integrity';
    protected $description = 'Audit database for integrity issues like orphans and negative stock';

    public function handle()
    {
        $this->info('Starting Database Integrity Audit...');

        // 1. Check for Negative Stock
        $this->info('Checking 1/4: Negative Stock Levels...');
        $negativeStock = Inventory::where('quantity_in_stock', '<', 0)->get();
        if ($negativeStock->count() > 0) {
            $this->error("Found {$negativeStock->count()} items with negative stock!");
            foreach($negativeStock as $item) {
                $this->line(" - Item #{$item->id} ({$item->product_name}): {$item->quantity_in_stock}");
            }
        } else {
            $this->info(' - No negative stock found.');
        }

        // 2. Check for Orphaned Sale Items (In JSON)
        $this->info('Checking 2/4: Sale Items Integrity (JSON)...');
        $sales = PosSale::with('customer')->get();
        $orphanCount = 0;
        foreach($sales as $sale) {
             if (is_array($sale->sale_items)) {
                foreach($sale->sale_items as $item) {
                    // Check if product exists (if product_id is present)
                    if (isset($item['product_id'])) {
                        $exists = Inventory::find($item['product_id']);
                        if (!$exists) {
                             $this->warn(" - Sale #{$sale->id}: Refers to missing Product ID {$item['product_id']}");
                             $orphanCount++;
                        }
                    }
                }
             }
        }
        if ($orphanCount == 0) $this->info(' - All sale items reference valid products.');

        // 3. Check Batch Consistency
        $this->info('Checking 3/4: Batch vs Inventory Consistency...');
        $inconsistencies = 0;
        $items = Inventory::where('tracking_type', 'batch')->get();
        foreach($items as $item) {
            $batchSum = Batch::where('inventory_id', $item->id)->where('quantity', '>', 0)->sum('quantity');
            // Allow small float difference
            if (abs($batchSum - $item->quantity_in_stock) > 0.01) {
                $this->warn(" - Mismatch Item #{$item->id} ({$item->product_name}): Inventory={$item->quantity_in_stock}, Batches={$batchSum}");
                $inconsistencies++;
            }
        }
        if ($inconsistencies == 0) $this->info(' - All batch totals match inventory records.');

        // 4. Check Sales Logic
        $this->info('Checking 4/4: Sales Total Calculation...');
        // Re-using $sales collection
        $calcErrors = 0;
        foreach($sales as $sale) {
            $calcTotal = 0;
            if (is_array($sale->sale_items)) {
                foreach($sale->sale_items as $sItem) {
                    $qty = $sItem['quantity'] ?? 0;
                    $price = $sItem['unit_price'] ?? 0;
                    $calcTotal += ($price * $qty);
                }
            }
            
            // Note: This is a rough check as tax/discounts might apply
            // just checking if it is egregiously wrong (e.g. 0 total for items)
            if ($sale->total_amount > 0 && $calcTotal == 0 && count($sale->sale_items ?? []) > 0) {
                 $this->warn(" - Sale #{$sale->id}: Total is {$sale->total_amount} but calculated item total is 0.");
                 $calcErrors++;
            }
        }
        if ($calcErrors == 0) $this->info(' - Sales totals appear consistent with items.');

        // 5. Traceability & Compliance
        $this->info('Checking 5/5: Traceability & Compliance...');
        
        // 5a. Check for Serialized Items with Quantity > 1 (Violation of Uniqueness)
        $serializedViolations = Batch::where('is_serialized', true)->where('quantity', '>', 1)->get();
        if ($serializedViolations->count() > 0) {
            $this->error("Found {$serializedViolations->count()} serialized batches with quantity > 1 (Should be unique):");
            foreach($serializedViolations as $batch) {
                $this->line(" - Batch #{$batch->batch_number} (Item {$batch->inventory_id}): Qty {$batch->quantity}");
            }
        } else {
             $this->info(' - Serial uniqueness enforced (No serialized batches with qty > 1).');
        }

        // 5b. Check for Expired Stock on Shelf
        $expiredStock = Batch::where('expiry_date', '<', now())
                             ->where('quantity', '>', 0)
                             ->get();
        if ($expiredStock->count() > 0) {
            $this->warn("Found {$expiredStock->count()} expired batches still in stock:");
            foreach($expiredStock as $batch) {
                 $this->line(" - Batch #{$batch->batch_number} (Item {$batch->inventory_id}): Expired {$batch->expiry_date->format('Y-m-d')}, Qty {$batch->quantity}");
            }
        } else {
            $this->info(' - No expired stock found on shelf.');
        }

        // 5c. Check for "Implants" that are NOT set to require serial tracking
        // Assuming "Implants" or "Orthopedics" category exists
        $untaggedImplants = Inventory::whereHas('category', function($q) {
            $q->whereIn('name', ['Implants', 'Orthopedics', 'Surgery']);
        })->where('requires_serial_tracking', false)->get();

        if ($untaggedImplants->count() > 0) {
            $this->warn("Found {$untaggedImplants->count()} Implant/Surgery items *without* serial tracking enabled:");
             foreach($untaggedImplants->take(5) as $item) {
                 $this->line(" - Item #{$item->id}: {$item->product_name}");
            }
        } else {
            $this->info(' - High-risk categories appear to have tracking enabled.');
        }

        $this->info('Audit Complete.');
    }
}
