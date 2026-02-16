<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryAdjustment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class InventoryAdjustmentController extends Controller
{
    /**
     * List adjustments for an inventory item.
     */
    public function index(Request $request, Inventory $inventory)
    {
        try {
            $perPage = (int) ($request->get('per_page', 50));
            $perPage = max(1, min(200, $perPage));

            $adjustments = InventoryAdjustment::where('inventory_id', $inventory->id)
                ->orderByDesc('created_at')
                ->paginate($perPage);

            return response()->json($adjustments);
        } catch (\Exception $e) {
            Log::error('Inventory Adjustment Index Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json(['error' => 'Server Error'], 500);
        }
    }

    /**
     * Create an adjustment and update stock atomically.
     */
    public function store(Request $request, Inventory $inventory)
    {
        $validated = $request->validate([
            'adjustment_type' => 'required|in:increase,decrease,set',
            'quantity' => 'required|integer|min:0',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $actor = $request->user();
        $staffId = $actor?->id;

        $result = DB::transaction(function () use ($inventory, $validated, $staffId) {
            /** @var \App\Models\Inventory $locked */
            $locked = Inventory::where('id', $inventory->id)->lockForUpdate()->firstOrFail();

            $oldQty = (int) $locked->quantity_in_stock;
            $type = $validated['adjustment_type'];
            $qty = (int) $validated['quantity'];

            if ($type === 'increase') {
                if ($qty < 1) {
                    throw ValidationException::withMessages([
                        'quantity' => ['Quantity must be at least 1 for increase.'],
                    ]);
                }
                $newQty = $oldQty + $qty;
            } elseif ($type === 'decrease') {
                if ($qty < 1) {
                    throw ValidationException::withMessages([
                        'quantity' => ['Quantity must be at least 1 for decrease.'],
                    ]);
                }
                if ($qty > $oldQty) {
                    throw ValidationException::withMessages([
                        'quantity' => ['Cannot decrease more than current stock.'],
                    ]);
                }
                $newQty = $oldQty - $qty;
            } else { // set
                $newQty = $qty;
            }

            $locked->update(['quantity_in_stock' => $newQty]);

            $adjustment = InventoryAdjustment::create([
                'inventory_id' => $locked->id,
                'staff_id' => $staffId,
                'adjustment_type' => $type,
                'quantity' => $qty,
                'old_quantity' => $oldQty,
                'new_quantity' => $newQty,
                'reason' => $validated['reason'],
                'notes' => $validated['notes'] ?? null,
            ]);

            return [$locked, $adjustment];
        });

        /** @var \App\Models\Inventory $updatedInventory */
        /** @var \App\Models\InventoryAdjustment $adjustment */
        [$updatedInventory, $adjustment] = $result;

        // Accounting Integration
        try {
            $accounting = new \App\Services\AccountingService();
            $accounting->recordInventoryAdjustment($adjustment, $request->user());
        } catch (\Exception $e) {
            Log::error('Failed to record inventory adjustment journal: ' . $e->getMessage());
            // Don't fail the request, just log it
        }

        // Audit Logging
        \App\Services\AuditService::log(
            'adjust',
            'inventory',
            "Adjusted stock for {$updatedInventory->product_name} ({$adjustment->adjustment_type}): {$adjustment->quantity} units. Reason: {$adjustment->reason}",
            $updatedInventory,
            ['quantity_in_stock' => $adjustment->old_quantity],
            ['quantity_in_stock' => $updatedInventory->quantity_in_stock]
        );

        return response()->json([
            'message' => 'Stock adjusted successfully',
            'inventory' => $updatedInventory,
            'adjustment' => $adjustment,
        ], 201);
    }
}


