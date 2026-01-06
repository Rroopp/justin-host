@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Credit Invoices</h1>
        <p class="mt-2 text-sm text-gray-600">Manage credit invoices and record partial/full payments</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('sales.invoices.summary') }}" class="inline-flex items-center px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">
            Generate Summary Invoice
        </a>
        <a href="{{ route('sales.index') }}" class="inline-flex items-center px-4 py-2 rounded-md border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
            Sales
        </a>
    </div>
</div>

@if (session('success'))
    <div class="mb-4 rounded-md bg-green-50 p-4 text-green-800 text-sm">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="mb-4 rounded-md bg-red-50 p-4 text-red-800 text-sm">{{ session('error') }}</div>
@endif

<form method="GET" action="{{ route('sales.invoices.index') }}" class="bg-white shadow rounded-lg p-4 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select name="payment_status" class="w-full rounded-md border-gray-300">
                <option value="">All</option>
                <option value="pending" @selected(request('payment_status') === 'pending')>Pending</option>
                <option value="partial" @selected(request('payment_status') === 'partial')>Partial</option>
                <option value="paid" @selected(request('payment_status') === 'paid')>Paid</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Customer / Invoice #</label>
            <input name="customer" value="{{ request('customer') }}" placeholder="Search..." class="w-full rounded-md border-gray-300" />
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="inline-flex items-center px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">
                Filter
            </button>
            <a href="{{ route('sales.invoices.index') }}" class="inline-flex items-center px-4 py-2 rounded-md border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                Reset
            </a>
        </div>
    </div>
</form>

<div class="bg-white shadow overflow-hidden sm:rounded-md">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paid</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Balance</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse ($invoices as $inv)
                @php
                    $paid = (float) ($inv->payments_sum_amount ?? 0);
                    $total = (float) ($inv->total ?? 0);
                    $balance = max($total - $paid, 0);
                @endphp
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $inv->invoice_number ?? ('INV-' . $inv->id) }}</div>
                        <div class="text-xs text-gray-500">{{ optional($inv->created_at)->format('Y-m-d H:i') }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $inv->customer_name ?? 'Walk-in Customer' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ optional($inv->due_date)->format('Y-m-d') ?? '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        KSh {{ number_format($total, 2) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        KSh {{ number_format($paid, 2) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        KSh {{ number_format($balance, 2) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                            @if ($inv->payment_status === 'paid') bg-green-100 text-green-800
                            @elseif ($inv->payment_status === 'partial') bg-gray-100 text-gray-800
                            @else bg-yellow-100 text-yellow-800 @endif">
                            {{ $inv->payment_status }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="{{ route('sales.invoices.show', $inv) }}" class="text-indigo-600 hover:text-indigo-900">View / Settle</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-6 py-8 text-center text-gray-500">No invoices found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="px-6 py-4">
        {{ $invoices->links() }}
    </div>
</div>
@endsection
