<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryAdjustment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryService
{
    /**
     * Receive new stock and update Weighted Average Cost (WAC).
     * 
     * Formula: 
     * New WAC = ((Old Qty * Old WAC) + (New Qty * Unit Cost)) / (Old Qty + New Qty)
     * 
     * @param Inventory $inventory The product model
     * @param int $quantity Quantity being received
     * @param float $unitCost Cost per unit
     * @param string $reason Reason for movement (e.g., 'Purchase Order', 'Restock')
     * @param string|null $notes Additional notes
     * @return InventoryAdjustment The created adjustment record
     */
    public function receiveStock(Inventory $inventory, int $quantity, float $unitCost, string $reason = 'Restock', ?string $notes = null, $user = null)
    {
        return DB::transaction(function () use ($inventory, $quantity, $unitCost, $reason, $notes, $user) {
            $oldQuantity = $inventory->quantity_in_stock;
            // Handle edge case where moving_average_cost might be 0/null initially
            $oldWac = $inventory->moving_average_cost > 0 ? $inventory->moving_average_cost : $inventory->price;
            
            $totalOldValue = $oldQuantity * $oldWac;
            $totalNewValue = $quantity * $unitCost;
            
            $newQuantity = $oldQuantity + $quantity;
            
            // Avoid division by zero (shouldn't happen on receive, but for safety)
            if ($newQuantity > 0) {
                $newWac = ($totalOldValue + $totalNewValue) / $newQuantity;
            } else {
                $newWac = $unitCost; // Fallback
            }

            // Update Inventory
            $inventory->moving_average_cost = $newWac;
            $inventory->quantity_in_stock = $newQuantity;
            $inventory->save();

            // Create Adjustment/Movement Record
            $adjustment = InventoryAdjustment::create([
                'inventory_id' => $inventory->id,
                'staff_id' => $user ? $user->id : null,
                'adjustment_type' => 'increase',
                'quantity' => $quantity,
                'old_quantity' => $oldQuantity,
                'new_quantity' => $newQuantity,
                'reason' => $reason,
                'notes' => $notes . " (WAC updated from " . number_format($oldWac, 2) . " to " . number_format($newWac, 2) . ")",
            ]);

            Log::info("InventoryService: Received {$quantity} of {$inventory->code}. WAC updated: {$oldWac} -> {$newWac}");

            return $adjustment;
        });
    }

    /**
     * Calculate COGS for a sale based on current WAC.
     * Uses FIFO logic conceptually, but WAC for valuation.
     */
    public function calculateCOGS(Inventory $inventory, int $quantity): float
    {
        // Use moving average cost if available, otherwise fallback to buying price
        $cost = $inventory->moving_average_cost > 0 ? $inventory->moving_average_cost : $inventory->price;
        return $cost * $quantity;
    }
}
