<?php

namespace App\Http\Controllers;

use App\Models\PosSale;
use App\Models\Inventory;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Today's sales query (initialize before role check)
        $todaySales = PosSale::whereDate('created_at', today());
        
        if ($user->role !== 'admin') {
            $todaySales->where('seller_username', $user->username);
            
            // Hide sensitive company-wide metrics
            $weekRevenue = 0; 
            // Removed explicit $monthRevenue = 0; -> We will calculate "My Month Revenue" below
            // Removed explicit $inventoryValue = 0; -> We will use this slot for "My Month Transactions"
            $inventoryValue = 0; // Still 0 for Inventory Value variable itself
            $pendingOrdersValue = 0;
        }
        
        // Ensure accurate counting for the restricted query
        $todaySalesCount = $todaySales->count();
        $todayRevenue = $todaySales->sum('total');

        // This month's sales
        $monthSales = PosSale::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
            
        if ($user->role !== 'admin') {
            $monthSales->where('seller_username', $user->username);
        }
        
        $monthRevenue = $monthSales->sum('total'); // Now works for both (scoped for staff)
        $monthSalesCount = $monthSales->count(); // New variable for "My Month Txns"

        // Low stock alerts (visible to all authorized staff for operation)
        $lowStockCount = Inventory::where('quantity_in_stock', '<=', 10)->count();
        $outOfStockCount = Inventory::where('quantity_in_stock', '<=', 0)->count();

        // Pending orders & Inventory Value (Admin Restricted)
        if ($user->role === 'admin') {
            $pendingOrdersValue = \App\Models\PurchaseOrder::where('status', 'pending')->count();
            $inventoryValue = Inventory::sum(DB::raw('quantity_in_stock * selling_price'));
        } else {
            $pendingOrdersValue = 0;
            $inventoryValue = 0;
        }
        
        // Expiring items (visible to all for safety)
        $expiringItemsCount = Inventory::whereDate('expiry_date', '>', now())
            ->whereDate('expiry_date', '<=', now()->addDays(90))
            ->count();

        // Recent sales
        $recentQuery = PosSale::orderBy('created_at', 'desc')->limit(10);
        if ($user->role !== 'admin') {
            $recentQuery->where('seller_username', $user->username);
        }
        $recentSales = $recentQuery->get();

        // Low stock items (visible to all)
        $lowStockItems = Inventory::where('quantity_in_stock', '<=', 10)
            ->orderBy('quantity_in_stock')
            ->limit(10)
            ->get();

        // Sales by day (Admin only for full trends, or User specific)
        // Sales by day (Admin: All, Staff: Own)
        $salesByDayQuery = PosSale::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total) as revenue')
            )
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date');

        if ($user->role !== 'admin') {
            $salesByDayQuery->where('seller_username', $user->username);
        }
        $salesByDay = $salesByDayQuery->get();
            
        $salesByPaymentMethodQuery = PosSale::select(
                'payment_method',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total) as revenue')
            )
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->groupBy('payment_method');
            
        if ($user->role !== 'admin') {
            $salesByPaymentMethodQuery->where('seller_username', $user->username);
        }
        $salesByPaymentMethod = $salesByPaymentMethodQuery->get();

        // Top selling products
        $topProductsQuery = PosSale::query()
            ->select('sale_items')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);

        if ($user->role !== 'admin') {
            $topProductsQuery->where('seller_username', $user->username);
        }

        $topProducts = $topProductsQuery->get();

        // Process top products
        $productSales = [];
        foreach ($topProducts as $sale) {
            $items = $sale->sale_items ?? [];

            if (!is_array($items)) {
                continue;
            }

            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $productName = $item['product_name'] ?? null;
                if (!$productName) {
                    continue;
                }

                $quantity = (int) ($item['quantity'] ?? 0);
                if (!isset($productSales[$productName])) {
                    $productSales[$productName] = 0;
                }
                $productSales[$productName] += $quantity;
            }
        }
        arsort($productSales);
        $topSellingProducts = array_slice($productSales, 0, 5, true);

        if ($request->expectsJson()) {
            return response()->json([
                'today_sales' => $todaySalesCount,
                'today_revenue' => $todayRevenue,
                'month_revenue' => $monthRevenue,
                'low_stock_count' => $lowStockCount,
                'out_of_stock_count' => $outOfStockCount,
                'expiring_items_count' => $expiringItemsCount,
                'pending_orders_value' => $pendingOrdersValue,
                'inventory_value' => $inventoryValue,
                'recent_sales' => $recentSales,
                'low_stock_items' => $lowStockItems,
                'sales_by_day' => $salesByDay,
                'sales_by_payment_method' => $salesByPaymentMethod,
                'top_selling_products' => $topSellingProducts,
                'sales_count_month' => $monthSalesCount,
            ]);
        }

        return view('dashboard.index', compact(
            'todaySalesCount',
            'todayRevenue',
            'monthRevenue',
            'lowStockCount',
            'outOfStockCount',
            'pendingOrdersValue',
            'inventoryValue',
            'recentSales',
            'lowStockItems',
            'salesByDay',
            'salesByPaymentMethod',
            'topSellingProducts',
            'expiringItemsCount',
            'monthSalesCount'
        ));
    }
}
