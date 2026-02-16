@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6" x-data="{
    calculateTotal() {
        let total = 0;
        document.querySelectorAll('.usage-input').forEach(input => {
            let qty = parseInt(input.value) || 0;
            let price = parseFloat(input.dataset.price) || 0;
            total += qty * price;
        });
        this.grandTotal = total.toFixed(2);
    },
    grandTotal: 0
}">
    <div class="mb-6">
        <a href="{{ route('sales.consignments.index') }}" class="text-indigo-600 hover:text-indigo-800">&larr; Back to List</a>
        <h1 class="text-3xl font-bold text-gray-800 mt-2">Reconcile Consignment Sale #{{ $sale->invoice_number }}</h1>
        <p class="text-gray-600">Patient: {{ $sale->patient_name ?? 'N/A' }} | Date: {{ $sale->created_at->format('Y-m-d') }}</p>
    </div>

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-lg p-6">
        <form action="{{ route('sales.consignments.reconcile', $sale->id) }}" method="POST">
            @csrf
            
            <div class="mb-4 bg-blue-50 p-4 rounded text-sm text-blue-800">
                <strong>How this works:</strong>
                <ul class="list-disc pl-5 mt-1">
                    <li>All original items are technically "Returned" to stock first.</li>
                    <li>Enter the quantity <strong>actually used</strong> below.</li>
                    <li>Only the used items will be billed on a new Invoice. Unused items remain in stock.</li>
                </ul>
            </div>

            <table class="min-w-full divide-y divide-gray-200 mb-6">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Batch</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sent Qty</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unit Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase bg-yellow-50">Used Qty</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Line Total</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($items as $index => $item)
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            {{ $item['product_name'] ?? $item['name'] }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ $item['batch_number'] ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            {{ $item['quantity'] }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            {{ number_format($item['price'], 2) }}
                        </td>
                        <td class="px-6 py-4 bg-yellow-50">
                            <input type="number" 
                                name="usage[{{ $item['id'] }}]" 
                                value="0" 
                                min="0" 
                                max="{{ $item['quantity'] }}"
                                class="usage-input w-24 border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                data-price="{{ $item['price'] }}"
                                @input="calculateTotal()"
                            >
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 font-medium">
                           <span x-text="(parseInt($el.parentElement.previousElementSibling.querySelector('input').value || 0) * {{ $item['price'] }}).toFixed(2)">0.00</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-gray-100 font-bold">
                        <td colspan="5" class="px-6 py-4 text-right">Total Usage Value:</td>
                        <td class="px-6 py-4" x-text="grandTotal">0.00</td>
                    </tr>
                </tfoot>
            </table>

            <div class="flex justify-end space-x-4">
                <a href="{{ route('sales.consignments.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 shadow-lg">
                    Reconcile & Generate Invoice
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
