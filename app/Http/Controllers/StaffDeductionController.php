<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\StaffRecurringDeduction;
use App\Models\PayrollDeductionType;
use Illuminate\Http\Request;

class StaffDeductionController extends Controller
{
    /**
     * Store a new recurring deduction
     */
    public function store(Request $request, Staff $staff)
    {
        if ($request->deduction_type_id === 'new') {
             $request->validate([
                'new_deduction_name' => 'required|string|max:255|unique:payroll_deduction_types,name',
                'new_liability_account_id' => 'required|exists:chart_of_accounts,id',
            ]);

            // Create new type
            $type = PayrollDeductionType::create([
                'name' => $request->new_deduction_name,
                'code' => strtoupper(\Illuminate\Support\Str::slug($request->new_deduction_name, '_')),
                'is_statutory' => false,
                'liability_account_id' => $request->new_liability_account_id,
                'is_active' => true,
            ]);
            
            $deductionTypeId = $type->id;
        } else {
            $request->validate([
                'deduction_type_id' => 'required|exists:payroll_deduction_types,id',
            ]);
            $deductionTypeId = $request->deduction_type_id;
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'balance' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $validated['deduction_type_id'] = $deductionTypeId;

        $staff->recurringDeductions()->create($validated);

        return redirect()->back()->with('success', 'Recurring deduction added successfully');
    }

    /**
     * Update an existing deduction (e.g. stop it)
     */
    public function update(Request $request, Staff $staff, StaffRecurringDeduction $deduction)
    {
        // ... (Optional, for now assume delete/create pattern or simple toggle)
    }

    /**
     * Delete/Stop a deduction
     */
    public function destroy(Staff $staff, StaffRecurringDeduction $deduction)
    {
        $deduction->delete();
        return redirect()->back()->with('success', 'Deduction removed successfully');
    }
}
