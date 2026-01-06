<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PosSale;
use App\Models\Expense;
use App\Models\Inventory;
use App\Services\GeminiAnalyticsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    protected $aiService;
    protected $analyticsService;

    public function __construct(GeminiAnalyticsService $aiService, \App\Services\AnalyticsService $analyticsService)
    {
        $this->aiService = $aiService;
        $this->analyticsService = $analyticsService;
    }

    public function index()
    {
        return view('reports.index');
    }

    public function sales(Request $request)
    {
        $period = $request->get('period', 'month');
        $dates = $this->getDateRange($period);
        
        $salesData = PosSale::whereBetween('created_at', [$dates['start'], $dates['end']])
            ->get();

        // Calculate daily totals for the chart
        $dailySales = $salesData->groupBy(function($sale) {
            return $sale->created_at->format('Y-m-d');
        })->map(function($sales) {
            return (object) [
                'date' => $sales->first()->created_at->format('Y-m-d'),
                'total' => $sales->sum('total'),
                'count' => $sales->count()
            ];
        })->values();

        // Calculate totals by category from the JSON items
        $byCategory = $salesData->flatMap(function ($sale) {
            return $sale->sale_items ?? [];
        })->groupBy(function ($item) {
            return $item['product_snapshot']['category'] ?? 'Uncategorized';
        })->map(function ($items, $category) {
            return (object) [
                'category' => $category,
                'total' => collect($items)->sum('item_total')
            ];
        })->values();

        return view('reports.sales', [
            'period' => $period,
            'start_date' => $dates['start'],
            'end_date' => $dates['end'],
            'sales_data' => $dailySales,
            'category_data' => $byCategory,
            'total_sales' => $salesData->sum('total'),
            'transaction_count' => $salesData->sum('count')
        ]);
    }

    public function inventory()
    {
        $inventory = Inventory::all();
        $totalValue = $inventory->sum(fn($i) => $i->price * $i->quantity_in_stock);
        $totalCost = $inventory->sum(fn($i) => $i->cost_price * $i->quantity_in_stock);
        
        $lowStock = $inventory->where('quantity_in_stock', '<=', 'reorder_level');
        
        $categoryValue = $inventory->groupBy('category')->map(function($items) {
            return $items->sum(fn($i) => $i->price * $i->quantity_in_stock);
        });

        return view('reports.inventory', [
            'total_value' => $totalValue,
            'total_cost' => $totalCost,
            'potential_profit' => $totalValue - $totalCost,
            'low_stock' => $lowStock,
            'category_values' => $categoryValue
        ]);
    }

    public function aiSummary(Request $request)
    {
        $period = $request->get('period', 'last_30_days');
        $dates = $this->getDateRange($period);

        // If 'deep' flag is present, use the advanced analytics
        if ($request->has('deep')) {
            $staffPerformance = $this->analyticsService->getStaffPerformance($dates['start'], $dates['end']);
            $productProfitability = $this->analyticsService->getProductProfitability($dates['start'], $dates['end']);
            $customerInsights = $this->analyticsService->getCustomerInsights($dates['start'], $dates['end']);
            $peakTimes = $this->analyticsService->getPeakSalesTimes($dates['start'], $dates['end']);
            
            $summary = $this->aiService->generateDeepAnalysis($period, $dates['start'], $dates['end'], [
                'staff' => $staffPerformance,
                'products' => $productProfitability,
                'customers' => $customerInsights,
                'peak_times' => $peakTimes
            ]);
        } else {
            $summary = $this->aiService->generateExecutiveSummary($period, $dates['start'], $dates['end']);
        }
        
        \Illuminate\Support\Facades\Log::info('ReportsController Summary', ['summary' => $summary]);

        return response()->json(['html' => $summary]);
    }

    public function deepAnalysis(Request $request)
    {
        $period = $request->get('period', 'month');
        $dates = $this->getDateRange($period);

        $staffPerformance = $this->analyticsService->getStaffPerformance($dates['start'], $dates['end']);
        $productStats = $this->analyticsService->getProductProfitability($dates['start'], $dates['end']);
        $customerInsights = $this->analyticsService->getCustomerInsights($dates['start'], $dates['end']);
        $peakTimes = $this->analyticsService->getPeakSalesTimes($dates['start'], $dates['end']);
        $paymentTrends = $this->analyticsService->getPaymentTrends($dates['start'], $dates['end']);

        return view('reports.deep-analysis', [
            'period' => $period,
            'start_date' => $dates['start'],
            'end_date' => $dates['end'],
            'staff_performance' => $staffPerformance,
            'top_profitable' => $productStats['by_profit'],
            'top_volume' => $productStats['by_volume'],
            'high_margin' => $productStats['high_margin'],
            'customer_insights' => $customerInsights,
            'peak_times' => $peakTimes,
            'payment_trends' => $paymentTrends
        ]);
    }

    private function getDateRange($period)
    {
        $end = Carbon::now();
        switch($period) {
            case 'week': $start = Carbon::now()->subDays(7); break;
            case 'month': $start = Carbon::now()->subDays(30); break;
            case 'quarter': $start = Carbon::now()->subMonths(3); break;
            case 'year': $start = Carbon::now()->subYear(); break;
            default: $start = Carbon::now()->subDays(30);
        }
        return ['start' => $start, 'end' => $end];
    }
}
