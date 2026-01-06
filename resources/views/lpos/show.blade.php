@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-start mb-6">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-gray-900">LPO #{{ $lpo->lpo_number }}</h1>
                <span class="px-2 py-0.5 text-xs font-semibold rounded-full 
                    {{ $lpo->status === 'active' ? 'bg-green-100 text-green-800' : '' }}
                    {{ $lpo->status === 'completed' ? 'bg-blue-100 text-blue-800' : '' }}
                    {{ $lpo->status === 'expired' ? 'bg-red-100 text-red-800' : '' }}">
                    {{ ucfirst($lpo->status) }}
                </span>
            </div>
            <p class="text-gray-600 mt-1">Customer: <span class="font-medium text-gray-900">{{ $lpo->customer->name }}</span></p>
        </div>
        <div class="text-right">
             <a href="{{ route('lpos.index') }}" class="text-sm text-gray-500 hover:text-gray-900">← Back to List</a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white p-6 rounded-lg shadow border-l-4 border-indigo-500">
                    <p class="text-sm text-gray-500 font-medium">Total Amount</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($lpo->amount, 2) }}</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow border-l-4 {{ $lpo->remaining_balance < ($lpo->amount * 0.1) ? 'border-red-500' : 'border-green-500' }}">
                    <p class="text-sm text-gray-500 font-medium">Remaining Balance</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($lpo->remaining_balance, 2) }}</p>
                </div>
            </div>

            <!-- Linked Sales History -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b">
                    <h3 class="font-bold text-gray-800">Linked Sales / Invoices</h3>
                </div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice #</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($lpo->sales as $sale)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ \Carbon\Carbon::parse($sale->created_at)->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                {{ $sale->invoice_number ?? 'REC-'.$sale->id }}
                            </td>
                            <td class="px-6 py-4 text-sm text-right text-gray-900">
                                {{ number_format($sale->total, 2) }}
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-2 text-xs rounded-full bg-gray-100">{{ $sale->payment_status }}</span>
                            </td>
                             <td class="px-6 py-4 text-sm text-right">
                                <a href="{{ route('receipts.print', $sale->id) }}" target="_blank" class="text-indigo-600 hover:underline">View</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                No sales linked to this LPO yet.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sidebar Info -->
        <div class="space-y-6">
            <!-- Details Card -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="font-bold text-gray-800 mb-4 border-b pb-2">Details</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Created:</dt>
                        <dd class="text-gray-900">{{ $lpo->created_at->format('M d, Y') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Valid From:</dt>
                        <dd class="text-gray-900">{{ $lpo->valid_from ? $lpo->valid_from->format('M d, Y') : '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Valid Until:</dt>
                        <dd class="text-gray-900">{{ $lpo->valid_until ? $lpo->valid_until->format('M d, Y') : 'No Expiry' }}</dd>
                    </div>
                </dl>
                @if($lpo->description)
                <div class="mt-4 pt-4 border-t">
                    <p class="text-xs text-gray-500 uppercase font-bold mb-1">Notes</p>
                    <p class="text-sm text-gray-700 bg-gray-50 p-2 rounded">{{ $lpo->description }}</p>
                </div>
                @endif
            </div>

            <!-- Document Card -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="font-bold text-gray-800 mb-4 border-b pb-2">Document</h3>
                @if($lpo->document_path)
                    <div class="flex items-center justify-between bg-gray-50 p-3 rounded mb-4">
                        <div class="flex items-center">
                            <svg class="w-6 h-6 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            <span class="text-sm font-medium text-gray-700 truncate max-w-[150px]">LPO Document</span>
                        </div>
                    </div>
                    <a href="{{ Storage::url($lpo->document_path) }}" target="_blank" class="block w-full text-center bg-gray-800 text-white py-2 rounded hover:bg-gray-700 transition">
                        Download / View
                    </a>
                @else
                    <div class="text-center py-6 text-gray-400">
                        <svg class="w-10 h-10 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <p class="text-sm">No document uploaded.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
