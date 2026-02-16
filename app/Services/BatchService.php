<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Inventory;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BatchService
{
    /**
     * Generate a unique batch number for a product.
     * Format: BTN-{PRODUCT_CODE}-{YYYYMMDD}-{SEQ}
     * or simple: BATCH-{YYYYMMDD}-{SEQ} if code is long
     */
    public function generateBatchNumber(?Inventory $inventory = null): string
    {
        $prefix = 'BATCH';
        $date = Carbon::now()->format('Ymd');
        
        if ($inventory && $inventory->code) {
            // Use short code if possible, taking last 4 chars if long
            $shortCode = Str::upper(substr($inventory->code, -4));
            $prefix = "BTN-{$shortCode}";
        }

        // Find the specific sequence for today
        // We look for batches starting with this prefix + date to count them
        $base = "{$prefix}-{$date}";
        
        // Optimistic locking / sequence generation
        // Count existing batches for this day to determine sequence
        $count = Batch::where('batch_number', 'like', "{$base}-%")->count();
        $sequence = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
        
        $candidate = "{$base}-{$sequence}";

        // Verify uniqueness (unlikely collision but safe)
        while (Batch::where('batch_number', $candidate)->exists()) {
            $count++;
            $sequence = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
            $candidate = "{$base}-{$sequence}";
        }

        return $candidate;
    }
}
