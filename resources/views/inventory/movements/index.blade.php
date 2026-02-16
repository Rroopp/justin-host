@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8 flex flex-col md:flex-row justify-between items-end gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Inventory Movement History</h1>
            <p class="text-gray-600 mt-1">Complete audit trail of all stock movements</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <form action="{{ route('inventory.movements.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <!-- Persist context filters -->
            @if(request('inventory_id'))
                <input type="hidden" name="inventory_id" value="{{ request('inventory_id') }}">
            @endif
            @if(request('batch_id'))
                <input type="hidden" name="batch_id" value="{{ request('batch_id') }}">
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Movement Type</label>
                <select name="movement_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">All Types</option>
                    @foreach($movementTypes as $value => $label)
                        <option value="{{ $value }}" {{ $movementType == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                <select name="location_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">All Locations</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" {{ $locationId == $location->id ? 'selected' : '' }}>{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 text-sm font-medium shadow-sm transition-colors">
                    <i class="fas fa-filter mr-2"></i>Apply
                </button>
                <a href="{{ route('inventory.movements.index') }}" class="px-3 py-2 bg-gray-100 text-gray-600 rounded-md hover:bg-gray-200 text-sm font-medium transition-colors" title="Clear Filters">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>

    @if(request('inventory_id') && $movements->isNotEmpty())
        <div class="mb-6 bg-blue-50 border border-blue-200 rounded-md p-4 flex justify-between items-center">
            <div class="flex items-center">
                <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                <span class="text-blue-700">
                    Showing history for <strong>{{ $movements->first()->inventory->product_name ?? 'Product #' . request('inventory_id') }}</strong>
                    @if(request('batch_id'))
                        (Batch: <strong>{{ $movements->first()->batch->batch_number ?? 'Batch #' . request('batch_id') }}</strong>)
                    @endif
                </span>
            </div>
            <a href="{{ route('inventory.movements.index') }}" class="text-sm text-blue-600 hover:text-blue-800 hover:underline">Clear Scope</a>
        </div>
    @endif

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-blue-500">
            <h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider">Total Movements</h3>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($totalMovements) }}</p>
            <p class="text-xs text-gray-400 mt-1">In selected period</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-green-500">
            <h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider">Stock Additions</h3>
            <p class="text-3xl font-bold text-green-600 mt-2">+{{ number_format($additions) }}</p>
            <p class="text-xs text-gray-400 mt-1">Units added</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-red-500">
            <h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider">Stock Reductions</h3>
            <p class="text-3xl font-bold text-red-600 mt-2">{{ number_format($reductions) }}</p>
            <p class="text-xs text-gray-400 mt-1">Units removed</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-purple-500">
            <h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider">Total Value</h3>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($totalValue, 2) }}</p>
            <p class="text-xs text-gray-400 mt-1">Movement value</p>
        </div>
    </div>

    <!-- Movement Type Breakdown -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Movement Type Breakdown</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            @foreach($movementsByType as $type)
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                    <div class="text-2xl font-bold text-indigo-600">{{ $type->count }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ $movementTypes[$type->movement_type] ?? $type->movement_type }}</div>
                    <div class="text-xs text-gray-400">{{ number_format($type->total_qty) }} units</div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Movements Table -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-900">Movement Log</h3>
            <span class="text-sm text-gray-500">{{ $movements->total() }} movements</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date/Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">From → To</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Performed By</th>
                        <th class="relative px-6 py-3">
                            <span class="sr-only">View</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($movements as $movement)
                        <tr class="hover:bg-gray-50 transition-colors cursor-pointer" onclick="window.location='{{ route('inventory.movements.show', $movement->id) }}'">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $movement->created_at->format('M d, Y') }}
                                <div class="text-xs text-gray-500">{{ $movement->created_at->format('h:i A') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-{{ $movement->movement_type_color }}-100 text-{{ $movement->movement_type_color }}-800">
                                    {{ $movement->getMovementTypeLabel() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $movement->inventory->product_name ?? 'N/A' }}</div>
                                @if($movement->batch)
                                    <div class="text-xs text-gray-500">Batch: {{ $movement->batch->batch_number }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="text-sm font-bold {{ $movement->quantity > 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @if($movement->fromLocation || $movement->toLocation)
                                    <div class="flex items-center gap-2">
                                        <span>{{ $movement->fromLocation->name ?? '—' }}</span>
                                        <i class="fas fa-arrow-right text-gray-400"></i>
                                        <span>{{ $movement->toLocation->name ?? '—' }}</span>
                                    </div>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-gray-900">
                                {{ number_format($movement->total_value ?? 0, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $movement->performedBy->name ?? 'System' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-exchange-alt text-4xl mb-3 text-gray-300"></i>
                                <p>No movements found for the selected filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        @if($movements->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $movements->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
