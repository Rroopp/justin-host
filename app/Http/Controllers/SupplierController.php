<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class SupplierController extends Controller
{
    /**
     * Display a listing of suppliers.
     */
    public function index(Request $request)
    {
        $query = Supplier::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $suppliers = $query->orderBy('name')->paginate(50);

        if ($request->expectsJson()) {
            return response()->json($suppliers);
        }

        return view('suppliers.index', compact('suppliers'));
    }

    /**
     * Store a newly created supplier.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'payment_terms' => 'nullable|string',
            'tax_id' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        // Avoid runtime errors if DB migrations haven't been applied yet
        $columns = Schema::getColumnListing('suppliers');
        $validated = array_intersect_key($validated, array_flip($columns));

        $supplier = Supplier::create($validated);

        if ($request->expectsJson()) {
            return response()->json($supplier, 201);
        }

        return redirect()->route('suppliers.index')->with('success', 'Supplier added successfully');
    }

    /**
     * Update the specified supplier.
     */
    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'payment_terms' => 'nullable|string',
            'tax_id' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        // Avoid runtime errors if DB migrations haven't been applied yet
        $columns = Schema::getColumnListing('suppliers');
        $validated = array_intersect_key($validated, array_flip($columns));

        $supplier->update($validated);

        if ($request->expectsJson()) {
            return response()->json($supplier);
        }

        return redirect()->route('suppliers.index')->with('success', 'Supplier updated successfully');
    }

    /**
     * Remove the specified supplier.
     */
    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Supplier deleted successfully']);
        }

        return redirect()->route('suppliers.index')->with('success', 'Supplier deleted successfully');
    }
}
