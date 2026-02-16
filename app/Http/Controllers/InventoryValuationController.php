<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Batch;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryValuationController extends Controller
{
    public function index(Request $request)
    {
        $groupBy = $request->get('group_by', 'location'); // location, category, ownership

        // Total inventory value
        $totalValue = Batch::where('status', 'available')
            ->selectRaw('SUM(quantity * cost_price) as value')
            ->value('value') ?? 0;

        // Total units
        $totalUnits = Batch::where('status', 'available')->sum('quantity');

        // Count of unique products
        $uniqueProducts = Batch::where('status', 'available')
            ->distinct('inventory_id')
            ->count('inventory_id');

        // Valuation by location
        $byLocation = Batch::where('status', 'available')
            ->join('locations', 'batches.location_id', '=', 'locations.id')
            ->selectRaw('locations.name as location, 
                         SUM(batches.quantity * batches.cost_price) as value,
                         SUM(batches.quantity) as units,
                         COUNT(DISTINCT batches.inventory_id) as products')
            ->groupBy('locations.id', 'locations.name')
            ->orderByDesc('value')
            ->get();

        // Valuation by category
        $byCategory = Batch::where('batches.status', 'available')
            ->join('inventory_master', 'batches.inventory_id', '=', 'inventory_master.id')
            ->selectRaw('inventory_master.category,
                         SUM(batches.quantity * batches.cost_price) as value,
                         SUM(batches.quantity) as units,
                         COUNT(DISTINCT batches.inventory_id) as products')
            ->groupBy('inventory_master.category')
            ->orderByDesc('value')
            ->get();

        // Valuation by ownership type
        $byOwnership = Batch::where('status', 'available')
            ->selectRaw('ownership_type,
                         SUM(quantity * cost_price) as value,
                         SUM(quantity) as units,
                         COUNT(DISTINCT inventory_id) as products')
            ->groupBy('ownership_type')
            ->get();

        // Aging analysis (batches older than 90, 180, 365 days)
        $aging = [
            'fresh' => Batch::where('status', 'available')
                ->where('created_at', '>=', now()->subDays(90))
                ->selectRaw('SUM(quantity * cost_price) as value, SUM(quantity) as units')
                ->first(),
            'aging_90' => Batch::where('status', 'available')
                ->whereBetween('created_at', [now()->subDays(180), now()->subDays(90)])
                ->selectRaw('SUM(quantity * cost_price) as value, SUM(quantity) as units')
                ->first(),
            'aging_180' => Batch::where('status', 'available')
                ->whereBetween('created_at', [now()->subDays(365), now()->subDays(180)])
                ->selectRaw('SUM(quantity * cost_price) as value, SUM(quantity) as units')
                ->first(),
            'aging_365' => Batch::where('status', 'available')
                ->where('created_at', '<', now()->subDays(365))
                ->selectRaw('SUM(quantity * cost_price) as value, SUM(quantity) as units')
                ->first(),
        ];

        // Top 10 most valuable products
        $topProducts = Batch::where('batches.status', 'available')
            ->join('inventory_master', 'batches.inventory_id', '=', 'inventory_master.id')
            ->selectRaw('inventory_master.product_name,
                         inventory_master.code,
                         SUM(batches.quantity * batches.cost_price) as value,
                         SUM(batches.quantity) as units')
            ->groupBy('batches.inventory_id', 'inventory_master.product_name', 'inventory_master.code')
            ->orderByDesc('value')
            ->limit(10)
            ->get();

        // Expiring soon (next 90 days)
        $expiringSoon = Batch::where('status', 'available')
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now(), now()->addDays(90)])
            ->selectRaw('SUM(quantity * cost_price) as value, SUM(quantity) as units')
            ->first();

        return view('inventory.valuation.index', compact(
            'totalValue',
            'totalUnits',
            'uniqueProducts',
            'byLocation',
            'byCategory',
            'byOwnership',
            'aging',
            'topProducts',
            'expiringSoon',
            'groupBy'
        ));
    }
}
