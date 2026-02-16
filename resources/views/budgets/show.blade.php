@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ $budget->name }}</h1>
                <p class="text-gray-600 mt-1">{{ $budget->reference_number }} • {{ ucfirst($budget->period_type) }} Budget</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('budgets.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-arrow-left mr-2"></i>Back
                </a>
                @if($budget->canEdit())
                    <a href="{{ route('budgets.edit', $budget) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <i class="fas fa-edit mr-2"></i>Edit
                    </a>
                @endif
                @if($budget->canApprove() && auth()->user()->role === 'admin')
                    <form action="{{ route('budgets.approve', $budget) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                            <i class="fas fa-check mr-2"></i>Approve
                        </button>
                    </form>
                @endif
                <!-- Export Buttons -->
                <a href="{{ route('budgets.export', ['budget' => $budget, 'format' => 'pdf']) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-file-pdf mr-2 text-red-600"></i>Export PDF
                </a>
                <a href="{{ route('budgets.export', ['budget' => $budget, 'format' => 'csv']) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-file-excel mr-2 text-green-600"></i>Export CSV
                </a>
            </div>
        </div>
    </div>

    <!-- Status & Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm font-medium text-gray-500 mb-2">Status</div>
            <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full 
                @if($budget->status === 'active') bg-green-100 text-green-800
                @elseif($budget->status === 'draft') bg-gray-100 text-gray-800
                @elseif($budget->status === 'completed') bg-blue-100 text-blue-800
                @else bg-gray-100 text-gray-600
                @endif">
                {{ ucfirst($budget->status) }}
            </span>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm font-medium text-gray-500 mb-2">Total Allocated</div>
            <div class="text-2xl font-bold text-gray-900">KES {{ number_format($budget->total_allocated, 0) }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm font-medium text-gray-500 mb-2">Total Spent</div>
            <div class="text-2xl font-bold text-gray-900">KES {{ number_format($budget->total_spent, 0) }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm font-medium text-gray-500 mb-2">Utilization</div>
            <div class="text-2xl font-bold {{ $budget->isOverBudget() ? 'text-red-600' : 'text-green-600' }}">
                {{ number_format($budget->getUtilizationPercentage(), 1) }}%
            </div>
        </div>
    </div>

    <!-- Budget Details -->
    <div class="bg-white rounded-lg shadow mb-6 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Budget Details</h2>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-500">Period</p>
                <p class="text-sm font-medium text-gray-900">{{ $budget->start_date->format('M d, Y') }} - {{ $budget->end_date->format('M d, Y') }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Created By</p>
                <p class="text-sm font-medium text-gray-900">{{ $budget->creator->full_name ?? 'Unknown' }}</p>
            </div>
            @if($budget->approved_by)
            <div>
                <p class="text-sm text-gray-500">Approved By</p>
                <p class="text-sm font-medium text-gray-900">{{ $budget->approver->full_name ?? 'Unknown' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Approved At</p>
                <p class="text-sm font-medium text-gray-900">{{ $budget->approved_at->format('M d, Y H:i') }}</p>
            </div>
            @endif
        </div>
        @if($budget->description)
        <div class="mt-4">
            <p class="text-sm text-gray-500">Description</p>
            <p class="text-sm text-gray-900 mt-1">{{ $budget->description }}</p>
        </div>
        @endif
    </div>

    <!-- Line Items & Variance Analysis -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Budget Line Items & Variance Analysis</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Allocated</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Spent</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Remaining</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Utilization</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Variance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($varianceData as $data)
                    <tr class="{{ $data['status'] === 'over' ? 'bg-red-50' : '' }}">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $data['category'] }}</div>
                        </td>
                        <td class="px-6 py-4 text-right text-sm text-gray-900">
                            KES {{ number_format($data['allocated'], 0) }}
                        </td>
                        <td class="px-6 py-4 text-right text-sm text-gray-900">
                            KES {{ number_format($data['spent'], 0) }}
                        </td>
                        <td class="px-6 py-4 text-right text-sm {{ $data['remaining'] < 0 ? 'text-red-600 font-semibold' : 'text-green-600' }}">
                            KES {{ number_format($data['remaining'], 0) }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end">
                                <div class="w-24 bg-gray-200 rounded-full h-2 mr-2">
                                    <div class="{{ $data['utilization'] > 100 ? 'bg-red-600' : ($data['utilization'] > 80 ? 'bg-yellow-500' : 'bg-green-600') }} h-2 rounded-full" style="width: {{ min($data['utilization'], 100) }}%"></div>
                                </div>
                                <span class="text-sm font-medium">{{ number_format($data['utilization'], 1) }}%</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-semibold {{ $data['variance'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                            {{ $data['variance'] > 0 ? '+' : '' }}KES {{ number_format($data['variance'], 0) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td class="px-6 py-4 text-sm font-bold text-gray-900">TOTAL</td>
                        <td class="px-6 py-4 text-right text-sm font-bold text-gray-900">KES {{ number_format($budget->total_allocated, 0) }}</td>
                        <td class="px-6 py-4 text-right text-sm font-bold text-gray-900">KES {{ number_format($budget->total_spent, 0) }}</td>
                        <td class="px-6 py-4 text-right text-sm font-bold {{ $budget->total_remaining < 0 ? 'text-red-600' : 'text-green-600' }}">KES {{ number_format($budget->total_remaining, 0) }}</td>
                        <td class="px-6 py-4 text-right text-sm font-bold">{{ number_format($budget->getUtilizationPercentage(), 1) }}%</td>
                        <td class="px-6 py-4 text-right text-sm font-bold {{ $budget->getVariance() > 0 ? 'text-red-600' : 'text-green-600' }}">
                            {{ $budget->getVariance() > 0 ? '+' : '' }}KES {{ number_format($budget->getVariance(), 0) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
