@extends('layouts.app')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Staff Dashboard</h1>
            <p class="mt-1 text-sm text-gray-600">Welcome back, {{ auth()->user()->full_name }}</p>
        </div>

        <!-- My Performance Stats -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">My Sales Today</dt>
                                <dd class="text-lg font-semibold text-gray-900">{{ $myTodaySalesCount }} (KES {{ number_format($myTodayRevenue, 2) }})</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">My Month Sales</dt>
                                <dd class="text-lg font-semibold text-gray-900">{{ $myMonthSalesCount }} (KES {{ number_format($myMonthRevenue, 2) }})</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Low Stock Alerts</dt>
                                <dd class="text-lg font-semibold text-gray-900">{{ $lowStockCount }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Expiring Soon</dt>
                                <dd class="text-lg font-semibold text-gray-900">{{ $expiringCount }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Upcoming Surgery Cases -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Upcoming Surgery Cases</h3>
                    @if($upcomingCases->count() > 0)
                        <div class="overflow-hidden">
                            <ul class="divide-y divide-gray-200">
                                @foreach($upcomingCases as $case)
                                    <li class="py-3">
                                        <div class="flex items-center justify-between">
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900 truncate">
                                                    Case #{{ $case->id }} - {{ $case->patient_name ?? 'N/A' }}
                                                </p>
                                                <p class="text-sm text-gray-500">
                                                    {{ \Carbon\Carbon::parse($case->surgery_date)->format('M d, Y') }}
                                                    @if($case->surgery_time)
                                                        at {{ \Carbon\Carbon::parse($case->surgery_time)->format('h:i A') }}
                                                    @endif
                                                </p>
                                            </div>
                                            <div class="ml-4 flex-shrink-0">
                                                <a href="{{ route('reservations.show', $case->id) }}" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                                    View
                                                </a>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <p class="text-sm text-gray-500">No upcoming cases in the next 7 days.</p>
                    @endif
                </div>
            </div>

            <!-- Active Set Dispatches -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Sets In Workflow</h3>
                    @if($activeSets->count() > 0)
                        <div class="overflow-hidden">
                            <ul class="divide-y divide-gray-200">
                                @foreach($activeSets as $set)
                                    <li class="py-3">
                                        <div class="flex items-center justify-between">
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900 truncate">{{ $set->name }}</p>
                                                <p class="text-sm text-gray-500">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                                        @if($set->status === 'dispatched') bg-blue-100 text-blue-800
                                                        @elseif($set->status === 'in_surgery') bg-purple-100 text-purple-800
                                                        @elseif($set->status === 'dirty') bg-orange-100 text-orange-800
                                                        @endif">
                                                        {{ ucfirst(str_replace('_', ' ', $set->status)) }}
                                                    </span>
                                                </p>
                                            </div>
                                            <div class="ml-4 flex-shrink-0">
                                                <a href="{{ route('sets.show', $set->id) }}" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                                    View
                                                </a>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <p class="text-sm text-gray-500">No sets currently in workflow.</p>
                    @endif
                </div>
            </div>

            <!-- Low Stock Items -->
            <div class="bg-white shadow rounded-lg lg:col-span-2">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Low Stock Items (Action Required)</h3>
                    @if($lowStockItems->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($lowStockItems as $item)
                                        <tr>
                                            <td class="px-3 py-3 text-sm text-gray-900">{{ $item->product_name }}</td>
                                            <td class="px-3 py-3 text-sm text-gray-900">{{ $item->quantity_in_stock }}</td>
                                            <td class="px-3 py-3 text-sm">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                                    @if($item->quantity_in_stock <= 5) bg-red-100 text-red-800
                                                    @else bg-yellow-100 text-yellow-800
                                                    @endif">
                                                    @if($item->quantity_in_stock <= 5) Critical @else Low @endif
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-gray-500">All items are well stocked.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
