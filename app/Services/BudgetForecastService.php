<?php

namespace App\Services;

use App\Models\PosSale;
use App\Models\Expense;
use App\Models\PurchaseOrder;
use App\Models\Inventory;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BudgetForecastService
{
    /**
     * Generate forecast for a category based on historical data
     */
    public function generateForecast(string $category, Carbon $startDate, Carbon $endDate, string $basis = 'historical_average')
    {
        $historicalData = $this->analyzeHistoricalData($category, $startDate, $endDate);
        
        switch ($basis) {
            case 'historical_average':
                return $this->forecastFromAverage($historicalData, $startDate, $endDate);
            case 'growth_projection':
                return $this->forecastFromGrowth($historicalData, $startDate, $endDate);
            default:
                return [
                    'projected_amount' => 0,
                    'confidence_level' => 0,
                    'growth_rate' => 0,
                    'seasonality_factor' => 1,
                    'historical_data' => [],
                ];
        }
    }

    /**
     * Analyze historical sales, expenses, and inventory movements
     */
    protected function analyzeHistoricalData(string $category, Carbon $startDate, Carbon $endDate)
    {
        $lookbackMonths = 12;
        $lookbackStart = $startDate->copy()->subMonths($lookbackMonths);
        
        // Analyze sales data
        $salesData = $this->analyzeSalesTrends($category, $lookbackStart, $startDate);
        
        // Analyze expense patterns
        $expenseData = $this->analyzeExpensePatterns($category, $lookbackStart, $startDate);
        
        // Analyze inventory movements
        $inventoryData = $this->analyzeInventoryMovements($category, $lookbackStart, $startDate);
        
        return [
            'sales' => $salesData,
            'expenses' => $expenseData,
            'inventory' => $inventoryData,
            'period_months' => $lookbackMonths,
        ];
    }

    /**
     * Analyze sales trends for a category
     */
    protected function analyzeSalesTrends(string $category, Carbon $start, Carbon $end)
    {
        $monthlySales = PosSale::whereBetween('created_at', [$start, $end])
            ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, SUM(total_amount) as total')
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $total = $monthlySales->sum('total');
        $average = $monthlySales->avg('total') ?? 0;
        $trend = $this->calculateTrend($monthlySales->pluck('total')->toArray());

        return [
            'monthly_data' => $monthlySales,
            'total' => $total,
            'average' => $average,
            'trend' => $trend,
        ];
    }

    /**
     * Analyze expense patterns
     */
    protected function analyzeExpensePatterns(string $category, Carbon $start, Carbon $end)
    {
        $monthlyExpenses = Expense::where('category', $category)
            ->whereBetween('date', [$start, $end])
            ->selectRaw('YEAR(date) as year, MONTH(date) as month, SUM(amount) as total')
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $total = $monthlyExpenses->sum('total');
        $average = $monthlyExpenses->avg('total') ?? 0;
        $trend = $this->calculateTrend($monthlyExpenses->pluck('total')->toArray());

        return [
            'monthly_data' => $monthlyExpenses,
            'total' => $total,
            'average' => $average,
            'trend' => $trend,
        ];
    }

    /**
     * Analyze inventory movements and costs
     */
    protected function analyzeInventoryMovements(string $category, Carbon $start, Carbon $end)
    {
        $inventoryCosts = Inventory::where('category', $category)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('SUM(price * quantity_in_stock) as total_value, AVG(price) as avg_price')
            ->first();

        return [
            'total_value' => $inventoryCosts->total_value ?? 0,
            'average_price' => $inventoryCosts->avg_price ?? 0,
        ];
    }

    /**
     * Calculate growth rate from historical data
     */
    protected function calculateGrowthRate(array $values)
    {
        if (count($values) < 2) {
            return 0;
        }

        $firstHalf = array_slice($values, 0, ceil(count($values) / 2));
        $secondHalf = array_slice($values, ceil(count($values) / 2));

        $firstAvg = array_sum($firstHalf) / count($firstHalf);
        $secondAvg = array_sum($secondHalf) / count($secondHalf);

        if ($firstAvg == 0) {
            return 0;
        }

        return (($secondAvg - $firstAvg) / $firstAvg) * 100;
    }

    /**
     * Calculate trend (positive/negative growth)
     */
    protected function calculateTrend(array $values)
    {
        if (count($values) < 2) {
            return 'stable';
        }

        $growthRate = $this->calculateGrowthRate($values);

        if ($growthRate > 5) {
            return 'increasing';
        } elseif ($growthRate < -5) {
            return 'decreasing';
        } else {
            return 'stable';
        }
    }

    /**
     * Forecast from historical average
     */
    protected function forecastFromAverage(array $historicalData, Carbon $startDate, Carbon $endDate)
    {
        $salesAvg = $historicalData['sales']['average'];
        $expensesAvg = $historicalData['expenses']['average'];
        
        $periodMonths = $startDate->diffInMonths($endDate) + 1;
        $projectedAmount = ($salesAvg * 0.3 + $expensesAvg) * $periodMonths; // 30% of sales + expenses
        
        $confidenceLevel = 75; // Medium confidence for average-based forecast

        return [
            'projected_amount' => round($projectedAmount, 2),
            'confidence_level' => $confidenceLevel,
            'growth_rate' => 0,
            'seasonality_factor' => 1,
            'historical_data' => $historicalData,
        ];
    }

    /**
     * Forecast from growth projection
     */
    protected function forecastFromGrowth(array $historicalData, Carbon $startDate, Carbon $endDate)
    {
        $salesValues = $historicalData['sales']['monthly_data']->pluck('total')->toArray();
        $expenseValues = $historicalData['expenses']['monthly_data']->pluck('total')->toArray();
        
        $salesGrowth = $this->calculateGrowthRate($salesValues);
        $expenseGrowth = $this->calculateGrowthRate($expenseValues);
        
        $salesAvg = $historicalData['sales']['average'];
        $expensesAvg = $historicalData['expenses']['average'];
        
        $periodMonths = $startDate->diffInMonths($endDate) + 1;
        
        // Apply growth rate
        $projectedSales = $salesAvg * (1 + ($salesGrowth / 100));
        $projectedExpenses = $expensesAvg * (1 + ($expenseGrowth / 100));
        
        $projectedAmount = ($projectedSales * 0.3 + $projectedExpenses) * $periodMonths;
        
        $confidenceLevel = 65; // Lower confidence for growth-based forecast

        return [
            'projected_amount' => round($projectedAmount, 2),
            'confidence_level' => $confidenceLevel,
            'growth_rate' => round(($salesGrowth + $expenseGrowth) / 2, 2),
            'seasonality_factor' => 1,
            'historical_data' => $historicalData,
        ];
    }

    /**
     * Apply seasonality adjustments
     */
    protected function applySeasonality(float $baseAmount, int $month)
    {
        // Simple seasonality factors (can be customized per business)
        $seasonalityMap = [
            1 => 0.9,  // January
            2 => 0.9,  // February
            3 => 1.0,  // March
            4 => 1.0,  // April
            5 => 1.1,  // May
            6 => 1.1,  // June
            7 => 1.0,  // July
            8 => 1.0,  // August
            9 => 1.1,  // September
            10 => 1.1, // October
            11 => 1.2, // November
            12 => 1.2, // December
        ];

        return $baseAmount * ($seasonalityMap[$month] ?? 1.0);
    }
}
