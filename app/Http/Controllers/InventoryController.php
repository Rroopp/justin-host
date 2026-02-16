<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\InventoryAdjustment;
use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\AccountingService;
use App\Services\InventoryService;
use App\Services\BatchService;

class InventoryController extends Controller
{
    use \App\Traits\CsvExportable;

    protected $auditService;

    public function __construct(\App\Services\AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Display a listing of the inventory.
     */
    public function index(Request $request)
    {
        $query = Inventory::with('batches');

        // Search filter
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('manufacturer', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Subcategory filter
        if ($request->has('subcategory')) {
            $query->where('subcategory', $request->subcategory);
        }

        // Stock level filter
        if ($request->has('stock_level')) {
            if ($request->stock_level === 'low') {
                $query->whereColumn('quantity_in_stock', '<=', 'min_stock_level');
            } else {
                $query->where('quantity_in_stock', '<=', 0);
            }
        }

        // Expiring Soon filter
        if ($request->has('expiring_soon') && $request->expiring_soon == 'true') {
            $query->whereDate('expiry_date', '>', now())
                  ->whereDate('expiry_date', '<=', now()->addDays(90));
        }

        // EXCLUDE SERVICE ITEMS from main inventory view
        // Services like "Surgical Set Rental Fee" are not physical inventory
        $query->where('category', '!=', 'Services');

        if ($request->has('export')) {
            $items = $query->orderBy('product_name')->get();
            $data = $items->map(function($item) {
                return [
                    'Code' => $item->code,
                    'Name' => $item->product_name,
                    'Category' => $item->category,
                    'Stock' => $item->quantity_in_stock,
                    'Price' => $item->selling_price,
                    'Manuf' => $item->manufacturer,
                    'Rentable' => $item->is_rentable ? 'Yes' : 'No',
                ];
            });
            if ($request->export === 'pdf') {
                $items = $query->orderBy('product_name')->get();
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('inventory.pdf', compact('items'));
                return $pdf->download('inventory_report.pdf');
            }

             return $this->streamCsv('inventory_list.csv', ['Code', 'Name', 'Category', 'Stock', 'Price', 'Manufacturer', 'Rentable'], $data, 'Inventory Report');
        }

        $inventory = $query->orderBy('product_name')->paginate(50);

        if ($request->expectsJson()) {
            return response()->json($inventory);
        }

        $categories = Category::with(['subcategories', 'attributes.options'])->get();
        $subcategories = Subcategory::all();

        return view('inventory.index', compact('inventory', 'categories', 'subcategories'));
    }

    /**
     * Store a newly created inventory item.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_name' => 'required|string|max:255',
            'category' => 'nullable|string',
            'subcategory' => 'nullable|string',
            'code' => 'nullable|string|unique:inventory_master,code',
            'unit' => 'nullable|string|max:50',
            'quantity_in_stock' => 'required|integer|min:0',
            'min_stock_level' => 'nullable|integer|min:0',
            'max_stock' => 'nullable|integer|min:0',
            'reorder_threshold' => 'nullable|integer|min:0',
            'expiry_date' => 'nullable|date',
            'batch_number' => 'nullable|string|max:255',
            'is_rentable' => 'nullable|boolean',
            'country_of_manufacture' => 'nullable|string|max:255',
            'packaging_unit' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'manufacturer' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'type' => 'nullable|string|max:255',
            'size' => 'nullable|string|max:50',
            'size_unit' => 'nullable|string|max:20',
            'attributes' => 'nullable',
        ]);

        if (!empty($validated['category'])) {
            $this->validateDynamicAttributes($validated['category'], $request->input('attributes', []));
        }

        // Avoid runtime errors if DB migrations haven't been applied yet
        $columns = Schema::getColumnListing('inventory_master');
        $validated = array_intersect_key($validated, array_flip($columns));

        // Auto-generate product name from attributes
        $validated['product_name'] = $this->generateProductName(
            $validated['category'] ?? '',
            $validated['subcategory'] ?? '',
            $validated['type'] ?? '',
            $validated['size'] ?? '',
            $validated['size_unit'] ?? ''
        );

        // Calculate profit
        $validated['profit'] = $validated['selling_price'] - $validated['price'];

        // Ensure category and subcategory exist
        $this->ensureCategoryAndSubcategory($validated['category'] ?? null, $validated['subcategory'] ?? null);

        // Auto-generate code if not provided
        if (empty($validated['code'])) {
            $validated['code'] = $this->generateProductCode($validated['category'] ?? 'PROD');
        }

        // Assign tax rate based on is_vatable flag
        if (isset($validated['is_vatable']) && $validated['is_vatable']) {
            // Assign VAT 16%
            $vatRate = \App\Models\TaxRate::where('rate', 16.00)->where('is_active', true)->first();
            $validated['tax_rate_id'] = $vatRate ? $vatRate->id : null;
        } else {
            // Assign default 0% rate
            $defaultRate = \App\Models\TaxRate::where('is_default', true)->where('is_active', true)->first();
            $validated['tax_rate_id'] = $defaultRate ? $defaultRate->id : null;
        }

        $inventory = Inventory::create($validated);

        // Sync to Assets (creates if rentable, deletes if not)
        $this->syncToAsset($inventory);

        $this->auditService->log(
            $request->user(),
            'create',
            'inventory',
            $inventory->id,
            "Created product: {$inventory->product_name} ({$inventory->code})",
            Inventory::class,
            null,
            $inventory->toArray()
        );

        if ($request->expectsJson()) {
            return response()->json($inventory, 201);
        }

        return redirect()->route('inventory.index')->with('success', 'Product added successfully');
    }

    /**
     * Generate unique product code
     */
    private function generateProductCode($category)
    {
        // Create prefix from category (max 4 chars, uppercase, alphanumeric only)
        $prefix = strtoupper(preg_replace('/[^A-Z0-9]/', '', substr($category, 0, 4)));
        if (empty($prefix)) {
            $prefix = 'PROD';
        }

        // Find the highest existing number for this prefix
        $lastCode = Inventory::where('code', 'LIKE', $prefix . '-%')
            ->orderByRaw('CAST(SUBSTRING(code, ' . (strlen($prefix) + 2) . ') AS UNSIGNED) DESC')
            ->value('code');

        if ($lastCode) {
            // Extract number and increment
            $lastNumber = (int) substr($lastCode, strlen($prefix) + 1);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Ensure category and subcategory exist in their respective tables
     */
    private function ensureCategoryAndSubcategory($categoryName, $subcategoryName)
    {
        if (empty($categoryName)) {
            return;
        }

        // 1. Ensure Category
        $category = Category::firstOrCreate(
            ['name' => $categoryName],
            ['description' => 'Auto-created']
        );

        // 2. Ensure Subcategory (if provided)
        if (!empty($subcategoryName)) {
            Subcategory::firstOrCreate(
                [
                    'name' => $subcategoryName,
                    'category_id' => $category->id
                ],
                ['description' => 'Auto-created']
            );
        }
    }

    /**
     * Generate product name from attributes
     */
    private function generateProductName($category, $subcategory, $type, $size, $sizeUnit = '')
    {
        $parts = array_filter([$category, $subcategory, $type]);
        
        // Add size with unit if both exist
        if ($size && $sizeUnit) {
            $parts[] = $size . $sizeUnit;
        } elseif ($size) {
            $parts[] = $size;
        }
        
        return implode(' ', $parts) ?: 'Unnamed Product';
    }

    /**
     * Sync Inventory Item to Asset
     */
    private function syncToAsset(Inventory $inventory)
    {
        // If rentable, Create or Update Asset
        if ($inventory->is_rentable) {
            $assetData = [
                'name' => $inventory->product_name,
                'category' => $inventory->category,
                'purchase_price' => $inventory->price,
                'purchase_date' => $inventory->created_at ?? now(),
                'description' => $inventory->description ?? 'Auto-created from Inventory',
                'serial_number' => $inventory->code,
                'useful_life_years' => 5, // Default
                'depreciation_method' => 'straight_line', // Default
                'salvage_value' => 0, // Default
                'location' => 'Store', // Default
            ];

            Asset::updateOrCreate(
                ['serial_number' => $inventory->code], // Match by unique code
                $assetData
            );
        } else {
            // If not rentable, DELETE Asset if it exists (state sync)
            Asset::where('serial_number', $inventory->code)->delete();
        }
    }

    /**
     * Update the specified inventory item.
     */
    public function update(Request $request, Inventory $inventory)
    {
        $validated = $request->validate([
            'product_name' => 'required|string|max:255',
            'category' => 'nullable|string',
            'subcategory' => 'nullable|string',
            'code' => 'nullable|string|unique:inventory_master,code,' . $inventory->id,
            'unit' => 'nullable|string|max:50',
            'quantity_in_stock' => 'required|integer|min:0',
            'min_stock_level' => 'nullable|integer|min:0',
            'max_stock' => 'nullable|integer|min:0',
            'reorder_threshold' => 'nullable|integer|min:0',
            'expiry_date' => 'nullable|date',
            'batch_number' => 'nullable|string|max:255',
            'is_rentable' => 'nullable|boolean',
            'is_vatable' => 'nullable|boolean',
            'country_of_manufacture' => 'nullable|string|max:255',
            'packaging_unit' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'manufacturer' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'type' => 'nullable|string|max:255',
            'size' => 'nullable|string|max:50',
            'size_unit' => 'nullable|string|max:20',
            'attributes' => 'nullable',
        ]);

        if (!empty($validated['category'])) {
            $this->validateDynamicAttributes($validated['category'], $request->input('attributes', []));
        }

        // Avoid runtime errors if DB migrations haven't been applied yet
        $columns = Schema::getColumnListing('inventory_master');
        $validated = array_intersect_key($validated, array_flip($columns));

        // Auto-generate product name from attributes
        $validated['product_name'] = $this->generateProductName(
            $validated['category'] ?? '',
            $validated['subcategory'] ?? '',
            $validated['type'] ?? '',
            $validated['size'] ?? '',
            $validated['size_unit'] ?? ''
        );

        // Calculate profit
        $validated['profit'] = $validated['selling_price'] - $validated['price'];

        // Ensure category and subcategory exist
        $this->ensureCategoryAndSubcategory($validated['category'] ?? null, $validated['subcategory'] ?? null);

        // Assign tax rate based on is_vatable flag
        if (isset($validated['is_vatable']) && $validated['is_vatable']) {
            // Assign VAT 16%
            $vatRate = \App\Models\TaxRate::where('rate', 16.00)->where('is_active', true)->first();
            $validated['tax_rate_id'] = $vatRate ? $vatRate->id : null;
        } else {
            // Assign default 0% rate
            $defaultRate = \App\Models\TaxRate::where('is_default', true)->where('is_active', true)->first();
            $validated['tax_rate_id'] = $defaultRate ? $defaultRate->id : null;
        }

        $inventory->update($validated);

        $inventory->refresh(); // Ensure we have latest casted values (e.g. is_rentable boolean)

        // Sync to Assets (creates if rentable, deletes if not)
        $this->syncToAsset($inventory);

        $this->auditService->log(
            $request->user(),
            'update',
            'inventory',
            $inventory->id,
            "Updated product: {$inventory->product_name}",
            Inventory::class,
            null,
            $validated
        );

        if ($request->expectsJson()) {
            return response()->json($inventory);
        }

        return redirect()->route('inventory.index')->with('success', 'Product updated successfully');
    }

    /**
     * Remove the specified inventory item.
     */
    public function destroy(Inventory $inventory)
    {
        $inventory->delete();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Product deleted successfully']);
        }

        return redirect()->route('inventory.index')->with('success', 'Product deleted successfully');
    }

    /**
     * Restock inventory item
     */
    /**
     * Restock inventory item
     */
    /**
     * Restock inventory item
     */
    public function restock(Request $request, Inventory $inventory)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'unit_cost' => 'required|numeric|min:0',
            'supplier_id' => 'nullable|exists:vendors,id', // or just supplier name string? vendors table exists? Yes.
            'payment_method' => 'required|in:cash,credit',
            'payment_account_id' => 'nullable|exists:chart_of_accounts,id',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'batch_number' => 'nullable|string|max:255', // Medical: usually required
            'expiry_date' => 'nullable|date', // Medical: usually required
        ]);

        if ($validated['payment_method'] === 'cash' && empty($validated['payment_account_id'])) {
             // For Cash purchase, payment account is required
             return redirect()->back()->withErrors(['payment_account_id' => 'Payment Account is required for Cash purchase.']);
        }

        DB::beginTransaction();
        try {
            // 1. Create or Update Batch Record
            $batchNumber = $validated['batch_number'];
            if (empty($batchNumber)) {
                $batchService = app(BatchService::class);
                $batchNumber = $batchService->generateBatchNumber($inventory);
            }
            $expiryDate = $validated['expiry_date'] ?? null;

            // Check if batch exists for this product
            $batch = \App\Models\Batch::where('inventory_id', $inventory->id)
                ->where('batch_number', $batchNumber)
                ->first();

            if ($batch) {
                $batch->quantity += $validated['quantity'];
                // Update cost if needed? Or keep weighted average? For now, we update cost only on new batch
                $batch->save();
            } else {
                \App\Models\Batch::create([
                    'inventory_id' => $inventory->id,
                    'batch_number' => $batchNumber,
                    'expiry_date' => $expiryDate,
                    'quantity' => $validated['quantity'],
                    'cost_price' => $validated['unit_cost'],
                ]);
            }

            // 2. Receive Stock (Updates Qty + WAC + Audit Trail)
            $inventoryService = new InventoryService();
            $unitCost = $validated['unit_cost'];
            
            $adjustment = $inventoryService->receiveStock(
                $inventory,
                $validated['quantity'],
                $unitCost,
                'Purchase', // Reason
                ($validated['notes'] ?? '') . " (Ref: " . ($validated['reference'] ?? '-') . ") [Batch: $batchNumber]",
                $request->user()
            );

            // 3. Trigger Accounting
            // Use new recordStockPurchase
            $totalCost = $unitCost * $validated['quantity'];
            $accounting = new AccountingService();
            
            $paymentAccountId = ($validated['payment_method'] === 'cash') ? $validated['payment_account_id'] : null;
            $vendorId = $validated['supplier_id'] ?? null;

            $accounting->recordStockPurchase(
                $adjustment, 
                $totalCost, 
                $paymentAccountId, 
                $vendorId, 
                $request->user()
            );

            DB::commit();

            $this->auditService->log(
                $request->user(),
                'restock',
                'inventory',
                $inventory->id,
                "Restocked {$validated['quantity']} units of {$inventory->product_name} (Batch: $batchNumber)",
                Inventory::class,
                null,
                $validated
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Stock updated and accounting entry created successfully',
                    'new_stock' => $inventory->fresh()->quantity_in_stock,
                    'batch_number' => $batchNumber
                ]);
            }

