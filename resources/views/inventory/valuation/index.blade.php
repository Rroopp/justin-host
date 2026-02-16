@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8 flex flex-col md:flex-row justify-between items-end gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Inventory Valuation</h1>
            <p class="text-gray-600 mt-1">Real-time stock value analysis and insights</p>
        </div>
        <button onclick="window.print()" class="bg-white text-gray-700 border border-gray-300 px-4 py-2 rounded-md hover:bg-gray-50 text-sm font-medium shadow-sm transition-colors">
            <i class="fas fa-print mr-2"></i>Print Report
        </button>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 p-6 rounded-lg shadow-lg text-white">
            <h3 class="text-indigo-100 text-xs font-bold uppercase tracking-wider">Total Inventory Value</h3>
            <p class="text-4xl font-bold mt-2">{{ number_format($totalValue, 2) }}</p>
            <p class="text-xs text-indigo-100 mt-1">Available stock only</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-green-500">
            <h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider">Total Units</h3>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($totalUnits) }}</p>
            <p class="text-xs text-gray-400 mt-1">In stock</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-blue-500">
            <h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider">Unique Products</h3>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($uniqueProducts) }}</p>
            <p class="text-xs text-gray-400 mt-1">SKUs in inventory</p>
        </div>
    </div>

    <!-- Ownership Breakdown -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Valuation by Ownership</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($byOwnership as $ownership)
                <div class="border rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-sm font-medium text-gray-700">
                            @if($ownership->ownership_type === 'company_owned')
                                <i class="fas fa-building text-indigo-500 mr-2"></i>Company Owned
                            @elseif($ownership->ownership_type === 'consigned')
                                <i class="fas fa-handshake text-purple-500 mr-2"></i>Consigned
                            @else
                                <i class="fas fa-exchange-alt text-orange-500 mr-2"></i>Loaned
                            @endif
                        </h4>
                    </div>
                    <div class="text-2xl font-bold text-gray-900">{{ number_format($ownership->value, 2) }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ number_format($ownership->units) }} units • {{ $ownership->products }} products</div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Valuation by Location and Category -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- By Location -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg font-medium text-gray-900">Valuation by Location</h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    @foreach($byLocation as $location)
                        <div class="border-l-4 border-indigo-500 pl-4 py-2">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-medium text-gray-900">{{ $location->location }}</h4>
                                    <p class="text-xs text-gray-500">{{ number_format($location->units) }} units • {{ $location->products }} products</p>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-bold text-gray-900">{{ number_format($location->value, 2) }}</div>
                                    <div class="text-xs text-gray-500">{{ number_format(($location->value / $totalValue) * 100, 1) }}%</div>
                                </div>
                            </div>
                            <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-indigo-500 h-2 rounded-full" style="width: {{ ($location->value / $totalValue) * 100 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- By Category -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg font-medium text-gray-900">Valuation by Category</h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    @foreach($byCategory as $category)
                        <div class="border-l-4 border-green-500 pl-4 py-2">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-medium text-gray-900">{{ $category->category ?? 'Uncategorized' }}</h4>
                                    <p class="text-xs text-gray-500">{{ number_format($category->units) }} units • {{ $category->products }} products</p>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-bold text-gray-900">{{ number_format($category->value, 2) }}</div>
                                    <div class="text-xs text-gray-500">{{ number_format(($category->value / $totalValue) * 100, 1) }}%</div>
                                </div>
                            </div>
                            <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-500 h-2 rounded-full" style="width: {{ ($category->value / $totalValue) * 100 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Aging Analysis -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Inventory Aging Analysis</h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="border rounded-lg p-4 bg-green-50">
                <div class="text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-check-circle text-green-500 mr-2"></i>Fresh (< 90 days)
                </div>
                <div class="text-2xl font-bold text-green-600">{{ number_format($aging['fresh']->value ?? 0, 2) }}</div>
                <div class="text-xs text-gray-500 mt-1">{{ number_format($aging['fresh']->units ?? 0) }} units</div>
            </div>
            <div class="border rounded-lg p-4 bg-yellow-50">
                <div class="text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-clock text-yellow-500 mr-2"></i>90-180 days
                </div>
                <div class="text-2xl font-bold text-yellow-600">{{ number_format($aging['aging_90']->value ?? 0, 2) }}</div>
                <div class="text-xs text-gray-500 mt-1">{{ number_format($aging['aging_90']->units ?? 0) }} units</div>
            </div>
            <div class="border rounded-lg p-4 bg-orange-50">
                <div class="text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-exclamation-triangle text-orange-500 mr-2"></i>180-365 days
                </div>
                <div class="text-2xl font-bold text-orange-600">{{ number_format($aging['aging_180']->value ?? 0, 2) }}</div>
                <div class="text-xs text-gray-500 mt-1">{{ number_format($aging['aging_180']->units ?? 0) }} units</div>
            </div>
            <div class="border rounded-lg p-4 bg-red-50">
                <div class="text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-times-circle text-red-500 mr-2"></i>Over 1 year
                </div>
                <div class="text-2xl font-bold text-red-600">{{ number_format($aging['aging_365']->value ?? 0, 2) }}</div>
                <div class="text-xs text-gray-500 mt-1">{{ number_format($aging['aging_365']->units ?? 0) }} units</div>
            </div>
        </div>
    </div>

    <!-- Expiry Alert -->
    @if($expiringSoon && $expiringSoon->value > 0)
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-8">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-yellow-400 text-xl"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">Stock Expiring Soon</h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p><strong>{{ number_format($expiringSoon->value, 2) }}</strong> worth of stock ({{ number_format($expiringSoon->units) }} units) will expire in the next 90 days.</p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Top 10 Most Valuable Products -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-medium text-gray-900">Top 10 Most Valuable Products</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rank</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Units</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Value</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">% of Total</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($topProducts as $index => $product)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 font-bold text-sm">
                                    {{ $index + 1 }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $product->product_name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $product->code }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500">
                                {{ number_format($product->units) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-gray-900">
                                {{ number_format($product->value, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500">
                                {{ number_format(($product->value / $totalValue) * 100, 2) }}%
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
