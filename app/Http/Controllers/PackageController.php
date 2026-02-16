<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\PackageItem;
use App\Models\Inventory;
use App\Models\Customer;
use App\Models\CustomerPackagePricing;
use App\Services\PackageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PackageController extends Controller
{
    protected $packageService;

    public function __construct(PackageService $packageService)
    {
        $this->packageService = $packageService;
    }

    /**
     * Display a listing of the packages.
     */
    public function index()
    {
        $packages = Package::withCount('items')->get();
        return view('packages.index', compact('packages'));
    }

    /**
     * Show the form for creating a new package.
     */
    public function create()
    {
        $inventory = Inventory::select('id', 'product_name', 'code', 'quantity_in_stock')->orderBy('product_name')->get();
        return view('packages.create', compact('inventory'));
    }

    /**
     * Store a newly created package in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:packages,code',
            'description' => 'nullable|string',
            'base_price' => 'required|numeric|min:0',
            'items' => 'array',
            'items.*.inventory_id' => 'required|exists:inventory_master,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        DB::beginTransaction();

        try {
            $package = Package::create([
                'name' => $validated['name'],
                'code' => $validated['code'] ?? strtoupper(substr($validated['name'], 0, 3)) . '-' . rand(1000, 9999),
                'description' => $validated['description'],
                'base_price' => $validated['base_price'],
            ]);

            if (isset($validated['items'])) {
                foreach ($validated['items'] as $item) {
                    PackageItem::create([
                        'package_id' => $package->id,
                        'inventory_id' => $item['inventory_id'],
                        'quantity' => $item['quantity'],
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('packages.index')->with('success', 'Package created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error creating package: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Show the form for editing the specified package.
     */
    public function edit(Package $package)
    {
        $package->load(['items.inventory', 'customerPricing']);
        $inventory = Inventory::select('id', 'product_name', 'code', 'quantity_in_stock')->orderBy('product_name')->get();
        $customers = Customer::select('id', 'name')->orderBy('name')->get();
        return view('packages.edit', compact('package', 'inventory', 'customers'));
    }

    /**
     * Update the specified package in storage.
     */
    public function update(Request $request, Package $package)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:packages,code,' . $package->id,
            'description' => 'nullable|string',
            'base_price' => 'required|numeric|min:0',
            'items' => 'array',
            'items.*.inventory_id' => 'required|exists:inventory_master,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'customer_prices' => 'array',
            'customer_prices.*.customer_id' => 'nullable|exists:customers,id',
            'customer_prices.*.price' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $package->update([
                'name' => $validated['name'],
                'code' => $validated['code'],
                'description' => $validated['description'],
                'base_price' => $validated['base_price'],
            ]);

            // Sync items (Delete all and recreate is simplest for MVP)
            $package->items()->delete();

            if (isset($validated['items'])) {
                foreach ($validated['items'] as $item) {
                    PackageItem::create([
                        'package_id' => $package->id,
                        'inventory_id' => $item['inventory_id'],
                        'quantity' => $item['quantity'],
                    ]);
                }
            }

            // Sync Customer Pricing
            // Simplest strategy: delete all for this package and recreate
            CustomerPackagePricing::where('package_id', $package->id)->delete();

            if (isset($validated['customer_prices'])) {
                foreach ($validated['customer_prices'] as $cp) {
                    if (!empty($cp['customer_id']) && isset($cp['price'])) {
                        CustomerPackagePricing::create([
                            'package_id' => $package->id,
                            'customer_id' => $cp['customer_id'],
                            'price' => $cp['price'],
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect()->route('packages.index')->with('success', 'Package updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error updating package: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified package from storage.
     */
    public function destroy(Package $package)
    {
        $package->delete();
        return redirect()->route('packages.index')->with('success', 'Package deleted successfully.');
    }

    /**
     * API: Get Package Details (Price + Items) for POS
     */
    public function getDetails(Request $request, Package $package)
    {
        $customerId = $request->query('customer_id');

        $price = $this->packageService->resolvePrice($package, $customerId);
        $items = $this->packageService->getTemplateItems($package);

        return response()->json([
            'id' => $package->id,
            'name' => $package->name,
            'price' => $price,
            'items' => $items,
        ]);
    }
}
