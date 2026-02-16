@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('inventory.movements.index') }}" class="text-sm text-gray-500 hover:text-gray-700">
                    <i class="fas fa-arrow-left mr-1"></i>Back to Log
                </a>
                <span class="text-gray-300">|</span>
                <span class="text-sm text-gray-400">Movement #{{ $movement->id }}</span>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-3">
                Movement Details
                <span class="px-3 py-1 text-sm font-semibold rounded-full bg-{{ $movement->movement_type_color }}-100 text-{{ $movement->movement_type_color }}-800">
                    {{ $movement->getMovementTypeLabel() }}
                </span>
            </h1>
        </div>
        <div class="flex gap-3">
            <button onclick="window.print()" class="bg-white text-gray-700 border border-gray-300 px-4 py-2 rounded-md hover:bg-gray-50 text-sm font-medium shadow-sm transition-colors">
                <i class="fas fa-print mr-2"></i>Print Record
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Main Details -->
        <div class="md:col-span-2 space-y-6">
            <!-- Product Information -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                    <h3 class="text-base font-medium text-gray-900">Product Information</h3>
                </div>
                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <div class="p-3 bg-indigo-50 rounded-lg">
                            <i class="fas fa-box text-indigo-600 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-lg font-bold text-gray-900">{{ $movement->inventory->product_name ?? 'Unknown Product' }}</h4>
                            <div class="mt-1 flex flex-wrap gap-x-4 gap-y-2 text-sm text-gray-500">
                                <span><span class="font-medium text-gray-700">Code:</span> {{ $movement->inventory->code ?? 'N/A' }}</span>
                                <span><span class="font-medium text-gray-700">Category:</span> {{ $movement->inventory->category ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>

                    @if($movement->batch)
                        <div class="mt-6 border-t border-gray-100 pt-4">
                            <h4 class="text-sm font-medium text-gray-900 mb-3">Batch Details</h4>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="block text-gray-500 text-xs">Batch Number</span>
                                    <span class="font-mono text-gray-900">{{ $movement->batch->batch_number }}</span>
                                </div>
                                @if($movement->batch->serial_number)
                                <div>
                                    <span class="block text-gray-500 text-xs">Serial Number</span>
                                    <span class="font-mono text-gray-900">{{ $movement->batch->serial_number }}</span>
                                </div>
                                @endif
                                @if($movement->batch->expiry_date)
                                <div>
                                    <span class="block text-gray-500 text-xs">Expiry Date</span>
                                    <span class="{{ \Carbon\Carbon::parse($movement->batch->expiry_date)->isPast() ? 'text-red-600 font-bold' : 'text-gray-900' }}">
                                        {{ \Carbon\Carbon::parse($movement->batch->expiry_date)->format('M d, Y') }}
                                    </span>
                                </div>
                                @endif
                                <div>
                                    <span class="block text-gray-500 text-xs">Status</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-{{ $movement->batch->status_color }}-100 text-{{ $movement->batch->status_color }}-800 capitalize">
                                        {{ str_replace('_', ' ', $movement->batch->status) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Movement Logic -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                    <h3 class="text-base font-medium text-gray-900">Movement Specifics</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <span class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Quantity Change</span>
                            <div class="flex items-center gap-4">
                                <div class="text-4xl font-bold {{ $movement->quantity > 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}
                                </div>
                                <div class="text-sm text-gray-500 border-l border-gray-200 pl-4">
                                    <div class="flex justify-between gap-4">
                                        <span>Before:</span>
                                        <span class="font-mono">{{ $movement->quantity_before ?? '-' }}</span>
                                    </div>
                                    <div class="flex justify-between gap-4">
                                        <span>After:</span>
                                        <span class="font-mono font-bold">{{ $movement->quantity_after ?? '-' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <span class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Location Change</span>
                            <div class="flex items-center gap-3 text-gray-900">
                                <div class="flex-1 bg-gray-50 p-3 rounded text-center">
                                    <div class="text-xs text-gray-500 mb-1">From</div>
                                    <div class="font-medium truncate" title="{{ $movement->fromLocation->name ?? 'None' }}">
                                        {{ $movement->fromLocation->name ?? '—' }}
                                    </div>
                                </div>
                                <i class="fas fa-arrow-right text-gray-400"></i>
                                <div class="flex-1 bg-gray-50 p-3 rounded text-center">
                                    <div class="text-xs text-gray-500 mb-1">To</div>
                                    <div class="font-medium truncate" title="{{ $movement->toLocation->name ?? 'None' }}">
                                        {{ $movement->toLocation->name ?? '—' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($movement->reason || $movement->notes)
                        <div class="mt-6 border-t border-gray-100 pt-4 bg-yellow-50 rounded-md p-4">
                            @if($movement->reason)
                                <div class="mb-2">
                                    <span class="font-bold text-gray-700 text-xs uppercase">Reason:</span>
                                    <p class="text-gray-900">{{ $movement->reason }}</p>
                                </div>
                            @endif
                            @if($movement->notes)
                                <div>
                                    <span class="font-bold text-gray-700 text-xs uppercase">Notes:</span>
                                    <p class="text-gray-900 italic">{{ $movement->notes }}</p>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar Details -->
        <div class="space-y-6">
            <!-- Financials -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                    <h3 class="text-base font-medium text-gray-900">Financial Impact</h3>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex justify-between items-end border-b border-gray-100 pb-3">
                        <span class="text-sm text-gray-600">Unit Cost</span>
                        <span class="text-lg font-medium text-gray-900">{{ number_format($movement->unit_cost, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-end">
                        <span class="text-sm font-bold text-gray-700">Total Value</span>
                        <span class="text-2xl font-bold text-gray-900">{{ number_format($movement->total_value, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Audit Info -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                    <h3 class="text-base font-medium text-gray-900">Audit Trail</h3>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <span class="block text-xs text-gray-500 mb-1">Date & Time</span>
                        <div class="flex items-center text-gray-900">
                            <i class="far fa-calendar-alt text-gray-400 mr-2"></i>
                            {{ $movement->created_at->format('M d, Y h:i A') }}
                        </div>
                    </div>
                    
                    <div>
                        <span class="block text-xs text-gray-500 mb-1">Performed By</span>
                        <div class="flex items-center text-gray-900">
                            <i class="far fa-user text-gray-400 mr-2"></i>
                            {{ $movement->performedBy->name ?? 'System' }}
                        </div>
                    </div>

                    @if($movement->approvedBy)
                        <div>
                            <span class="block text-xs text-gray-500 mb-1">Approved By</span>
                            <div class="flex items-center text-green-700 bg-green-50 p-2 rounded">
                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                <div>
                                    <div class="font-medium">{{ $movement->approvedBy->name }}</div>
                                    <div class="text-xs">{{ $movement->approved_at->format('M d, Y') }}</div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($movement->reference_type)
                        <div class="pt-3 border-t border-gray-100">
                            <span class="block text-xs text-gray-500 mb-1">Source Document</span>
                            <div class="flex items-center text-indigo-600 bg-indigo-50 p-2 rounded">
                                <i class="fas fa-file-alt text-indigo-400 mr-2"></i>
                                <div>
                                    <div class="font-medium capitalize">{{ str_replace('_', ' ', $movement->reference_type) }}</div>
                                    <div class="text-xs text-indigo-500">ID: #{{ $movement->reference_id }}</div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
