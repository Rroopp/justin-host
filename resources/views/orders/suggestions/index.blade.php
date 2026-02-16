@extends('layouts.app')

@section('title', 'Order Suggestions')

@section('content')
<div class="px-4 py-6 sm:px-0">
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Order Suggestions</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Low Stock Card -->
        <a href="{{ route('orders.suggestions.low-stock') }}" class="block p-6 bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-red-100 text-red-600">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h2 class="text-xl font-semibold text-gray-800">Low Stock</h2>
                    <p class="text-gray-500 mt-1">{{ $lowStockCount ?? 0 }} items below threshold</p>
                </div>
            </div>
            <div class="mt-4 text-blue-600 font-medium">View Low Stock Items &rarr;</div>
        </a>

        <!-- Top Selling Card -->
        <a href="{{ route('orders.suggestions.top-selling') }}" class="block p-6 bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h2 class="text-xl font-semibold text-gray-800">Top Selling</h2>
                    <p class="text-gray-500 mt-1">Based on recent sales volume</p>
                </div>
            </div>
            <div class="mt-4 text-blue-600 font-medium">View Top Sellers &rarr;</div>
        </a>

        <!-- By Supplier Card -->
        <a href="{{ route('orders.suggestions.by-supplier') }}" class="block p-6 bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h2 class="text-xl font-semibold text-gray-800">By Supplier</h2>
                    <p class="text-gray-500 mt-1">Grouped order suggestions</p>
                </div>
            </div>
            <div class="mt-4 text-blue-600 font-medium">View Supplier Lists &rarr;</div>
        </a>
    </div>
</div>
@endsection
