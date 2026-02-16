<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\PosSale;
use App\Models\Expense;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProfitLossController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        // 1. Revenue (Total Sales)
        // Filter by completed sales only? Usually 'sale_status' = 'completed'. assuming all records are valid sales.
        $sales = PosSale::whereBetween('created_at', [$start, $end])->get();
        $revenue = $sales->sum('total');

        // 2. Cost of Goods Sold (COGS)
        // We iterate through sale items to summize the Cost Basis
        $cogs = $sales->flatMap(function ($sale) {
            return $sale->sale_items ?? [];
        })->sum(function ($item) {
            $qty = $item['quantity'] ?? 0;
            // STRICT COST PRIORITY:
            // 1. Snapshot 'cost_price' (Best)
            // 2. Snapshot 'buying_price' (Alternative)
            // 3. Current Inventory Master 'cost_price' (Fallback)
            // 4. Default 0 (Do NOT use retail price, it hides loss)
            
            $snapshot = $item['product_snapshot'] ?? [];
            $cost = $snapshot['cost_price'] ?? $snapshot['buying_price'] ?? null;
            
            if ($cost === null && isset($item['product_id'])) {
                // Fallback to master if snapshot missing cost (e.g. legacy data)
                $product = Inventory::find($item['product_id']);
                $cost = $product ? $product->cost_price : 0;
            }

            return (float)($cost ?? 0) * (int)$qty;
        });

        // 3. Gross Profit
        $grossProfit = $revenue - $cogs;
        $grossMargin = $revenue > 0 ? ($grossProfit / $revenue) * 100 : 0;

        // 4. Operating Expenses
        $expenses = Expense::whereBetween('date', [$start, $end])->get();
        $totalExpenses = $expenses->sum('amount');
        
        // Group by category for chart
        $expensesByCategory = $expenses->groupBy('category')->map(function($group) {
            return $group->sum('amount');
        })->sortDesc();

        // 5. Net Profit
        $netProfit = $grossProfit - $totalExpenses;
        $netMargin = $revenue > 0 ? ($netProfit / $revenue) * 100 : 0;

        return view('reports.profit-loss', compact(
            'startDate', 
            'endDate', 
            'revenue', 
            'cogs', 
            'grossProfit', 
            'grossMargin', 
            'totalExpenses', 
            'expensesByCategory', 
            'netProfit', 
            'netMargin'
        ));
    }
}
