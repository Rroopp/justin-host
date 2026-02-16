@extends('layouts.app')

@section('title', 'Inventory Health')

@section('content')
<div class="px-4 py-6 sm:px-0">
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Inventory Health</h1>

    <!-- Key Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        
        <!-- Total Value -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500 truncate">Total Inventory Value (Cost)</dt>
                <dd class="mt-1 text-3xl font-semibold text-gray-900">KSh {{ number_format($totalInventoryValue, 2) }}</dd>
            </div>
        </div>

        <!-- Potential Revenue -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500 truncate">Potential Revenue</dt>
                <dd class="mt-1 text-3xl font-semibold text-gray-900">KSh {{ number_format($potentialRevenue, 2) }}</dd>
            </div>
        </div>

        <!-- Total Items -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500 truncate">Total Product Lines</dt>
                <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ number_format($totalItems) }}</dd>
            </div>
        </div>

        <!-- Total Quantity -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <dt class="text-sm font-medium text-gray-500 truncate">Total Units in Stock</dt>
                <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ number_format($totalStockQuantity) }}</dd>
            </div>
        </div>
    </div>

    <!-- Health Indicators -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-8">
        <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Stock Health Indicators</h3>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-0 divide-y md:divide-y-0 md:divide-x divide-gray-200">
            <div class="px-4 py-5 sm:p-6 text-center">
                <dt class="text-base font-normal text-gray-900">Low Stock Items</dt>
                <dd class="mt-1 flex justify-between items-baseline md:block lg:flex">
                    <div class="flex items-baseline text-2xl font-semibold text-red-600 mx-auto">
                        {{ $lowStockCount }}
                        <span class="ml-2 text-sm font-medium text-gray-500">items</span>
                    </div>
                </dd>
                <div class="mt-2">
                    <a href="{{ route('orders.suggestions.low-stock') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">View items</a>
                </div>
            </div>
            
            <div class="px-4 py-5 sm:p-6 text-center">
                <dt class="text-base font-normal text-gray-900">Out of Stock</dt>
                <dd class="mt-1 flex justify-between items-baseline md:block lg:flex">
                    <div class="flex items-baseline text-2xl font-semibold text-red-800 mx-auto">
                        {{ $outOfStockCount }}
                        <span class="ml-2 text-sm font-medium text-gray-500">items</span>
                    </div>
                </dd>
                <!-- Reuse low stock link as out of stock is a subset usually, or add specific filter later -->
                <div class="mt-2 text-sm text-gray-500">Critical attention needed</div>
            </div>

            <div class="px-4 py-5 sm:p-6 text-center">
                <dt class="text-base font-normal text-gray-900">Expiring Soon (30 days)</dt>
                <dd class="mt-1 flex justify-between items-baseline md:block lg:flex">
                    <div class="flex items-baseline text-2xl font-semibold text-yellow-600 mx-auto">
                        {{ $expiringSoonCount }}
                        <span class="ml-2 text-sm font-medium text-gray-500">batches</span>
                    </div>
                </dd>
                 <div class="mt-2 text-sm text-gray-500">Check batch details</div>
            </div>
        </div>
    </div>
</div>
@endsection
