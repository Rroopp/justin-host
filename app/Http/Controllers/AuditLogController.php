<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditLogController extends Controller
{
    /**
     * Display audit logs (admin only)
     */
    public function index(Request $request)
    {
        // Only admins can view audit logs
        if (!$request->user() || !$request->user()->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        $query = AuditLog::query()->with('user')->orderBy('created_at', 'desc');

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

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('user_name', 'like', "%{$search}%")
                  ->orWhere('target_id', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        // Get unique values for filters
        $modules = AuditLog::select('module')->distinct()->pluck('module');
        $actions = AuditLog::select('action')->distinct()->pluck('action');
        $users = DB::table('staff')->select('id', 'full_name')->get();

        if ($request->expectsJson()) {
            return response()->json([
                'logs' => $query->paginate(50),
                'filters' => [
                    'modules' => $modules,
                    'actions' => $actions,
                    'users' => $users,
                ]
            ]);
        }

        $logs = $query->paginate(50);

        return view('audit-logs.index', compact('logs', 'modules', 'actions', 'users'));
    }

    /**
     * Show detailed audit log entry
     */
    public function show($id)
    {
        $log = AuditLog::with('user')->findOrFail($id);

        if (request()->expectsJson()) {
            return response()->json($log);
        }

        return view('audit-logs.show', compact('log'));
    }

    /**
     * Export audit logs to CSV
     */
    public function export(Request $request)
    {
        if (!$request->user() || !$request->user()->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        $query = AuditLog::query()->orderBy('created_at', 'desc');

        // Apply same filters as index
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
            $query->where('created_at', '>=', $request->date_from);
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
            
            // Headers
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

            // Data
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
