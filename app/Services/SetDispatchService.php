<?php

namespace App\Services;

use App\Models\SurgicalSet;
use App\Models\CaseReservation;
use App\Models\SetMovement;
use Illuminate\Support\Facades\DB;
use Exception;

class SetDispatchService
{
    /**
     * Dispatch a surgical set for a case reservation
     * 
     * @param SurgicalSet $set
     * @param CaseReservation $case
     * @return bool
     * @throws Exception
     */
    public function dispatch(SurgicalSet $set, CaseReservation $case): bool
    {
        // Validation
        if (!$set->isAvailable()) {
            throw new Exception("Set '{$set->name}' is not available for dispatch. Current status: {$set->status}");
        }

        // if (!$set->isSterile()) {
        //     throw new Exception("Set '{$set->name}' is not sterile. Current sterilization status: {$set->sterilization_status}");
        // }

        // Check if set is complete (all instruments present)
        $missingInstruments = $set->instruments()->missing()->count();
        if ($missingInstruments > 0) {
            throw new Exception("Set '{$set->name}' has {$missingInstruments} missing instrument(s). Cannot dispatch incomplete set.");
        }

        DB::beginTransaction();
        try {
            // Update set status
            $set->update([
                'status' => SurgicalSet::STATUS_DISPATCHED,
                'sterilization_status' => 'sterile', // Assume sterile upon dispatch verification
            ]);

            // Link set to case reservation
            $set->caseReservations()->attach($case->id, [
                'status' => 'dispatched',
            ]);

            // Log movement
            SetMovement::create([
                'surgical_set_id' => $set->id,
                'from_status' => SurgicalSet::STATUS_AVAILABLE,
                'to_status' => SurgicalSet::STATUS_DISPATCHED,
                'case_reservation_id' => $case->id,
                'moved_by' => auth()->id(),
                'notes' => "Dispatched for case: {$case->case_number}",
            ]);

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Mark set as in use (surgery started)
     */
    public function markInUse(SurgicalSet $set, CaseReservation $case): bool
    {
        if ($set->status !== SurgicalSet::STATUS_DISPATCHED) {
            throw new Exception("Set must be dispatched before marking as in use.");
        }

        $set->update(['status' => SurgicalSet::STATUS_IN_SURGERY]);

        SetMovement::create([
            'surgical_set_id' => $set->id,
            'from_status' => SurgicalSet::STATUS_DISPATCHED,
            'to_status' => SurgicalSet::STATUS_IN_SURGERY,
            'case_reservation_id' => $case->id,
            'moved_by' => auth()->id(),
            'notes' => "Surgery started",
        ]);

        return true;
    }
}
