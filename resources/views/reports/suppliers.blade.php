@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8 flex flex-col md:flex-row justify-between items-end gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Supplier Performance</h1>
            <p class="text-gray-600 mt-1">Vendor reliability analysis and strategic procurement insights.</p>
        </div>
        
        <form action="{{ route('reports.suppliers') }}" method="GET" class="flex items-end gap-4 bg-white p-4 rounded-lg shadow-sm border border-gray-200">
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 text-sm font-medium shadow-sm transition-colors">
                <i class="fas fa-filter mr-2"></i>Update Report
            </button>
            <button type="button" onclick="window.print()" class="bg-white text-gray-700 border border-gray-300 px-4 py-2 rounded-md hover:bg-gray-50 text-sm font-medium shadow-sm transition-colors">
                <i class="fas fa-print mr-2"></i>Print
            </button>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-indigo-500">
            <h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider">Active Suppliers</h3>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ $totalSuppliers }}</p>
            <p class="text-xs text-gray-400 mt-1">Vendors in period</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-green-500">
            <h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider">Total Procurement</h3>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($totalSpend, 2) }}</p>
            <p class="text-xs text-gray-400 mt-1">Total spend</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-blue-500">
            <h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider">Purchase Orders</h3>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ $totalOrders }}</p>
            <p class="text-xs text-gray-400 mt-1">Total orders placed</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-purple-500">
            <h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider">Avg. Order Value</h3>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($avgOrderValue, 2) }}</p>
            <p class="text-xs text-gray-400 mt-1">Per purchase order</p>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Top Suppliers Chart -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Top Suppliers by Spend</h3>
            <div class="relative h-80 w-full">
                <canvas id="topSuppliersChart"></canvas>
            </div>
        </div>

        <!-- Monthly Trend Chart -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Procurement Trend</h3>
            <div class="relative h-80 w-full">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Detailed Supplier Table -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-900">Detailed Supplier Metrics</h3>
            <span class="text-sm text-gray-500">{{ $suppliers->count() }} suppliers</span>
        </div>
        <div class="overflow-x-auto max-h-[600px]">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 sticky top-0 z-10">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Reliability</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Orders</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Spend</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Avg. Value</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Order</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($suppliers as $supplier)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-indigo-600">{{ $supplier->supplier_name }}</div>
                                <div class="text-xs text-gray-500">ID: #{{ $supplier->id }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $supplier->contact_person }}</div>
                                <div class="text-xs text-gray-500">{{ $supplier->email }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex flex-col items-center">
                                    <div class="text-2xl font-bold {{ $supplier->reliability_score >= 70 ? 'text-green-600' : ($supplier->reliability_score >= 40 ? 'text-yellow-600' : 'text-red-600') }}">
                                        {{ $supplier->reliability_score }}
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-1.5 mt-1">
                                        <div class="h-1.5 rounded-full {{ $supplier->reliability_score >= 70 ? 'bg-green-500' : ($supplier->reliability_score >= 40 ? 'bg-yellow-500' : 'bg-red-500') }}" 
                                             style="width: {{ $supplier->reliability_score }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="text-sm font-medium text-gray-900">{{ $supplier->total_orders }}</div>
                                <div class="text-xs text-gray-500">
                                    <span class="text-green-600">{{ $supplier->completed_orders }} done</span> / 
                                    <span class="text-yellow-600">{{ $supplier->pending_orders }} pending</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-gray-900">
                                {{ number_format($supplier->total_spend, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500">
                                {{ number_format($supplier->average_order_value, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @if($supplier->last_order_date)
                                    {{ $supplier->last_order_date->format('M d, Y') }}
                                    <div class="text-xs text-gray-400">{{ $supplier->last_order_date->diffForHumans() }}</div>
                                @else
                                    <span class="text-gray-400">No orders</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-truck text-4xl mb-3 text-gray-300"></i>
                                <p>No suppliers found for this period.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Top Suppliers Bar Chart
    const ctxTop = document.getElementById('topSuppliersChart').getContext('2d');
    new Chart(ctxTop, {
        type: 'bar',
        data: {
            labels: {!! json_encode($spendDistribution->pluck('name')) !!},
            datasets: [{
                label: 'Total Spend',
                data: {!! json_encode($spendDistribution->pluck('value')) !!},
                backgroundColor: 'rgba(79, 70, 229, 0.8)',
                borderColor: 'rgba(79, 70, 229, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return ' Spend: ' + new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(context.raw);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { borderDash: [2, 4], color: '#f3f4f6' }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });

    // 2. Monthly Trend Line Chart
    const ctxTrend = document.getElementById('trendChart').getContext('2d');
    new Chart(ctxTrend, {
        type: 'line',
        data: {
            labels: {!! json_encode($monthlyTrend->pluck('month')) !!},
            datasets: [{
                label: 'Monthly Procurement',
                data: {!! json_encode($monthlyTrend->pluck('total')) !!},
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.4,
                fill: true,
                pointRadius: 4,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return ' ' + new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(context.raw);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { borderDash: [2, 4], color: '#f3f4f6' }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
});
</script>
@endsection
