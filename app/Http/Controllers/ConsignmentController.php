<?php

namespace App\Http\Controllers;

use App\Models\ConsignmentTransaction;
use App\Models\ConsignmentStockLevel;
use App\Models\Location;
use App\Models\Inventory;
use App\Models\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConsignmentController extends Controller
{
    /**
     * Dashboard - Overview of consignment operations
     */
    public function index()
    {
        $consignmentLocations = Location::where('type', 'consignment')->where('is_active', true)->get();
        
        $stats = [
            'total_locations' => $consignmentLocations->count(),
            'total_stock_value' => ConsignmentStockLevel::get()->sum('value'),
            'unbilled_amount' => ConsignmentTransaction::unbilled()->get()->sum('value'),
            'aging_items' => ConsignmentStockLevel::aging(90)->count(),
            'pending_surgery_sales' => \App\Models\PosSale::where('sale_status', 'consignment')->where('is_reconciled', false)->count(),
        ];

        return view('consignment.index', compact('consignmentLocations', 'stats'));
    }

    /**
     * Show stock at specific location
     */
    public function locationStock(Location $location)
    {
        if (!$location->isConsignment()) {
            return redirect()->route('consignment.index')->with('error', 'Not a consignment location');
        }

        $stock = $location->consignmentStock()->with(['inventory', 'batch'])->get();
        $transactions = $location->consignmentTransactions()->with(['inventory', 'createdBy'])->latest()->paginate(50);

        return view('consignment.location-stock', compact('location', 'stock', 'transactions'));
    }

    /**
     * Place stock at consignment location
     */
    public function placeStock(Request $request)
    {
        $validated = $request->validate([
            'location_id' => 'required|exists:locations,id',
            'inventory_id' => 'required|exists:inventory_master,id',
            'batch_id' => 'nullable|exists:batches,id',
            'quantity' => 'required|integer|min:1',
            'transaction_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated) {
            // Create transaction
            $transaction = ConsignmentTransaction::create([
                ...$validated,
                'transaction_type' => 'placed',
                'created_by' => auth()->id(),
            ]);

            // Update or create stock level
            $this->updateStockLevel($validated['location_id'], $validated['inventory_id'], $validated['batch_id'] ?? null);
        });

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Stock placed successfully'], 201);
        }

        return redirect()->back()->with('success', 'Stock placed at consignment location');
    }

    /**
     * Mark stock as used (manual entry)
     */
    public function useStock(Request $request)
    {
        $validated = $request->validate([
            'location_id' => 'required|exists:locations,id',
            'inventory_id' => 'required|exists:inventory_master,id',
            'batch_id' => 'nullable|exists:batches,id',
            'quantity' => 'required|integer|min:1',
            'transaction_date' => 'required|date',
            'reference_type' => 'nullable|string',
            'reference_id' => 'nullable|integer',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated) {
            // Create transaction
            $transaction = ConsignmentTransaction::create([
                ...$validated,
                'transaction_type' => 'used',
                'billed' => false,
                'created_by' => auth()->id(),
            ]);

            // Update stock level
            $this->updateStockLevel($validated['location_id'], $validated['inventory_id'], $validated['batch_id'] ?? null);
        });

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Stock usage recorded'], 201);
        }

        return redirect()->back()->with('success', 'Stock usage recorded');
    }

    /**
     * Return unused stock
     */
    public function returnStock(Request $request)
    {
        $validated = $request->validate([
            'location_id' => 'required|exists:locations,id',
            'inventory_id' => 'required|exists:inventory_master,id',
            'batch_id' => 'nullable|exists:batches,id',
            'quantity' => 'required|integer|min:1',
            'transaction_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated) {
            // Create transaction
            $transaction = ConsignmentTransaction::create([
                ...$validated,
                'transaction_type' => 'returned',
                'created_by' => auth()->id(),
            ]);

            // Update stock level
            $this->updateStockLevel($validated['location_id'], $validated['inventory_id'], $validated['batch_id'] ?? null);
        });

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Stock return recorded'], 201);
        }

        return redirect()->back()->with('success', 'Stock return recorded');
    }

    /**
     * View unbilled transactions
     */
    public function unbilled(Request $request)
    {
        $query = ConsignmentTransaction::unbilled()->with(['location', 'inventory', 'batch', 'createdBy']);

        if ($request->has('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->inDateRange($request->start_date, $request->end_date);
        }

        $transactions = $query->latest('transaction_date')->paginate(50);
        $locations = Location::where('type', 'consignment')->where('is_active', true)->get();

        $totalUnbilled = $transactions->sum('value');

        return view('consignment.unbilled', compact('transactions', 'locations', 'totalUnbilled'));
    }

    /**
     * Generate bill for unbilled transactions
     */
    public function generateBill(Request $request)
    {
        $validated = $request->validate([
            'transaction_ids' => 'required|array',
            'transaction_ids.*' => 'exists:consignment_transactions,id',
            'billing_reference' => 'nullable|string',
        ]);

        $transactions = ConsignmentTransaction::whereIn('id', $validated['transaction_ids'])->get();

        DB::transaction(function () use ($transactions, $validated) {
            foreach ($transactions as $transaction) {
                $transaction->markAsBilled($validated['billing_reference'] ?? 'INV-' . now()->format('Ymd-His'));
            }
        });

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Transactions marked as billed', 'count' => $transactions->count()]);
        }

        return redirect()->back()->with('success', $transactions->count() . ' transactions marked as billed');
    }

    /**
     * Hospital-wise ledger
     */
    public function ledger(Location $location, Request $request)
    {
        if (!$location->isConsignment()) {
            return redirect()->route('consignment.index')->with('error', 'Not a consignment location');
        }

        $startDate = $request->input('start_date', now()->subMonths(3)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $transactions = $location->consignmentTransactions()
            ->with(['inventory', 'batch', 'createdBy'])
            ->inDateRange($startDate, $endDate)
            ->orderBy('transaction_date')
            ->get();

        $stock = $location->consignmentStock()->with(['inventory', 'batch'])->get();

        $summary = [
            'total_placed' => $transactions->where('transaction_type', 'placed')->sum('quantity'),
            'total_used' => $transactions->where('transaction_type', 'used')->sum('quantity'),
            'total_returned' => $transactions->where('transaction_type', 'returned')->sum('quantity'),
            'unbilled_amount' => $location->unbilled_amount,
            'current_stock_value' => $location->total_consignment_value,
        ];

        return view('consignment.ledger', compact('location', 'transactions', 'stock', 'summary', 'startDate', 'endDate'));
    }

    /**
     * Stock aging report
     */
    public function aging(Request $request)
    {
        $days = $request->input('days', 90);

        $agingStock = ConsignmentStockLevel::aging($days)
            ->with(['location', 'inventory', 'batch'])
            ->orderBy('days_at_location', 'desc')
            ->get();

        $summary = [
            'over_90_days' => ConsignmentStockLevel::aging(90)->count(),
            'over_180_days' => ConsignmentStockLevel::aging(180)->count(),
            'over_365_days' => ConsignmentStockLevel::aging(365)->count(),
        ];

        return view('consignment.aging', compact('agingStock', 'summary', 'days'));
    }

    /**
     * Helper: Update stock levels
     */
    private function updateStockLevel($locationId, $inventoryId, $batchId = null)
    {
        $stockLevel = ConsignmentStockLevel::firstOrCreate([
            'location_id' => $locationId,
            'inventory_id' => $inventoryId,
            'batch_id' => $batchId,
        ]);

        $stockLevel->updateLevels();
    }
    /**
     * List consignment sales for reconciliation
     */
    public function salesIndex(Request $request)
    {
        $status = $request->input('status', 'pending');

        if ($status === 'reconciled') {
            $query = \App\Models\PosSale::where('is_reconciled', true)
                ->latest('reconciled_at');
        } else {
            $query = \App\Models\PosSale::where('sale_status', 'consignment')
                ->where('is_reconciled', false) // Ensure strictly pending
                ->latest();
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $dateField = $status === 'reconciled' ? 'reconciled_at' : 'timestamp';
            $query->whereBetween($dateField, [$request->start_date, $request->end_date]);
        }
        
        // Search by Patient or Customer
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('patient_name', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%");
            });
        }

        $sales = $query->paginate(20);

        return view('consignment.sales.index', compact('sales', 'status'));
    }

    /**
     * View specific sale for reconciliation
     */
    public function reconcileView($id)
    {
        $sale = \App\Models\PosSale::findOrFail($id);
        
        // Ensure it's a consignment sale
        if ($sale->sale_status !== 'consignment') {
            return redirect()->route('sales.consignments.index')->with('error', 'Not a consignment sale');
        }

        // Get related ConsignmentTransaction items (created during POS)
        // These are currently marked as 'used' but need confirmation
        $transactions = ConsignmentTransaction::where('reference_type', \App\Models\PosSale::class)
            ->where('reference_id', $sale->id)
            ->with(['inventory', 'batch'])
            ->get();
        if ($transactions->isEmpty()) {
            // Fallback: If no transactions (maybe old sale before POS hook), try to build from sale_items
            // This is "self-healing" logic
            $tempTransactions = collect();
            
            if ($sale->sale_items) {
                foreach ($sale->sale_items as $item) {
                     // Handle different key naming conventions in legacy fields
                     $invId = $item['inventory_id'] ?? $item['product_id'] ?? null;
                     
                     // Check if it's a product that has inventory
                     if ($invId) {
                         $inventory = \App\Models\Inventory::find($invId);
                         if ($inventory) {
                             $batch = null;
                             if (!empty($item['batch_id'])) {
                                 $batch = \App\Models\Batch::find($item['batch_id']);
                             }

                             // Create a temporary object acting like a ConsignmentTransaction
                             // We use stdClass to avoid Eloquent's ID casting/guarding issues which turn strings to 0
                             $tempTran = new \stdClass();
                             $tempTran->id = 'temp_' . $invId;
                             $tempTran->inventory_id = $inventory->id;
                             $tempTran->batch_id = $batch ? $batch->id : null;
                             $tempTran->quantity = $item['quantity'];
                             $tempTran->transaction_type = 'used';
                             
                             // Attach relations manually as properties
                             $tempTran->inventory = $inventory;
                             $tempTran->batch = $batch; 
                             
                             $tempTransactions->push($tempTran);
                         }
                     }
                }
            }
            $transactions = $tempTransactions;
        }

        return view('consignment.sales.reconcile', compact('sale', 'transactions'));
    }

    /**
     * Process reconciliation (Confirm Used vs Returned)
     */
    public function processReconciliation(Request $request, $id)
    {
        $sale = \App\Models\PosSale::findOrFail($id);
        
        \Illuminate\Support\Facades\Log::info("Reconciling Sale #{$id}", ['input' => $request->all()]);

        try {
            $validated = $request->validate([
                'items' => 'required|array',
                // Relaxed validation: Allow any string/int for ID to support temp and real IDs
                'items.*.transaction_id' => 'required',
                'items.*.quantity_used' => 'required|integer|min:0',
                'items.*.quantity_returned' => 'required|integer|min:0',
            ]);

            DB::transaction(function () use ($sale, $validated) {
                $totalUsedValue = 0;
                $updatedSaleItems = []; // Initialize array to prevent undefined variable error
            // ... (setup for sale items update check if needed) ...

            foreach ($validated['items'] as $itemData) {
                $tranId = $itemData['transaction_id'];
                
                // Handle Temporary Transaction (Legacy/Fallback)
                if (str_starts_with($tranId, 'temp_')) {
                    $inventoryId = str_replace('temp_', '', $tranId);
                    $usedQty = $itemData['quantity_used'];
                    $returnedQty = $itemData['quantity_returned'];
                    
                     // For temp, we assume 'original' was the sum.
                     // But we don't have the "Placed" record, so we just focus on tracking what happened now.
                     // 1. If Returned > 0, we must ADD it back to stock.
                     // 2. Used portion is already deducted (at Dispatch).
                     // 3. We should create a PERMANENT record for the used portion now, so it stops being "temp".
                     
                     // Retrieve Inventory to get price
                     $inventory = Inventory::findOrFail($inventoryId);
                     
                     // Find Batch if possible (from sale_items? tough, we don't have batch_id here easily without lookup)
                     // Hack: Look up batch from sale_items using inventory_id
                     $batchId = null;
                     foreach($sale->sale_items as $sItem) {
                         if(($sItem['inventory_id'] ?? null) == $inventoryId) {
                             $batchId = $sItem['batch_id'] ?? null;
                             break;
                         }
                     }

                     if ($usedQty > 0) {
                        ConsignmentTransaction::create([
                            'location_id' => auth()->user()->location_id ?? Location::where('type', 'consignment')->first()->id ?? 1, // Fallback location
                            'inventory_id' => $inventoryId,
                            'batch_id' => $batchId,
                            'transaction_type' => 'used',
                            'quantity' => $usedQty,
                            'transaction_date' => now(),
                            'reference_type' => \App\Models\PosSale::class,
                            'reference_id' => $sale->id,
                            'created_by' => auth()->id(),
                            'billed' => true, // Auto-billed by logic below
                            'notes' => 'Reconciled from legacy sale'
                        ]);
                        $totalUsedValue += ($usedQty * $inventory->selling_price);

                        // Add to updated sale items list for invoice generation (Legacy Fallback)
                        foreach($sale->sale_items as $sItem) {
                            if(($sItem['inventory_id'] ?? $sItem['product_id'] ?? null) == $inventoryId) {
                                $newItem = $sItem;
                                $newItem['quantity'] = $usedQty;
                                $newItem['item_total'] = $usedQty * $inventory->selling_price;
                                $updatedSaleItems[] = $newItem;
                                break;
                            }
                        }
                     }
                     
                     if ($returnedQty > 0) {
                         // CREATE RETURN RECORD
                        ConsignmentTransaction::create([
                            'location_id' => auth()->user()->location_id ?? Location::where('type', 'consignment')->first()->id ?? 1,
                            'inventory_id' => $inventoryId,
                            'batch_id' => $batchId,
                            'transaction_type' => 'returned',
                            'quantity' => $returnedQty,
                            'transaction_date' => now(),
                            'reference_type' => \App\Models\PosSale::class,
                            'reference_id' => $sale->id,
                            'created_by' => auth()->id(),
                            'notes' => 'Returned from Legacy Sale Reconstruction'
                        ]);
                        
                        // RESTORE STOCK
                        // We must assume the location ID. If PosSale has location_id (it should!), use that.
                        // PosController doesn't seem to save location_id on PosSale explicitly in my view, but usually it does.
                        // Let's assume we use the user's location or default.
                        // Actually, better to check where stock was deducted from? 
                        // For now, restoring to main Inventory is key.
                        $inventory->increment('quantity_in_stock', $returnedQty);
                         if ($batchId) {
                            $batch = Batch::find($batchId);
                            if($batch) $batch->increment('quantity', $returnedQty);
                        }
                     }
                     
                     continue; // Done with this item
                }
                
                // Standard Logic (Existing Transaction)
                $transaction = ConsignmentTransaction::findOrFail($tranId);
                
                $originalQty = $transaction->quantity;
                $usedQty = $itemData['quantity_used'];
                $returnedQty = $itemData['quantity_returned'];

                if ($originalQty != ($usedQty + $returnedQty)) {
                    throw new \Exception("Quantity mismatch for item. Original: $originalQty, Used+Returned: " . ($usedQty + $returnedQty));
                }

                // Update Transaction? Or Split?
                // Best approach: Update the existing 'used' transaction to the actual used qty.
                // Create a NEW 'returned' transaction for the returned qty.

                // 1. Update Used Portion
                if ($usedQty > 0) {
                    $transaction->update(['quantity' => $usedQty]);
                    $totalUsedValue += ($usedQty * $transaction->inventory->selling_price); // Or logic to get price from sale item
                    
                     // Add to updated sale items list for invoice generation
                    // We need to find the original item structure to preserve price/name info
                    foreach($sale->sale_items as $sItem) {
                        $sItemInvId = $sItem['inventory_id'] ?? $sItem['product_id'] ?? null;
                        $sItemBatchId = $sItem['batch_id'] ?? null;
                        
                        // strict match on Inventory ID
                        if ($sItemInvId == $transaction->inventory_id) {
                            // Optional strict match on Batch ID if both have it
                            if ($transaction->batch_id && $sItemBatchId && $transaction->batch_id != $sItemBatchId) {
                                continue; 
                            }
                            
                            $newItem = $sItem;
                            $newItem['quantity'] = $usedQty;
                            $newItem['item_total'] = $usedQty * $transaction->inventory->selling_price; // Recalculate item total
                            $updatedSaleItems[] = $newItem;
                            break;
                        }
                    }
                } else {
                    // If 0 used, we can delete the 'used' transaction or change it to 'returned' fully?
                    // Let's keep it simple: If 0 used, delete this specific record (since we create a return record below)
                    // BUT, we need to track history. Better: Update to 0? No, delete is cleaner for "Usage".
                    $transaction->delete(); 
                    // Note: Deleting 'used' transaction automatically "reverses" the consumption logic if using Observers, 
                    // but here we are explicit. Ideally we should soft delete or just update qty.
                    // For now, let's assume update to 0 is okay, but we'll delete to keep "Unbilled" list clean.
                }

                // 2. Handle Returns
                if ($returnedQty > 0) {
                    ConsignmentTransaction::create([
                        'location_id' => $transaction->location_id,
                        'inventory_id' => $transaction->inventory_id,
                        'batch_id' => $transaction->batch_id,
                        'transaction_type' => 'returned',
                        'quantity' => $returnedQty,
                        'transaction_date' => now(),
                        'reference_type' => \App\Models\PosSale::class,
                        'reference_id' => $sale->id,
                        'created_by' => auth()->id(),
                        'notes' => "Returned from Surgery via Reconciliation (Sale #{$sale->id})",
                    ]);

                    // Restore Stock to Location (or Main Stock?)
                    // Logic: "Used" decremented stock. "Returned" should increment it back.
                    $this->updateStockLevel($transaction->location_id, $transaction->inventory_id, $transaction->batch_id);
                    
                    // ALSO: Return to main inventory master (since POS deducted it globally)
                    // IMPORTANT: POS logic deducted from Inventory Master. We must put it back.
                    $inventory = Inventory::find($transaction->inventory_id);
                    $inventory->increment('quantity_in_stock', $returnedQty);
                    
                    // Restore Batch Quantity
                     if ($transaction->batch_id) {
                        $batch = Batch::find($transaction->batch_id);
                        $batch->increment('quantity', $returnedQty);
                    }
                }

                // Re-calculate ConsignmentStockLevel for the used portion just in case
                if ($usedQty > 0) {
                    $this->updateStockLevel($transaction->location_id, $transaction->inventory_id, $transaction->batch_id);
                }
            }

            // 3. Finalize Sale Record
            $sale->sale_items = $updatedSaleItems; // CRITICAL: Update the JSON to reflect ONLY used items
            $sale->total = $totalUsedValue; // Update total to reflect ONLY used items
            $sale->subtotal = $totalUsedValue; // Simplification (tax calculation might be needed)
            // Recalculate VAT if needed:
            // $sale->vat = ...
            $sale->sale_status = 'completed'; // No longer 'consignment'
            $sale->payment_status = 'Pending'; // Still pending payment (Invoice generated)
            $sale->document_type = 'invoice'; // Convert delivery note to invoice
            
            // Mark as Reconciled
            $sale->is_reconciled = true;
            $sale->reconciled_at = now();

            // Generate Invoice Number if missing
            if (!$sale->invoice_number) {
                 $prefix = settings('invoice_prefix', 'INV');
                 $year = date('Y');
                 // ... (Gener logic similar to POS) ... simplified for this snippet
                 $sale->invoice_number = $prefix . '-' . $year . '-' . $sale->id; 
            }
            
            $sale->save();

            // Update receipt_data for printing (Crucial for correct Invoice generation)
            $receiptData = $sale->receipt_data ?? [];
            $receiptData['items'] = $updatedSaleItems;
            $receiptData['total'] = $totalUsedValue;
            $receiptData['subtotal'] = $totalUsedValue; // Simplified, assuming inclusive or no tax for now
            $receiptData['vat'] = $sale->vat ?? 0;
            $receiptData['document_type'] = 'invoice';
            $receiptData['invoice_number'] = $sale->invoice_number;
            $receiptData['date'] = now()->toIso8601String(); // Date of Invoice Generation
            
            $sale->receipt_data = $receiptData;
            $sale->save();

            // 4. Mark Transactions as Billed? 
            // In strict workflow, generating the invoice acts as billing.
            // But for 'Unbilled' consignment view, we might want to flag them.
            // Let's mark them as 'billed' now since they are part of a generated Sales Invoice.
             ConsignmentTransaction::where('reference_type', \App\Models\PosSale::class)
                ->where('reference_id', $sale->id)
                ->where('transaction_type', 'used')
                ->update(['billed' => true, 'billed_date' => now(), 'billing_reference' => $sale->invoice_number]);

        });

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e; // Let Laravel handle validation errors normally (redirect with errors)
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Reconciliation Error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->withInput()->withErrors(['error' => 'Reconciliation failed: ' . $e->getMessage()]);
        }

        return redirect()->route('sales.consignments.index')->with('success', 'Reconciliation completed. Invoice generated.');
    }
}
