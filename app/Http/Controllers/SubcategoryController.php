<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubcategoryController extends Controller
{
    /**
     * Store a new subcategory.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string',
        ]);

        // Enforce uniqueness within category
        $exists = Subcategory::where('category_id', $validated['category_id'])
            ->where('name', $validated['name'])
            ->exists();
        if ($exists) {
            return response()->json(['message' => 'Subcategory already exists for this category.'], 422);
        }

        $subcategory = Subcategory::create($validated);

        return response()->json($subcategory, 201);
    }

    /**
     * Update a subcategory.
     */
    public function update(Request $request, Subcategory $subcategory)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'description' => 'nullable|string',
        ]);

        // Check uniqueness within category (ignore current)
        $duplicate = Subcategory::where('category_id', $validated['category_id'])
            ->where('name', $validated['name'])
            ->where('id', '!=', $subcategory->id)
            ->exists();
        if ($duplicate) {
            return response()->json(['message' => 'Subcategory already exists for this category.'], 422);
        }

        $oldName = $subcategory->name;
        $oldCategory = Category::find($subcategory->category_id);

        $subcategory->update($validated);

        $newCategory = Category::find($subcategory->category_id);
        $oldCategoryName = $oldCategory?->name;
        $newCategoryName = $newCategory?->name;

        // Keep Inventory rows consistent since inventory_master stores category/subcategory as strings.
        if ($oldCategoryName && $newCategoryName) {
            Inventory::where('category', $oldCategoryName)
                ->where('subcategory', $oldName)
                ->update([
                    'category' => $newCategoryName,
                    'subcategory' => $subcategory->name,
                ]);
        }

        return response()->json($subcategory);
    }

    /**
     * Delete a subcategory (blocked if referenced by inventory).
     */
    public function destroy(Subcategory $subcategory)
    {
        $category = Category::find($subcategory->category_id);
        $categoryName = $category?->name;

        if ($categoryName) {
            $inUse = Inventory::where('category', $categoryName)
                ->where('subcategory', $subcategory->name)
                ->exists();
            if ($inUse) {
                return response()->json([
                    'message' => 'Cannot delete subcategory because it is used by inventory items.',
                ], 422);
            }
        }

        $subcategory->delete();

        return response()->json(['message' => 'Subcategory deleted successfully']);
    }
}


