<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    use \App\Traits\CsvExportable;

    /**
     * List all assets
     */
    public function index(Request $request)
    {
        $query = Asset::query();

        // Export functionality
        if ($request->has('export')) {
            $assets = $query->orderBy('purchase_date', 'desc')->get();
            $data = $assets->map(function($asset) {
                return [
                    'Name' => $asset->name,
                    'Category' => $asset->category,
                    'Purchase Price' => $asset->purchase_price,
                    'Purchase Date' => $asset->purchase_date->format('Y-m-d'),
                    'Useful Life (Years)' => $asset->useful_life_years,
                    'Salvage Value' => $asset->salvage_value ?? 0,
                    'Annual Depreciation' => $asset->calculateDepreciation(),
                    'Serial Number' => $asset->serial_number ?? '-',
                    'Location' => $asset->location ?? '-',
                ];
            });
            
            return $this->streamCsv('assets_report.csv', ['Name', 'Category', 'Purchase Price', 'Purchase Date', 'Useful Life (Years)', 'Salvage Value', 'Annual Depreciation', 'Serial Number', 'Location'], $data, 'Assets Report');
        }

        $assets = $query->orderBy('purchase_date', 'desc')->get();
        return view('assets.index', compact('assets'));
    }

    /**
     * Store a new asset
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'purchase_price' => 'required|numeric|min:0',
            'purchase_date' => 'required|date',
            'useful_life_years' => 'required|numeric|min:1',
            'salvage_value' => 'nullable|numeric|min:0',
            'serial_number' => 'nullable|string',
            'location' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        Asset::create($validated);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Asset created successfully');
    }

    /**
     * Show the form for editing (for AJAX)
     */
    public function edit(Asset $asset)
    {
        return response()->json($asset);
    }

    /**
     * Update an asset
     */
    public function update(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'purchase_price' => 'required|numeric|min:0',
            'purchase_date' => 'required|date',
            'useful_life_years' => 'required|numeric|min:1',
            'salvage_value' => 'nullable|numeric|min:0',
            'serial_number' => 'nullable|string',
            'location' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $asset->update($validated);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Asset updated successfully');
    }

    /**
     * Delete an asset
     */
    public function destroy(Asset $asset)
    {
        $asset->delete();
        
        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }
        
        return redirect()->back()->with('success', 'Asset deleted successfully');
    }
}
