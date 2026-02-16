<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Lpo;
use App\Models\Supplier;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SupplierReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfDay()->format('Y-m-d'));
        
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        // 1. Get all suppliers with comprehensive metrics
        $suppliers = Supplier::withCount(['purchaseOrders as total_orders' => function($query) use ($start, $end) {
            $query->whereBetween('created_at', [$start, $end]);
        }])
        ->withSum(['purchaseOrders as total_spend' => function($query) use ($start, $end) {
            $query->whereBetween('created_at', [$start, $end]);
        }], 'total_amount')
        ->withCount(['purchaseOrders as completed_orders' => function($query) use ($start, $end) {
            $query->whereBetween('created_at', [$start, $end])
                  ->where('status', 'received');
        }])
        ->withCount(['purchaseOrders as pending_orders' => function($query) use ($start, $end) {
            $query->whereBetween('created_at', [$start, $end])
                  ->whereIn('status', ['pending', 'approved']);
        }])
        ->get()
        ->map(function($supplier) use ($start, $end) {
            // Calculate metrics
            $supplier->average_order_value = $supplier->total_orders > 0 
                ? $supplier->total_spend / $supplier->total_orders 
                : 0;
            
            // Reliability Score (0-100)
            // Based on: completion rate (60%), order frequency (20%), avg order value (20%)
            $completionRate = $supplier->total_orders > 0 
                ? ($supplier->completed_orders / $supplier->total_orders) * 100 
                : 0;
            
            $supplier->reliability_score = round(
                ($completionRate * 0.6) + 
                (min($supplier->total_orders / 10, 10) * 2) + // Frequency bonus
                (min($supplier->average_order_value / 10000, 10) * 2) // Value bonus
            );
            
            // Last order date
            $lastOrder = $supplier->purchaseOrders()
                ->whereBetween('created_at', [$start, $end])
                ->latest()
                ->first();
            $supplier->last_order_date = $lastOrder ? $lastOrder->created_at : null;
            
            return $supplier;
        })
        ->sortByDesc('total_spend');

        // 2. Calculate summary metrics
        $totalSuppliers = $suppliers->count();
        $totalSpend = $suppliers->sum('total_spend');
        $totalOrders = $suppliers->sum('total_orders');
        $avgOrderValue = $totalOrders > 0 ? $totalSpend / $totalOrders : 0;

        // 3. Top performers
        $topSuppliers = $suppliers->take(5);
        
        // 4. Monthly spend trend (SQLite compatible)
        $monthlyTrend = PurchaseOrder::whereBetween('created_at', [$start, $end])
            ->selectRaw("strftime('%Y-%m', created_at) as month, SUM(total_amount) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // 5. Supplier distribution by spend
        $spendDistribution = $suppliers->map(function($s) {
            return [
                'name' => $s->supplier_name,
                'value' => $s->total_spend,
                'orders' => $s->total_orders
            ];
        })->take(10);

        return view('reports.suppliers', compact(
            'startDate', 
            'endDate', 
            'suppliers',
            'totalSuppliers',
            'totalSpend',
            'totalOrders',
            'avgOrderValue',
            'topSuppliers',
            'monthlyTrend',
            'spendDistribution'
        ));
    }
}
