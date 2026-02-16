<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Inventory;
use App\Models\Supplier;
use App\Models\Customer;
use App\Models\Location;
use Illuminate\Http\Request;

class BatchController extends Controller
{
    public function index(Request $request)
    {
        $query = Batch::with(['inventory', 'manufacturer', 'soldToCustomer', 'location']);

        // Filters
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('batch_number', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhereHas('inventory', function($q) use ($search) {
                      $q->where('product_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('recall_status') && $request->recall_status) {
            $query->where('recall_status', $request->recall_status);
        }

        if ($request->has('inventory_id') && $request->inventory_id) {
            $query->where('inventory_id', $request->inventory_id);
        }

        if ($request->has('expiring_soon')) {
            $query->expiringSoon(90);
        }

        if ($request->has('serialized_only')) {
            $query->serialized();
        }

        $batches = $query->orderBy('created_at', 'desc')->paginate(50);

        if ($request->expectsJson()) {
            return response()->json($batches);
        }

        $products = Inventory::orderBy('product_name')->get();
        $manufacturers = Supplier::orderBy('name')->get();
        $locations = Location::orderBy('name')->get();

        return view('batches.index', compact('batches', 'products', 'manufacturers', 'locations'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'inventory_id' => 'required|exists:inventory_master,id',
            'manufacturer_id' => 'nullable|exists:suppliers,id',
            'batch_number' => 'required|string',
            'serial_number' => 'nullable|string|unique:batches,serial_number',
            'is_serialized' => 'nullable|boolean',
            'expiry_date' => 'nullable|date',
            'quantity' => 'required|integer|min:1',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'location_id' => 'nullable|exists:locations,id',
        ]);

        $validated['status'] = 'available';
        $validated['recall_status'] = 'none';

        $batch = Batch::create($validated);

        if ($request->expectsJson()) {
            return response()->json($batch->load(['inventory', 'manufacturer']), 201);
        }

        return redirect()->route('batches.index')->with('success', 'Batch created successfully');
    }

    public function update(Request $request, Batch $batch)
    {
        $validated = $request->validate([
            'inventory_id' => 'required|exists:inventory_master,id',
            'manufacturer_id' => 'nullable|exists:suppliers,id',
            'batch_number' => 'required|string',
            'serial_number' => 'nullable|string|unique:batches,serial_number,' . $batch->id,
            'is_serialized' => 'nullable|boolean',
            'expiry_date' => 'nullable|date',
            'quantity' => 'required|integer|min:0',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'location_id' => 'nullable|exists:locations,id',
            'status' => 'nullable|in:available,sold,recalled,expired,damaged,returned',
        ]);

        $batch->update($validated);

        if ($request->expectsJson()) {
            return response()->json($batch->load(['inventory', 'manufacturer']));
        }

        return redirect()->route('batches.index')->with('success', 'Batch updated successfully');
    }

    public function destroy(Batch $batch)
    {
        $batch->delete();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Batch deleted successfully']);
        }

        return redirect()->route('batches.index')->with('success', 'Batch deleted successfully');
    }

    /**
     * Mark batch as recalled
     */
    public function recall(Request $request, Batch $batch)
    {
        $validated = $request->validate([
            'recall_reason' => 'required|string',
            'recall_date' => 'nullable|date',
        ]);

        $batch->markAsRecalled($validated['recall_reason'], $validated['recall_date'] ?? null);

        if ($request->expectsJson()) {
            return response()->json($batch);
        }

        return redirect()->route('batches.index')->with('success', 'Batch marked as recalled');
    }

    /**
     * Resolve recall
     */
    public function resolveRecall(Request $request, Batch $batch)
    {
        $validated = $request->validate([
            'resolution_notes' => 'nullable|string',
        ]);

        $batch->resolveRecall($validated['resolution_notes'] ?? null);

        if ($request->expectsJson()) {
            return response()->json($batch);
        }

        return redirect()->route('batches.index')->with('success', 'Recall resolved');
    }

    /**
     * Traceability search - find batch by serial number
     */
    public function traceability(Request $request)
    {
        $serialNumber = $request->input('serial_number');

        if (!$serialNumber) {
            return view('batches.traceability');
        }

        $batch = Batch::with(['inventory', 'manufacturer', 'soldToCustomer', 'location'])
                      ->where('serial_number', $serialNumber)
                      ->first();

        if (!$batch) {
            return view('batches.traceability', [
                'error' => 'Serial number not found',
                'serial_number' => $serialNumber
            ]);
        }

        // Get full traceability chain
        $chain = [
            'manufacturer' => $batch->manufacturer,
            'batch' => $batch,
            'location' => $batch->location,
            'customer' => $batch->soldToCustomer,
            'product' => $batch->inventory,
        ];

        return view('batches.traceability', compact('batch', 'chain', 'serialNumber'));
    }

    /**
     * Get expiring batches
     */
    public function expiring(Request $request)
    {
        $days = $request->input('days', 90);

        $batches = Batch::with(['inventory', 'location'])
                        ->expiringSoon($days)
                        ->orderBy('expiry_date')
                        ->get();

        if ($request->expectsJson()) {
            return response()->json($batches);
        }

        return view('batches.expiring', compact('batches', 'days'));
    }

    /**
     * Get recalled batches
     */
    public function recalled(Request $request)
    {
        $batches = Batch::with(['inventory', 'manufacturer'])
                        ->recalled()
                        ->orderBy('recall_date', 'desc')
                        ->get();

        if ($request->expectsJson()) {
            return response()->json($batches);
        }

        return view('batches.recalled', compact('batches'));
    }
}
