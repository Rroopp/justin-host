<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * Categories management page (Blade).
     */
    public function index()
    {
        return view('inventory.categories');
    }

    /**
     * List categories + subcategories (JSON).
     */
    public function list()
    {
        $categories = Category::with(['subcategories', 'attributes.options'])
            ->orderBy('name')
            ->get();

        return response()->json($categories);
    }

    /**
     * Store a new category.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string',
        ]);

        $category = Category::create($validated);

        return response()->json($category, 201);
    }

    /**
     * Update a category.
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($category->id)],
            'description' => 'nullable|string',
        ]);

        $oldName = $category->name;
        $category->update($validated);

        // Keep Inventory rows consistent since inventory_master stores category as a string.
        if ($oldName !== $category->name) {
            Inventory::where('category', $oldName)->update(['category' => $category->name]);
        }

        return response()->json($category);
    }

    /**
     * Delete a category (blocked if referenced by inventory).
     */
    public function destroy(Category $category)
    {
        $inUse = Inventory::where('category', $category->name)->exists();
        if ($inUse) {
            return response()->json([
                'message' => 'Cannot delete category because it is used by inventory items.',
            ], 422);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted successfully']);
    }

    /**
     * Get subcategories for a specific category name
     */
    public function getSubcategories($categoryName)
    {
        $category = Category::where('name', $categoryName)->first();
        
        if (!$category) {
            return response()->json([]);
        }
        
        $subcategories = $category->subcategories()->orderBy('name')->get(['id', 'name']);
        return response()->json($subcategories);
    }
}


