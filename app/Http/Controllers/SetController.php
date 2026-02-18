<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Inventory;
use App\Models\SurgicalSet;
use App\Models\SetInstrument;
use App\Models\Asset;
use App\Models\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SetController extends Controller
{
    /**
     * Display a listing of sets (Surgical Sets).
     */
    public function index()
    {
        $sets = SurgicalSet::with(['location', 'instruments', 'asset'])
            ->get()
            ->map(function ($set) {
                $set->total_instruments = $set->instruments->count();
                $set->missing_instruments = $set->instruments->where('condition', 'missing')->count();
                
                // Consumables in the "Mobile Store" (Location)
                $set->consumable_count = $set->location ? $set->location->batches()->sum('quantity') : 0;
                
                return $set;
            });

        return view('sets.index', compact('sets'));
    }

    /**
     * Show the form for creating a new set.
     */
    public function create()
    {
        $products = Inventory::orderBy('product_name')->get();
        return view('sets.create', compact('products'));
    }

    /**
     * Store a newly created set.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'asset_name' => 'required|string|max:255',
            'purchase_price' => 'required|numeric|min:0',
            'purchase_date' => 'required|date',
            
            // Instruments (Reusable)
            'instruments' => 'nullable|array',
            'instruments.*.name' => 'required|string',
            'instruments.*.quantity' => 'required|integer|min:1',
            'instruments.*.inventory_id' => 'nullable|exists:inventory_master,id',
            'instruments.*.serial_number' => 'nullable|string',

            // Consumables (Contents)
            'contents' => 'nullable|array',
            'contents.*.inventory_id' => 'required|exists:inventory_master,id',
            'contents.*.quantity' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            // 1. Create Asset (Financial Entity)
            // Note: Asset model has guarded = [], so all fields are assignable.
            $asset = Asset::create([
                'name' => $validated['asset_name'],
                'category' => 'Surgical Set',
                'purchase_price' => $validated['purchase_price'],
                'purchase_date' => $validated['purchase_date'],
                'useful_life_years' => 5, // Default
                'location' => 'Main Store', // Initial location
                'status' => 'active', 
            ]);

            // 2. Create Location (Mobile Store)
            $location = Location::create([
                'name' => $validated['name'] . ' (Store)',
                'type' => 'set',
                'asset_id' => $asset->id,
                'is_active' => true,
            ]);

            // 3. Create SurgicalSet (Operational Entity)
            $set = SurgicalSet::create([
                'name' => $validated['name'],
                'asset_id' => $asset->id,
                'location_id' => $location->id,
                'status' => 'available',
                'sterilization_status' => 'non_sterile',
                'responsible_staff_id' => Auth::id(),
            ]);

            // 4. Create Set Instruments (Fixed Contents)
            if (!empty($validated['instruments'])) {
                foreach ($validated['instruments'] as $inst) {
                    SetInstrument::create([
                        'surgical_set_id' => $set->id,
                        'name' => $inst['name'],
                        'inventory_id' => $inst['inventory_id'] ?? null,
                        'serial_number' => $inst['serial_number'] ?? null,
                        'quantity' => $inst['quantity'],
                        'condition' => 'good',
                    ]);
                }
            }

            // 5. Create Consumables Template (Par Levels)
            if (!empty($request->contents)) {
                foreach ($request->contents as $item) {
                    \App\Models\SetContent::create([
                        'location_id' => $location->id,
                        'inventory_id' => $item['inventory_id'],
                        'standard_quantity' => $item['quantity'],
                    ]);
                }
            }

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Surgical Set created successfully.',
                    'redirect' => route('sets.index')
                ]);
            }

            return redirect()->route('sets.index')->with('success', 'Surgical Set created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create set: ' . $e->getMessage()
                ], 500);
            }
            return back()->with('error', 'Failed: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified set (Dashboard).
     */
    public function show($id)
    {
        $set = SurgicalSet::with(['asset', 'location', 'instruments', 'movements.caseReservation'])->findOrFail($id);
        
        // 1. Instruments Status
        $instruments = $set->instruments;

        // 2. Consumables (Live Inventory in the Set Location)
        $consumables = collect();
        if ($set->location) {
            $consumables = Batch::where('location_id', $set->location->id)
                ->where('quantity', '>', 0)
                ->with('inventory')
                ->get();
        }

        // 3. Set Contents (Template) - Fix for undefined variable in view
        $contents = collect();
        if ($set->location) {
             $contents = $set->location->setContents()->with('inventory')->get();
        }

        return view('sets.show', compact('set', 'instruments', 'consumables', 'contents'));
    }

    /**
     * Remove the specified surgical set from storage.
     */
    public function destroy(SurgicalSet $set)
    {
        DB::beginTransaction();
        try {
            // 1. Delete Related Instruments
            $set->instruments()->delete();

            // 2. Find and Handle Location & Contents
            $location = $set->location;
            if ($location) {
                // Delete set contents (template)
                $location->setContents()->delete();
                
                // Note: We leave Batches (Stock) alone or delete them?
                // If we delete location, batches constraint might fail or cascade.
                // Assuming batches cascade or we should delete them.
                // For safety, we'll let DB cascade handle it if set up, or just delete location.
                $location->delete();
            }

            // 3. Delete Asset
            if ($set->asset) {
                $set->asset->delete();
            }

            // 4. Delete the Set itself
            $set->delete();

            DB::commit();
            return redirect()->route('sets.index')->with('success', 'Surgical Set deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete set: ' . $e->getMessage());
        }
    }

    /**
     * Update the status of the specified set manually.
     */
    public function updateStatus(Request $request, SurgicalSet $set)
    {
        $validated = $request->validate([
            'status' => 'required|string',
            'sterilization_status' => 'nullable|string|in:sterile,non_sterile,expired',
        ]);

        // Basic validation of status against model constants would be ideal, 
        // but for flexibility we allow strings, or strict check:
        // $allowed = [SurgicalSet::STATUS_AVAILABLE, SurgicalSet::STATUS_DIRTY, ...];

        $set->status = $validated['status'];
        
        if ($request->has('sterilization_status')) {
            $set->sterilization_status = $validated['sterilization_status'];
        }

        // Auto-logic for convenience
        if ($set->status === SurgicalSet::STATUS_DIRTY) {
            $set->sterilization_status = 'non_sterile';
        }

        $set->save();

        return back()->with('success', "Set updated: {$set->status}");
    }
}
