<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Inventory;
use App\Models\Location;
use Illuminate\Http\Request;
use Carbon\Carbon;

class InventoryMovementController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfDay()->format('Y-m-d'));
        $movementType = $request->get('movement_type');
        $locationId = $request->get('location_id');
        $inventoryId = $request->get('inventory_id');
        
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        // Build query
        $query = InventoryMovement::with([
            'inventory',
            'batch',
            'fromLocation',
            'toLocation',
            'performedBy'
        ])
        ->whereBetween('created_at', [$start, $end]);

        // Apply filters
        if ($movementType) {
            $query->where('movement_type', $movementType);
        }

        if ($locationId) {
            $query->where(function($q) use ($locationId) {
                $q->where('from_location_id', $locationId)
                  ->orWhere('to_location_id', $locationId);
            });
        }

        if ($inventoryId) {
            $query->where('inventory_id', $inventoryId);
        }

        if ($request->has('batch_id')) {
            $query->where('batch_id', $request->get('batch_id'));
        }

        // Get movements with pagination
        $movements = $query->latest()->paginate(50);

        // Summary statistics
        $totalMovements = $query->count();
        $totalValue = $query->sum('total_value');
        $additions = InventoryMovement::whereBetween('created_at', [$start, $end])
            ->where('quantity', '>', 0)
            ->sum('quantity');
        $reductions = InventoryMovement::whereBetween('created_at', [$start, $end])
            ->where('quantity', '<', 0)
            ->sum('quantity');

        // Movement type breakdown
        $movementsByType = InventoryMovement::whereBetween('created_at', [$start, $end])
            ->selectRaw('movement_type, COUNT(*) as count, SUM(ABS(quantity)) as total_qty')
            ->groupBy('movement_type')
            ->get();

        // Get filter options
        $locations = Location::orderBy('name')->get();
        $products = Inventory::orderBy('product_name')->get();
        $movementTypes = [
            'receipt' => 'Goods Receipt',
            'transfer' => 'Stock Transfer',
            'reservation' => 'Reserved',
            'usage' => 'Used in Surgery',
            'sale' => 'Sale',
            'return' => 'Return',
            'adjustment' => 'Adjustment',
            'write_off' => 'Write-off',
            'consignment_out' => 'Consignment Out',
            'consignment_return' => 'Consignment Return',
            'consignment_sale' => 'Consignment Sale',
        ];

        return view('inventory.movements.index', compact(
            'movements',
            'startDate',
            'endDate',
            'movementType',
            'locationId',
            'inventoryId',
            'totalMovements',
            'totalValue',
            'additions',
            'reductions',
            'movementsByType',
            'locations',
            'products',
            'movementTypes'
        ));
    }

    public function show($id)
    {
        $movement = InventoryMovement::with([
            'inventory',
            'batch',
            'fromLocation',
            'toLocation',
            'performedBy',
            'approvedBy'
        ])->findOrFail($id);

        return view('inventory.movements.show', compact('movement'));
    }
}
