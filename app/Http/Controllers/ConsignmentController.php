<?php

namespace App\Http\Controllers;

use App\Models\PosSale;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConsignmentController extends Controller
{
    /**
     * List active consignments.
     */
    public function index()
    {
        $consignments = PosSale::where('sale_status', 'consignment')
            ->with('customer')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
            
        return view('sales.consignments.index', compact('consignments'));
    }

    /**
     * Show reconciliation form.
     */
    public function show($id)
    {
        $sale = PosSale::findOrFail($id);
        
        if ($sale->sale_status !== 'consignment') {
            return redirect()->route('sales.invoices.show', $sale->id)
                ->with('warning', 'This sale is already completed.');
        }

        return view('sales.consignments.reconcile', compact('sale'));
    }

    /**
     * Reconcile consignment: process returns and finalize sale.
     */
    public function reconcile(Request $request, $id)
    {
        $sale = PosSale::findOrFail($id);

        if ($sale->sale_status !== 'consignment') {
            return redirect()->back()->with('error', 'This consignment is already processed.');
        }

        $validated = $request->validate([
            'returned_quantities' => 'array',
            'returned_quantities.*' => 'integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            $originalItems = $sale->sale_items; // JSON array
            
            $newItems = [];
            $returnedItemsLog = [];
            
            $subtotal = 0;
            
            // Loop through original items and adjust quantities
            foreach ($originalItems as $index => $item) {
                $productId = $item['product_id'];
                $originalQty = $item['quantity'];
                
                // Get returned qty from input (key is product_id or index? using product_id for safety)
                // Use input name 'returned_quantities[product_id]'
                $returnedQty = $validated['returned_quantities'][$productId] ?? 0;
                
                // Validation: Cannot return more than dispatched
                if ($returnedQty > $originalQty) {
                    throw new \Exception("Cannot return more than dispatched for item: " . $item['product_name']);
                }
                
                $soldQty = $originalQty - $returnedQty;
                
                // If items were returned, restock them
                if ($returnedQty > 0) {
                    Inventory::where('id', $productId)->increment('quantity_in_stock', $returnedQty);
                    
                    $returnedItemsLog[] = [
                        'product_name' => $item['product_name'],
                        'returned' => $returnedQty
                    ];
                }

                // If any sold, keep in sale items
                if ($soldQty > 0) {
                    $item['quantity'] = $soldQty;
                    $item['item_total'] = $soldQty * $item['unit_price'];
                    $subtotal += $item['item_total'];
                    $newItems[] = $item;
                }
                // If 0 sold (all returned), remove from final invoice items
            }

            // Recalculate totals using VAT-INCLUSIVE pricing
            $discountAmount = $sale->discount_percentage > 0 ? ($subtotal * $sale->discount_percentage / 100) : 0;
            $taxable = $subtotal - $discountAmount;
            
            // VAT-INCLUSIVE calculation: Extract VAT from the price
            // Calculate VAT ratio from original sale (to preserve the original tax rate)
            $originalTaxable = $sale->subtotal - $sale->discount_amount;
            $taxRate = ($originalTaxable > 0) ? ($sale->vat / $originalTaxable) : 0;
            
            // Extract VAT using VAT-inclusive formula: VAT = taxable × (rate / (1 + rate))
            // If original rate was 16%, then taxRate = 0.16/1.16 = 0.1379...
            // To get the percentage rate back: actualRate = taxRate / (1 - taxRate)
            $actualTaxRate = ($taxRate < 1) ? ($taxRate / (1 - $taxRate)) : 0;
            
            // Now calculate VAT-inclusive: VAT = taxable × (actualRate / (1 + actualRate))
            $newVat = $taxable * ($actualTaxRate / (1 + $actualTaxRate));
            $newTotal = $taxable; // Total is the same as taxable (VAT already included)

            // Update Sale
            $sale->sale_items = $newItems;
            $sale->subtotal = $subtotal;
            $sale->discount_amount = $discountAmount;
            $sale->vat = $newVat;
            $sale->total = $newTotal;
            $sale->sale_status = 'completed';
            $sale->document_type = 'invoice';
            
            // Generate Invoice Number now if not exists (it shouldnt for packing slip)
            if (!$sale->invoice_number) {
                 $year = date('Y');
                 $prefix = 'INV';
                 // Simple generation logic (copied from POSController or shared service ideally)
                 $searchPattern = "{$prefix}-{$year}-%";
                 $lastRecord = PosSale::where('invoice_number', 'like', $searchPattern)->orderBy('id', 'desc')->first();
                 $sequence = 1;
                 if ($lastRecord && preg_match('/-(\d+)$/', $lastRecord->invoice_number, $matches)) {
                     $sequence = intval($matches[1]) + 1;
                 }
                 $sale->invoice_number = sprintf("%s-%s-%04d", $prefix, $year, $sequence);
            }
            
            // Update customer balance (Now we book the debt)
            // Assuming Credit because Consignments are credit-based
            if ($sale->payment_method === 'Credit' && $sale->customer_id) {
                $customer = $sale->customer;
                if ($customer) {
                    $customer->increment('current_balance', $newTotal);
                }
            }

            // Update receipt_data to reflect only used items (not returned items)
            $receiptData = $sale->receipt_data ?? [];
            $receiptData['items'] = $newItems;
            $receiptData['subtotal'] = $subtotal;
            $receiptData['discount_amount'] = $discountAmount;
            $receiptData['vat'] = $newVat;
            $receiptData['total'] = $newTotal;
            $sale->receipt_data = $receiptData;
            
            // Set delivery_note_data to only items actually used (AFTER reconciliation)
            if (empty($sale->delivery_note_data)) {
                $sale->delivery_note_data = [
                    'items' => $newItems, // Only used items, not original items
                    'date' => now(),
                    'seller' => $sale->seller_username,
                    'customer_info' => $sale->customer_snapshot,
                    'sale_id' => $sale->id
                ];
                $sale->delivery_note_generated = true;
            }

            $sale->save();
            
            // --- ACCOUNTING AUTOMATION (Consignment Reconciled) ---
            // Now we recognize the revenue and COGS for the items actually sold
            $accounting = new \App\Services\AccountingService();
            $accounting->recordSale($sale, $request->user());
            // -----------------------------------------------------

            DB::commit();

            return redirect()->route('sales.invoices.show', $sale->id)
                ->with('success', 'Consignment reconciled and Invoice generated.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Reconciliation failed: ' . $e->getMessage());
        }
    }
}
