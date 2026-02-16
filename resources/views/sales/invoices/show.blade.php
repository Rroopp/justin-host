@extends('layouts.app')

@section('content')
@php
    $total = (float) ($sale->total ?? 0);
@endphp

<div class="flex items-start justify-between mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Invoice {{ $sale->invoice_number ?? ('INV-' . $sale->id) }}</h1>
        <p class="mt-2 text-sm text-gray-600">
            Customer: <span class="font-medium text-gray-900">{{ $sale->customer_name ?? 'Walk-in Customer' }}</span>
            <span class="mx-2 text-gray-300">•</span>
            Status:
            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                @if ($sale->payment_status === 'paid') bg-green-100 text-green-800
                @elseif ($sale->payment_status === 'partial') bg-gray-100 text-gray-800
                @else bg-yellow-100 text-yellow-800 @endif">
                {{ $sale->payment_status }}
            </span>
        </p>
    </div>

    <div class="flex gap-2">
        <a href="{{ route('sales.invoices.index') }}" class="inline-flex items-center px-4 py-2 rounded-md border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
            Back
        </a>
        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
            <button @click="open = !open" class="inline-flex items-center px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">
                Print
                <svg class="ml-2 -mr-1 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
            <div x-show="open" class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none" style="display: none;" x-transition>
                <div class="py-1">
                    <a href="{{ route('receipts.print', $sale->id) }}?type=invoice" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Invoice</a>
                    <a href="{{ route('receipts.print', $sale->id) }}?type=receipt" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Receipt</a>
                    <a href="{{ route('receipts.print', $sale->id) }}?type=delivery_note" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Delivery Note</a>
                    <a href="{{ route('receipts.print', $sale->id) }}?type=packing_slip" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Packing Slip</a>
                </div>
            </div>
        </div>
    </div>
</div>

@if (session('success'))
    <div class="mb-4 rounded-md bg-green-50 p-4 text-green-800 text-sm">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="mb-4 rounded-md bg-red-50 p-4 text-red-800 text-sm">{{ session('error') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Invoice Summary -->
    <div class="lg:col-span-1">
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Summary</h2>

            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Invoice Date</dt>
                    <dd class="text-gray-900 font-medium">{{ optional($sale->created_at)->format('Y-m-d') }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Due Date</dt>
                    <dd class="text-gray-900 font-medium">{{ optional($sale->due_date)->format('Y-m-d') ?? '-' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Total</dt>
                    <dd class="text-gray-900 font-medium">KSh {{ number_format($total, 2) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Paid</dt>
                    <dd class="text-gray-900 font-medium">KSh {{ number_format((float) $amountPaid, 2) }}</dd>
                </div>
                <div class="flex justify-between border-t pt-3">
                    <dt class="text-gray-500">Balance Due</dt>
                    <dd class="text-gray-900 font-bold">KSh {{ number_format((float) $balanceDue, 2) }}</dd>
                </div>
                @if ($sale->customer && \Illuminate\Support\Facades\Schema::hasColumn('customers', 'current_balance'))
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Customer Balance</dt>
                        <dd class="text-gray-900 font-medium">KSh {{ number_format((float) ($sale->customer->current_balance ?? 0), 2) }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        <!-- Record Payment -->
        <div class="bg-white shadow rounded-lg p-6 mt-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Record Payment</h2>

            <form method="POST" action="{{ route('sales.invoices.payments.store', $sale) }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700">Amount</label>
                    <input name="amount" type="number" step="0.01" min="0.01" value="{{ old('amount', number_format((float) $balanceDue, 2, '.', '')) }}"
                        class="mt-1 block w-full rounded-md border-gray-300" required />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Payment Method</label>
                    <select name="payment_method" class="mt-1 block w-full rounded-md border-gray-300" required>
                        @php $pm = old('payment_method', 'Cash'); @endphp
                        <option value="Cash" @selected($pm === 'Cash')>Cash</option>
                        <option value="M-Pesa" @selected($pm === 'M-Pesa')>M-Pesa</option>
                        <option value="Bank" @selected($pm === 'Bank')>Bank</option>
                        <option value="Cheque" @selected($pm === 'Cheque')>Cheque</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Payment Date</label>
                    <input name="payment_date" type="date" value="{{ old('payment_date', now()->toDateString()) }}" class="mt-1 block w-full rounded-md border-gray-300" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Reference</label>
                    <input name="payment_reference" value="{{ old('payment_reference') }}" class="mt-1 block w-full rounded-md border-gray-300" placeholder="MPesa code / bank ref / receipt #" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Notes</label>
                    <textarea name="payment_notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300" placeholder="Optional">{{ old('payment_notes') }}</textarea>
                </div>

                <div class="flex items-center gap-2">
                    <input id="update_customer_balance" name="update_customer_balance" type="checkbox" value="1" class="rounded border-gray-300" @checked(old('update_customer_balance', true))>
                    <label for="update_customer_balance" class="text-sm text-gray-700">Also reduce customer balance</label>
                </div>

                <button type="submit" class="w-full inline-flex justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    Save Payment
                </button>
            </form>
        </div>
    </div>

    <!-- Items + Payment History -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Items</h2>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Unit</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach (($sale->sale_items ?? []) as $item)
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-900">{{ $item['product_name'] ?? '-' }}</td>
                                <td class="px-4 py-2 text-sm text-gray-900">{{ $item['quantity'] ?? 0 }}</td>
                                <td class="px-4 py-2 text-sm text-gray-900">KSh {{ number_format((float) ($item['unit_price'] ?? 0), 2) }}</td>
                                <td class="px-4 py-2 text-sm font-medium text-gray-900">KSh {{ number_format((float) ($item['item_total'] ?? 0), 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Payment History</h2>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($sale->payments as $p)
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-900">{{ optional($p->payment_date)->format('Y-m-d') ?? optional($p->created_at)->format('Y-m-d') }}</td>
                                <td class="px-4 py-2 text-sm text-gray-900">{{ $p->payment_method }}</td>
                                <td class="px-4 py-2 text-sm text-gray-900">{{ $p->payment_reference ?? '-' }}</td>
                                <td class="px-4 py-2 text-sm font-medium text-gray-900">KSh {{ number_format((float) ($p->amount ?? 0), 2) }}</td>
                                <td class="px-4 py-2 text-sm text-gray-700">{{ $p->payment_notes ?? '' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-gray-500">No payments recorded yet</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
