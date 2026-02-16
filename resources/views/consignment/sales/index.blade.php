@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Surgery Sales Reconciliation</h1>
        <a href="{{ route('consignment.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
            ← Back to Dashboard
        </a>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <a href="{{ route('sales.consignments.index', ['status' => 'pending']) }}" 
               class="{{ $status === 'pending' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Pending Reconciliation
            </a>
            <a href="{{ route('sales.consignments.index', ['status' => 'reconciled']) }}" 
               class="{{ $status === 'reconciled' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Reconciled History
            </a>
        </nav>
    </div>

    <!-- Info Banner -->
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-blue-700">
                    Below are "Consignment" sales. 
                    @if($status === 'pending')
                        Click <strong>Reconcile</strong> to confirm Used vs Returned items. This will generate the final invoice.
                    @else
                        These sales have been reconciled and invoices generated.
                    @endif
                </p>
            </div>
        </div>
    </div>

    <!-- Sales Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference / Invoice</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer / Patient</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Value</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($sales as $sale)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $sale->timestamp->format('Y-m-d H:i') }}
                            @if($sale->reconciled_at)
                                <div class="text-xs text-green-600" title="Reconciled Date">Rec: {{ $sale->reconciled_at->format('Y-m-d') }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            #{{ $sale->id }}
                            @if($sale->invoice_number)
                                <span class="text-indigo-600 font-mono text-xs block">{{ $sale->invoice_number }}</span>
                            @endif
                            @if($sale->lpo_number)
                                <span class="text-gray-500 text-xs block">LPO: {{ $sale->lpo_number }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <div class="font-medium text-gray-900">{{ $sale->customer_name ?? 'Guest' }}</div>
                            @if($sale->patient_name)
                                <div class="text-xs">Patient: {{ $sale->patient_name }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ number_format($sale->total, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($sale->is_reconciled)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Reconciled
                                </span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    Pending
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            @if(!$sale->is_reconciled)
                                <a href="{{ route('sales.consignments.reconcile', $sale->id) }}" class="text-white hover:text-white bg-indigo-600 hover:bg-indigo-700 px-3 py-1 rounded shadow-sm text-xs uppercase tracking-wider">
                                    Reconcile
                                </a>
                            @else
                                <a href="{{ route('sales.invoices.show', $sale->id) }}" target="_blank" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                    View Invoice
                                </a>
                                {{-- Optional: Edit reconciliation? Maybe dangerous --}}
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            No sales found in this category.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $sales->appends(['status' => $status])->links() }}
    </div>
</div>
@endsection
