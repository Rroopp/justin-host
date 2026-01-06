@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Stock Takes</h1>
            <p class="mt-2 text-sm text-gray-600">Physical inventory count and reconciliation</p>
        </div>
        <a href="{{ route('stock-takes.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
            <i class="fas fa-plus mr-2"></i>New Stock Take
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg p-4 mb-6">
        <form method="GET" action="{{ route('stock-takes.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full rounded-md border-gray-300" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="reconciled" {{ request('status') === 'reconciled' ? 'selected' : '' }}>Reconciled</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full rounded-md border-gray-300" onchange="this.form.submit()">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full rounded-md border-gray-300" onchange="this.form.submit()">
            </div>
            <div class="flex items-end">
                <a href="{{ route('stock-takes.index') }}" class="text-indigo-600 hover:text-indigo-900 text-sm">Clear Filters</a>
            </div>
        </form>
    </div>

    <!-- Stock Takes Table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Items</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created By</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($stockTakes as $stockTake)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $stockTake->reference_number }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $stockTake->date->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $stockTake->items->count() }} items
                            @if($stockTake->variance_count > 0)
                                <span class="text-orange-600">({{ $stockTake->variance_count }} variances)</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                @if($stockTake->status === 'draft') bg-gray-100 text-gray-800
                                @elseif($stockTake->status === 'in_progress') bg-blue-100 text-blue-800
                                @elseif($stockTake->status === 'completed') bg-yellow-100 text-yellow-800
                                @else bg-green-100 text-green-800
                                @endif">
                                {{ ucfirst(str_replace('_', ' ', $stockTake->status)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $stockTake->creator->full_name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex flex-col items-end gap-2">
                                <a href="{{ route('stock-takes.show', $stockTake) }}" class="text-indigo-600 hover:text-indigo-900">View</a>
                                <a href="{{ route('stock-takes.sheet', $stockTake) }}" target="_blank" class="text-blue-600 hover:text-blue-900">Print Sheet</a>
                                <a href="{{ route('stock-takes.export', $stockTake) }}" class="text-green-600 hover:text-green-900">Export CSV</a>
                                @if($stockTake->canReconcile())
                                    <form action="{{ route('stock-takes.reconcile', $stockTake) }}" method="POST" onsubmit="return confirm('Are you sure you want to reconcile this stock take? This will update inventory quantities.')">
                                        @csrf
                                        <button type="submit" class="text-orange-600 hover:text-orange-900">Reconcile</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            No stock takes found. <a href="{{ route('stock-takes.create') }}" class="text-indigo-600 hover:text-indigo-900">Create one now</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $stockTakes->links() }}
    </div>
</div>
@endsection
