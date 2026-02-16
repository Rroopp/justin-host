<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InventoryAgingController extends Controller
{
    public function index(Request $request)
    {
        // 1. Fetch Active Batches (instock)
        // We use Batch model because it has specific 'created_at' (entry date) and 'expiry_date'
        $batches = Batch::where('status', 'available')
            ->where('quantity', '>', 0)
            ->with('inventory') // Eager load product details
            ->get();

        // 2. Shelf Age Analysis (FIFO / Dormancy)
        $now = Carbon::now();
        $shelfAgeBuckets = [
            '0-90 days' => ['count' => 0, 'value' => 0, 'items' => collect()],
            '91-180 days' => ['count' => 0, 'value' => 0, 'items' => collect()],
            '181-365 days' => ['count' => 0, 'value' => 0, 'items' => collect()],
            'Over 1 year' => ['count' => 0, 'value' => 0, 'items' => collect()],
        ];

        // 3. Expiry Risk Analysis
        $expiryBuckets = [
            'Expired' => ['count' => 0, 'value' => 0, 'items' => collect()],
            'Expiring < 30d' => ['count' => 0, 'value' => 0, 'items' => collect()],
            'Expiring 30-90d' => ['count' => 0, 'value' => 0, 'items' => collect()],
            'Fresh (>90d)' => ['count' => 0, 'value' => 0, 'items' => collect()],
        ];

        foreach ($batches as $batch) {
            $value = $batch->quantity * $batch->cost_price;
            
            // --- Shelf Age Calculation ---
            $ageDays = $batch->created_at->diffInDays($now);
            $bucketKey = match(true) {
                $ageDays <= 90 => '0-90 days',
                $ageDays <= 180 => '91-180 days',
                $ageDays <= 365 => '181-365 days',
                default => 'Over 1 year',
            };
            
            $shelfAgeBuckets[$bucketKey]['count'] += $batch->quantity;
            $shelfAgeBuckets[$bucketKey]['value'] += $value;
            // Only store top value items to avoid memory bloat
            if ($shelfAgeBuckets[$bucketKey]['items']->count() < 10) {
                 $shelfAgeBuckets[$bucketKey]['items']->push($batch);
            }

            // --- Expiry Calculation (if expiry_date exists) ---
            if ($batch->expiry_date) {
                $expiryDate = $batch->expiry_date;
                $diffDays = $now->diffInDays($expiryDate, false); // false = return negative for past dates
                
                $expiryKey = match(true) {
                    $diffDays < 0 => 'Expired',
                    $diffDays <= 30 => 'Expiring < 30d',
                    $diffDays <= 90 => 'Expiring 30-90d',
                    default => 'Fresh (>90d)',
                };

                $expiryBuckets[$expiryKey]['count'] += $batch->quantity;
                $expiryBuckets[$expiryKey]['value'] += $value;
                if ($expiryBuckets[$expiryKey]['items']->count() < 10) {
                     $expiryBuckets[$expiryKey]['items']->push($batch);
                }
            }
        }

        // 4. Critical List (Expired or Expiring < 30d)
        $criticalExpiry = $expiryBuckets['Expired']['items']->merge($expiryBuckets['Expiring < 30d']['items']);
        $totalExpiredValue = $expiryBuckets['Expired']['value'];
        $totalRiskValue = $expiryBuckets['Expiring < 30d']['value'] + $expiryBuckets['Expiring 30-90d']['value'];

        return view('reports.inventory-aging', [
            'shelf_age_buckets' => $shelfAgeBuckets,
            'expiry_buckets' => $expiryBuckets,
            'critical_expiry_items' => $criticalExpiry,
            'total_expired_value' => $totalExpiredValue,
            'total_risk_value' => $totalRiskValue,
            'total_inventory_value' => $batches->sum(fn($b) => $b->quantity * $b->cost_price)
        ]);
    }
}
