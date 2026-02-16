@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-8 font-sans">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between mb-8 print:hidden">
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                    Inventory Intelligence
                </h2>
                <p class="mt-1 text-sm text-gray-500">Valuation, risk analysis, and turnover metrics.</p>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
                 <button onclick="window.print()" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none">
                    <i class="fas fa-print mr-2"></i> Print Report
                </button>
            </div>
        </div>

        <!-- Print Header -->
        <div class="hidden print:block mb-6">
            @include('partials.document_header')
            <div class="text-center mt-4">
                <h2 class="text-xl font-bold text-gray-900">Inventory Health Report</h2>
                <p class="text-sm text-gray-500 mt-1">Generated: {{ now()->format('d M Y, h:i A') }}</p>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
            <!-- Total Cost Valuation -->
            <div class="bg-white shadow rounded-lg border-l-4 border-indigo-500">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-indigo-50 rounded-md p-3">
                            <i class="fas fa-coins text-indigo-600 text-xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Stock Cost</dt>
                            <dd class="text-lg font-bold text-gray-900">KES {{ number_format($total_cost) }}</dd>
                            <p class="text-xs text-green-600 font-medium">+{{ number_format($potential_profit) }} Potential Profit</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Turnover Rate -->
            <div class="bg-white shadow rounded-lg border-l-4 border-blue-500">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-blue-50 rounded-md p-3">
                            <i class="fas fa-sync text-blue-600 text-xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dt class="text-sm font-medium text-gray-500 truncate">Turnover Rate (30d)</dt>
                            <dd class="text-lg font-bold text-gray-900">{{ number_format($turnover_rate * 100, 1) }}%</dd>
                             <p class="text-xs text-gray-500 font-medium">Avg Sales Class: {{ $turnover_rate > 0.3 ? 'Fast' : ($turnover_rate > 0.1 ? 'Medium' : 'Slow') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stock Health -->
            <div class="bg-white shadow rounded-lg border-l-4 border-red-500">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-red-50 rounded-md p-3">
                            <i class="fas fa-heartbeat text-red-600 text-xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dt class="text-sm font-medium text-gray-500 truncate">Immediate Action</dt>
                             <div class="flex space-x-3 text-sm font-bold mt-1">
                                <span class="text-red-700">{{ $out_of_stock->count() }} Out</span>
                                <span class="text-gray-300">|</span>
                                <span class="text-yellow-600">{{ $low_stock->count() }} Low</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

             <!-- Dead Stock Risk -->
            <div class="bg-white shadow rounded-lg border-l-4 border-gray-500">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-gray-100 rounded-md p-3">
                            <i class="fas fa-hourglass-end text-gray-600 text-xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dt class="text-sm font-medium text-gray-500 truncate">Slow Moving (>90d)</dt>
                            <dd class="text-lg font-bold text-gray-900">{{ $slow_moving->count() }} Items</dd>
                            <p class="text-xs text-gray-500 font-medium">Tying up capital</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8 break-inside-avoid">
            
            <!-- Valuation Chart -->
            <div class="bg-white rounded-lg shadow p-6 print:border print:shadow-none">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Capital Allocation by Category</h3>
                <div class="relative h-64">
                     <canvas id="categoryChart"></canvas>
                </div>
                <p class="mt-4 text-xs text-gray-500 text-center">Charts top 8 categories by total cost value.</p>
            </div>

            <!-- Critical Low Stock List -->
            <div class="bg-white rounded-lg shadow print:border print:shadow-none">
                <div class="px-6 py-4 border-b border-gray-200 bg-red-50 flex justify-between items-center">
                     <h3 class="text-lg font-semibold text-red-800">
                        <i class="fas fa-exclamation-circle mr-2"></i> Critical Reorder List
                    </h3>
                    <span class="text-xs font-bold bg-white text-red-800 px-2 py-1 rounded-full border border-red-200">Top 10</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                         <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Stock</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                             @forelse($out_of_stock->merge($low_stock)->take(10) as $item)
                            <tr>
                                <td class="px-6 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $item->product_name }}</td>
                                <td class="px-6 py-3 whitespace-nowrap text-right text-sm text-gray-500">{{ $item->quantity_in_stock }} / {{ $item->reorder_level }}</td>
                                <td class="px-6 py-3 whitespace-nowrap text-right text-xs">
                                    <span class="px-2 inline-flex leading-5 font-semibold rounded-full {{ $item->quantity_in_stock == 0 ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ $item->quantity_in_stock == 0 ? 'OUT' : 'LOW' }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">No critical alerts. Operations healthy.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tables Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8 break-inside-avoid">
            
             <!-- Top Value Holders -->
             <div class="bg-white rounded-lg shadow print:border print:shadow-none">
                <div class="px-6 py-4 border-b border-gray-200 bg-indigo-50">
                    <h3 class="text-lg font-semibold text-indigo-900">Highest Value Assets</h3>
                </div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Qty</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Value</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($top_value_items as $item)
                        <tr>
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900">{{ $item->product_name }}</td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm text-gray-500">{{ $item->quantity_in_stock }}</td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-medium text-gray-900">KES {{ number_format($item->price * $item->quantity_in_stock) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Slow Moving -->
             <div class="bg-white rounded-lg shadow print:border print:shadow-none">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-semibold text-gray-900">Stagnant Stock (>90 Days)</h3>
                </div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Last Update</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Qty</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($slow_moving->take(5) as $item)
                        <tr>
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900">{{ $item->product_name }}</td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm text-gray-500">{{ $item->updated_at->diffForHumans() }}</td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm text-gray-900">{{ $item->quantity_in_stock }}</td>
                        </tr>
                        @empty
                         <tr>
                            <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">No stagnant stock found. Excellent turnover!</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                @if($slow_moving->count() > 5)
                <div class="px-6 py-2 bg-gray-50 text-right">
                    <a href="#" class="text-xs text-indigo-600 hover:text-indigo-900">View all {{ $slow_moving->count() }} items &rarr;</a>
                </div>
                @endif
            </div>

        </div>

    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('categoryChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: @json($category_values->keys()),
                datasets: [{
                    data: @json($category_values->values()),
                    backgroundColor: [
                        '#4f46e5', '#10b981', '#f59e0b', '#ef4444', 
                        '#8b5cf6', '#ec4899', '#6366f1', '#14b8a6'
                    ],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            padding: 20
                        }
                    }
                }
            }
        });
    });
</script>
@endsection