            return redirect()->back()->with('success', 'Stock updated successfully (Journal Entry Created, Batch Created)');

        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->expectsJson()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Failed to restock: ' . $e->getMessage());
        }
    }

    /**
     * Get low stock alerts
     */
    public function lowStockAlerts(Request $request)
    {
        $threshold = $request->get('threshold');
        $alertsQuery = Inventory::query();

        if ($threshold !== null && $threshold !== '') {
            $alertsQuery->where('quantity_in_stock', '<=', (int) $threshold);
        } else {
            $alertsQuery->whereColumn('quantity_in_stock', '<=', 'min_stock_level');
        }

        $alerts = $alertsQuery->orderBy('quantity_in_stock')->get();

        if ($request->expectsJson()) {
            return response()->json($alerts);
        }

        return view('inventory.alerts', compact('alerts'));
    }

    /**
     * Get categories
     */
    public function categories()
    {
        $categories = Category::with(['subcategories', 'attributes.options'])->get();
        return response()->json($categories);
    }

    /**
     * Get unique types for autocomplete
     */
    public function getTypes(Request $request)
    {
        $types = Inventory::whereNotNull('type')
            ->where('type', '!=', '')
            ->distinct()
            ->orderBy('type')
            ->pluck('type');
        
        return response()->json($types);
    }

    /**
     * Get unique sizes for autocomplete
     */
    public function getSizes(Request $request)
    {
        $sizes = Inventory::whereNotNull('size')
            ->where('size', '!=', '')
            ->distinct()
            ->orderBy('size')
            ->pluck('size');
        
        return response()->json($sizes);
    }

    /**
     * Validate dynamic attributes based on category
     */
    private function validateDynamicAttributes($categoryName, $submittedAttributes)
    {
        $category = Category::where('name', $categoryName)->with('attributes')->first();
        if (!$category) return;

        // Ensure attributes is an array
        if (!is_array($submittedAttributes)) {
            $submittedAttributes = [];
        }

        foreach ($category->attributes as $attr) {
            if ($attr->is_required) {
                // check if key exists and is not empty
                if (!array_key_exists($attr->slug, $submittedAttributes) || 
                    $submittedAttributes[$attr->slug] === '' || 
                    $submittedAttributes[$attr->slug] === null) {
                    
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'attributes.' . $attr->slug => "The {$attr->name} field is required for {$categoryName}.",
                    ]);
                }
            }
        }
    }
}
