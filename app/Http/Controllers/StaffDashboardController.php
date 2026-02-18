<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PosSale;
use App\Models\Inventory;
use App\Models\CaseReservation;
use App\Models\SurgicalSet;
use Illuminate\Support\Facades\DB;

class StaffDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // 1. My Sales Stats (Personal Performance)
        $todayQuery = PosSale::whereDate('created_at', today())
            ->where('seller_username', $user->username);
            
        $monthQuery = PosSale::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('seller_username', $user->username);

        $myTodaySalesCount = $todayQuery->count();
        $myTodayRevenue = $todayQuery->sum('total'); // Allowed: "My Sales"
        $myMonthSalesCount = $monthQuery->count();
        $myMonthRevenue = $monthQuery->sum('total');

        // 2. Inventory Alerts (Operational Safety)
        $lowStockCount = Inventory::where('quantity_in_stock', '<=', 10)->count();
        $outOfStockCount = Inventory::where('quantity_in_stock', '<=', 0)->count();
        
        // Expiring soon (90 days)
        $expiringCount = Inventory::whereDate('expiry_date', '>', now())
            ->whereDate('expiry_date', '<=', now()->addDays(90))
            ->count();

        // List of Low Stock Items (for immediate action)
        $lowStockItems = Inventory::where('quantity_in_stock', '<=', 10)
            ->where('quantity_in_stock', '>', 0)
            ->orderBy('quantity_in_stock', 'asc')
            ->limit(5)
            ->get();

        // 3. Upcoming Surgery Cases (Next 7 days) - Operational Awareness
        $upcomingCases = CaseReservation::whereIn('status', ['confirmed', 'scheduled'])
            ->where('surgery_date', '>=', today())
            ->where('surgery_date', '<=', today()->addDays(7))
            ->orderBy('surgery_date', 'asc')
            ->limit(10)
            ->get();

        // 4. Sets currently "In Workflow" (Dispatched/In Surgery/Dirty)
        // Staff need to know what to chase up or clean
        $activeSets = SurgicalSet::whereIn('status', [
                SurgicalSet::STATUS_DISPATCHED, 
                SurgicalSet::STATUS_IN_SURGERY, 
                SurgicalSet::STATUS_DIRTY
            ])
            ->limit(10)
            ->get();

        return view('dashboard.staff', compact(
            'myTodaySalesCount',
            'myTodayRevenue',
            'myMonthSalesCount',
            'myMonthRevenue',
            'lowStockCount',
            'outOfStockCount',
            'expiringCount',
            'lowStockItems',
            'upcomingCases',
            'activeSets'
        ));
    }
}
