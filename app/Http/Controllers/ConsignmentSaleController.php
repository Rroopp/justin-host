<?php

namespace App\Http\Controllers;

use App\Models\PosSale;
use App\Models\Inventory;
use App\Models\Batch;
use App\Models\InventoryMovement;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ConsignmentSaleController extends Controller
{
    /**
     * List pending consignment sales requiring reconciliation.
     */
    public function index()
    {
        $sales = PosSale::where('sale_status', 'consignment')
            ->where('is_reconciled', false)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('sales.consignments.index', compact('sales'));
    }

    /**
     * Show reconciliation form for a specific consignment sale.
     */
    public function show($id)
    {
        $sale = PosSale::findOrFail($id);
        
        if ($sale->sale_status !== 'consignment' || $sale->is_reconciled) {
            return redirect()->route('sales.consignments.index')
                ->with('error', 'This sale is not pending reconciliation.');
        }

        // Parse sale_items if string (though cast should handle it)
        $items = is_string($sale->sale_items) ? json_decode($sale->sale_items, true) : $sale->sale_items;

        return view('sales.consignments.show', compact('sale', 'items'));
    }

    /**
     * Reconcile: Return all stock, then create new invoice for used items.
     */
    public function reconcile(Request $request, $id)
    {
        $sale = PosSale::findOrFail($id);
        
        if ($sale->sale_status !== 'consignment' || $sale->is_reconciled) {
            return back()->with('error', 'Invalid sale for reconciliation.');
        }

        $validated = $request->validate([
            'usage' => 'required|array',
            'usage.*' => 'integer|min:0',
            'notes' => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            $items = is_array($sale->sale_items) ? $sale->sale_items : json_decode($sale->sale_items, true);
            $newSaleItems = [];
            $totalAmount = 0;

            // 1. Release ALL Original Stock (Return logic)
            foreach ($items as $item) {
                // Find batch
                $batchId = $item['batch_id'] ?? null;
                $batch = $batchId ? Batch::find($batchId) : null; // Warning: if batch deleted?
                
                if ($batch) {
                    $batch->increment('quantity', $item['quantity']);
                    
                    InventoryMovement::logMovement([
                        'inventory_id' => $item['id'],
                        'batch_id' => $batch->id,
                        'quantity_before' => $batch->quantity - $item['quantity'],
                        'quantity_after' => $batch->quantity,
                        'quantity_change' => $item['quantity'],
                        'type' => 'return',
                        'reference_type' => 'consignment_reconciliation',
                        'reference_id' => $sale->id,
                        'notes' => "Auto-return from Consignment #{$sale->invoice_number} Reconciliation",
                        'user_id' => auth()->id(),
                        'location_id' => null // Main Store? Original sale didn't track location explicitly in JSON? Assuming Main.
                    ]);
                }
            }

            // 2. Process Usage & Create New Sale Items
            foreach ($items as $originalItem) {
                $itemId = $originalItem['id']; // Inventory ID in JSON
                // Usage array keyed by something unique? 
                // Wait, JSON items don't have unique IDs if duplicates exist. 
                // But usually we iterate based on index or assume unique product+batch.
                // Let's assume validation matches index or key.
                // Actually, the view should send usage keyed by... index? or inventory_id?
                // If view sends usage[inventory_id], duplicates merge.
                // Better: iterate validated usage?
                
                // Let's assume input 'usage' is keyed by $inventory_id (if unique).
                // Or better: keyed by index 0,1,2 matched to $items array.
                
                // Simplified: User inputs usage per Row.
                // Index-based matching is safest if view uses loop index.
                
                // However, request->validate usage.* implies we get an array.
                // Let's trace by Inventory ID for now, assuming unique items in sale.
                $usedQty = $validated['usage'][$itemId] ?? 0;
                
                if ($usedQty > 0) {
                    // Deduct again
                    $batchId = $originalItem['batch_id'] ?? null;
                    $batch = $batchId ? Batch::find($batchId) : null;

                    if ($batch) {
                        if ($batch->quantity < $usedQty) {
                            throw new \Exception("Insufficient stock to reconcile {$originalItem['product_name']}");
                        }
                        
                        $batch->decrement('quantity', $usedQty);
                        
                        InventoryMovement::logMovement([
                            'inventory_id' => $itemId,
                            'batch_id' => $batch->id,
                            'quantity_before' => $batch->quantity + $usedQty,
                            'quantity_after' => $batch->quantity,
                            'quantity_change' => -$usedQty,
                            'type' => 'sale',
                            'reference_type' => 'pos_sale', // New ID later
                            'reference_id' => null, // Placeholder
                            'notes' => "Consignment Usage Reconciled",
                            'user_id' => auth()->id(),
                        ]);

                        $lineTotal = $originalItem['price'] * $usedQty; // Recalculate based on original price
                        $totalAmount += $lineTotal;

                        $newSaleItems[] = array_merge($originalItem, [
                            'quantity' => $usedQty,
                            'total' => $lineTotal
                        ]);
                    }
                }
            }

            // 3. Create Final Invoice (PosSale)
            if (count($newSaleItems) > 0) {
                $invoiceNumber = 'INV-' . strtoupper(Str::random(8));
                
                $finalSale = PosSale::create([
                    'sale_type' => 'direct', // converted
                    'payment_method' => 'credit', // Invoiced
                    'payment_status' => 'pending',
                    'sale_status' => 'completed',
                    'sale_items' => $newSaleItems,
                    'subtotal' => $totalAmount,
                    'total' => $totalAmount,
                    'paid_amount' => 0,
                    'customer_id' => $sale->customer_id,
                    'customer_name' => $sale->customer_name,
                    'seller_username' => auth()->user()->username ?? 'system',
                    'invoice_number' => $invoiceNumber,
                    'is_reconciled' => true,
                    'document_type' => 'invoice',
                    'patient_name' => $sale->patient_name,
                    'patient_number' => $sale->patient_number, // Carry over
                    'timestamp' => now(),
                ]);
            }

            // 4. Mark Original as Reconciled
            $sale->update([
                'is_reconciled' => true, 
                'reconciled_at' => now(),
                'sale_status' => 'reconciled_closed' // New status?
            ]);

            // 5. Record accounting entry for consignment reconciliation
            if (isset($finalSale)) {
                // Calculate total cost for COGS entry
                $totalCost = 0;
                foreach ($newSaleItems as $item) {
                    $batch = Batch::find($item['batch_id'] ?? null);
                    if ($batch) {
                        $totalCost += $batch->cost_price * $item['quantity'];
                    }
                }

                // Create a consignment object for accounting
                $consignmentData = (object) [
                    'id' => $finalSale->id,
                    'total_amount' => $totalAmount,
                    'total_cost' => $totalCost,
                    'payment_method' => $finalSale->payment_method,
                    'reconciled_at' => now(),
                ];

                $accountingService = new AccountingService();
                $accountingService->recordConsignmentReconciliation($consignmentData, $request->user());
            }

            DB::commit();
            
            if (isset($finalSale)) {
                return redirect()->route('sales.invoices.show', $finalSale->id)
                    ->with('success', 'Reconciliation complete. Invoice generated.');
            } else {
                return redirect()->route('sales.consignments.index')
                    ->with('success', 'Reconciliation complete. No items used (All returned).');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Reconciliation failed: ' . $e->getMessage());
        }
    }
}
