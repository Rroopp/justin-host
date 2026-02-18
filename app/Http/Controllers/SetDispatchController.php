<?php

namespace App\Http\Controllers;

use App\Models\CaseReservation;
use App\Models\SurgicalSet;
use App\Services\SetDispatchService;
use App\Services\SetReturnService;
use Illuminate\Http\Request;
use Exception;

class SetDispatchController extends Controller
{
    protected $dispatchService;
    protected $returnService;

    public function __construct(SetDispatchService $dispatchService, SetReturnService $returnService)
    {
        $this->dispatchService = $dispatchService;
        $this->returnService = $returnService;
    }

    /**
     * Show dispatch dashboard
     */
    public function index()
    {
        $upcomingCases = CaseReservation::with(['surgicalSets', 'customer'])
            ->upcoming()
            ->orderBy('surgery_date')
            ->get();

        $availableSets = SurgicalSet::with(['location', 'instruments'])
            ->where('status', SurgicalSet::STATUS_AVAILABLE)
            ->whereNotNull('location_id') // Exclude orphaned sets
            ->get();

        return view('sets.dispatch.index', compact('upcomingCases', 'availableSets'));
    }

    /**
     * Show the Dispatch form (Select Set & Verify).
     */
    public function create($reservation_id)
    {
        $reservation = CaseReservation::with('surgicalSets')->findOrFail($reservation_id);
        
        // Get IDs of sets already attached to this reservation
        $attachedSetIds = $reservation->surgicalSets->pluck('id')->toArray();

        // Available Sets (excluding ones already attached to this case)
        $availableSets = SurgicalSet::with(['instruments'])
            ->where('status', SurgicalSet::STATUS_AVAILABLE)
            ->whereNotNull('location_id') // Exclude orphaned sets
            ->whereNotIn('id', $attachedSetIds)
            ->orderBy('name')
            ->get();

        return view('sets.dispatch', compact('reservation', 'availableSets'));
    }

    /**
     * Process Dispatch (Set Status -> Dispatched).
     */
    public function store(Request $request, $reservation_id)
    {
        $request->validate([
            'surgical_set_id' => 'required|exists:surgical_sets,id',
        ]);

        try {
            $reservation = CaseReservation::findOrFail($reservation_id);
            $set = SurgicalSet::findOrFail($request->surgical_set_id);

            $this->dispatchService->dispatch($set, $reservation);

            return redirect()->route('reservations.show', $reservation->id)
                ->with('success', "Surgical Set '{$set->name}' dispatched successfully.");
        } catch (Exception $e) {
            return back()
                ->with('error', 'Dispatch failed: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Mark set as in use (surgery started)
     */
    public function markInUse(Request $request, $reservation_id)
    {
        $request->validate([
            'surgical_set_id' => 'required|exists:surgical_sets,id',
        ]);

        try {
            $reservation = CaseReservation::findOrFail($reservation_id);
            $set = SurgicalSet::findOrFail($request->surgical_set_id);

            $this->dispatchService->markInUse($set, $reservation);

            return back()->with('success', "Set '{$set->name}' marked as in use");
        } catch (Exception $e) {
            return back()->with('error', 'Failed to mark in use: ' . $e->getMessage());
        }
    }

    /**
     * Show Reconcile Form (Return Set).
     */
    public function reconcile($reservation_id)
    {
        $reservation = CaseReservation::with(['surgicalSets' => function($q) {
            $q->wherePivot('status', 'dispatched');
        }])->findOrFail($reservation_id);

        // Constraint: Cannot return before surgery time
        if ($reservation->surgery_date && $reservation->surgery_date->isFuture()) {
             return redirect()->route('reservations.show', $reservation->id)
                ->with('error', 'Cannot return set before the scheduled surgery time (' . $reservation->surgery_date->format('M d H:i') . ').');
        }

        $set = $reservation->surgicalSets->first();

        if (!$set) {
            return redirect()->route('reservations.show', $reservation->id)
                ->with('error', 'No dispatched set found for this reservation.');
        }
        
        // Load instruments for checklist
        $set->load('instruments');

        return view('sets.reconcile', compact('reservation', 'set'));
    }

    /**
     * Process Return (Set Status -> Dirty).
     */
    public function storeReconciliation(Request $request, $reservation_id)
    {
        $request->validate([
            'surgical_set_id' => 'required|exists:surgical_sets,id',
            'instruments' => 'nullable|array',
            'instruments.*.status' => 'nullable|in:good,damaged,missing,maintenance',
            'notes' => 'nullable|string',
        ]);

        try {
            $set = SurgicalSet::findOrFail($request->surgical_set_id);
            
            // Prepare reconciliation data
            $reconciliationData = [
                'instruments' => $request->instruments ?? [],
                'notes' => $request->notes,
            ];

            $this->returnService->return($set, $reconciliationData);

            return redirect()->route('reservations.show', $reservation_id)
                ->with('success', 'Surgical Set returned and reconciled.');
        } catch (Exception $e) {
            return back()
                ->with('error', 'Return failed: ' . $e->getMessage())
                ->withInput();
        }
    }
}
