<?php

namespace App\Http\Controllers;

use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Location;
use App\Models\Batch;
use App\Models\Inventory;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
    /**
     * List transfers
     */
    public function index(Request $request)
    {
        $query = StockTransfer::with(['fromLocation', 'toLocation', 'user', 'items.inventory', 'items.batch'])
            ->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate(20));
    }

    /**
     * Get locations for dropdown
     */
    public function locations()
    {
        return response()->json(Location::where('is_active', true)->get());
    }

    /**
     * Create a new transfer (Draft/Pending)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'from_location_id' => 'nullable|exists:locations,id',
            'to_location_id' => 'nullable|exists:locations,id', // Nullable if to "Default Store" (usually id 1 or null)
            'notes' => 'nullable|string',
            'transfer_date' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.inventory_id' => 'required|exists:inventory_master,id',
            'items.*.batch_id' => 'nullable', 
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validated['from_location_id'] == $validated['to_location_id']) {
            return response()->json(['error' => 'Source and Destination cannot be the same.'], 400);
        }

        DB::beginTransaction();
        try {
            $transfer = StockTransfer::create([
                'from_location_id' => $validated['from_location_id'],
                'to_location_id' => $validated['to_location_id'],
                'user_id' => $request->user()->id,
                'status' => 'pending',
                'notes' => $validated['notes'],
                'transfer_date' => $validated['transfer_date'] ?? now(),
            ]);

            foreach ($validated['items'] as $item) {
                // 1. Handle Explicit Batch Selection
                if (!empty($item['batch_id']) && $item['batch_id'] !== 'legacy') {
                    // Verify batch exists and belongs to product
                     $batch = Batch::find($item['batch_id']);
                     // Validation checks...
                    if ($batch->inventory_id != $item['inventory_id']) throw new \Exception("Batch {$batch->batch_number} does not match product.");
                    if ($batch->location_id != $validated['from_location_id']) throw new \Exception("Batch {$batch->batch_number} is not at the source location.");
                    
                     StockTransferItem::create([
                        'stock_transfer_id' => $transfer->id,
                        'inventory_id' => $item['inventory_id'],
                        'batch_id' => $item['batch_id'],
                        'quantity' => $item['quantity'],
                    ]);
                    continue; // Done with this item
                }
                
                 // 2. Handle Legacy Batch Auto-Creation
                if (($item['batch_id'] ?? '') === 'legacy') {
                    // Create a system-generated batch to hold this stock
                    $batchNumber = 'SYS-LEG-' . strtoupper(uniqid());
                    $inventory = Inventory::find($item['inventory_id']);
                    
                    $batch = Batch::create([
                        'inventory_id' => $item['inventory_id'],
                        'batch_number' => $batchNumber,
                        'quantity' => $item['quantity'], 
                        'location_id' => $validated['from_location_id'],
                        'expiry_date' => null, 
                        'cost_price' => $inventory->price ?? 0,
                        'selling_price' => $inventory->selling_price ?? 0,
                    ]);
                    
                     StockTransferItem::create([
                        'stock_transfer_id' => $transfer->id,
                        'inventory_id' => $item['inventory_id'],
                        'batch_id' => $batch->id,
                        'quantity' => $item['quantity'],
                    ]);
                    continue;
                }

                // 3. Handle No Batch Selected (FIFO Auto-Assignment)
                if (empty($item['batch_id'])) {
                    // Find oldest batches with stock at source location
                    $batches = Batch::where('inventory_id', $item['inventory_id'])
                                    ->where('location_id', $validated['from_location_id'])
                                    ->where('quantity', '>', 0)
                                    ->orderBy('expiry_date', 'asc') // FIFO
                                    ->orderBy('created_at', 'asc')
                                    ->get();

                    $qtyNeeded = $item['quantity'];
                    $qtyCovered = 0;
                    
                    // 1. Take from existing batches
                    foreach ($batches as $batch) {
                        if ($qtyNeeded <= 0) break;

                        $qtyToTake = min($qtyNeeded, $batch->quantity);
                        
                        StockTransferItem::create([
                            'stock_transfer_id' => $transfer->id,
                            'inventory_id' => $item['inventory_id'],
                            'batch_id' => $batch->id,
                            'quantity' => $qtyToTake,
                        ]);

                        $qtyNeeded -= $qtyToTake;
                        $qtyCovered += $qtyToTake;
                    }

                    // 2. If still needed, check for Unbatched/Legacy Stock (Only at Main Store/Null Location)
                    if ($qtyNeeded > 0 && empty($validated['from_location_id'])) {
                         $inventory = Inventory::find($item['inventory_id']);
                         // Calculate Unbatched Stock: Total Stock - Sum of ALL batches (anywhere)
                         $totalBatched = Batch::where('inventory_id', $item['inventory_id'])->sum('quantity');
                         $unbatchedLegacy = $inventory->quantity_in_stock - $totalBatched;
                         
                         if ($unbatchedLegacy >= $qtyNeeded) {
                             // Create a new Batch for this legacy stock
                             $batchNumber = 'SYS-LEG-' . strtoupper(uniqid());
                             $newBatch = Batch::create([
                                'inventory_id' => $item['inventory_id'],
                                'batch_number' => $batchNumber,
                                'quantity' => $qtyNeeded, // Move just what is needed
                                'location_id' => null, // At Main Store
                                'expiry_date' => null, 
                                'cost_price' => $inventory->price ?? 0,
                                'selling_price' => $inventory->selling_price ?? 0,
                            ]);
                            
                            StockTransferItem::create([
                                'stock_transfer_id' => $transfer->id,
                                'inventory_id' => $item['inventory_id'],
                                'batch_id' => $newBatch->id,
                                'quantity' => $qtyNeeded,
                            ]);
                            
                            $qtyNeeded = 0; // Fulfilled
                         }
                    }

                    if ($qtyNeeded > 0) {
                        $inv = Inventory::find($item['inventory_id']);
                        throw new \Exception("Insufficient stock for {$inv->product_name} at source location. (Needed: {$item['quantity']}, Found in Batches: {$batches->sum('quantity')})");
                    }
                }
            }

            DB::commit();
            return response()->json($transfer->load('items'), 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Complete Transfer (Move Stock)
     */
    public function complete(Request $request, $id)
    {
        $transfer = StockTransfer::with('items')->find($id);
        
        if (!$transfer) {
            return response()->json(['error' => 'Transfer not found'], 404);
        }

        if ($transfer->status !== 'pending') {
            return response()->json(['error' => 'Transfer is already ' . $transfer->status], 400);
        }

        DB::beginTransaction();
        try {
            foreach ($transfer->items as $item) {
                // 1. DEDUCT FROM SOURCE
                if ($item->batch_id) {
                    $sourceBatch = Batch::lockForUpdate()->find($item->batch_id);
                    if (!$sourceBatch) throw new \Exception("Source batch not found for item {$item->id}");
                    if ($sourceBatch->quantity < $item->quantity) {
                        throw new \Exception("Insufficient quantity in source batch {$sourceBatch->batch_number}.");
                    }
                    $sourceBatch->decrement('quantity', $item->quantity);
                    
                    // 2. ADD TO DESTINATION
                    // Find or create batch at destination with SAME properties
                    $destBatch = Batch::where('inventory_id', $item->inventory_id)
                        ->where('batch_number', $sourceBatch->batch_number)
                        ->where('location_id', $transfer->to_location_id)
                        ->first();

                    if ($destBatch) {
                        $destBatch->increment('quantity', $item->quantity);
                    } else {
                        Batch::create([
                            'inventory_id' => $item->inventory_id,
                            'batch_number' => $sourceBatch->batch_number,
                            'expiry_date' => $sourceBatch->expiry_date,
                            'quantity' => $item->quantity,
                            'cost_price' => $sourceBatch->cost_price,
                            'selling_price' => $sourceBatch->selling_price,
                            'location_id' => $transfer->to_location_id,
                        ]);
                    }

                } else {
                    // Legacy/FIFO logic if no batch specified?
                    // For now, fail if no batch in strict medical mode.
                    // Or implement FIFO deduction from source location and pushing to dest?
                    // Let's assume strict batch for now as per requirements.
                    throw new \Exception("Batch ID is required for stock transfer.");
                }
            }

            $transfer->status = 'completed';
            $transfer->save();

            // Calculate total value for accounting entry
            $totalValue = 0;
            foreach ($transfer->items as $item) {
                $batch = Batch::find($item->batch_id);
                if ($batch) {
                    $totalValue += $batch->cost_price * $item->quantity;
                }
            }
            $transfer->total_value = $totalValue;

            // Record accounting entry for stock transfer
            $accountingService = new AccountingService();
            $accountingService->recordStockTransfer($transfer, $request->user());

            DB::commit();
            return response()->json(['message' => 'Transfer completed successfully', 'transfer' => $transfer]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
    public function view()
    {
        return view('stock_transfers.index');
    }

    public function createView()
    {
        return view('stock_transfers.create');
    }
}
