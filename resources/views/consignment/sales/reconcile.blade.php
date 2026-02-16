@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold">Reconcile Consignment Sale #{{ $sale->id }}</h1>
            <p class="text-gray-500">Dispatched on {{ $sale->timestamp->format('Y-m-d H:i') }} by {{ $sale->seller_username }}</p>
        </div>
        <a href="{{ route('sales.consignments.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
            Cancel
        </a>
    </div>

    @if($sale->patient_name)
    <div class="bg-indigo-50 border-l-4 border-indigo-500 p-4 mb-6">
        <h3 class="font-bold text-indigo-700">Patient Details</h3>
        <p><strong>Name:</strong> {{ $sale->patient_name }}</p>
        <p><strong>Number:</strong> {{ $sale->patient_number ?? 'N/A' }}</p>
        <p><strong>Surgeon:</strong> {{ $sale->surgeon_name ?? 'N/A' }}</p>
    </div>
    @endif
    
    @if ($errors->any())
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">There were errors with your submission</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <ul class="list-disc pl-5 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('sales.consignments.process', $sale->id) }}" method="POST" id="reconcileForm">
        @csrf
        
        <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Batch</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Unit Price</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Dispatched</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-green-600 uppercase">Used</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-red-600 uppercase">Returned</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-900 uppercase">Line Total</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($transactions as $index => $transaction)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ $transaction->inventory->product_name }}</div>
                                <div class="text-xs text-gray-500">{{ $transaction->inventory->product_code }}</div>
                                <input type="hidden" name="items[{{ $index }}][transaction_id]" value="{{ $transaction->id }}">
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ optional($transaction->batch)->batch_number ?? 'FIFO' }}
                                @if($transaction->batch && $transaction->batch->expiry_date)
                                    <br><span class="text-xs {{ $transaction->batch->expiry_date < now() ? 'text-red-500' : 'text-gray-400' }}">
                                        Exp: {{ $transaction->batch->expiry_date->format('Y-m-d') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 text-right">
                                {{ number_format($transaction->inventory->selling_price, 2) }}
                                <input type="hidden" class="unit-price" value="{{ $transaction->inventory->selling_price }}">
                            </td>
                            <td class="px-6 py-4 text-sm font-bold text-gray-900 text-center bg-gray-50">
                                {{ $transaction->quantity }}
                                <input type="hidden" class="original-qty" value="{{ $transaction->quantity }}">
                            </td>
                            <td class="px-6 py-4 bg-green-50 text-center">
                                <input type="number" 
                                       name="items[{{ $index }}][quantity_used]" 
                                       value="{{ $transaction->quantity }}" 
                                       min="0" 
                                       max="{{ $transaction->quantity }}"
                                       class="qty-used w-20 border-gray-300 rounded shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 text-center font-bold text-green-700"
                                       onchange="updateQty(this, 'used')">
                            </td>
                            <td class="px-6 py-4 bg-red-50 text-center">
                                <input type="number" 
                                       name="items[{{ $index }}][quantity_returned]" 
                                       value="0" 
                                       min="0" 
                                       max="{{ $transaction->quantity }}"
                                       class="qty-returned w-20 border-gray-300 rounded shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 text-center font-bold text-red-700"
                                       onchange="updateQty(this, 'returned')">
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-gray-900">
                                <span class="line-total">{{ number_format($transaction->quantity * $transaction->inventory->selling_price, 2) }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex justify-between space-x-4 bg-gray-50 p-6 rounded-lg shadow items-center">
            <div class="text-left">
                <p class="text-sm text-gray-500">Total Dispatched Value: <span class="font-semibold">{{ number_format($transactions->sum(fn($t) => $t->quantity * $t->inventory->selling_price), 2) }}</span></p>
            </div>
            <div class="text-right flex items-center space-x-6">
                <div>
                    <p class="text-sm text-gray-600">Total Items Used: <span id="total-used" class="font-bold text-gray-800">{{ $transactions->sum('quantity') }}</span></p>
                    <p class="text-sm text-gray-600">Total Items Returned: <span id="total-returned" class="font-bold text-red-600">0</span></p>
                </div>
                <div class="border-l pl-6">
                    <p class="text-xs text-gray-500 uppercase tracking-widest font-semibold">Invoice Total</p>
                    <p class="text-3xl font-bold text-green-600" id="invoice-total">
                        {{ number_format($transactions->sum(fn($t) => $t->quantity * $t->inventory->selling_price), 2) }}
                    </p>
                </div>
            </div>
        </div>
        
        <div class="flex justify-end mt-6">
            <button type="submit" class="bg-indigo-600 text-white px-8 py-3 rounded-lg font-bold shadow-lg hover:bg-indigo-700 hover:shadow-xl transition transform hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Generate Invoice
            </button>
        </div>
    </form>
</div>

<script>
function updateQty(input, type) {
    const row = input.closest('tr');
    const originalQty = parseInt(row.querySelector('.original-qty').value);
    const unitPrice = parseFloat(row.querySelector('.unit-price').value);
    const usedInput = row.querySelector('.qty-used');
    const returnedInput = row.querySelector('.qty-returned');
    const lineTotalSpan = row.querySelector('.line-total');
    
    let usedQty = parseInt(usedInput.value) || 0;
    let returnedQty = parseInt(returnedInput.value) || 0;
    
    // Calculate counterpart based on what changed
    if (type === 'used') {
        if (usedQty > originalQty) usedQty = originalQty;
        if (usedQty < 0) usedQty = 0;
        
        returnedQty = originalQty - usedQty;
        
        // Update input values
        usedInput.value = usedQty;
        returnedInput.value = returnedQty;
    } else {
        if (returnedQty > originalQty) returnedQty = originalQty;
        if (returnedQty < 0) returnedQty = 0;
        
        usedQty = originalQty - returnedQty;
        
        // Update input values
        returnedInput.value = returnedQty;
        usedInput.value = usedQty;
    }

    // Update Line Total
    const lineTotal = usedQty * unitPrice;
    lineTotalSpan.textContent = lineTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

    updateTotals();
}

function updateTotals() {
    let totalUsed = 0;
    let totalReturned = 0;
    let invoiceTotal = 0;
    
    document.querySelectorAll('tbody tr').forEach(row => {
        const usedQty = parseInt(row.querySelector('.qty-used').value || 0);
        const returnedQty = parseInt(row.querySelector('.qty-returned').value || 0);
        const unitPrice = parseFloat(row.querySelector('.unit-price').value || 0);
        
        totalUsed += usedQty;
        totalReturned += returnedQty;
        invoiceTotal += (usedQty * unitPrice);
    });
    
    document.getElementById('total-used').innerText = totalUsed;
    document.getElementById('total-returned').innerText = totalReturned;
    document.getElementById('invoice-total').innerText = invoiceTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}
</script>
@endsection
