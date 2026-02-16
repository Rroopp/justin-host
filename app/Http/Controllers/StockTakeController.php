<?php

namespace App\Http\Controllers;

use App\Models\StockTake;
use App\Models\StockTakeItem;
use App\Models\Inventory;
use App\Models\InventoryAdjustment;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockTakeController extends Controller
{
    use \App\Traits\CsvExportable;

    /**
     * Display a listing of stock takes
     */
    public function index(Request $request)
    {
        $query = StockTake::with(['creator', 'approver']);

        // Status filter
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Date range filter
        if ($request->has('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        $stockTakes = $query->orderBy('date', 'desc')->paginate(50);

        if ($request->expectsJson()) {
            return response()->json($stockTakes);
        }

        return view('stock-takes.index', compact('stockTakes'));
    }

    /**
     * Show the form for creating a new stock take
     */
    public function create()
    {
        $categories = Inventory::distinct()->pluck('category')->filter()->values();
        
        return view('stock-takes.create', compact('categories'));
    }

    /**
     * Store a newly created stock take
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'category_filter' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Generate reference number
            $validated['reference_number'] = StockTake::generateReferenceNumber();
            $validated['created_by'] = $request->user()->id;
            $validated['status'] = 'draft';

            $stockTake = StockTake::create($validated);


            // Get inventory items based on category filter
            // Inventory uses SoftDeletes, so deleted items are automatically excluded
            $inventoryQuery = Inventory::query();
            
            if (!empty($validated['category_filter'])) {
                $inventoryQuery->whereIn('category', $validated['category_filter']);
            }

            $inventoryItems = $inventoryQuery->orderBy('category')->orderBy('product_name')->get();

            // Create stock take items
            foreach ($inventoryItems as $item) {
                StockTakeItem::create([
                    'stock_take_id' => $stockTake->id,
                    'inventory_id' => $item->id,
                    'system_quantity' => $item->quantity_in_stock,
                ]);
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json($stockTake->load('items'), 201);
            }

            return redirect()->route('stock-takes.show', $stockTake)
                ->with('success', 'Stock take created successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to create stock take: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified stock take
     */
    public function show(StockTake $stockTake)
    {
        $stockTake->load(['items.inventory', 'creator', 'approver']);

        // Group items by category for easier counting
        $itemsByCategory = $stockTake->items->groupBy(function($item) {
            return $item->inventory?->category ?? 'Uncategorized';
        });

        // Prepare counts data for Alpine.js
        $countsData = [];
        foreach ($stockTake->items as $item) {
            $countsData[$item->id] = [
                'item_id' => $item->id,
                'physical_quantity' => $item->physical_quantity,
                'notes' => $item->notes ?? '',
                'variance' => $item->variance
            ];
        }

        return view('stock-takes.show', compact('stockTake', 'itemsByCategory', 'countsData'));
    }

    /**
     * Generate printable stock take sheet
     */
    public function generateSheet(StockTake $stockTake)
    {
        $stockTake->load(['items.inventory']);

        // Group items by category
        $itemsByCategory = $stockTake->items->groupBy(function($item) {
            return $item->inventory?->category ?? 'Uncategorized';
        });

        return view('stock-takes.print-sheet', compact('stockTake', 'itemsByCategory'));
    }

    /**
     * Update physical counts
     */
    public function updateCounts(Request $request, StockTake $stockTake)
    {
        if (!$stockTake->isEditable()) {
            return redirect()->back()->with('error', 'This stock take cannot be edited');
        }

        $validated = $request->validate([
            'counts' => 'required|array',
            'counts.*.item_id' => 'required|exists:stock_take_items,id',
            'counts.*.physical_quantity' => 'nullable|numeric|min:0',
            'counts.*.notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            foreach ($validated['counts'] as $count) {
                $item = StockTakeItem::findOrFail($count['item_id']);
                
                if ($item->stock_take_id !== $stockTake->id) {
                    continue; // Security check
                }

                $item->physical_quantity = $count['physical_quantity'] ?? null;
                $item->notes = $count['notes'] ?? null;
                $item->calculateVariance();
            }

            // Update stock take status
            if ($stockTake->status === 'draft') {
                $stockTake->status = 'in_progress';
                $stockTake->save();
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Counts updated successfully']);
            }

            return redirect()->back()->with('success', 'Counts updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }

            return redirect()->back()->with('error', 'Failed to update counts: ' . $e->getMessage());
        }
    }

    /**
     * Reconcile stock take and update inventory
     */
    public function reconcile(Request $request, StockTake $stockTake)
    {
        if (!$stockTake->canReconcile()) {
            return redirect()->back()->with('error', 'This stock take cannot be reconciled');
        }

        DB::beginTransaction();
        try {
            $accountingService = new AccountingService();

            foreach ($stockTake->items as $item) {
                if (!$item->hasVariance()) {
                    continue; // Skip items with no variance
                }

                $inventory = $item->inventory;
                
                if (!$inventory) {
                    continue; // Skip items where inventory has been permanently deleted
                }

                $oldQuantity = $inventory->quantity_in_stock;
                $newQuantity = $item->physical_quantity;

                // Create inventory adjustment record
                $adjustment = InventoryAdjustment::create([
                    'inventory_id' => $inventory->id,
                    'staff_id' => $request->user()->id,
                    'adjustment_type' => $item->variance > 0 ? 'increase' : 'decrease',
                    'quantity' => abs($item->variance),
                    'old_quantity' => $oldQuantity,
                    'new_quantity' => $newQuantity,
                    'reason' => 'Stock Take Reconciliation',
                    'notes' => "Stock Take #{$stockTake->reference_number}" . 
                              ($item->notes ? " - {$item->notes}" : ''),
                ]);

                // Update inventory quantity
                $inventory->quantity_in_stock = $newQuantity;
                $inventory->save();

                // Record accounting entry
                $accountingService->recordInventoryAdjustment($adjustment, $request->user());
            }

            // Update stock take status
            $stockTake->status = 'reconciled';
            $stockTake->approved_by = $request->user()->id;
            $stockTake->save();

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Stock take reconciled successfully']);
            }

            return redirect()->route('stock-takes.show', $stockTake)
                ->with('success', 'Stock take reconciled successfully. Inventory has been updated.');

        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }

            return redirect()->back()->with('error', 'Failed to reconcile: ' . $e->getMessage());
        }
    }

    /**
     * Export stock take to CSV
     */
    public function export(StockTake $stockTake)
    {
        $stockTake->load(['items.inventory']);

        $data = $stockTake->items->map(function($item) {
            return [
                'Code' => $item->inventory?->code ?? 'N/A',
                'Product' => $item->inventory?->product_name ?? 'Unknown Item',
                'Category' => $item->inventory?->category ?? 'Uncategorized',
                'System Qty' => $item->system_quantity,
                'Physical Qty' => $item->physical_quantity ?? '',
                'Variance' => $item->variance ?? '',
                'Variance %' => $item->variance_percentage ? number_format($item->variance_percentage, 2) . '%' : '',
                'Notes' => $item->notes ?? '',
            ];
        });

        $filename = "stock-take-{$stockTake->reference_number}.csv";
        $headers = ['Code', 'Product', 'Category', 'System Qty', 'Physical Qty', 'Variance', 'Variance %', 'Notes'];
        
        return $this->streamCsv($filename, $headers, $data, "Stock Take {$stockTake->reference_number}");
    }

    /**
     * Mark stock take as completed (ready for reconciliation)
     */
    public function complete(Request $request, StockTake $stockTake)
    {
        if ($stockTake->status !== 'in_progress') {
            return redirect()->back()->with('error', 'Only in-progress stock takes can be completed');
        }

        // Check if all items have been counted
        $uncountedItems = $stockTake->items()->whereNull('physical_quantity')->count();
        
        if ($uncountedItems > 0) {
            return redirect()->back()
                ->with('warning', "There are {$uncountedItems} items that haven't been counted. Complete anyway?");
        }

        $stockTake->status = 'completed';
        $stockTake->save();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Stock take marked as completed']);
        }

        return redirect()->back()->with('success', 'Stock take completed. You can now reconcile it.');
    }
}
