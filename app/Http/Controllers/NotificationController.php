<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Inventory;
use App\Models\PurchaseOrder;
use App\Models\PayrollRun;

class NotificationController extends Controller
{
    /**
     * Get current system notifications/alerts
     */
    public function index(Request $request)
    {
        $notifications = [];
        $user = $request->user();

        // 1. Low Stock Alerts (If enabled)
        // Check local preference via the SettingsController logic or user_preferences table if implemented
        // For now, we assume global settings or check if user has access to inventory
        if ($user->hasRole(['admin', 'supervisor', 'inventory_manager'])) {
            // Get low stock threshold from settings (default 10)
            $threshold = DB::table('settings')->where('key', 'low_stock_threshold')->value('value') ?? 10;
            
            $lowStockCount = Inventory::where('quantity', '<=', $threshold)->count();
            
            if ($lowStockCount > 0) {
                $notifications[] = [
                    'id' => 'low_stock',
                    'type' => 'warning',
                    'icon' => 'exclamation-triangle',
                    'title' => 'Low Stock Alert',
                    'message' => "$lowStockCount items are running low on stock.",
                    'link' => route('inventory.index', ['stock_level' => 'low']),
                    'time' => now()->diffForHumans()
                ];
            }

            // 1b. Expiring Items Alert
            $expiringCount = Inventory::whereDate('expiry_date', '>', now())
                ->whereDate('expiry_date', '<=', now()->addDays(90))
                ->count();

            if ($expiringCount > 0) {
                $notifications[] = [
                    'id' => 'expiring_stock',
                    'type' => 'warning',
                    'icon' => 'clock',
                    'title' => 'Expiring Stock',
                    'message' => "$expiringCount items expire within 90 days.",
                    'link' => route('inventory.index', ['stock_level' => 'expiring']), // Filter we just added
                    'time' => now()->diffForHumans()
                ];
            }
        }

        // 2. Pending Orders (If enabled)
        if ($user->hasRole(['admin', 'supervisor'])) {
            $pendingOrders = PurchaseOrder::where('status', 'pending')->count();
            
            if ($pendingOrders > 0) {
                $notifications[] = [
                    'id' => 'pending_orders',
                    'type' => 'info',
                    'icon' => 'shopping-cart',
                    'title' => 'Pending Orders',
                    'message' => "$pendingOrders purchase orders need approval.",
                    'link' => route('orders.index', ['status' => 'pending']),
                    'time' => now()->diffForHumans()
                ];
            }
        }

        // 3. Draft Payroll Runs (If enabled)
        if ($user->hasRole(['admin', 'supervisor', 'accountant'])) {
            $draftPayrolls = PayrollRun::where('status', 'DRAFT')->count();
            
            if ($draftPayrolls > 0) {
                $notifications[] = [
                    'id' => 'draft_payrolls',
                    'type' => 'info',
                    'icon' => 'cash',
                    'title' => 'Draft Payrolls',
                    'message' => "$draftPayrolls payroll runs are in draft.",
                    'link' => route('payroll.index'),
                    'time' => now()->diffForHumans()
                ];
            }
        }

        return response()->json([
            'count' => count($notifications),
            'notifications' => $notifications
        ]);
    }
}
