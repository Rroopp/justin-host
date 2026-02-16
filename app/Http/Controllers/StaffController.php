<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\StaffActivityLog;
use App\Models\PayrollDeductionType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    private function logActivity(Request $request, string $activityType, string $description, array $metadata = []): void
    {
        $actor = $request->user();
        if (!$actor) {
            return;
        }

        StaffActivityLog::create([
            'staff_id' => $actor->id,
            'username' => $actor->username,
            'activity_type' => $activityType,
            'description' => $description,
            'ip_address' => $request->ip(),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Display a listing of staff.
     */
    public function index(Request $request)
    {
        $query = Staff::query()
            ->where('is_deleted', false);

        // Role filter
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Status filter
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('full_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $staff = $query->orderBy('full_name')->paginate(50);

        if ($request->expectsJson()) {
            return response()->json($staff);
        }

        return view('staff.index', compact('staff'));
    }

    /**
     * Display the specified staff member.
     */
    public function show(Staff $staff)
    {
        $staff->load(['recurringDeductions.deductionType']);
        $deductionTypes = PayrollDeductionType::where('is_statutory', false)->get(); // Only show manual types
        $liabilityAccounts = \App\Models\ChartOfAccount::where('account_type', 'Liability')->orderBy('code')->get();
        
        return view('staff.show', compact('staff', 'deductionTypes', 'liabilityAccounts'));
    }

    /**
     * Store a newly created staff member.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:255|unique:staff,username',
            'password' => 'required|string|min:' . settings('password_min_length', 6),
            'full_name' => 'required|string|max:255',
            'role' => 'required|in:admin,staff,accountant',
            'status' => 'required|in:active,inactive,suspended',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'id_number' => 'nullable|string|max:50',
            'salary' => 'nullable|numeric|min:0',
            'designation' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:50',
        ]);

        // Ensure primary_role is in roles array - REMOVED logic
        // if (!in_array($validated['primary_role'], $validated['roles'])) {
        //     $validated['roles'][] = $validated['primary_role'];
        // }

        $validated['password_hash'] = Hash::make($validated['password']);
        unset($validated['password']);

        $staff = Staff::create($validated);

        $this->logActivity(
            $request,
            'staff_create',
            'Created staff account for ' . $staff->full_name . ' (' . $staff->username . ')',
            ['target_staff_id' => $staff->id, 'target_username' => $staff->username]
        );

        if ($request->expectsJson()) {
            return response()->json($staff, 201);
        }

        return redirect()->route('staff.index')->with('success', 'Staff member added successfully');
    }

    /**
     * Update the specified staff member.
     */
    public function update(Request $request, Staff $staff)
    {
        $before = $staff->toArray();

        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255', Rule::unique('staff')->ignore($staff->id)],
            'password' => 'nullable|string|min:' . settings('password_min_length', 6),
            'full_name' => 'required|string|max:255',
            'role' => 'required|in:admin,staff,accountant',
            'status' => 'required|in:active,inactive,suspended',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'id_number' => 'nullable|string|max:50',
            'salary' => 'nullable|numeric|min:0',
            'designation' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:50',
        ]);

        // Ensure primary_role is in roles array - REMOVED logic

        // Update password if provided
        if (!empty($validated['password'])) {
            $validated['password_hash'] = Hash::make($validated['password']);
        }
        unset($validated['password']);

        $staff->update($validated);

        $after = $staff->fresh()->toArray();
        $changed = [];

        foreach ($after as $key => $value) {
            // Skip timestamp updates if they are the only change
            if ($key === 'updated_at') continue;
            
            // Handle array comparisons (like roles)
            if (isset($before[$key])) {
                if (is_array($value) || is_array($before[$key])) {
                    if (json_encode($value) !== json_encode($before[$key])) {
                        $changed[] = $key;
                    }
                } elseif ($value != $before[$key]) {
                    $changed[] = $key;
                }
            }
        }
        
        $safeChanged = array_values(array_filter($changed, fn ($key) => $key !== 'password_hash'));

        $this->logActivity(
            $request,
            'staff_update',
            'Updated staff account for ' . $staff->full_name . ' (' . $staff->username . ')',
            ['target_staff_id' => $staff->id, 'target_username' => $staff->username, 'changed_fields' => $safeChanged]
        );

        if ($request->expectsJson()) {
            return response()->json($staff);
        }

        return redirect()->route('staff.index')->with('success', 'Staff member updated successfully');
    }

    /**
     * Remove the specified staff member.
     */
    public function destroy(Staff $staff)
    {
        // Soft delete
        $staff->update(['is_deleted' => true]);

        $this->logActivity(
            request(),
            'staff_delete',
            'Deleted staff account for ' . $staff->full_name . ' (' . $staff->username . ')',
            ['target_staff_id' => $staff->id, 'target_username' => $staff->username]
        );

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Staff member deleted successfully']);
        }

        return redirect()->route('staff.index')->with('success', 'Staff member deleted successfully');
    }

    /**
     * Suspend a staff member
     */
    public function suspend(Request $request, Staff $staff)
    {
        if ($staff->status === 'suspended') {
            return redirect()->back()->with('error', 'Staff member is already suspended');
        }

        $staff->update(['status' => 'suspended']);

        $this->logActivity(
            $request,
            'staff_suspend',
            'Suspended staff account for ' . $staff->full_name . ' (' . $staff->username . ')',
            ['target_staff_id' => $staff->id, 'target_username' => $staff->username]
        );

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Staff member suspended successfully', 'staff' => $staff]);
        }

        return redirect()->route('staff.index')->with('success', 'Staff member suspended successfully');
    }

    /**
     * Reinstate a suspended staff member
     */
    public function reinstate(Request $request, Staff $staff)
    {
        if ($staff->status !== 'suspended') {
            return redirect()->back()->with('error', 'Staff member is not suspended');
        }

        $staff->update(['status' => 'active']);

        $this->logActivity(
            $request,
            'staff_reinstate',
            'Reinstated staff account for ' . $staff->full_name . ' (' . $staff->username . ')',
            ['target_staff_id' => $staff->id, 'target_username' => $staff->username]
        );

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Staff member reinstated successfully', 'staff' => $staff]);
        }

        return redirect()->route('staff.index')->with('success', 'Staff member reinstated successfully');
    }

    /**
     * Get staff activity log
     */
    public function activity(Request $request, Staff $staff = null)
    {
        $query = StaffActivityLog::query();

        if ($staff) {
            $query->where('staff_id', $staff->id);
        }

        if ($request->has('activity_type')) {
            $query->where('activity_type', $request->activity_type);
        }

        $activities = $query->orderBy('created_at', 'desc')->paginate(50);

        return response()->json($activities);
    }
}
