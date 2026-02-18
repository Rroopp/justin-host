<?php

namespace App\Http\Controllers;

use App\Models\SurgeryUsage;
use App\Models\SurgeryUsageItem;
use App\Models\Location;
use App\Models\Inventory;
use App\Models\Batch;
use App\Services\SurgeryAccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SurgeryUsageController extends Controller
{
    /**
     * Display a listing of usages.
     */
    public function index()
    {
        $usages = SurgeryUsage::with(['user', 'setLocation', 'items'])->latest()->paginate(20);
        return view('surgery_usage.index', compact('usages'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $sets = Location::where('type', 'set')->get();
        // We might need adjacent data (products/batches) but let's load via API/Alpine
        return view('surgery_usage.create', compact('sets'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'surgery_date' => 'required|date',
            'patient_name' => 'nullable|string',
            'patient_number' => 'nullable|string',
            'surgeon_name' => 'nullable|string',
            'facility_name' => 'nullable|string',
            'set_location_id' => 'nullable|exists:locations,id',
            'items' => 'required|array',
            'items.*.inventory_id' => 'required|exists:inventory_master,id',
            'items.*.batch_id' => 'nullable|exists:batches,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.from_set' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            $usage = SurgeryUsage::create([
                'surgery_date' => $validated['surgery_date'],
                'patient_name' => $validated['patient_name'],
                'patient_number' => $validated['patient_number'],
                'surgeon_name' => $validated['surgeon_name'],
                'facility_name' => $validated['facility_name'],
                'set_location_id' => $validated['set_location_id'],
                'user_id' => Auth::id(),
            ]);

            foreach ($validated['items'] as $item) {
                // Determine source location
                // If from_set is true, we deduct from set_location_id
                // If false, we deduct from 'Main Store' or user context?
                // Let's assume from_set means from the selected set_location_id.
                // If set_location_id is null, from_set must be false.
                
                $locationId = null;
                if (!empty($item['from_set']) && $item['from_set']) {
                    $locationId = $validated['set_location_id'];
                } else {
                    // Default store?
                     $defaultStore = Location::where('type', 'store')->first();
                     $locationId = $defaultStore ? $defaultStore->id : null;
                }

                // Deduct Stock
                if ($item['batch_id']) {
                    $batch = Batch::find($item['batch_id']);
                    // Verify location? 
                    // $batch->location_id == $locationId check?
                    
                    if ($batch->quantity >= $item['quantity']) {
                        $batch->decrement('quantity', $item['quantity']);
                    } else {
                        throw new \Exception("Insufficient stock in batch {$batch->batch_number}");
                    }
                } else {
                    // FIFO logic not implemented here yet, require batch for medical safety
                     throw new \Exception("Batch ID required for surgery usage.");
                }

                SurgeryUsageItem::create([
                    'surgery_usage_id' => $usage->id,
                    'inventory_id' => $item['inventory_id'],
                    'batch_id' => $item['batch_id'],
                    'quantity' => $item['quantity'],
                    'from_set' => $item['from_set'] ?? false,
                ]);
            }

            // Post COGS to accounting (feature-flagged, safe to call always)
            try {
                $accountingService = new SurgeryAccountingService();
                $journalEntry = $accountingService->recordSurgeryCogs($usage, null, Auth::user());
                
                if ($journalEntry) {
                    Log::info('SurgeryUsageController: COGS posted', [
                        'surgery_usage_id' => $usage->id,
                        'journal_entry_id' => $journalEntry->id
                    ]);
                }
            } catch (\Exception $e) {
                // Log but don't fail the request - accounting can be reconciled later
                Log::error('SurgeryUsageController: Failed to post COGS', [
                    'surgery_usage_id' => $usage->id,
                    'error' => $e->getMessage()
                ]);
            }

            DB::commit();
            return redirect()->route('surgery-usage.index')->with('success', 'Surgery usage recorded successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error recording usage: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $usage = SurgeryUsage::with(['items.inventory', 'items.batch', 'setLocation', 'user'])->findOrFail($id);
        return view('surgery_usage.show', compact('usage'));
    }
}
