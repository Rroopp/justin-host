<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    protected $fillable = [
        'batch_id',
        'inventory_id',
        'movement_type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'from_location_id',
        'to_location_id',
        'reference_type',
        'reference_id',
        'reason',
        'notes',
        'unit_cost',
        'total_value',
        'performed_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'quantity_before' => 'integer',
        'quantity_after' => 'integer',
        'unit_cost' => 'decimal:2',
        'total_value' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'performed_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by');
    }

    /**
     * Get the reference model (polymorphic-like behavior)
     */
    public function reference()
    {
        if (!$this->reference_type || !$this->reference_id) {
            return null;
        }

        $modelMap = [
            'purchase_order' => PurchaseOrder::class,
            'stock_transfer' => StockTransfer::class,
            'pos_sale' => PosSale::class,
            'inventory_adjustment' => InventoryAdjustment::class,
            'consignment_transaction' => ConsignmentTransaction::class,
            'case_reservation' => CaseReservation::class,
        ];

        $modelClass = $modelMap[$this->reference_type] ?? null;
        
        return $modelClass ? $modelClass::find($this->reference_id) : null;
    }

    /**
     * Scopes
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('movement_type', $type);
    }

    public function scopeForInventory($query, int $inventoryId)
    {
        return $query->where('inventory_id', $inventoryId);
    }

    public function scopeForBatch($query, int $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    public function scopeAtLocation($query, int $locationId)
    {
        return $query->where(function($q) use ($locationId) {
            $q->where('from_location_id', $locationId)
              ->orWhere('to_location_id', $locationId);
        });
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeAdditions($query)
    {
        return $query->where('quantity', '>', 0);
    }

    public function scopeReductions($query)
    {
        return $query->where('quantity', '<', 0);
    }

    /**
     * Helper Methods
     */
    public function isAddition(): bool
    {
        return $this->quantity > 0;
    }

    public function isReduction(): bool
    {
        return $this->quantity < 0;
    }

    public function isApproved(): bool
    {
        return !is_null($this->approved_at);
    }

    public function getMovementTypeLabel(): string
    {
        return match($this->movement_type) {
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
            default => ucfirst(str_replace('_', ' ', $this->movement_type)),
        };
    }

    public function getMovementTypeColorAttribute(): string
    {
        return match($this->movement_type) {
            'receipt', 'return', 'consignment_return' => 'green',
            'sale', 'usage', 'consignment_sale' => 'blue',
            'transfer', 'reservation' => 'yellow',
            'adjustment' => 'orange',
            'write_off' => 'red',
            'consignment_out' => 'purple',
            default => 'gray',
        };
    }

    /**
     * Static helper to log a movement
     */
    public static function logMovement(array $data): self
    {
        // Calculate total value if not provided
        if (!isset($data['total_value']) && isset($data['quantity'], $data['unit_cost'])) {
            $data['total_value'] = abs($data['quantity']) * $data['unit_cost'];
        }

        // Set performed_by to current user if not provided
        if (!isset($data['performed_by']) && auth()->check()) {
            $data['performed_by'] = auth()->id();
        }

        return self::create($data);
    }
}
