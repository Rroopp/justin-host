<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use App\Models\Customer;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RentalController extends Controller
{
    use \App\Traits\CsvExportable;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Rental::with(['customer', 'rentalItems.inventory'])->orderByDesc('created_at');

        // Status Filter
        if ($request->has('status') && $request->status !== '') {
            $status = $request->status;
            if ($status === 'overdue') {
                $query->where('expected_return_at', '<', now()->startOfDay())
                      ->where('status', '!=', 'returned');
            } elseif ($status === 'due_today') {
                $query->whereDate('expected_return_at', now()->today())
                      ->where('status', '!=', 'returned');
            } else {
                $query->where('status', $status);
            }
        }

        if ($request->has('export')) {
            $rentals = $query->get();
            $data = $rentals->map(function($r) {
                // Summarize items
                $itemSummary = $r->rentalItems->map(fn($i) => "{$i->inventory->product_name} (x{$i->quantity})")->join(', ');
                
                return [
                    'ID' => $r->id,
                    'Customer' => $r->customer->name ?? 'N/A',
                    'Rented At' => $r->rented_at->format('Y-m-d H:i'),
                    'Expected Return' => $r->expected_return_at ? $r->expected_return_at->format('Y-m-d') : '-',
                    'Status' => $r->status,
                    'Items' => $itemSummary
                ];
            });
            return $this->streamCsv('rentals_report.csv', ['ID', 'Customer', 'Rented At', 'Expected Return', 'Status', 'Items'], $data, 'Rentals History');
        }

        $rentals = $query->paginate(20);
        return view('rentals.index', compact('rentals'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        // Only fetch rentable items that are in stock
        $rentables = Inventory::where('is_rentable', true)
            ->where('quantity_in_stock', '>', 0)
            ->orderBy('product_name')
            ->get();
            
        return view('rentals.create', compact('customers', 'rentables'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'rented_at' => 'required|date',
            'expected_return_at' => 'nullable|date|after:rented_at',
            'items' => 'required|array|min:1',
            'items.*.inventory_id' => 'required|exists:inventory_master,id',
            'items.*.quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Create Rental
            $rental = Rental::create([
                'customer_id' => $validated['customer_id'],
                'rented_at' => $validated['rented_at'],
                'expected_return_at' => $validated['expected_return_at'],
                'status' => 'active',
                'items' => [], // Deprecated JSON column
                'notes' => $validated['notes'],
            ]);

            foreach ($validated['items'] as $item) {
                // Determine item details (for validation)
                $inventory = Inventory::select('product_name', 'selling_price', 'quantity_in_stock')->find($item['inventory_id']);
                
                // Check stock
                if ($inventory->quantity_in_stock < $item['quantity']) {
                    throw new \Exception("Insufficient stock for {$inventory->product_name}. Available: {$inventory->quantity_in_stock}");
                }

                // Decrement stock
                Inventory::where('id', $item['inventory_id'])->decrement('quantity_in_stock', $item['quantity']);

                // Create Rental Item
                $rental->rentalItems()->create([
                    'inventory_id' => $item['inventory_id'],
                    'quantity' => $item['quantity'],
                    'price_at_rental' => $inventory->selling_price,
                    'condition_out' => 'Good', // Default to Good
                    'condition_in' => null,
                ]);
            }

            DB::commit();
            return redirect()->route('rentals.index')->with('success', 'Rental created successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to create rental: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Rental $rental)
    {
        $rental->load(['customer', 'rentalItems.inventory']);
        return view('rentals.show', compact('rental'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Rental $rental)
    {
        // For now, only allowing editing notes or return date if active
        return view('rentals.edit', compact('rental'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Rental $rental)
    {
        $request->validate([
            'notes' => 'nullable|string',
            'expected_return_at' => 'nullable|date',
        ]);
        
        $rental->update($request->only('notes', 'expected_return_at'));
        return redirect()->route('rentals.show', $rental)->with('success', 'Rental updated');
    }

    /**
     * Show return form
     */
    public function returnForm(Rental $rental)
    {
        if ($rental->status !== 'active' && $rental->status !== 'overdue') {
            return back()->with('error', 'This rental is already returned or billed.');
        }
        $rental->load('rentalItems.inventory');
        return view('rentals.return', compact('rental'));
    }

    /**
     * Process return
     */
    public function processReturn(Request $request, Rental $rental)
    {
        $request->validate([
            'returned_at' => 'required|date',
            'items' => 'required|array',
        ]);

        DB::beginTransaction();
        try {
            $rental->load('rentalItems');
            
            // Re-increment stock
            foreach ($rental->rentalItems as $item) {
                // Find input for this item - assuming input is indexed by rental_item_id or inventory_id
                // View likely sends items[inventory_id] or items[rental_item_id]
                // Let's assume input matches the structure in returnForm
                
                // For robustness, let's look for matching inventory_id in request input
                // Or better, request items keyed by rental_item_id if possible. 
                // But migration maintained inventory_id. Let's rely on inventory_id for now as index
                
                // Assuming request input structure: items[inventory_id] = ['condition_in' => '...']
                // or items = [ ['inventory_id' => 1, 'condition_in' => '...'], ... ]
                
                $inputData = null;
                foreach ($request->items as $inputItem) {
                    if (isset($inputItem['inventory_id']) && $inputItem['inventory_id'] == $item->inventory_id) {
                        $inputData = $inputItem;
                        break;
                    }
                }

                if ($inputData) {
                    $item->update([
                        'condition_in' => $inputData['condition_in'] ?? 'Good',
                    ]);
                }

                // Increment stock back
                Inventory::where('id', $item->inventory_id)->increment('quantity_in_stock', $item->quantity);
            }

            $rental->update([
                'status' => 'returned',
                'returned_at' => $request->returned_at,
            ]);

            DB::commit();
            return redirect()->route('rentals.show', $rental)->with('success', 'Rental returned successfully. Items restocked.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error processing return: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Rental $rental)
    {
        // Only allow delete if it hasn't messed with stock (e.g. if we want to void it)
        // For MVP, just delete and warn user manually to fix stock if active.
        // Better: Re-stock if active.
        
        if ($rental->status === 'active' || $rental->status === 'overdue') {
             // Restock logic similar to return
             foreach ($rental->rentalItems as $item) {
                 Inventory::where('id', $item->inventory_id)->increment('quantity_in_stock', $item->quantity);
             }
        }
        
        $rental->delete();
        return redirect()->route('rentals.index')->with('success', 'Rental deleted.');
    }
}
