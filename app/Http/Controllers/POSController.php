<?php

namespace App\Http\Controllers;

use App\Models\PosSale;
use App\Models\Inventory;
use App\Events\SaleCompleted;
use App\Models\Customer;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class POSController extends Controller
{
    /**
     * Display the POS interface.
     */
    public function index()
    {
        // PERFORMANCE FIX: Do NOT load all products. Fetch via API search.
        $products = []; 
        $packages = \App\Models\Package::withCount('items')->where('is_active', true)->get();
        
        $customers = Customer::orderBy('name')->get();
        
        // Pass packages and staff to view
        $staff = \App\Models\Staff::where('status', 'active')->orderBy('full_name')->get();
        return view('pos.index', compact('products', 'customers', 'packages', 'staff'));
    }

    /**
     * Search inventory for POS (AJAX)
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        
        // If empty query, return recent/top items
        if (empty($query)) {
            $products = Inventory::with(['batches' => function($q) {
                $q->where('quantity', '>', 0)->where('expiry_date', '>', now());
            }])
            ->where('category', '!=', 'Services')
            ->limit(20)
            ->get();
            return response()->json($products);
        }

        $products = Inventory::with(['batches' => function($q) {
                $q->where('quantity', '>', 0)->where('expiry_date', '>', now());
            }])
            ->where(function($q) use ($query) {
                $q->where('product_name', 'like', "%{$query}%")
                  ->orWhere('code', 'like', "%{$query}%")
                  ->orWhere('manufacturer', 'like', "%{$query}%");
            })
            ->where('category', '!=', 'Services') // Exclude services from product search (they might be handled differently or included if needed)
            ->limit(30)
            ->get();
            
        return response()->json($products);
    }

    /**
     * Process a POS sale.
     */
    public function store(Request $request)
    {
        Log::info('POS Store Payload:', $request->all());

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.id' => 'required', // Relaxed to support Package IDs (checked manually/runtime)
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.type' => 'nullable|in:sale,rental,package_header,package_component', // Add type validation
            'items.*.price' => 'nullable|numeric|min:0', // Allow custom price for rentals
            'items.*.batch_number' => 'nullable|string', // Optional: if null, FIFO logic applies
            'items.*.name' => 'nullable|string', // Ensure name is passed/validated so it appears in receipt
            // NOTE: Invoices are generated ONLY for Credit sales (per business rule).
            'payment_method' => 'required|string', // Relaxed from strict 'in' check to debug/unblock
            'subtotal' => 'required|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'vat' => 'nullable|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_info' => 'nullable|array',
            // Frontend may still submit a value; we will enforce the rule below.
            'document_type' => 'required|in:receipt,invoice,delivery_note,packing_slip',
            'invoice_number' => 'nullable|string',
            // Due date is required for Credit (invoice) sales
            'due_date' => 'nullable|date|required_if:payment_method,Credit',
            'lpo_number' => 'nullable|string',
            'patient_type' => 'nullable|in:Inpatient,Outpatient',
            'patient_name' => 'nullable|string',
            'patient_number' => 'nullable|string',
            'facility_name' => 'nullable|string',
            'surgeon_name' => 'nullable|string',
            'nurse_name' => 'nullable|string',
            'nurse_name' => 'nullable|string',
            'sale_status' => 'nullable|in:completed,consignment',
            'lpo_id' => 'nullable|exists:lpos,id',
            // Commission Data
            'commission_staff_id' => 'nullable|exists:staff,id',
            'commission_amount' => 'nullable|numeric|min:0',
            'commission_note' => 'nullable|string',
            'location_id' => 'nullable|exists:locations,id', // Support multi-location POS
            'sale_type' => 'nullable|in:sale,surgery_usage',
        ]);

        if ($validator->fails()) {
            Log::error('POS Validation Failed:', $validator->errors()->toArray());
            return response()->json(['message' => 'Validation Failed', 'errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $saleStatus = $validated['sale_status'] ?? 'completed';
        $isConsignment = $saleStatus === 'consignment';
        $isCreditSale = $validated['payment_method'] === 'Credit';

        // Enforce document rules:
        // - Consignment => packing_slip
        // - Credit => invoice
        // - Other => receipt (or user choice)
        if ($isConsignment) {
            $documentType = 'packing_slip';
        } elseif ($isCreditSale) {
            $documentType = 'invoice';
        } else {
            $documentType = $validated['document_type'] ?? 'receipt';
        }

        // Sanity check
        if (!$isCreditSale && !$isConsignment && $documentType === 'invoice') {
            $documentType = 'receipt';
        }

        DB::beginTransaction();
        try {
            $consignmentUsage = [];
            $saleItems = [];
            $rentalItems = []; // Items to be tracked in rentals table
            $lowStockAlerts = [];

            // Process each item
            foreach ($validated['items'] as $item) {
                // Determine Item Type
                $type = $item['type'] ?? 'sale';
                
                // --- CASE 1: PACKAGE HEADER (Financial Wrapper) ---
                if ($type === 'package_header') {
                    // No inventory deduction. Just recorded in PosSale items (below) and total.
                    // We don't create individual 'Sale' record for header as it's not physical stock.
                    // But we DO need to include it in $saleItems for the invoice.
                    $saleItems[] = [
                        'product_id' => null,   // No inventory ID
                        'package_id' => $item['id'], // Use the ID passed (which is package_id here)
                        'product_name' => $item['name'] ?? 'Package', // Name passed from frontend
                        'quantity' => 1,        // Packages usually qty 1, but could be more
                        'unit_price' => $item['price'],
                        'item_total' => $item['price'] * $item['quantity'],
                        'type' => 'package_header',
                        'product_snapshot' => [],
                    ];
                    continue; // Skip standard inventory logic
                }
                
                // --- CASE 2: STANDARD ITEM or PACKAGE COMPONENT ---
                // Lock the row to prevent race conditions during concurrent sales
                $product = Inventory::where('id', $item['id'])->lockForUpdate()->firstOrFail();
                
                // --- TRACEABILITY ENFORCEMENT ---
                // For medical implants and devices requiring serial tracking, batch selection is MANDATORY
                if ($product->requires_serial_tracking && empty($item['batch_number'])) {
                    DB::rollBack();
                    return response()->json([
                        'error' => "⚠️ Batch/Serial Number Required\n\n{$product->product_name} is a traceable medical device.\n\nYou must select a specific Batch/Serial Number before adding it to the cart.\n\nThis is required for regulatory compliance and patient safety.",
                        'product_name' => $product->product_name,
                        'requires_batch' => true
                    ], 422);
                }
                // -----------------------------
                
                // Check stock availability
                if ($product->quantity_in_stock < $item['quantity']) {
                    DB::rollBack();
                    return response()->json([
                        'error' => "Insufficient stock for {$product->product_name}. Available: {$product->quantity_in_stock}, Requested: {$item['quantity']}"
                    ], 400);
                }

                // Deduct stock
                // $product->decrement('quantity_in_stock', $item['quantity']); // OLD WAY

                // NEW WAY: Batch Deduction (FIFO or Specific)
                $qtyToDeduct = $item['quantity'];
                $batchesAffected = [];
                $totalItemCost = 0; // Track total cost for COGS
                
                // Location Context: Priority to Item's location (Multi-Set), fallback to Request location
                $locationId = $item['location_id'] ?? $request->input('location_id', null); 

                // Check is consignment context
                $isConsignmentLocation = false;
                if ($locationId) {
                    $locationModel = \App\Models\Location::find($locationId);
                    $isConsignmentLocation = $locationModel && $locationModel->isConsignment();
                }
                $trackConsignment = $isConsignment || $isConsignmentLocation;

                // 2a. Specific Batch Requested?
                if (!empty($item['batch_number'])) {
                    $batchQuery = \App\Models\Batch::where('inventory_id', $product->id)
                        ->where('batch_number', $item['batch_number']);
                    
                    if ($locationId) {
                        $batchQuery->where('location_id', $locationId);
                    } else {
                        $batchQuery->whereNull('location_id');
                    }
                    
                    $batch = $batchQuery->lockForUpdate()->first();
                    
                    if (!$batch) {
                        DB::rollBack();
                        return response()->json(['error' => "Batch {$item['batch_number']} not found for {$product->product_name} at this location."], 400); 
                    }

                    if ($batch->quantity < $qtyToDeduct) {
                        DB::rollBack();
                        return response()->json(['error' => "Insufficient stock in Batch {$item['batch_number']} for {$product->product_name}. Available: {$batch->quantity}"], 400);
                    }

                    // Capture cost before deduction
                    $batchCost = $batch->cost_price ?? $product->price ?? 0;
                    $totalItemCost += ($qtyToDeduct * $batchCost);

                    $batch->decrement('quantity', $qtyToDeduct);
                    $batchesAffected[] = $batch->batch_number;

                    if ($trackConsignment) {
                        $consignmentUsage[] = [
                            'location_id' => $locationId,
                            'inventory_id' => $product->id,
                            'batch_id' => $batch->id,
                            'quantity' => $qtyToDeduct,
                        ];
                    }

                } else {
                    // 2b. FIFO (First Expiring First Out) - Location Aware
                    $batchesQuery = $product->batches()->where('quantity', '>', 0)->orderBy('expiry_date', 'asc');
                    
                    if ($locationId) {
                        $batchesQuery->where('location_id', $locationId);
                    } else {
                        $batchesQuery->whereNull('location_id');
                    }
                    
                    $batches = $batchesQuery->lockForUpdate()->get();
                    
                    $remainingToDeduct = $qtyToDeduct;
                    
                    foreach ($batches as $batch) {
                        if ($remainingToDeduct <= 0) break;

                        $take = min($batch->quantity, $remainingToDeduct);
                        
                        // Capture cost
                        $batchCost = $batch->cost_price ?? $product->price ?? 0;
                        $totalItemCost += ($take * $batchCost);

                        $batch->decrement('quantity', $take);
                        $remainingToDeduct -= $take;
                        $batchesAffected[] = $batch->batch_number;

                        if ($trackConsignment) {
                            $consignmentUsage[] = [
                                'location_id' => $locationId,
                                'inventory_id' => $product->id,
                                'batch_id' => $batch->id,
                                'quantity' => $take,
                            ];
                        }
                    }

                    // If we still have remaining to deduct (e.g. stock inconsistency), 
                    // assume remaining items cost is based on product master price
                    if ($remainingToDeduct > 0) {
                        $productCost = $product->price ?? 0;
                        $totalItemCost += ($remainingToDeduct * $productCost);
                    }
                }

                // Always keep Master Stock in sync
                $product->decrement('quantity_in_stock', $item['quantity']);

                // Calculate weighted average unit cost
                $unitCostPrice = ($item['quantity'] > 0) ? ($totalItemCost / $item['quantity']) : 0;

                // Check for low stock (per-product threshold; falls back to global setting or 10)
                $lowStockThreshold = $product->min_stock_level ?? settings('low_stock_threshold', 10);
                if ($product->quantity_in_stock <= $lowStockThreshold) {
                    $lowStockAlerts[] = [
                        'product_id' => $product->id,
                        'product_name' => $product->product_name,
                        'quantity_in_stock' => $product->quantity_in_stock,
                        'out_of_stock' => $product->quantity_in_stock == 0,
                    ];
                }

                // Prepare sale item (for PosSale record - financial)
                // Use provided price (for rentals or package headers) or selling_price
                // IF Package Component, price MUST be 0 (or whatever frontend sent, usually 0)
                $unitPrice = isset($item['price']) ? $item['price'] : $product->selling_price;
                
                $lineItem = [
                    'product_id' => $product->id,
                    'product_name' => $product->product_name,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'cost_price' => round($unitCostPrice, 2), // NEW: Store calculated cost
                    'item_total' => $unitPrice * $item['quantity'],
                    'type' => $type, // 'sale', 'rental', 'package_component' 
                    'product_snapshot' => $product->toArray(),
                ];
                $saleItems[] = $lineItem;

                // If rental, add to tracking array
                if ($type === 'rental') {
                    $rentalItems[] = [
                        'inventory_id' => $product->id,
                        'name' => $product->product_name,
                        'quantity' => $item['quantity'],
                        'condition' => 'Good', // Default condition
                        'price_at_rental' => $unitPrice
                    ];
                }

                // Create individual sale record (Financial History & Stock Log)
                \App\Models\Sale::create([
                    'inventory_id' => $product->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'total' => $unitPrice * $item['quantity'], // Will be 0 for package components
                    'seller_username' => $request->user() ? $request->user()->username : 'system',
                    'product_snapshot' => array_merge($product->toArray(), ['transaction_type' => $type]),
                ]);
            }

            // Get customer info
            $customer = null;
            $customerInfo = $validated['customer_info'] ?? [];
            
            if ($validated['customer_id']) {
                $customer = Customer::find($validated['customer_id']);
                $customerInfo = array_merge($customerInfo, $customer->toArray());
            }

            // Generate document number if needed
            $invoiceNumber = $validated['invoice_number'] ?? null;
            if (!$invoiceNumber) {
                $prefix = 'REC';
                if ($documentType === 'invoice') $prefix = settings('invoice_prefix', 'INV');
                if ($documentType === 'delivery_note') $prefix = 'DN';
                
                $year = date('Y');
                
                // Find max number for this type and year to increment
                // Pattern: PRE-YYYY-XXXXXX
                $searchPattern = "{$prefix}-{$year}-%";
                $lastRecord = PosSale::where('invoice_number', 'like', $searchPattern)
                    ->orderBy('id', 'desc')
                    ->first();
                
                $sequence = 1;
                if ($lastRecord && preg_match('/-(\d+)$/', $lastRecord->invoice_number, $matches)) {
                    $sequence = intval($matches[1]) + 1;
                }
                
                $invoiceNumber = sprintf("%s-%s-%04d", $prefix, $year, $sequence);
            }

            // Create POS sale record
            $posSale = PosSale::create([
                'sale_type' => $validated['sale_type'] ?? 'sale',
                'sale_items' => $saleItems,
                'payment_method' => $validated['payment_method'],
                // Credit OR Consignment is unpaid until settled.
                'payment_status' => ($isCreditSale || $isConsignment) ? 'Pending' : 'Paid',
                'sale_status' => $saleStatus,
                'subtotal' => $validated['subtotal'],
                'discount_percentage' => $validated['discount_percentage'] ?? 0,
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'vat' => $validated['vat'] ?? 0,
                'total' => $validated['total'],
                'customer_id' => $validated['customer_id'],
                'customer_name' => $customerInfo['name'] ?? null,
                'customer_phone' => $customerInfo['phone'] ?? null,
                'customer_email' => $customerInfo['email'] ?? null,
                'customer_snapshot' => $customerInfo,
                'seller_username' => $request->user()?->username ?? 'system',
                'timestamp' => now()->toIso8601String(),
                'document_type' => $documentType,
                'invoice_number' => $documentType === 'invoice' ? $invoiceNumber : null,
                'due_date' => $documentType === 'invoice' ? ($validated['due_date'] ?? null) : null,
                'lpo_number' => $validated['lpo_number'] ?? null,
                'patient_type' => $validated['patient_type'] ?? null,
                'patient_name' => $validated['patient_name'] ?? null,
                'patient_number' => $validated['patient_number'] ?? null,
                'facility_name' => $validated['facility_name'] ?? null,
                'surgeon_name' => $validated['surgeon_name'] ?? null,
                'nurse_name' => $validated['nurse_name'] ?? null,
                'receipt_data' => [
                    'sale_id' => null, // Will be updated after save
                    'date' => now()->toIso8601String(),
                    'items' => $saleItems,
                    'subtotal' => $validated['subtotal'],
                    'discount_percentage' => $validated['discount_percentage'] ?? 0,
                    'discount_amount' => $validated['discount_amount'] ?? 0,
                    'vat' => $validated['vat'] ?? 0,
                    'total' => $validated['total'],
                    'payment_method' => $validated['payment_method'],
                    'customer_info' => $customerInfo,
                    'seller' => $request->user() ? $request->user()->username : 'system',
                    'document_type' => $documentType,
                    'invoice_number' => $documentType === 'invoice' ? $invoiceNumber : null,
                    'due_date' => $documentType === 'invoice' ? ($validated['due_date'] ?? null) : null,
                    'lpo_number' => $validated['lpo_number'] ?? null,
                    'patient_type' => $validated['patient_type'] ?? null,
                    'patient_name' => $validated['patient_name'] ?? null,
                    'patient_number' => $validated['patient_number'] ?? null,
                    'facility_name' => $validated['facility_name'] ?? null,
                    'surgeon_name' => $validated['surgeon_name'] ?? null,
                    'nurse_name' => $validated['nurse_name'] ?? null,
                ],
                'delivery_note_data' => [
                    'items' => $saleItems, // Capture original items here
                    'date' => now()->toIso8601String(),
                    'seller' => $request->user() ? $request->user()->username : 'system',
                    'customer_info' => $customerInfo,
                    'patient_name' => $validated['patient_name'] ?? null,
                    'patient_number' => $validated['patient_number'] ?? null,
                    'facility_name' => $validated['facility_name'] ?? null,
                    'surgeon_name' => $validated['surgeon_name'] ?? null,
                    'nurse_name' => $validated['nurse_name'] ?? null,
                ],
            ]);

            // Update receipt_data with sale_id
            $receiptData = $posSale->receipt_data ?? [];
            $receiptData['sale_id'] = $posSale->id;
            $posSale->receipt_data = $receiptData;
            $posSale->save();

            // --- CREATE CONSIGNMENT TRANSACTIONS ---
            // Now that we have the POS Sale ID, we can record the consignment usage
            if (!empty($consignmentUsage)) {
                foreach ($consignmentUsage as $usage) {
                    \App\Models\ConsignmentTransaction::create([
                        'location_id' => $usage['location_id'],
                        'inventory_id' => $usage['inventory_id'],
                        'batch_id' => $usage['batch_id'],
                        'transaction_type' => 'used',
                        'quantity' => $usage['quantity'],
                        'transaction_date' => now(),
                        'reference_type' => PosSale::class,
                        'reference_id' => $posSale->id,
                        'billed' => false,
                        'created_by' => $request->user()->id,
                        'notes' => "POS Sale #{$posSale->id} - " . ($validated['patient_name'] ?? 'Consignment Usage'),
                    ]);
                    
                    // Note: ConsignmentStockLevel is auto-updated by Observer or manually if needed.
                    // Assuming Observer exists or logic handles it in ConsignmentTransaction model boot.
                    // If not, we might need:
                    // \App\Models\ConsignmentStockLevel::syncStock($usage['location_id'], $usage['inventory_id'], $usage['batch_id']);
                }
            }
            // ---------------------------------------

            // Create Rental Record if there are rental items
            if (!empty($rentalItems)) {
                // Determine return date (default 3 days? or from input?)
                // For now, default to 3 days from now, or let user edit later
                $expectedReturn = now()->addDays(3);
                
                $rental = \App\Models\Rental::create([
                    'customer_id' => $validated['customer_id'] ?? null,
                    'pos_sale_id' => $posSale->id,
                    'rented_at' => now(),
                    'expected_return_at' => $expectedReturn,
                    'status' => 'active',
                    'items' => $rentalItems,
                    'notes' => 'Created via POS Sale #' . $posSale->id
                ]);

                // Create Rental Items relations
                foreach ($rentalItems as $rItem) {
                    $rental->rentalItems()->create([
                        'inventory_id' => $rItem['inventory_id'],
                        'quantity' => $rItem['quantity'],
                        'condition_out' => $rItem['condition'] ?? 'Good', // Map condition -> condition_out
                        'price_at_rental' => $rItem['price_at_rental'],
                        'notes' => '',
                    ]);
                }
            }

            // If this is a Credit invoice and a customer is selected, increase their balance (accounts receivable).
            // Consignments do not increase balance yet (handled at reconciliation).
            if ($isCreditSale && !$isConsignment && $customer && Schema::hasColumn('customers', 'current_balance')) {
                $current = (float) ($customer->current_balance ?? 0);
                $customer->current_balance = $current + (float) ($posSale->total ?? 0);
                $customer->save();
            }

            // Handle LPO
            // Handle LPO (Smart Lookup)
            $lpo = null;
            if (!empty($validated['lpo_id'])) {
                $lpo = \App\Models\Lpo::lockForUpdate()->find($validated['lpo_id']);
            } elseif (!empty($validated['lpo_number'])) {
                // Try to find active LPO by number, restricted to this customer if selected
                $lpoQuery = \App\Models\Lpo::where('lpo_number', $validated['lpo_number'])
                    ->where('status', 'active');
                
                if (!empty($validated['customer_id'])) {
                    $lpoQuery->where('customer_id', $validated['customer_id']);
                }
                
                $lpo = $lpoQuery->lockForUpdate()->first();
            }

            if ($lpo) {
                // Deduct from LPO balance
                $lpo->remaining_balance -= $posSale->total;
                
                // Link Sale to LPO
                $posSale->lpo_id = $lpo->id;
                // Ensure lpo_number field on sale matches the actual LPO found
                $posSale->lpo_number = $lpo->lpo_number; 
                $posSale->save();
                
                $lpo->save();
                $lpo->save();
            }

            // --- COMMISSION AUTOMATION ---
            if (auth()->user()->hasRole('admin') && !empty($validated['commission_staff_id']) && !empty($validated['commission_amount'])) {
                $commissionType = 'sale';
                // Heuristic: If there are packages, maybe it's a service/procedure commission?
                // For now, default to 'sale' but could be 'service' if user selected it (future).
                // Or check if package_header exists in items.
                $hasPackage = collect($validated['items'])->contains('type', 'package_header');
                if ($hasPackage) $commissionType = 'service';

                \App\Models\Commission::create([
                    'staff_id' => $validated['commission_staff_id'],
                    'pos_sale_id' => $posSale->id,
                    'amount' => $validated['commission_amount'],
                    'type' => $commissionType,
                    'status' => 'pending',
                    'description' => $validated['commission_note'] ?? 'Commission from POS Sale #' . $posSale->id,
                ]);
            }
            // -----------------------------

            // --- ACCOUNTING AUTOMATION ---
            // Record the sale in the General Ledger (COGS, Revenue, Assets, VAT)
            // Skip for Consignments (Revenue recognized only upon reconciliation)
            if ($saleStatus !== 'consignment') {
                $accounting = new \App\Services\AccountingService();
                $journalEntry = $accounting->recordSale($posSale, $request->user());

                if (!$journalEntry) {
                    // Strict check: If accounting fails (e.g., missing accounts), fail the sale so we don't have data mismatches.
                    throw new \Exception("Failed to post Journal Entry. Please ensure Chart of Accounts (Revenue/Inventory/COGS/VAT/AR) are configured.");
                }
            }
            // -----------------------------

            // --- AUDIT LOGGING ---
            $auditService = app(AuditService::class);
            $auditService->log(
                $request->user(),
                'sale_completed',
                'pos_sales',
                $posSale->id,
                "POS Sale #{$posSale->id} completed: {$documentType} for " . settings('currency_symbol', 'KSh') . " {$posSale->total}",
                PosSale::class,
                null,
                [
                    'sale_id' => $posSale->id,
                    'total' => $posSale->total,
                    'payment_method' => $posSale->payment_method,
                    'document_type' => $documentType,
                    'items_count' => count($saleItems),
                    'customer_id' => $validated['customer_id'] ?? null,
                    'has_rentals' => !empty($rentalItems),
                    'sale_status' => $saleStatus,
                ]
            );
            // ---------------------

            DB::commit();

            // Fire Accounting Event (Async or Sync per Queue config)
            SaleCompleted::dispatch($posSale);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'sale_id' => $posSale->id,
                    'total_amount' => $posSale->total,
                    'items_sold' => count($saleItems),
                    'low_stock_alerts' => $lowStockAlerts,
                    'payment_method' => $posSale->payment_method,
                    'document_data' => $posSale->receipt_data,
                    'document_type' => $posSale->document_type,
                    'has_rentals' => !empty($rentalItems)
                ], 201);
            }

            return redirect()->route('pos.index')->with([
                'success' => 'Sale completed successfully',
                'sale_id' => $posSale->id,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Transaction failed: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Transaction failed: ' . $e->getMessage());
        }
    }

    /**
     * Get receipt data
     */
    public function getReceipt($id)
    {
        $sale = PosSale::findOrFail($id);
        
        if (!$sale->receipt_generated) {
            return response()->json(['error' => 'Receipt was dismissed'], 404);
        }

        return response()->json([
            'receipt_data' => $sale->receipt_data,
            'receipt_generated' => $sale->receipt_generated,
        ]);
    }


    /**
     * Save the current cart state.
     */
    public function saveCart(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'cart_data' => 'required|array', // JSON payload
        ]);

        $name = $validated['name'] ?? 'Cart ' . now()->format('H:i:s');
        
        $cart = DB::table('saved_carts')->insertGetId([
            'name' => $name,
            'cart_data' => json_encode($validated['cart_data']),
            'seller_username' => $request->user()?->username ?? 'system',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'cart_id' => $cart,
            'message' => 'Cart saved successfully'
        ]);
    }

    /**
     * List saved carts for the current user.
     */
    public function listCarts(Request $request)
    {
        $username = $request->user()?->username ?? 'system';
        
        // Show all carts or just user's carts? 
        // Typically shared in a store, but let's stick to user's for simplicity or all if needed.
        // Let's show all latest first.
        
        $carts = DB::table('saved_carts')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($cart) {
                // Decode just for summary if needed, or send raw
                $cart->cart_data = json_decode($cart->cart_data, true);
                return $cart;
            });

        return response()->json($carts);
    }

    /**
     * Delete a saved cart.
     */
    public function deleteCart(Request $request, $id)
    {
        DB::table('saved_carts')->where('id', $id)->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Printable receipt / invoice view (Blade).
     */
    public function printReceipt(Request $request, $id)
    {
        $sale = PosSale::findOrFail($id);

        if (!$sale->receipt_generated) {
            abort(404, 'Receipt was dismissed');
        }

        // Allow overriding document type via query parameter
        $requestedType = $request->query('type');
        $documentType = ($requestedType && in_array($requestedType, ['invoice', 'receipt', 'delivery_note', 'packing_slip'], true))
            ? $requestedType
            : $sale->document_type;

        // BLOCKER: Explicitly deny access to INVOICE/RECEIPT types if it's still in 'consignment' status (unreconciled)
        // Packing Slips / Delivery Notes ARE allowed (as they are needed for shipping/surgery).
        if ($sale->sale_status === 'consignment' && !$sale->is_reconciled) {
             if (in_array($documentType, ['invoice', 'receipt'])) {
                 abort(403, 'Invoice/Receipt not generated yet. Please reconcile first. You may print a Delivery Note or Packing Slip.');
             }
        }

        // Determine data source:
        // - Packing Slip / Delivery Note: Use 'delivery_note_data' (Original Items) if available
        // - Invoice / Receipt: Use 'receipt_data' (Current/Reconciled Items)
        $data = $sale->receipt_data ?? [];

        if (in_array($documentType, ['packing_slip', 'delivery_note']) && !empty($sale->delivery_note_data)) {
            $data = $sale->delivery_note_data;
            // Merge in case some fields (like new dates or payment statuses) are needed from main record?
            // Actually, for packing slip, we want the SNAPSHOT. So using $data directly is safer to preserve history.
        }

        // Fetch Company Settings
        $companySettings = DB::table('settings')
            ->where('category', 'company')
            ->pluck('value', 'key');

        // Fetch Default Template for the requested document type
        $template = \App\Models\DocumentTemplate::where('template_type', $documentType)
            ->where('is_default', true)
            ->first();

        return view('pos.receipt', [
            'sale' => $sale,
            'data' => $data,
            'company' => $companySettings,
            'template' => $template,
            'requested_type' => $documentType, // Pass the requested type to the view
        ]);
    }
}
