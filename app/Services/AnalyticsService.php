<?php

namespace App\Services;

use App\Models\PosSale;
use App\Models\Inventory;
use App\Models\Staff;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Get comprehensive staff performance metrics
     */
    /**
     * Get comprehensive staff performance metrics
     */
    public function getStaffPerformance(Carbon $startDate, Carbon $endDate)
    {
        // 1. Aggregated Sales by Seller
        $staffMetrics = PosSale::whereBetween('created_at', [$startDate, $endDate])
            ->select(
                'seller_username',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(total) as total_revenue'),
                DB::raw('AVG(total) as average_ticket_value')
            )
            ->groupBy('seller_username')
            ->get();

        // Prepare Inventory Cache for Fallbacks (Avoid N+1)
        // We only need costs for items sold in this period that might miss snapshot data
        // For simplicity/performance trade-off, we'll fetch basic cost map if dataset isn't huge
        // Or we do it inside the loop but with eager loading?
        // Better: Fetch all sold sales first.
        
        $allSales = PosSale::whereBetween('created_at', [$startDate, $endDate])->get();
        
        // Collect all product IDs needed for fallback
        $productIds = [];
        foreach ($allSales as $sale) {
             if (isset($sale->sale_items) && is_array($sale->sale_items)) {
                 foreach ($sale->sale_items as $item) {
                     // Only need fallback if snapshot cost is missing
                     $snap = $item['product_snapshot'] ?? [];
                     if (!isset($snap['cost_price']) && !isset($snap['buying_price']) && isset($item['product_id'])) {
                         $productIds[] = $item['product_id'];
                     }
                 }
             }
        }
        $productIds = array_unique($productIds);
        $inventoryCosts = Inventory::whereIn('id', $productIds)->pluck('price', 'id');

        // 2. Enhance with Profitability
        return $staffMetrics->map(function ($metric) use ($allSales, $inventoryCosts) {
            
            // Filter pre-fetched sales for this user
            $userSales = $allSales->where('seller_username', $metric->seller_username);
            
            $totalCost = 0;
            $itemsSold = 0;

            foreach ($userSales as $sale) {
                if (isset($sale->sale_items) && is_array($sale->sale_items)) {
                    foreach ($sale->sale_items as $item) {
                        $qty = $item['quantity'] ?? 0;
                        
                        // Strict Cost Logic
                        $snapshot = $item['product_snapshot'] ?? [];
                        // Check multiple potential keys for cost in snapshot
                        $cost = $snapshot['cost_price'] ?? $snapshot['buying_price'] ?? $snapshot['price'] ?? null;
                        
                        if ($cost === null && isset($item['product_id'])) {
                             // Fast lookup from pre-loaded cache
                             $cost = $inventoryCosts[$item['product_id']] ?? 0;
                        }

                        $totalCost += ((float)$cost * (int)$qty);
                        $itemsSold += (int)$qty;
                    }
                }
            }

            $grossProfit = $metric->total_revenue - $totalCost;
            $margin = $metric->total_revenue > 0 ? ($grossProfit / $metric->total_revenue) * 100 : 0;

            return [
                'name' => $metric->seller_username,
                'transactions' => $metric->transaction_count,
                'revenue' => $metric->total_revenue,
                'avg_ticket' => $metric->average_ticket_value,
                'gross_profit' => $grossProfit,
                'margin_percent' => round($margin, 2),
                'items_sold' => $itemsSold
            ];
        })->sortByDesc('revenue')->values();
    }

    /**
     * Get Product Profitability Analysis
     */
    public function getProductProfitability(Carbon $startDate, Carbon $endDate, $limit = 20)
    {
        $sales = PosSale::whereBetween('created_at', [$startDate, $endDate])->get();
        
        // Cache needed IDs
        $productIds = [];
        foreach ($sales as $sale) {
             if (isset($sale->sale_items) && is_array($sale->sale_items)) {
                 foreach ($sale->sale_items as $item) {
                     $snap = $item['product_snapshot'] ?? [];
                     if (!isset($snap['cost_price']) && !isset($snap['buying_price']) && isset($item['product_id'])) {
                         $productIds[] = $item['product_id'];
                     }
                 }
             }
        }
        $productIds = array_unique($productIds);
        $inventoryCosts = Inventory::whereIn('id', $productIds)->pluck('price', 'id');

        $productStats = [];

        foreach ($sales as $sale) {
            if (!isset($sale->sale_items) || !is_array($sale->sale_items)) continue;

            foreach ($sale->sale_items as $item) {
                $name = $item['product_name'] ?? 'Unknown';
                $id = $item['product_id'] ?? 'diff_' . $name;
                $qty = $item['quantity'] ?? 0;
                $total = $item['item_total'] ?? 0;
                
                // Cost Logic
                $snap = $item['product_snapshot'] ?? [];
                $cost = $snap['cost_price'] ?? $snap['buying_price'] ?? $snap['price'] ?? null;
                
                if ($cost === null && isset($item['product_id'])) {
                    $cost = $inventoryCosts[$item['product_id']] ?? 0;
                }
                $cost = (float)$cost;

                $profit = $total - ($cost * $qty);

                if (!isset($productStats[$id])) {
                    $productStats[$id] = [
                        'id' => $id,
                        'name' => $name,
                        'qty_sold' => 0,
                        'total_revenue' => 0,
                        'total_cost' => 0,
                        'total_profit' => 0,
                    ];
                }

                $productStats[$id]['qty_sold'] += $qty;
                $productStats[$id]['total_revenue'] += $total;
                $productStats[$id]['total_cost'] += ($cost * $qty);
                $productStats[$id]['total_profit'] += $profit;
            }
        }

        // Calculate margins and format
        $results = collect($productStats)->map(function($stat) {
            $margin = $stat['total_revenue'] > 0 
                ? ($stat['total_profit'] / $stat['total_revenue']) * 100 
                : 0;

            return [
                'name' => $stat['name'],
                'qty' => $stat['qty_sold'],
                'revenue' => $stat['total_revenue'],
                'profit' => $stat['total_profit'],
                'margin' => round($margin, 2)
            ];
        });

        return [
            'by_profit' => $results->sortByDesc('profit')->take($limit)->values(),
            'by_volume' => $results->sortByDesc('qty')->take($limit)->values(),
            // High margin logic: margin > 40% (example) or just sort descending
            'high_margin' => $results->where('qty', '>', 5)->sortByDesc('margin')->take($limit)->values(), 
        ];
    }

    /**
     * Get Weekly/Monthly Trends
     */
    public function getTrends(Carbon $startDate, Carbon $endDate)
    {
        $sales = PosSale::whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total) as revenue'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $sales;
    }

    /**
     * Get Customer Insights
     */
    public function getCustomerInsights(Carbon $startDate, Carbon $endDate, $limit = 10)
    {
        return PosSale::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('customer_id') 
            ->select(
                'customer_name',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(total) as total_spent'),
                DB::raw('MAX(created_at) as last_purchase')
            )
            ->groupBy('customer_name')
            ->orderByDesc('total_spent')
            ->limit($limit)
            ->get();
    }

    /**
     * Get Peak Sales Times (Hour of Day)
     */
    public function getPeakSalesTimes(Carbon $startDate, Carbon $endDate)
    {
        // SQLite uses strftime('%H', ...), MySQL uses HOUR(...)
        // Assuming MySQL given 'laravel-version'. If SQLite, use strftime.
        // We will try generic approach or check driver, but typical LAMP stack is MySQL.
        // Let's use a safe fallback for SQLite if needed, but typically:
        
        $driver = DB::connection()->getDriverName();
        $hourFunc = $driver === 'sqlite' ? "strftime('%H', created_at)" : 'HOUR(created_at)';

        return PosSale::whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw("$hourFunc as hour"),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total) as revenue')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();
    }

    /**
     * Get Payment Method Trends
     */
    public function getPaymentTrends(Carbon $startDate, Carbon $endDate)
    {
        return PosSale::whereBetween('created_at', [$startDate, $endDate])
            ->select(
                'payment_method',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total) as total')
            )
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();
    }
}
