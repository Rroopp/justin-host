@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Unbilled Consignment Transactions</h1>
        <div class="flex space-x-2">
            <a href="{{ route('consignment.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                ← Back to Dashboard
            </a>
            <button onclick="billSelected()" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                💰 Generate Bill for Selected
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET" action="{{ route('consignment.unbilled') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Location</label>
                <select name="location_id" class="w-full border rounded px-3 py-2">
                    <option value="">All Locations</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" {{ request('location_id') == $location->id ? 'selected' : '' }}>
                            {{ $location->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Start Date</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">End Date</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full border rounded px-3 py-2">
            </div>
            <div class="flex items-end">
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 mr-2">Filter</button>
                <a href="{{ route('consignment.unbilled') }}" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Clear</a>
            </div>
        </form>
    </div>

    <!-- Summary -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
        <div class="flex justify-between items-center">
            <div>
                <div class="text-sm text-gray-600">Total Unbilled Transactions</div>
                <div class="text-2xl font-bold text-yellow-700">{{ $transactions->total() }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-600">Total Unbilled Amount</div>
                <div class="text-2xl font-bold text-yellow-700">{{ number_format($totalUnbilled, 2) }}</div>
            </div>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left">
                        <input type="checkbox" id="selectAll" onclick="toggleSelectAll()">
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Batch</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Value</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created By</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($transactions as $transaction)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <input type="checkbox" class="transaction-checkbox" value="{{ $transaction->id }}">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            {{ $transaction->transaction_date->format('Y-m-d') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            {{ $transaction->location->name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <div class="font-medium">{{ $transaction->inventory->product_name ?? 'N/A' }}</div>
                            @if($transaction->notes)
                                <div class="text-xs text-gray-500">{{ Str::limit($transaction->notes, 50) }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            {{ $transaction->batch->batch_number ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            {{ $transaction->quantity }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">
                            {{ number_format($transaction->value, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            {{ $transaction->createdBy->name ?? 'N/A' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                            No unbilled transactions found
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $transactions->links() }}
    </div>
</div>

<!-- Bill Generation Form -->
<form id="billForm" action="{{ route('consignment.generate-bill') }}" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="billing_reference" id="billing_reference">
</form>

<script>
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.transaction-checkbox');
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
}

function billSelected() {
    const checkboxes = document.querySelectorAll('.transaction-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Please select at least one transaction to bill');
        return;
    }

    const invoiceRef = prompt('Enter invoice reference (optional):');
    if (invoiceRef === null) return; // User cancelled

    const form = document.getElementById('billForm');
    document.getElementById('billing_reference').value = invoiceRef || '';

    // Add transaction IDs to form
    checkboxes.forEach(cb => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'transaction_ids[]';
        input.value = cb.value;
        form.appendChild(input);
    });

    form.submit();
}
</script>
@endsection
