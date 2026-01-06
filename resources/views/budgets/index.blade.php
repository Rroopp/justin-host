@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Budgets</h1>
            <p class="text-gray-600 mt-1">Manage and track your company budgets</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('budgets.dashboard') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-chart-line mr-2"></i>Dashboard
            </a>
            <a href="{{ route('budgets.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                <i class="fas fa-plus mr-2"></i>New Budget
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-indigo-100 rounded-md p-3">
                    <i class="fas fa-file-invoice-dollar text-indigo-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Budgets</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['total_budgets'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Active Budgets</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['active_budgets'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-blue-100 rounded-md p-3">
                    <i class="fas fa-wallet text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Allocated</p>
                    <p class="text-2xl font-bold text-gray-900">KES {{ number_format($stats['total_allocated'], 0) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-orange-100 rounded-md p-3">
                    <i class="fas fa-chart-pie text-orange-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Avg Utilization</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['avg_utilization'] ?? 0, 1) }}%</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow mb-6 p-4">
        <form method="GET" action="{{ route('budgets.index') }}" class="flex gap-4">
            <select name="status" class="rounded-md border-gray-300 text-sm">
                <option value="">All Statuses</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Archived</option>
            </select>
            <select name="period_type" class="rounded-md border-gray-300 text-sm">
                <option value="">All Periods</option>
                <option value="annual" {{ request('period_type') === 'annual' ? 'selected' : '' }}>Annual</option>
                <option value="quarterly" {{ request('period_type') === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                <option value="monthly" {{ request('period_type') === 'monthly' ? 'selected' : '' }}>Monthly</option>
            </select>
            <input type="number" name="year" placeholder="Year" value="{{ request('year') }}" class="rounded-md border-gray-300 text-sm" min="2020" max="2030">
            <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 text-sm font-medium">
                <i class="fas fa-filter mr-2"></i>Filter
            </button>
            @if(request()->hasAny(['status', 'period_type', 'year']))
                <a href="{{ route('budgets.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 text-sm font-medium">
                    <i class="fas fa-times mr-2"></i>Clear
                </a>
            @endif
        </form>
    </div>

    <!-- Budgets Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Budget</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Allocated</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Spent</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utilization</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($budgets as $budget)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">{{ $budget->name }}</div>
                        <div class="text-xs text-gray-500">{{ $budget->reference_number }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ ucfirst($budget->period_type) }}</div>
                        <div class="text-xs text-gray-500">{{ $budget->start_date->format('M d, Y') }} - {{ $budget->end_date->format('M d, Y') }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        KES {{ number_format($budget->total_allocated, 0) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        KES {{ number_format($budget->total_spent, 0) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @php
                            $utilization = $budget->getUtilizationPercentage();
                            $color = $utilization > 100 ? 'red' : ($utilization > 80 ? 'yellow' : 'green');
                        @endphp
                        <div class="flex items-center">
                            <div class="w-full bg-gray-200 rounded-full h-2 mr-2">
                                <div class="bg-{{ $color }}-600 h-2 rounded-full" style="width: {{ min($utilization, 100) }}%"></div>
                            </div>
                            <span class="text-sm font-medium text-gray-900">{{ number_format($utilization, 1) }}%</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            @if($budget->status === 'active') bg-green-100 text-green-800
                            @elseif($budget->status === 'draft') bg-gray-100 text-gray-800
                            @elseif($budget->status === 'completed') bg-blue-100 text-blue-800
                            @else bg-gray-100 text-gray-600
                            @endif">
                            {{ ucfirst($budget->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="{{ route('budgets.show', $budget) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">View</a>
                        @if($budget->canEdit())
                            <a href="{{ route('budgets.edit', $budget) }}" class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>
                        @endif
                        @if($budget->canApprove() && auth()->user()->role === 'admin')
                            <form action="{{ route('budgets.approve', $budget) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-green-600 hover:text-green-900">Approve</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-4 text-gray-300"></i>
                        <p class="text-lg">No budgets found</p>
                        <a href="{{ route('budgets.create') }}" class="text-indigo-600 hover:text-indigo-900 mt-2 inline-block">Create your first budget</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($budgets->hasPages())
    <div class="mt-6">
        {{ $budgets->links() }}
    </div>
    @endif
</div>
@endsection
