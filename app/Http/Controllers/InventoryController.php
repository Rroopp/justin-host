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
use Illuminate\Support\Facades\Schema;
use App\Services\AccountingService;
use App\Services\InventoryService;

class InventoryController extends Controller
{
    use \App\Traits\CsvExportable;

    /**
     * Display a listing of the inventory.
     */
    public function index(Request $request)
    {
        $query = Inventory::query();

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

        $inventory = Inventory::create($validated);

        // Sync to Assets (creates if rentable, deletes if not)
        $this->syncToAsset($inventory);

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

        $inventory->update($validated);

        $inventory->refresh(); // Ensure we have latest casted values (e.g. is_rentable boolean)

        // Sync to Assets (creates if rentable, deletes if not)
        $this->syncToAsset($inventory);

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
    public function restock(Request $request, Inventory $inventory)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // 1. & 2. Receive Stock (Updates Qty + WAC + Audit Trail)
            $inventoryService = new InventoryService();
            // Assuming manual restock implies current buying price unless specified (future improvement: add cost input)
            $unitCost = $inventory->price; 
            
            $adjustment = $inventoryService->receiveStock(
                $inventory,
                $validated['quantity'],
                $unitCost,
                'Restock',
                $validated['notes'] ?? 'Manual Restock',
                $request->user()
            );

            // 3. Trigger Accounting (Dr Inventory, Cr COGS/Equity/AP)
            // Note: If we paid cash, this should be Cr Cash. If it's just "found" stock, Cr Equity/Income.
            // For now, keeping existing logic but passing the adjustment.
            $accounting = new AccountingService();
            $accounting->recordInventoryAdjustment($adjustment, $request->user());

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Stock updated and accounting entry created successfully',
                    'new_stock' => $inventory->fresh()->quantity_in_stock,
                ]);
            }

            return redirect()->back()->with('success', 'Stock updated successfully (Journal Entry Created)');

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
