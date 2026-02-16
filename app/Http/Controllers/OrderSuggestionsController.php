<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\PurchaseOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderSuggestionsController extends Controller
{
    /**
     * Display a listing of order suggestions.
     */
    public function index()
    {
        // Default to low stock for the main view or provide summary
        $globalThreshold = settings('low_stock_threshold', 10);
        $lowStockCount = Inventory::whereRaw("quantity_in_stock <= COALESCE(min_stock_level, ?)", [$globalThreshold])->count();
        $topSellingCount = Sale::distinct('inventory_id')->count(); // Just a rough number
        
        return view('orders.suggestions.index', compact('lowStockCount', 'topSellingCount'));
    }

    /**
     * Suggestions based on top selling items
     */
    public function topSelling(Request $request)
    {
        $days = $request->get('days', 30);
        $limit = $request->get('limit', 20);

        $topSelling = Sale::select('inventory_id', DB::raw('SUM(quantity) as total_sold'))
            ->where('date', '>=', now()->subDays($days))
            ->with(['inventory'])
            ->groupBy('inventory_id')
            ->orderByDesc('total_sold')
            ->limit($limit)
            ->get();

        // Check current stock for these items
        $suggestions = $topSelling->map(function ($sale) {
            $inventory = $sale->inventory;
            if (!$inventory) return null;

            return [
                'product_id' => $inventory->id,
                'product_name' => $inventory->product_name,
                'category' => $inventory->category,
                'current_stock' => $inventory->quantity_in_stock,
                'min_stock' => $inventory->min_stock_level,
                'total_sold' => $sale->total_sold,
                'suggested_reorder' => max(0, $inventory->max_stock - $inventory->quantity_in_stock),
            ];
        })->filter();

        return view('orders.suggestions.top-selling', compact('suggestions', 'days'));
    }

    /**
     * Suggestions based on low stock
     */
    public function lowStock()
    {
        $globalThreshold = settings('low_stock_threshold', 10);
        $lowStockItems = Inventory::whereRaw("quantity_in_stock <= COALESCE(min_stock_level, ?)", [$globalThreshold])
            ->orWhereColumn('quantity_in_stock', '<=', 'reorder_threshold')
            ->orderBy('quantity_in_stock', 'asc')
            ->get();

        return view('orders.suggestions.low-stock', compact('lowStockItems'));
    }

    /**
     * Suggestions grouped by supplier
     */
    public function bySupplier()
    {
        // 1. Get low stock items
        $globalThreshold = settings('low_stock_threshold', 10);
        $lowStockItems = Inventory::whereRaw("quantity_in_stock <= COALESCE(min_stock_level, ?)", [$globalThreshold])
             ->get();

        $suggestionsBySupplier = [];
        $noSupplierItems = [];

        foreach ($lowStockItems as $item) {
            // Find last purchase order item for this product
            $lastPoItem = PurchaseOrderItem::where('product_id', $item->id)
                ->latest('created_at')
                ->with('order.supplier')
                ->first();

            if ($lastPoItem && $lastPoItem->order && $lastPoItem->order->supplier) {
                $supplierName = $lastPoItem->order->supplier->name;
                $supplierId = $lastPoItem->order->supplier->id;

                if (!isset($suggestionsBySupplier[$supplierId])) {
                    $suggestionsBySupplier[$supplierId] = [
                        'supplier_name' => $supplierName,
                        'supplier_id' => $supplierId,
                        'items' => []
                    ];
                }

                $suggestionsBySupplier[$supplierId]['items'][] = $item;
            } else {
                $noSupplierItems[] = $item;
            }
        }

        return view('orders.suggestions.by-supplier', compact('suggestionsBySupplier', 'noSupplierItems'));
    }
}
