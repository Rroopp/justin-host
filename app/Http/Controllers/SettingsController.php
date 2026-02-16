<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    private function auditSettingChange(string $key, ?string $category, $oldValue, $newValue, ?string $changedBy, ?string $changeReason): void
    {
        if (!Schema::hasTable('settings_audit_log')) {
            return;
        }

        $old = is_array($oldValue) ? json_encode($oldValue) : (is_null($oldValue) ? null : (string) $oldValue);
        $new = is_array($newValue) ? json_encode($newValue) : (is_null($newValue) ? null : (string) $newValue);

        // Only log when something actually changed.
        if ($old === $new) {
            return;
        }

        DB::table('settings_audit_log')->insert([
            'setting_key' => $key,
            'category' => $category,
            'old_value' => $old,
            'new_value' => $new,
            'changed_by' => $changedBy,
            'change_reason' => $changeReason,
            'changed_at' => now(),
        ]);
    }

    private function setSetting(string $key, $value, string $category, ?string $changedBy, ?string $changeReason = null, ?string $settingType = null, ?string $description = null): void
    {
        $existing = DB::table('settings')->where('key', $key)->first();

        $serialized = is_array($value) ? json_encode($value) : $value;
        $oldValue = $existing?->value;

        $payload = [
            'value' => $serialized,
            'category' => $category,
            'updated_by' => $changedBy,
            'updated_at' => now(),
        ];
        if ($settingType !== null) {
            $payload['setting_type'] = $settingType;
        }
        if ($description !== null) {
            $payload['description'] = $description;
        }
        if ($changeReason !== null) {
            $payload['change_reason'] = $changeReason;
        }

        DB::table('settings')->updateOrInsert(['key' => $key], $payload);
        
        // Clear cache so changes are reflected immediately
        \App\Services\SettingsService::clearCache($key);

        $this->auditSettingChange($key, $category, $oldValue, $serialized, $changedBy, $changeReason);
    }

    /**
     * Display settings page.
     */
    public function index(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json($this->getAllSettings());
        }

        // Role-based filtering: strict check for admin
        // Non-admins should only see the page skeleton, the Vue/Alpine frontend will
        // request what it needs, and those endpoints must also be secured.
        // However, since we pass $settings to view, let's filter it here too.
        
        $role = $request->user()?->role;
        $isAdmin = $role === 'admin';

        $allSettings = $this->getAllSettings();
        
        if (!$isAdmin) {
            // Filter out system, security, company, modules, audit for non-admins
            // Effectively, they might only see empty arrays or nothing, 
            // relying on 'userPreferences' which is separate.
            $settings = collect([]); // Empty for non-admins, they only get Preferences via separate API
        } else {
            $settings = $allSettings;
        }

        return view('settings.index', compact('settings'));
    }

    /**
     * Get all settings grouped by category
     */
    public function getAllSettings()
    {
        $settings = DB::table('settings')
            ->orderBy('category')
            ->orderBy('key')
            ->get()
            ->groupBy('category');

        return $settings->map(function ($items) {
            return $items->pluck('value', 'key');
        });
    }

    /**
     * Get settings by category
     */
    public function getByCategory(Request $request, $category)
    {
        $settings = DB::table('settings')
            ->where('category', $category)
            ->get()
            ->pluck('value', 'key');

        return response()->json($settings);
    }

    /**
     * Update settings
     */
    public function update(Request $request)
    {
        // Security Check
        if ($request->user()?->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'nullable',
            'settings.*.category' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            foreach ($validated['settings'] as $setting) {
                $this->setSetting(
                    $setting['key'],
                    $setting['value'],
                    $setting['category'],
                    $request->user() ? $request->user()->username : 'system',
                    $request->input('change_reason') ?? null
                );
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Settings updated successfully']);
            }

            return redirect()->back()->with('success', 'Settings updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Failed to update settings: ' . $e->getMessage()], 500);
            }

            return redirect()->back()->with('error', 'Failed to update settings: ' . $e->getMessage());
        }
    }

    /**
     * Initialize default settings
     */
    public function initializeDefaults()
    {
        $defaults = [
            // System Settings
            ['key' => 'currency_code', 'value' => 'KSH', 'category' => 'system', 'setting_type' => 'string'],
            ['key' => 'currency_symbol', 'value' => 'KSh', 'category' => 'system', 'setting_type' => 'string'],
            ['key' => 'default_tax_rate', 'value' => '16', 'category' => 'system', 'setting_type' => 'decimal'],
            ['key' => 'invoice_prefix', 'value' => 'INV-', 'category' => 'system', 'setting_type' => 'string'],
            ['key' => 'invoice_start_number', 'value' => '1', 'category' => 'system', 'setting_type' => 'integer'],
            
            // Inventory Settings
            ['key' => 'low_stock_threshold', 'value' => '10', 'category' => 'inventory', 'setting_type' => 'integer'],
            ['key' => 'auto_restock_suggestions', 'value' => '1', 'category' => 'inventory', 'setting_type' => 'boolean'],
            
            // Security Settings
            ['key' => 'session_timeout_minutes', 'value' => '60', 'category' => 'security', 'setting_type' => 'integer'],
            ['key' => 'password_min_length', 'value' => '6', 'category' => 'security', 'setting_type' => 'integer'],

            // Company Settings (Defaults based on existing hardcoded values)
            ['key' => 'company_name', 'value' => 'JASTENE MEDICAL LTD', 'category' => 'company', 'setting_type' => 'string'],
            ['key' => 'company_address', 'value' => "KIKI BUILDING, KISII-NYAMIRA HIGHWAY.\nP.O BOX 4334-40200 KISII TOWN, KENYA.", 'category' => 'company', 'setting_type' => 'string'],
            ['key' => 'company_phone', 'value' => '(+254) 737019207 / (+254) 726567419', 'category' => 'company', 'setting_type' => 'string'],
            ['key' => 'company_email', 'value' => 'info@jastenemedical.com', 'category' => 'company', 'setting_type' => 'string'],

            ['key' => 'module_pos_enabled', 'value' => '1', 'category' => 'modules', 'setting_type' => 'boolean'],
            ['key' => 'module_inventory_enabled', 'value' => '1', 'category' => 'modules', 'setting_type' => 'boolean'],
            ['key' => 'module_orders_enabled', 'value' => '1', 'category' => 'modules', 'setting_type' => 'boolean'],
            ['key' => 'module_staff_enabled', 'value' => '1', 'category' => 'modules', 'setting_type' => 'boolean'],
            ['key' => 'module_accounting_enabled', 'value' => '1', 'category' => 'modules', 'setting_type' => 'boolean'],
            ['key' => 'module_rentals_enabled', 'value' => '1', 'category' => 'modules', 'setting_type' => 'boolean'],
            ['key' => 'module_payroll_enabled', 'value' => '1', 'category' => 'modules', 'setting_type' => 'boolean'],
            ['key' => 'module_reports_enabled', 'value' => '1', 'category' => 'modules', 'setting_type' => 'boolean'],
            ['key' => 'module_suppliers_enabled', 'value' => '1', 'category' => 'modules', 'setting_type' => 'boolean'],
            ['key' => 'module_customers_enabled', 'value' => '1', 'category' => 'modules', 'setting_type' => 'boolean'],
        ];

        foreach ($defaults as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                array_merge($setting, [
                    'description' => '',
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        return response()->json(['message' => 'Default settings initialized']);
    }

    /**
     * Company info (settings category: company)
     */
    public function company(Request $request)
    {
        $settings = DB::table('settings')
            ->where('category', 'company')
            ->orderBy('key')
            ->get()
            ->pluck('value', 'key');

        return response()->json($settings);
    }

    public function updateCompany(Request $request)
    {
        if ($request->user()?->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'company' => 'required|array',
            'company.company_name' => 'nullable|string|max:255',
            'company.company_address' => 'nullable|string|max:1000',
            'company.company_phone' => 'nullable|string|max:50',
            'company.company_email' => 'nullable|email|max:255',
            'company.company_registration' => 'nullable|string|max:255',
            'company.tax_number' => 'nullable|string|max:255',
        ]);

        $by = $request->user()?->username ?? 'system';
        $reason = $request->input('change_reason') ?? 'Company information update';

        DB::beginTransaction();
        try {
            foreach ($validated['company'] as $key => $value) {
                $this->setSetting($key, $value, 'company', $by, $reason);
            }
            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Upload company logo
     */
    public function uploadCompanyLogo(Request $request)
    {
        if ($request->user()?->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        try {
            if ($request->hasFile('logo')) {
                $file = $request->file('logo');
                $filename = 'company_logo_' . time() . '.' . $file->getClientOriginalExtension();
                // Store in public/uploads accessible via web
                $path = $file->storeAs('uploads', $filename, 'public');
                
                // Save path to settings
                // We use the storage URL format: /storage/uploads/filename
                $url = '/storage/' . $path;
                
                $this->setSetting(
                    'company_logo', 
                    $url, 
                    'company', 
                    $request->user()->username, 
                    'Logo update'
                );

                return response()->json(['success' => true, 'url' => $url]);
            }
            return response()->json(['error' => 'No file uploaded'], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Upload failed: ' . $e->getMessage()], 500);
        }
    }



    /**
     * Module toggles (settings category: modules)
     */
    public function modules(Request $request)
    {
        $defaults = [
            'module_orders_enabled' => '1',
            'module_staff_enabled' => '1',
            'module_accounting_enabled' => '1',
            'module_inventory_enabled' => '1',
            'module_pos_enabled' => '1',
            'module_rentals_enabled' => '1',
            'module_payroll_enabled' => '1',
            'module_reports_enabled' => '1',
            'module_suppliers_enabled' => '1',
            'module_customers_enabled' => '1',
            'module_consignments_enabled' => '1',
            'module_commissions_enabled' => '1',
        ];

        $stored = DB::table('settings')
            ->where('category', 'modules')
            ->get()
            ->pluck('value', 'key')
            ->toArray();

        return response()->json(array_merge($defaults, $stored));
    }

    public function updateModules(Request $request)
    {
        if ($request->user()?->role !== 'admin') {
             return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'modules' => 'required|array',
            'modules.module_orders_enabled' => 'required|in:0,1',
            'modules.module_staff_enabled' => 'required|in:0,1',
            'modules.module_accounting_enabled' => 'required|in:0,1',
            'modules.module_inventory_enabled' => 'required|in:0,1',
            'modules.module_pos_enabled' => 'required|in:0,1',
            'modules.module_rentals_enabled' => 'required|in:0,1',
            'modules.module_payroll_enabled' => 'required|in:0,1',
            'modules.module_reports_enabled' => 'required|in:0,1',
            'modules.module_suppliers_enabled' => 'required|in:0,1',
            'modules.module_customers_enabled' => 'required|in:0,1',
            'modules.module_consignments_enabled' => 'required|in:0,1',
            'modules.module_commissions_enabled' => 'required|in:0,1',
        ]);

        $by = $request->user()?->username ?? 'system';
        $reason = $request->input('change_reason') ?? 'Module toggle update';

        DB::beginTransaction();
        try {
            foreach ($validated['modules'] as $key => $value) {
                $this->setSetting($key, $value, 'modules', $by, $reason, 'boolean', 'Module enabled/disabled');
            }
            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Settings audit log (admin only)
     */
    public function auditLog(Request $request)
    {
        if (!Schema::hasTable('settings_audit_log')) {
            return response()->json([]);
        }

        $limit = (int) ($request->get('limit', 100));
        $limit = max(1, min(500, $limit));

        $rows = DB::table('settings_audit_log')
            ->orderByDesc('changed_at')
            ->limit($limit)
            ->get();

        return response()->json($rows);
    }

    /**
     * User preferences (per staff)
     */
    public function userPreferences(Request $request)
    {
        $staff = $request->user();
        if (!$staff) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if (!Schema::hasTable('user_preferences')) {
            return response()->json([]);
        }

        $prefs = DB::table('user_preferences')
            ->where('staff_id', $staff->id)
            ->get();

        $result = [];
        foreach ($prefs as $p) {
            $val = $p->preference_value;
            if (is_string($val)) {
                $decoded = json_decode($val, true);
                $result[$p->preference_key] = json_last_error() === JSON_ERROR_NONE ? $decoded : $val;
            } else {
                $result[$p->preference_key] = $val;
            }
        }

        return response()->json($result);
    }

    public function updateUserPreferences(Request $request)
    {
        $staff = $request->user();
        if (!$staff) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if (!Schema::hasTable('user_preferences')) {
            return response()->json(['message' => 'Preferences table not found'], 400);
        }

        $validated = $request->validate([
            'preferences' => 'required|array',
        ]);

        DB::beginTransaction();
        try {
            foreach ($validated['preferences'] as $key => $value) {
                DB::table('user_preferences')->updateOrInsert(
                    ['staff_id' => $staff->id, 'preference_key' => $key],
                    [
                        'preference_value' => is_array($value) ? json_encode($value) : (is_null($value) ? null : (string) $value),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get all staff permissions (admin only)
     */
    public function getPermissions(Request $request)
    {
        $staffMembers = \App\Models\Staff::where('role', '!=', 'admin')
            ->where('is_deleted', false)
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'role', 'permissions', 'designation']);

        return response()->json($staffMembers);
    }

    /**
     * Update permissions for a staff member
     */
    public function updatePermissions(Request $request, \App\Models\Staff $staff)
    {
        if ($staff->role === 'admin') {
             return response()->json(['error' => 'Cannot modify permissions for admin users'], 400);
        }

        $validated = $request->validate([
            'permissions' => 'present|array',
            'permissions.*' => 'string'
        ]);

        $staff->permissions = $validated['permissions'];
        $staff->save();

        return response()->json(['success' => true, 'permissions' => $staff->permissions]);
    }

    /**
     * Simple settings backup (JSON snapshot of settings table).
     */
    public function backup(Request $request)
    {
        $rows = DB::table('settings')->orderBy('category')->orderBy('key')->get()->toArray();
        $dir = storage_path('app/backups');
        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $file = $dir . '/settings_backup_' . now()->format('Ymd_His') . '.json';
        File::put($file, json_encode($rows, JSON_PRETTY_PRINT));

        return response()->json(['success' => true, 'path' => $file]);
    }

    /**
     * Get comprehensive audit logs (all modules, all actions)
     */
    public function getAuditLogs(Request $request)
    {
        $query = \App\Models\AuditLog::with(['user'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from . ' 00:00:00');
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('user_name', 'like', "%{$search}%")
                  ->orWhere('target_id', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate(50);

        return response()->json($logs);
    }

    /**
     * Get distinct modules for filter dropdown
     */
    public function getAuditModules()
    {
        $modules = \App\Models\AuditLog::select('module')
            ->distinct()
            ->orderBy('module')
            ->pluck('module');

        return response()->json($modules);
    }

    /**
     * Get distinct actions for filter dropdown
     */
    public function getAuditActions()
    {
        $actions = \App\Models\AuditLog::select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return response()->json($actions);
    }

    /**
     * Get all users for filter dropdown
     */
    public function getAuditUsers()
    {
        $users = \App\Models\Staff::select('id', 'full_name')
            ->orderBy('full_name')
            ->get();

        return response()->json($users);
    }

    /**
     * Export audit logs to CSV
     */
    public function exportAuditLogs(Request $request)
    {
        $query = \App\Models\AuditLog::with(['user'])
            ->orderBy('created_at', 'desc');

        // Apply same filters
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from . ' 00:00:00');
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        $logs = $query->get();

        $filename = 'audit_logs_' . now()->format('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($logs) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'ID',
                'Date/Time',
                'User',
                'Action',
                'Module',
                'Description',
                'Target Type',
                'Target ID',
                'IP Address',
            ]);

            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->user_name,
                    $log->action,
                    $log->module,
                    $log->description,
                    $log->target_type,
                    $log->target_id,
                    $log->ip_address,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
