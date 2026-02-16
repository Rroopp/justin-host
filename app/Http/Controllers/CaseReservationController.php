<?php

namespace App\Http\Controllers;

use App\Models\CaseReservation;
use App\Models\CaseReservationItem;
use App\Models\Inventory;
use App\Models\Batch;
use App\Models\Location;
use App\Models\InventoryMovement;
use App\Models\PosSale;
use App\Models\Sale;
use App\Models\SetContent;
use App\Models\Category;
use App\Models\Package;
use App\Models\PackageItem;
use App\Models\Customer;
use App\Models\SurgicalSet;
use App\Models\SetMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CaseReservationController extends Controller
{
    public function index(Request $request)
    {
        $query = CaseReservation::query()->with('creator');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('patient_name', 'like', "%{$search}%")
                  ->orWhere('surgeon_name', 'like', "%{$search}%")
                  ->orWhere('procedure_name', 'like', "%{$search}%")
                  ->orWhere('case_number', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_filter')) {
            if ($request->date_filter === 'upcoming') {
                $query->where('surgery_date', '>=', now()->startOfDay());
            } elseif ($request->date_filter === 'past') {
                $query->where('surgery_date', '<', now()->startOfDay());
            }
        }

        $reservations = $query->orderBy('surgery_date', 'asc')->paginate(20);
        $totalUpcoming = CaseReservation::upcoming()->count();

        return view('reservations.index', compact('reservations', 'totalUpcoming'));
    }

    public function create()
    {
        // Include both surgical sets and Main Store, sorted with Main Store first
        $locations = Location::whereIn('type', ['set', 'store'])
            ->where('is_active', true)
            ->orderByRaw("CASE WHEN name = 'Main Store' THEN 0 ELSE 1 END, name ASC")
            ->get();
        
        $customers = Customer::where('is_active', true)->orderBy('name')->get();

        return view('reservations.create', compact('locations', 'customers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_name' => 'required|string|max:255',
            'surgeon_name' => 'required|string|max:255',
            'procedure_name' => 'nullable|string|max:255',
            'surgery_date' => 'required|date',
            'location_id' => 'required|exists:locations,id',
            'customer_id' => 'nullable|exists:customers,id',
            'notes' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['status'] = 'draft';

        $reservation = CaseReservation::create($validated);

        return redirect()->route('reservations.show', $reservation->id)
            ->with('success', 'Case draft created. Please add items to reserve.');
    }

    public function update(Request $request, $id)
    {
        $reservation = CaseReservation::findOrFail($id);

        if ($reservation->status !== 'draft') {
            return back()->with('error', 'Cannot update a confirmed reservation. Please cancel and recreate or contact admin.');
        }

        $validated = $request->validate([
            'patient_name' => 'required|string|max:255',
            'surgeon_name' => 'required|string|max:255',
            'procedure_name' => 'nullable|string|max:255',
            'surgery_date' => 'required|date',
            'customer_id' => 'nullable|exists:customers,id',
            'notes' => 'nullable|string',
        ]);

        $reservation->update($validated);

        return back()->with('success', 'Case details updated successfully.');
    }

    public function show($id)
    {
        $reservation = CaseReservation::with(['items.inventory', 'items.batch', 'location', 'creator'])->findOrFail($id);
        $locations = Location::all();
        $categories = \App\Models\Category::all();
        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        
        return view('reservations.show', compact('reservation', 'locations', 'categories', 'customers'));
    }

    public function addItem(Request $request, $id)
    {
        $reservation = CaseReservation::findOrFail($id);
        
        if ($reservation->status !== 'draft') {
            return back()->with('error', 'Cannot add items to a confirmed reservation. Please complete or cancel it first.');
        }

        $validated = $request->validate([
            'inventory_id' => 'required|exists:inventory_master,id',
            'batch_id' => 'nullable|exists:batches,id',
            'quantity' => 'required|integer|min:1',
        ]);

        // Verify stock availability
        if (!empty($validated['batch_id'])) {
            $batch = Batch::find($validated['batch_id']);
            if ($batch->quantity < $validated['quantity']) {
            if ($request->wantsJson()) {
                return response()->json(['error' => "Insufficient stock in batch {$batch->batch_number}. Available: {$batch->quantity}"], 422);
            }
            return back()->with('error', "Insufficient stock in batch {$batch->batch_number}. Available: {$batch->quantity}");
        }
    } else {
        // Allow adding without batch if configured, else enforce
        // For now, we allow it (Controller allows it), but maybe warn?
        // logic continues...
    }

        try {
            CaseReservationItem::create([
                'case_reservation_id' => $reservation->id,
                'inventory_id' => $validated['inventory_id'],
                'batch_id' => $validated['batch_id'] ?? null,
                'quantity_reserved' => $validated['quantity'],
                'status' => 'pending',
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to add item to reservation: " . $e->getMessage());
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Failed to add item. ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Failed to add item. Please try again.');
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Item added to draft.', 'success' => true]);
        }
        return back()->with('success', 'Item added to draft.');
    }

    public function removeItem($id, $itemId)
    {
        $reservation = CaseReservation::findOrFail($id);
        if ($reservation->status !== 'draft') {
            return back()->with('error', 'Cannot remove items from a confirmed reservation.');
        }
        
        CaseReservationItem::where('case_reservation_id', $id)->where('id', $itemId)->delete();
        
        return back()->with('success', 'Item removed.');
    }

    public function confirm($id)
    {
        $reservation = CaseReservation::with('items')->findOrFail($id);

        if ($reservation->status !== 'draft') {
            return back()->with('error', 'Reservation is not in draft status.');
        }

        if ($reservation->items->isEmpty()) {
            return back()->with('error', 'Cannot confirm an empty reservation.');
        }

        // Check for booking conflicts if reserving a surgical set
        $location = Location::find($reservation->location_id);
        if ($location && $location->type === 'set') {
            // Check for overlapping reservations on the same set
            $conflictingReservation = CaseReservation::where('location_id', $reservation->location_id)
                ->where('id', '!=', $reservation->id)
                ->where('status', 'confirmed')
                ->where(function($query) use ($reservation) {
                    // Check if surgery dates overlap (assuming 4-hour surgery duration)
                    $surgeryStart = $reservation->surgery_date;
                    $surgeryEnd = $reservation->surgery_date->copy()->addHours(4);
                    
                    $query->whereBetween('surgery_date', [$surgeryStart, $surgeryEnd])
                          ->orWhere(function($q) use ($surgeryStart) {
                              $q->where('surgery_date', '<=', $surgeryStart);
                              
                              if (DB::getDriverName() === 'sqlite') {
                                  $q->whereRaw("datetime(surgery_date, '+4 hours') >= ?", [$surgeryStart]);
                              } else {
                                  $q->whereRaw('DATE_ADD(surgery_date, INTERVAL 4 HOUR) >= ?', [$surgeryStart]);
                              }
                          });
                })
                ->first();

            if ($conflictingReservation) {
                return back()->with('error', 
                    "Booking conflict: {$location->name} is already reserved for another surgery on " . 
                    $conflictingReservation->surgery_date->format('M d, Y @ h:i A') . 
                    " (Case: {$conflictingReservation->case_number}, Surgeon: {$conflictingReservation->surgeon_name}). " .
                    "Please choose a different time or location."
                );
            }
        }

        DB::transaction(function () use ($reservation) {
            foreach ($reservation->items as $item) {
                if (!$item->batch_id) {
                    // Skip stock deduction for items without batch (e.g. Services, Rentals)
                    continue;
                }

                $batch = Batch::lockForUpdate()->find($item->batch_id);
                
                if ($batch->quantity < $item->quantity_reserved) {
                    throw new \Exception("Insufficient stock for {$batch->product_name} (Batch: {$batch->batch_number}). Available: {$batch->quantity}, Requested: {$item->quantity_reserved}");
                }

                // Log movement (Deduct stock)
                $movementData = [
                    'inventory_id' => $item->inventory_id,
                    'batch_id' => $item->batch_id,
                    'movement_type' => 'reservation',
                    'quantity' => -1 * $item->quantity_reserved, // Negative for deduction
                    'quantity_before' => $batch->quantity, // Assuming accessor
                    'quantity_after' => $batch->quantity - $item->quantity_reserved,
                    'from_location_id' => $batch->location_id,
                    'reference_type' => 'case_reservation',
                    'reference_id' => $reservation->id,
                    'unit_cost' => $batch->cost_price,
                    'notes' => "Reserved for Case: {$reservation->case_number}",
                ];

                // Update batch using standard decrement to trigger observers if any
                $batch->decrement('quantity', $item->quantity_reserved);
                
                InventoryMovement::logMovement($movementData);
            }

            $reservation->update(['status' => 'confirmed']);
        });

        return back()->with('success', 'Reservation confirmed. Stock has been allocated.');
    }

    public function complete(Request $request, $id)
    {
        $reservation = CaseReservation::with('items.inventory', 'items.batch')->findOrFail($id);

        if ($reservation->status !== 'confirmed') {
            return back()->with('error', 'Only confirmed reservations can be completed.');
        }

        $inputUsage = $request->input('usage', []);
        $inputNotes = $request->input('notes', []);

        DB::transaction(function () use ($reservation, $inputUsage, $inputNotes) {
            // 1. Release ALL reserved stock first (Clean Slate)
            foreach ($reservation->items as $item) {
                if (!$item->batch_id) {
                     // For service items or rentals (no batch), we just mark returned if unused later.
                     // But here we release reservation. Just update status?
                     $item->update(['status' => 'returned']);
                     continue;
                }

                $batch = Batch::lockForUpdate()->find($item->batch_id);
                
                // Log movement (Return stock from reservation)
                // We return it so we can "Sell" it properly below, maintaining the audit trail
                $movementData = [
                    'inventory_id' => $item->inventory_id,
                    'batch_id' => $item->batch_id,
                    'movement_type' => 'return', 
                    'quantity' => $item->quantity_reserved,
                    'quantity_before' => $batch->quantity,
                    'quantity_after' => $batch->quantity + $item->quantity_reserved,
                    'to_location_id' => $batch->location_id,
                    'reference_type' => 'case_reservation',
                    'reference_id' => $reservation->id,
                    'unit_cost' => $batch->cost_price,
                    'notes' => "Release for Case Completion: {$reservation->case_number}",
                ];

                $batch->increment('quantity', $item->quantity_reserved);
                InventoryMovement::logMovement($movementData);
                
                $item->update(['status' => 'returned']); // Temporarily mark returned
            }

            // 2. Process Actual Usage & Create Sale
            $saleItems = [];
            $totalAmount = 0;
            $itemsUsedCount = 0;

            foreach ($reservation->items as $item) {
                $usedQty = (int) ($inputUsage[$item->id] ?? 0);
                $note = $inputNotes[$item->id] ?? null;

                // Update Item Record
                $item->update([
                    'quantity_used' => $usedQty,
                    'status' => $usedQty > 0 ? 'used' : 'returned',
                    'notes' => $note
                ]);

                if ($usedQty > 0) {
                    $itemsUsedCount++;
                    $product = $item->inventory;
                    $batch = null;
                    
                    if ($item->batch_id) {
                         $batch = Batch::lockForUpdate()->find($item->batch_id);
                    }
                    
                    // Deduct for Sale
                    if ($batch) {
                        if ($batch->quantity < $usedQty) {
                            throw new \Exception("Detailed stock error: Batch {$batch->batch_number} has {$batch->quantity}, trying to use {$usedQty}. This shouldn't happen after release.");
                        }

                        $movementData = [
                            'inventory_id' => $item->inventory_id,
                            'batch_id' => $item->batch_id,
                            'movement_type' => 'sale',
                            'quantity' => -1 * $usedQty,
                            'quantity_before' => $batch->quantity,
                            'quantity_after' => $batch->quantity - $usedQty,
                            'from_location_id' => $batch->location_id,
                            'reference_type' => 'case_reservation', // Or 'pos_sale' if we link it to the sale ID later
                            'reference_id' => $reservation->id,
                            'unit_cost' => $batch->cost_price,
                            'notes' => "Used in Case: {$reservation->case_number}",
                        ];
    
                        $batch->decrement('quantity', $usedQty);
                        InventoryMovement::logMovement($movementData);
                    } else {
                        // Service Item logic or non-batched
                        if ($product->type !== 'service') {
                            $product->decrement('quantity_in_stock', $usedQty);
                        }
                    }
                    // Stock Management
                    if ($product->type !== 'service') {
                        // Check stock availability
                        // Ideally checking was done before, but here we are manual. 
                        
                        // We use batch logic only for physical items
                         if ($item->batch_id) {
                            $batch = Batch::find($item->batch_id);
                             if ($batch) {
                                $batch->decrement('quantity', $usedQty);
                             } else {
                                $product->decrement('quantity_in_stock', $usedQty);
                             }
                         } else {
                             $product->decrement('quantity_in_stock', $usedQty); 
                         }
                        
                        InventoryMovement::logMovement($movementData);
                    }

                    // Prepare Invoice Line Item
                    $unitPrice = $item->custom_price ?? $product->selling_price; // Use custom price if set (e.g. rental fee)
                    $lineTotal = $unitPrice * $usedQty;
                    $totalAmount += $lineTotal;

                    $saleItems[] = [
                        'product_id' => $product->id,
                        'product_name' => $product->product_name,
                        'quantity' => $usedQty,
                        'batch_number' => $batch ? $batch->batch_number : null, // Track batch in sale
                        'unit_price' => $unitPrice,
                        'item_total' => $lineTotal,
                        'type' => 'sale',
                        'product_snapshot' => $product->toArray(),
                    ];
                    
                    // Legacy Sale Record (optional but good for consistency)
                    Sale::create([
                        'inventory_id' => $product->id,
                        'product_id' => $product->id,
                        'quantity' => $usedQty,
                        'total' => $lineTotal,
                        'seller_username' => auth()->user()->username ?? 'system',
                        'product_snapshot' => $product->toArray(),
                    ]);
                }
            }

            // 3. Create POS Sale (Invoice)
            if ($itemsUsedCount > 0) {
                $invoiceNumber = 'INV-' . strtoupper(Str::random(8)); // Basic generation
                
                $sale = PosSale::create([
                    'invoice_number' => $invoiceNumber,
                    'document_type' => 'invoice',
                    'customer_name' => $reservation->customer ? $reservation->customer->name : ($reservation->patient_name . ' (' . ($reservation->patient_id ?? 'No ID') . ')'),
                    'customer_id' => $reservation->customer_id,
                    'seller_username' => auth()->user()->username ?? 'system',
                    'timestamp' => now(),
                    'sale_items' => $saleItems, // Cast to JSON automatically by model? Assuming Yes.
                    'subtotal' => $totalAmount,
                    'tax' => 0, // Simplified
                    'total' => $totalAmount,
                    'payment_method' => 'Credit', // Invoices are usually Credit
                    'payment_status' => 'pending',
                    'sale_status' => 'completed',
                    'notes' => "Generated from Case: {$reservation->case_number}. Surgeon: {$reservation->surgeon_name}",
                ]);

                // Link case to sale (if column exists, or just via notes/logic)
                // $reservation->update(['pos_sale_id' => $sale->id]); // If we added the column. 
                // For now, we rely on the generated invoice.
            }

            $reservation->update(['status' => 'completed']);
        });

        return redirect()->route('reservations.show', $id)->with('success', 'Case completed. Invoice generated for used items.');
    }

    public function cancel($id)
    {
        $reservation = CaseReservation::with(['items', 'surgicalSets'])->findOrFail($id);

        if ($reservation->status === 'completed') {
            return back()->with('error', 'Cannot cancel a completed case.');
        }

        DB::transaction(function () use ($reservation) {
            // Cancel any dispatched sets
            foreach ($reservation->surgicalSets as $set) {
                // If set was dispatched, return it to available
                if ($set->pivot->status === 'dispatched') {
                    $set->update(['status' => 'available']);
                    $reservation->surgicalSets()->updateExistingPivot($set->id, ['status' => 'returned']);
                    
                    // Close movement log
                    SetMovement::where('surgical_set_id', $set->id)
                        ->where('case_reservation_id', $reservation->id)
                        ->whereNull('returned_at')
                        ->update([
                            'returned_at' => now(), 
                            'status' => 'returned', 
                            'notes' => 'Case Cancelled'
                        ]);
                }
            }

            if ($reservation->status === 'confirmed') {
                // Return stock
                foreach ($reservation->items as $item) {
                    if (!$item->batch_id) {
                        $item->update(['status' => 'returned']);
                        continue;
                    }

                    $batch = Batch::lockForUpdate()->find($item->batch_id);
                    
                    if (!$batch) {
                        // Batch likely deleted, just mark item returned
                        $item->update(['status' => 'returned']);
                        continue;
                    }
                    
                    // Log movement (Return stock)
                    $movementData = [
                        'inventory_id' => $item->inventory_id,
                        'batch_id' => $item->batch_id,
                        'movement_type' => 'return', // Or 'reservation_cancel' if we had it, 'return' is fine
                        'quantity' => $item->quantity_reserved, // Positive to add back
                        'quantity_before' => $batch->quantity,
                        'quantity_after' => $batch->quantity + $item->quantity_reserved,
                        'to_location_id' => $batch->location_id, // It goes back where it was
                        'reference_type' => 'case_reservation',
                        'reference_id' => $reservation->id,
                        'unit_cost' => $batch->cost_price ?? 0,
                        'notes' => "Reservation Cancelled: {$reservation->case_number}",
                    ];

                    $batch->increment('quantity', $item->quantity_reserved);
                    InventoryMovement::logMovement($movementData);
                    
                    $item->update(['status' => 'returned']);
                }
            }
            
            $reservation->update(['status' => 'cancelled']);
        });

        return back()->with('success', 'Case cancelled. Any reserved stock has been returned.');
    }

    public function searchItems(Request $request)
    {
        $search = $request->input('query');
        $category = $request->input('category');
        $type = $request->input('type'); // 'product' or 'set'

        // 1. Inventory Items
        $products = collect([]);
        if (!$type || $type === 'product') {
            $q = Inventory::with(['batches' => function($q) {
                    $q->where('quantity', '>', 0)->orderBy('expiry_date', 'asc');
                }]);
            
            if ($search && strlen($search) >= 1) {
                $q->where(function($query) use ($search) {
                    $query->where('product_name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            }

            if ($request->filled('category')) {
                $q->where('category', $request->input('category'));
            }

            $products = $q->limit(20)
                ->orderBy('product_name')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'type' => 'product',
                        'name' => $item->product_name,
                        'code' => $item->code,
                        'available_stock' => $item->quantity_in_stock, // Inventory model uses quantity_in_stock
                        'batches' => $item->batches,
                        'display_name' => $item->product_name
                    ];
                });
        }

        // 2. Sets (Locations where type='set')
        $sets = collect([]);
        if (!$type || $type === 'set') {
            // Sets usually don't adhere to 'category' filter unless we map it.
            // For now, if category is selected, we might skip sets OR show them if they match name.
            if (!$request->filled('category') || $type === 'set') {
                $q = Location::where('type', 'set');
                if ($search) {
                    $q->where('name', 'like', "%{$search}%");
                }
                $sets = $q->limit(10)
                    ->get()
                    ->map(function ($set) {
                        $itemCount = SetContent::where('location_id', $set->id)->count();
                        return [
                            'id' => $set->id,
                            'type' => 'set',
                            'name' => $set->name,
                            'code' => 'SET',
                            'available_stock' => 1, 
                            'batches' => [],
                            'display_name' => '[SET] ' . $set->name . " ($itemCount items)"
                        ];
                    });
            }
        }

        // 3. Packages
        $packages = collect([]);
        if (!$type || $type === 'package') {
            if (!$request->filled('category') || $type === 'package') {
                 $q = Package::where('is_active', true);
                 if ($search) {
                     $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%");
                 }
                 $packages = $q->limit(10)->get()->map(function($pkg) {
                     return [
                         'id' => $pkg->id,
                         'type' => 'package',
                         'name' => $pkg->name,
                         'code' => $pkg->code,
                         'available_stock' => 1, // Logical availability
                         'batches' => [],
                         'display_name' => '[PKG] ' . $pkg->name
                     ];
                 });
            }
        }

        return response()->json($products->merge($sets)->merge($packages)->values());
    }

    public function addPackage(Request $request, $id)
    {
        $reservation = CaseReservation::findOrFail($id);
        
        if ($reservation->status !== 'draft') {
            return back()->with('error', 'Cannot add items to a confirmed reservation.');
        }

        $package = Package::with('items')->findOrFail($request->package_id);

        $addedCount = 0;
        $missingCount = 0;

        foreach ($package->items as $pItem) {
            // Find batches for this item at the RESERVATION LOCATION (or main store?)
            // Usually stock is drawn from the location serving the case.
            $batches = Batch::where('inventory_id', $pItem->inventory_id)
                            ->where('location_id', $reservation->location_id)
                            ->where('quantity', '>', 0)
                            ->orderBy('expiry_date', 'asc')
                            ->get();

            $needed = $pItem->quantity;

            foreach ($batches as $batch) {
                if ($needed <= 0) break;
                
                $take = min($needed, $batch->quantity);
                
                CaseReservationItem::create([
                    'case_reservation_id' => $reservation->id,
                    'inventory_id' => $pItem->inventory_id,
                    'batch_id' => $batch->id,
                    'quantity_reserved' => $take,
                    'status' => 'pending',
                ]);

                $needed -= $take;
                $addedCount++;
            }

            if ($needed > 0) {
                $missingCount++;
            }
        }

        $msg = "Added contents of package '{$package->name}' ($addedCount items).";
        if ($missingCount > 0) {
            $msg .= " Warning: {$missingCount} types of items had insufficient stock in location.";
        }

        return back()->with('success', $msg);
    }



    public function addSet(Request $request, $id)
    {
        $reservation = CaseReservation::findOrFail($id);
        
        if ($reservation->status !== 'draft') {
            return back()->with('error', 'Cannot add items to a confirmed reservation.');
        }

        $set = Location::with('setContents')->where('type', 'set')->findOrFail($request->set_id);

        // handle Rental Option
        if ($request->boolean('is_rental')) {
            $serviceItem = $this->getSetRentalServiceItem();
            // Add the Rental Fee Line Item
            CaseReservationItem::create([
                'case_reservation_id' => $reservation->id,
                'inventory_id' => $serviceItem->id,
                // No batch for service
                'quantity_reserved' => 1,
                'status' => 'pending', // Pending confirmation
                'custom_price' => $set->rental_price > 0 ? $set->rental_price : 0.00,
                'notes' => "Rental Fee for Set: {$set->name}"
            ]);
        }

        $addedCount = 0;
        $missingCount = 0;

        foreach ($set->setContents as $content) {
            // ... (rest of logic same)
            // Find batches for this item AT the set location
            $batches = Batch::where('inventory_id', $content->inventory_id)
                            ->where('location_id', $set->id)
                            ->where('quantity', '>', 0)
                            ->orderBy('expiry_date', 'asc')
                            ->get();

            $needed = $content->standard_quantity;

            foreach ($batches as $batch) {
                if ($needed <= 0) break;
                
                $take = min($needed, $batch->quantity);
                
                CaseReservationItem::create([
                    'case_reservation_id' => $reservation->id,
                    'inventory_id' => $content->inventory_id,
                    'batch_id' => $batch->id,
                    'quantity_reserved' => $take,
                    'status' => 'pending',
                    'notes' => "Set: {$set->name}", 
                ]);

                $needed -= $take;
                $addedCount++;
            }

            // If still needed (no stock or partial stock), add line item without batch
            if ($needed > 0) {
                CaseReservationItem::create([
                    'case_reservation_id' => $reservation->id,
                    'inventory_id' => $content->inventory_id,
                    'batch_id' => null, // No specific batch reserved
                    'quantity_reserved' => $needed,
                    'status' => 'pending', // Pending stock assignment
                    'notes' => "Set: {$set->name}",
                ]);
                $addedCount++;
                $missingCount++; // We still count this as "missing stock" for the warning
            }
        }

        $msg = "Added contents of set '{$set->name}'.";
        if ($missingCount > 0) {
            $msg .= " Note: {$missingCount} items have no stock assigned yet (marked as pending).";
        }

        return back()->with('success', $msg);
    }

    private function getSetRentalServiceItem()
    {
        // Find or Create the Generic Service Item for Set Rentals
        return Inventory::firstOrCreate(
            ['code' => 'SVC-SET-RENTAL'],
            [
                'product_name' => 'Surgical Set Rental Fee',
                'type' => 'service',
                'category' => 'Services', // Ensure this category logic aligns with app
                'selling_price' => 0, // Base price 0, overridden by custom_price
                'quantity_in_stock' => 9999, // Infinite for service
                'description' => 'Fee for renting a surgical set',
                'unit' => 'Session'
            ]
        );
    }
}
