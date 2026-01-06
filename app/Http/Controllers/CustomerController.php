<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class CustomerController extends Controller
{
    use \App\Traits\CsvExportable;

    /**
     * Display a listing of customers.
     */
    public function index(Request $request)
    {
        $query = Customer::query();

        // Search filter
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('customer_code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('facility', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('patient_name', 'like', "%{$search}%");
            });
        }

        // Patient type filter
        if ($request->has('patient_type')) {
            $query->where('patient_type', $request->patient_type);
        }

        if ($request->has('export')) {
            $customers = $query->orderBy('name')->get();
            $data = $customers->map(function($c) {
                return [
                    'Code' => $c->customer_code,
                    'Name' => $c->name,
                    'Type' => $c->customer_type,
                    'Phone' => $c->phone,
                    'Email' => $c->email,
                    'Facility' => $c->facility,
                    'Patient Name' => $c->patient_name,
                    'Patient Type' => $c->patient_type,
                    'Balance' => $c->current_balance
                ];
            });
            return $this->streamCsv('customer_list.csv', ['Code', 'Name', 'Type', 'Phone', 'Email', 'Facility', 'Patient Name', 'Patient Type', 'Balance'], $data, 'Customer List');
        }

        $customers = $query->orderBy('name')->paginate(50);

        if ($request->expectsJson()) {
            return response()->json($customers);
        }

        return view('customers.index', compact('customers'));
    }

    /**
     * Store a newly created customer.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_type' => 'nullable|in:individual,corporate,hospital',
            'name' => 'required|string|max:255',
            'facility' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:50',
            'country' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:255',
            'payment_terms' => 'nullable|string',
            'credit_limit' => 'nullable|numeric|min:0',
            'current_balance' => 'nullable|numeric',
            'is_active' => 'nullable|boolean',
            'patient_name' => 'nullable|string|max:255',
            'patient_number' => 'nullable|string|max:50',
            'patient_type' => 'nullable|in:Inpatient,Outpatient',
        ]);

        // Avoid runtime errors if DB migrations haven't been applied yet
        $columns = Schema::getColumnListing('customers');
        $validated = array_intersect_key($validated, array_flip($columns));

        $customer = Customer::create($validated);

        if ($request->expectsJson()) {
            return response()->json($customer, 201);
        }

        return redirect()->route('customers.index')->with('success', 'Customer added successfully');
    }

    /**
     * Update the specified customer.
     */
    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'customer_type' => 'nullable|in:individual,corporate,hospital',
            'name' => 'required|string|max:255',
            'facility' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:50',
            'country' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:255',
            'payment_terms' => 'nullable|string',
            'credit_limit' => 'nullable|numeric|min:0',
            'current_balance' => 'nullable|numeric',
            'is_active' => 'nullable|boolean',
            'patient_name' => 'nullable|string|max:255',
            'patient_number' => 'nullable|string|max:50',
            'patient_type' => 'nullable|in:Inpatient,Outpatient',
        ]);

        // Avoid runtime errors if DB migrations haven't been applied yet
        $columns = Schema::getColumnListing('customers');
        $validated = array_intersect_key($validated, array_flip($columns));

        $customer->update($validated);

        if ($request->expectsJson()) {
            return response()->json($customer);
        }

        return redirect()->route('customers.index')->with('success', 'Customer updated successfully');
    }

    /**
     * Remove the specified customer.
     */
    public function destroy(Customer $customer)
    {
        $customer->delete();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Customer deleted successfully']);
        }

        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully');
    }
}
