@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between mb-8 print:hidden">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Inventory Health</h1>
            <p class="text-gray-600 mt-1">Stock valuation and risk analysis.</p>
        </div>
        <div class="flex gap-2">
            <button onclick="window.print()" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-print mr-2"></i> Print
            </button>
            <a href="{{ route('reports.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                Back to Reports
            </a>
        </div>
    </div>

    <!-- Printable Header -->
    <div class="hidden print:block mb-8 border-b pb-4">
        <h1 class="text-2xl font-bold text-gray-900">Inventory Health Report</h1>
        <p class="text-xs text-gray-400 mt-1">Generated: {{ now()->format('Y-m-d H:i') }}</p>
    </div>

    <!-- Valuation Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-indigo-500">
            <div class="text-sm font-medium text-gray-500 mb-1">Total Stock Value (Retail)</div>
            <div class="text-3xl font-bold text-indigo-700">KES {{ number_format($total_value, 2) }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-gray-500">
            <div class="text-sm font-medium text-gray-500 mb-1">Total Stock Cost</div>
            <div class="text-3xl font-bold text-gray-700">KES {{ number_format($total_cost, 2) }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
            <div class="text-sm font-medium text-gray-500 mb-1">Potential Profit</div>
            <div class="text-3xl font-bold text-green-700">KES {{ number_format($potential_profit, 2) }}</div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8 break-inside-avoid">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Stock Value by Category</h3>
            <canvas id="valuationChart" height="200"></canvas>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Stock Status Overview</h3>
            <div class="flex items-center justify-center p-4">
                <div class="text-center w-1/3">
                    <div class="text-3xl font-bold text-red-600">{{ $low_stock->where('quantity_in_stock', 0)->count() }}</div>
                    <div class="text-sm text-gray-500">Out of Stock</div>
                </div>
                <div class="text-center w-1/3 border-l border-r">
                    <div class="text-3xl font-bold text-yellow-600">{{ $low_stock->where('quantity_in_stock', '>', 0)->count() }}</div>
                    <div class="text-sm text-gray-500">Low Stock</div>
                </div>
                <div class="text-center w-1/3">
                    <div class="text-3xl font-bold text-gray-900">{{ $low_stock->count() }}</div>
                    <div class="text-sm text-gray-500">Total Alerts</div>
                </div>
            </div>
            <div class="mt-4">
                 <a href="{{ route('inventory.index', ['stock_level' => 'low']) }}" class="text-sm text-indigo-600 hover:text-indigo-900 block text-center">View Warning Items &rarr;</a>
            </div>
        </div>
    </div>

    <!-- Low Stock Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden break-before-page">
        <div class="px-6 py-4 border-b border-gray-200 bg-red-50">
            <h3 class="text-lg font-semibold text-red-800 flex items-center">
                <i class="fas fa-exclamation-triangle mr-2"></i> Critical Stock Alerts
            </h3>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">In Stock</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Reorder Level</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Unit Value</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($low_stock->take(15) as $item)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $item->name }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $item->category }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold {{ $item->quantity_in_stock == 0 ? 'text-red-600' : 'text-yellow-600' }}">
                        {{ $item->quantity_in_stock }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500">{{ $item->reorder_level }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">KES {{ number_format($item->price, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">No stock alerts found. Good job!</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($low_stock->count() > 15)
        <div class="px-6 py-3 bg-gray-50 text-right">
            <span class="text-xs text-gray-500">Showing first 15 alerts only</span>
        </div>
        @endif
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    new Chart(document.getElementById('valuationChart'), {
        type: 'bar',
        data: {
            labels: @json($category_values->keys()),
            datasets: [{
                label: 'Stock Value (KES)',
                data: @json($category_values->values()),
                backgroundColor: 'rgba(79, 70, 229, 0.6)',
                borderColor: 'rgb(79, 70, 229)',
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: { legend: { display: false } }
        }
    });
</script>
@endsection
