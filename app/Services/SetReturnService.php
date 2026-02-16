<?php

namespace App\Services;

use App\Models\SurgicalSet;
use App\Models\CaseReservation;
use App\Models\SetMovement;
use App\Models\SetInstrument;
use Illuminate\Support\Facades\DB;
use Exception;

class SetReturnService
{
    /**
     * Return a surgical set after surgery
     * 
     * @param SurgicalSet $set
     * @param array $reconciliationData
     * @return bool
     * @throws Exception
     */
    public function return(SurgicalSet $set, array $reconciliationData = []): bool
    {
        if (!in_array($set->status, [SurgicalSet::STATUS_DISPATCHED, SurgicalSet::STATUS_IN_SURGERY])) {
            throw new Exception("Set '{$set->name}' is not currently dispatched. Current status: {$set->status}");
        }

        DB::beginTransaction();
        try {
            // Process instrument reconciliation
            if (isset($reconciliationData['instruments'])) {
                $this->reconcileInstruments($set, $reconciliationData['instruments']);
            }

            // Update set status
            $newStatus = SurgicalSet::STATUS_DIRTY;
            $set->update([
                'status' => $newStatus,
                'sterilization_status' => 'non_sterile',
            ]);

            // Log movement
            SetMovement::create([
                'surgical_set_id' => $set->id,
                'from_status' => $set->status,
                'to_status' => $newStatus,
                'moved_by' => auth()->id(),
                'notes' => $reconciliationData['notes'] ?? 'Set returned from surgery',
            ]);

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reconcile instruments after return
     */
    protected function reconcileInstruments(SurgicalSet $set, array $instrumentsData): void
    {
        foreach ($instrumentsData as $instrumentId => $data) {
            $instrument = SetInstrument::find($instrumentId);
            
            if (!$instrument || $instrument->surgical_set_id !== $set->id) {
                continue;
            }

            // Update instrument status based on reconciliation
            $updates = [];
            
            if (isset($data['status'])) {
                $updates['status'] = $data['status']; // good, damaged, missing, maintenance
            }

            if (isset($data['condition'])) {
                $updates['condition'] = $data['condition'];
            }

            if (isset($data['notes'])) {
                $updates['notes'] = $data['notes'];
            }

            if (!empty($updates)) {
                $instrument->update($updates);
            }
        }
    }

    /**
     * Mark set as ready for sterilization
     */
    public function sendToSterilization(SurgicalSet $set): bool
    {
        if ($set->status !== SurgicalSet::STATUS_DIRTY) {
            throw new Exception("Only dirty sets can be sent to sterilization.");
        }

        $set->update([
            'status' => SurgicalSet::STATUS_STERILIZING,
        ]);

        SetMovement::create([
            'surgical_set_id' => $set->id,
            'from_status' => SurgicalSet::STATUS_DIRTY,
            'to_status' => SurgicalSet::STATUS_STERILIZING,
            'moved_by' => auth()->id(),
            'notes' => 'Sent to CSSD for sterilization',
        ]);

        return true;
    }

    /**
     * Mark set as sterile and available
     */
    public function markSterileAndAvailable(SurgicalSet $set): bool
    {
        $set->update([
            'status' => SurgicalSet::STATUS_AVAILABLE,
            'sterilization_status' => 'sterile',
        ]);

        SetMovement::create([
            'surgical_set_id' => $set->id,
            'from_status' => $set->status,
            'to_status' => SurgicalSet::STATUS_AVAILABLE,
            'moved_by' => auth()->id(),
            'notes' => 'Sterilization complete, set available',
        ]);

        return true;
    }
}
