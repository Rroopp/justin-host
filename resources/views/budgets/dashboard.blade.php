@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Budget Dashboard</h1>
            <p class="text-gray-600 mt-1">Overview of all active budgets</p>
        </div>
        <a href="{{ route('budgets.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            <i class="fas fa-list mr-2"></i>View All Budgets
        </a>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm font-medium text-gray-500 mb-2">Total Allocated</div>
            <div class="text-2xl font-bold text-gray-900">KES {{ number_format($stats['total_allocated'], 0) }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm font-medium text-gray-500 mb-2">Total Spent</div>
            <div class="text-2xl font-bold text-gray-900">KES {{ number_format($stats['total_spent'], 0) }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm font-medium text-gray-500 mb-2">Remaining</div>
            <div class="text-2xl font-bold text-green-600">KES {{ number_format($stats['total_remaining'], 0) }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm font-medium text-gray-500 mb-2">Avg Utilization</div>
            <div class="text-2xl font-bold text-blue-600">{{ number_format($stats['avg_utilization'] ?? 0, 1) }}%</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm font-medium text-gray-500 mb-2">Over Budget</div>
            <div class="text-2xl font-bold text-red-600">{{ $stats['over_budget_count'] }}</div>
        </div>
    </div>

    <!-- Active Budgets Overview -->
    <div class="bg-white rounded-lg shadow mb-8">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Active Budgets</h2>
        </div>
        <div class="p-6">
            @forelse($activeBudgets as $budget)
            <div class="mb-6 last:mb-0">
                <div class="flex justify-between items-center mb-2">
                    <div>
                        <h3 class="text-sm font-medium text-gray-900">{{ $budget->name }}</h3>
                        <p class="text-xs text-gray-500">{{ $budget->start_date->format('M d') }} - {{ $budget->end_date->format('M d, Y') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-gray-900">{{ number_format($budget->getUtilizationPercentage(), 1) }}%</p>
                        <p class="text-xs text-gray-500">KES {{ number_format($budget->total_spent, 0) }} / {{ number_format($budget->total_allocated, 0) }}</p>
                    </div>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                    @php
                        $utilization = $budget->getUtilizationPercentage();
                        $color = $utilization > 100 ? 'bg-red-600' : ($utilization > 80 ? 'bg-yellow-500' : 'bg-green-600');
                    @endphp
                    <div class="{{ $color }} h-3 rounded-full transition-all" style="width: {{ min($utilization, 100) }}%"></div>
                </div>
                <div class="mt-2 flex justify-between text-xs text-gray-600">
                    <span>{{ $budget->lineItems->count() }} line items</span>
                    <a href="{{ route('budgets.show', $budget) }}" class="text-indigo-600 hover:text-indigo-900">View Details →</a>
                </div>
            </div>
            @empty
            <div class="text-center py-12 text-gray-500">
                <i class="fas fa-chart-line text-4xl mb-4 text-gray-300"></i>
                <p>No active budgets</p>
                <a href="{{ route('budgets.create') }}" class="text-indigo-600 hover:text-indigo-900 mt-2 inline-block">Create a budget</a>
            </div>
            @endforelse
        </div>
    </div>

    <!-- Over Budget Categories -->
    @if($overBudgetCategories->count() > 0)
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Top Over-Budget Categories</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Budget</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Allocated</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Spent</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Variance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($overBudgetCategories as $item)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $item->category }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $item->budget->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">KES {{ number_format($item->allocated_amount, 0) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 font-semibold">KES {{ number_format($item->spent_amount, 0) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 font-bold">
                            +KES {{ number_format($item->getVariance(), 0) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
