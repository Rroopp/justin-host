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

        // Fetch enriched analytics from the service
        $staffPerformance = $this->analyticsService->getStaffPerformance($dates['start'], $dates['end']);
        $productStats = $this->analyticsService->getProductProfitability($dates['start'], $dates['end']);
        $peakTimes = $this->analyticsService->getPeakSalesTimes($dates['start'], $dates['end']);
        $paymentTrends = $this->analyticsService->getPaymentTrends($dates['start'], $dates['end']);
        
        // Calculate estimated profit (Total - Cost)
        // Note: This relies on product snapshot cost_price being accurate
        $totalCost = $salesData->sum(function($sale) {
             return collect($sale->sale_items)->sum(function($item) {
                 return ($item['product_snapshot']['cost_price'] ?? 0) * ($item['quantity'] ?? 0);
             });
        });
        $netProfit = $salesData->sum('total') - $totalCost;

        return view('reports.sales', [
            'period' => $period,
            'start_date' => $dates['start'],
            'end_date' => $dates['end'],
            'sales_data' => $dailySales,
            'category_data' => $byCategory,
            'total_sales' => $salesData->sum('total'),
            'transaction_count' => $salesData->sum('count'),
            'net_profit' => $netProfit,
            'staff_performance' => $staffPerformance,
            'top_products' => $productStats['by_volume']->take(5),
            'top_products_revenue' => $productStats['by_profit']->take(5),
            'peak_times' => $peakTimes,
            'payment_trends' => $paymentTrends
        ]);
    }

    public function inventory(Request $request)
    {
        // 1. Efficient Data Retrieval
        $inventory = Inventory::select('id', 'product_name', 'category', 'quantity_in_stock', 'reorder_level', 'price', 'cost_price', 'updated_at')
            ->get();

        // 2. Financial Metrics
        $totalRetailValue = $inventory->sum(fn($i) => $i->price * $i->quantity_in_stock);
        $totalCostValue = $inventory->sum(fn($i) => $i->cost_price * $i->quantity_in_stock);
        $potentialProfit = $totalRetailValue - $totalCostValue;

        // 3. Risk Analysis
        $lowStock = $inventory->filter(fn($i) => $i->quantity_in_stock <= $i->reorder_level && $i->quantity_in_stock > 0);
        $outOfStock = $inventory->filter(fn($i) => $i->quantity_in_stock == 0);
        
        // Slow Moving: No updates (sales/restock) in 90 days and has stock
        $ninetyDaysAgo = Carbon::now()->subDays(90);
        $slowMoving = $inventory->filter(function($i) use ($ninetyDaysAgo) {
            return $i->quantity_in_stock > 0 && $i->updated_at < $ninetyDaysAgo; 
        });

        // 4. Category Analytics
        $categoryValue = $inventory->groupBy('category')->map(function($items) {
            return $items->sum(fn($i) => $i->cost_price * $i->quantity_in_stock); // Cost basis for risk
        })->sortDesc()->take(8); // Top 8 categories

        // 5. Pareto Analysis (Top Value Items)
        $topValueItems = $inventory->sortByDesc(fn($i) => $i->price * $i->quantity_in_stock)->take(5);

        // 6. Turnover Proxy (Last 30 Days Sales / Current Inventory Value)
        // Note: Real turnover needs Avg Inventory over time, this is a distinct point-in-time proxy
        $last30DaysSalesCost = PosSale::where('created_at', '>=', Carbon::now()->subDays(30))
            ->get()
            ->flatMap(fn($sale) => $sale->sale_items ?? [])
            ->sum(fn($item) => ($item['product_snapshot']['cost_price'] ?? 0) * ($item['quantity'] ?? 0));
        
        $turnoverRate = $totalCostValue > 0 ? ($last30DaysSalesCost / $totalCostValue) : 0;


        return view('reports.inventory', [
            'total_value' => $totalRetailValue,
            'total_cost' => $totalCostValue,
            'potential_profit' => $potentialProfit,
            'low_stock' => $lowStock,
            'out_of_stock' => $outOfStock,
            'slow_moving' => $slowMoving,
            'category_values' => $categoryValue,
            'top_value_items' => $topValueItems,
            'turnover_rate' => $turnoverRate,
            'period_sales_cost' => $last30DaysSalesCost
        ]);
    }

    public function aiSummary(Request $request)
    {
        try {
            $period = $request->get('period', 'last_30_days');
            $type = $request->get('type', 'general'); // Standardize on 'type' vs 'deep' flag
            
            if ($period === 'custom' && $request->has(['start', 'end'])) {
                $dates = [
                    'start' => Carbon::parse($request->get('start'))->startOfDay(),
                    'end' => Carbon::parse($request->get('end'))->endOfDay()
                ];
            } else {
                $dates = $this->getDateRange($period);
            }

            // Check if AI service is configured
            if (!config('services.gemini.key')) {
                return response()->json([
                    'html' => '<div class="p-4 bg-yellow-50 border border-yellow-200 rounded"><p class="text-yellow-800"><strong>AI Service Not Configured:</strong> Please set the GEMINI_API_KEY in your .env file.</p></div>'
                ]);
            }

            if ($type === 'expense') {
                // Fetch Expense Data for AI Context
                $expenses = Expense::whereBetween('date', [$dates['start'], $dates['end']])->get();
                $byCategory = $expenses->groupBy('category')->map->sum('amount');
                $dailyTrend = $expenses->groupBy(fn($e) => $e->date->format('Y-m-d'))->map->sum('amount');
                $topSpenders = $byCategory->sortDesc()->take(5);
                
                // Simple Anomaly Logic for Context
                $avg = $dailyTrend->avg();
                $anomalies = $dailyTrend->filter(fn($val) => $val > ($avg * 2.5));

                $summary = $this->aiService->generateExpenseAnalysis($period, $dates['start'], $dates['end'], [
                    'total_expense' => $expenses->sum('amount'),
                    'period_revenue_approx' => PosSale::whereBetween('created_at', [$dates['start'], $dates['end']])->sum('total'),
                    'top_categories' => $topSpenders,
                    'daily_trend_summary' => ['avg' => $avg, 'peak' => $dailyTrend->max()],
                    'anomalies' => $anomalies
                ]);
            } elseif ($request->has('deep') || $type === 'deep') {
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
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('AI Summary Error', ['error' => $e->getMessage()]);
            return response()->json([
                'html' => '<div class="p-4 bg-red-50 border border-red-200 rounded"><p class="text-red-800"><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p></div>'
            ], 500);
        }
    }

    public function chat(Request $request)
    {
        $question = $request->input('message');
        $period = $request->input('period', 'month');
        $dates = $this->getDateRange($period);
        
        $answer = $this->aiService->askData($question, $dates['start'], $dates['end']);
        
        return response()->json(['answer' => $answer]);
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
