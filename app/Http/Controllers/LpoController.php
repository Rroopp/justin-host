<?php

namespace App\Http\Controllers;

use App\Models\Lpo;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LpoController extends Controller
{
    /**
     * Display a listing of LPOs.
     */
    public function index(Request $request)
    {
        $query = Lpo::with('customer')->latest();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        $lpos = $query->paginate(15);
        
        // Facilities only (customers with 'facility' set or type='organization' if applicable)
        // For simplicity, just listing all customers for filter
        $customers = Customer::orderBy('name')->get();

        return view('lpos.index', compact('lpos', 'customers'));
    }

    /**
     * Show the form for creating a new LPO.
     */
    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        return view('lpos.create', compact('customers'));
    }

    /**
     * Store a newly created LPO in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'lpo_number' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'lpo_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
            'description' => 'nullable|string'
        ]);

        $path = null;
        if ($request->hasFile('lpo_document')) {
            $path = $request->file('lpo_document')->store('lpos', 'public');
        }

        $lpo = Lpo::create([
            'customer_id' => $validated['customer_id'],
            'lpo_number' => $validated['lpo_number'],
            'amount' => $validated['amount'],
            'remaining_balance' => $validated['amount'], // Initial balance = total amount
            'valid_from' => $validated['valid_from'],
            'valid_until' => $validated['valid_until'],
            'description' => $validated['description'],
            'document_path' => $path,
            'status' => 'active'
        ]);

        return redirect()->route('lpos.index')->with('success', 'LPO created successfully.');
    }

    /**
     * Display the specified LPO.
     */
    public function show(Lpo $lpo)
    {
        $lpo->load(['customer', 'sales.payments']);
        return view('lpos.show', compact('lpo'));
    }

    /**
     * Remove the specified LPO from storage.
     */
    public function destroy(Lpo $lpo)
    {
        $lpo->delete();
        return redirect()->route('lpos.index')->with('success', 'LPO deleted successfully.');
    }
    
    /**
     * API: Get active LPOs for a specific customer.
     * Used by POS frontend.
     */
    public function getActiveLpos($customerId)
    {
        $lpos = Lpo::where('customer_id', $customerId)
            ->where('status', 'active')
            ->where(function ($q) {
                // Check expiry if set
                $q->whereNull('valid_until')
                  ->orWhere('valid_until', '>=', now());
            })
            ->where('remaining_balance', '>', 0)
            ->get();
            
        return response()->json($lpos);
    }
}
