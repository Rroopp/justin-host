<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryHealthController extends Controller
{
    /**
     * Display inventory health dashboard
     */
    public function index()
    {
        $totalItems = Inventory::count();
        $totalStockQuantity = Inventory::sum('quantity_in_stock');
        
        // Calculate estimated total value (Cost Basis)
        // Assuming 'price' is the cost price.
        $totalInventoryValue = Inventory::select(DB::raw('SUM(price * quantity_in_stock) as total_value'))->value('total_value');
        
        // Potential Revenue (Retail Basis)
        $potentialRevenue = Inventory::select(DB::raw('SUM(selling_price * quantity_in_stock) as total_revenue'))->value('total_revenue');

        $lowStockCount = Inventory::whereColumn('quantity_in_stock', '<=', 'min_stock_level')->count();
        $outOfStockCount = Inventory::where('quantity_in_stock', '<=', 0)->count();

        // Expiring Soon (if expiry_date is used)
        $expiringSoonCount = Inventory::whereNotNull('expiry_date')
            ->where('expiry_date', '>', now())
            ->where('expiry_date', '<=', now()->addDays(30))
            ->count();
            
        // Dead Stock candidates (simplified: added long ago and high quantity? or just simple list)
        // Without last_sold_at, it's hard to be precise. 
        // We could look at created_at if no sales exist?
        // For now, let's skip complex dead stock logic until we have better tracking.

        return view('inventory.health', compact(
            'totalItems',
            'totalStockQuantity',
            'totalInventoryValue',
            'potentialRevenue',
            'lowStockCount',
            'outOfStockCount',
            'expiringSoonCount'
        ));
    }
}
