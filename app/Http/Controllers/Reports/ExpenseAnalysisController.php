<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExpenseAnalysisController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        // 1. Fetch Expenses
        $expenses = Expense::whereBetween('date', [$start, $end])
            ->orderBy('date', 'desc')
            ->get();

        $totalExpenses = $expenses->sum('amount');

        // 2. Fetch Revenue for Ratio Analysis
        $totalRevenue = \App\Models\PosSale::whereBetween('created_at', [$start, $end])->sum('total');
        $expenseRatio = $totalRevenue > 0 ? ($totalExpenses / $totalRevenue) * 100 : 0;

        // 3. Category Breakdown for Chart (Doughnut)
        $byCategory = $expenses->groupBy('category')->map(function($items, $category) {
            return [
                'name' => $category ?: 'Uncategorized',
                'value' => $items->sum('amount'),
                'count' => $items->count(),
                'color' => $this->getCategoryColor($category) // Helper for consistent colors
            ];
        })->values()->sortByDesc('value');

        // 4. Daily Trend (Line Chart)
        $dailyTrend = $expenses->groupBy(function($expense) {
            return Carbon::parse($expense->date)->format('Y-m-d');
        })->map(function($items, $date) {
            return [
                'date' => $date,
                'amount' => $items->sum('amount'),
                'count' => $items->count()
            ];
        })->sortBy('date');

        // 5. Anomaly Detection (Simple)
        $anomalies = collect();
        if ($dailyTrend->count() > 0) {
            $avgDaily = $dailyTrend->avg('amount');
            $threshold = $avgDaily * 2.5; // 2.5x deviation
            $anomalies = $dailyTrend->filter(function($day) use ($threshold) {
                return $day['amount'] > $threshold;
            });
        }

        return view('reports.expenses', compact(
            'startDate',
            'endDate',
            'expenses',
            'totalExpenses',
            'totalRevenue',
            'expenseRatio',
            'byCategory',
            'dailyTrend',
            'anomalies'
        ));
    }

    private function getCategoryColor($category)
    {
        // Simple hash-to-color or predefined map
        $colors = [
            'Rent' => '#6366f1', // Indigo
            'Salaries' => '#10b981', // Green
            'Utilities' => '#f59e0b', // Amber
            'Supplies' => '#ec4899', // Pink
            'Maintenance' => '#8b5cf6', // Violet
            'Taxes' => '#ef4444', // Red
        ];
        return $colors[$category] ?? '#9ca3af'; // Gray default
    }
}
