@extends('layouts.app')

@section('content')
<div class="py-6">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 md:px-8">
        <div class="mb-6">
            <a href="{{ route('refunds.index') }}" class="text-indigo-600 hover:text-indigo-900">
                ← Back to Refunds
            </a>
        </div>

        <!-- Status Alert -->
        @if(session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <!-- Header -->
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 flex justify-between items-center">
                <div>
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        Refund {{ $refund->refund_number }}
                    </h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">
                        Created {{ $refund->created_at->format('F j, Y \a\t g:i A') }}
                    </p>
                </div>
                <div>
                    <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full 
                        @if($refund->status === 'completed') bg-green-100 text-green-800
                        @elseif($refund->status === 'pending') bg-yellow-100 text-yellow-800
                        @elseif($refund->status === 'rejected') bg-red-100 text-red-800
                        @else bg-gray-100 text-gray-800
                        @endif">
                        {{ ucfirst($refund->status) }}
                    </span>
                </div>
            </div>

            <!-- Details -->
            <div class="px-4 py-5 sm:p-6">
                <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Original Sale</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <a href="{{ route('sales.index') }}" class="text-indigo-600 hover:text-indigo-900">
                                {{ $refund->posSale->invoice_number ?? 'Sale #' . $refund->pos_sale_id }}
                            </a>
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Refund Type</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($refund->refund_type) }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Refund Amount</dt>
                        <dd class="mt-1 text-lg font-semibold text-gray-900">
                            {{ settings('currency_symbol', 'KSh') }} {{ number_format($refund->refund_amount, 2) }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Refund Method</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $refund->refund_method }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Requested By</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $refund->requestedBy->full_name ?? 'N/A' }}</dd>
                    </div>

                    @if($refund->approved_by)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Approved By</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $refund->approvedBy->full_name ?? 'N/A' }}
                            @if($refund->approved_at)
                                <span class="text-gray-500">on {{ $refund->approved_at->format('Y-m-d H:i') }}</span>
                            @endif
                        </dd>
                    </div>
                    @endif

                    @if($refund->reference_number)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Reference Number</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $refund->reference_number }}</dd>
                    </div>
                    @endif

                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">Reason</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $refund->reason }}</dd>
                    </div>

                    @if($refund->admin_notes)
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">Admin Notes</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $refund->admin_notes }}</dd>
                    </div>
                    @endif
                </dl>

                <!-- Refund Items -->
                <div class="mt-6">
                    <h4 class="text-sm font-medium text-gray-500 mb-3">Refunded Items</h4>
                    <div class="border rounded-md overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Unit Price</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($refund->refund_items as $item)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-900">{{ $item['product_name'] ?? 'N/A' }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-500">{{ $item['quantity'] ?? 0 }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-500">{{ settings('currency_symbol', 'KSh') }} {{ number_format($item['unit_price'] ?? 0, 2) }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-900">{{ settings('currency_symbol', 'KSh') }} {{ number_format(($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0), 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-4 text-center text-sm text-gray-500">No item details available.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Processing Status -->
                @if($refund->status === 'completed')
                <div class="mt-6 bg-green-50 border border-green-200 rounded-md p-4">
                    <div class="flex items-start">
                        <svg class="h-5 w-5 text-green-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-green-800">Refund Processed</h3>
                            <div class="mt-2 text-sm text-green-700">
                                <ul class="list-disc pl-5 space-y-1">
                                    @if($refund->inventory_restored)
                                        <li>Inventory restored to stock</li>
                                    @endif
                                    @if($refund->accounting_reversed)
                                        <li>Accounting entries reversed</li>
                                    @endif
                                    @if($refund->journal_entry_id)
                                        <li>Journal Entry: <a href="{{ route('accounting.journal-entries') }}" class="underline">View Entry</a></li>
                                    @endif
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Approval Actions (Admin Only, Pending Status) -->
                @if($refund->status === 'pending' && auth()->user()->hasRole('admin'))
                <div class="mt-6 border-t pt-6">
                    <h4 class="text-sm font-medium text-gray-900 mb-4">Admin Actions</h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Approve Form -->
                        <form action="{{ route('refunds.approve', $refund->id) }}" method="POST" class="border border-green-300 rounded-md p-4 bg-green-50">
                            @csrf
                            <h5 class="font-medium text-green-900 mb-3">Approve Refund</h5>
                            
                            <div class="mb-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Refund Method (Optional Override)</label>
                                <select name="refund_method" class="w-full rounded-md border-gray-300 text-sm">
                                    <option value="">Use Original ({{ $refund->refund_method }})</option>
                                    <option value="Cash">Cash</option>
                                    <option value="M-Pesa">M-Pesa</option>
                                    <option value="Bank">Bank Transfer</option>
                                    <option value="Credit Note">Credit Note</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Reference Number (Optional)</label>
                                <input type="text" name="reference_number" placeholder="M-Pesa/Bank ref..." class="w-full rounded-md border-gray-300 text-sm">
                            </div>

                            <div class="mb-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Admin Notes (Optional)</label>
                                <textarea name="admin_notes" rows="2" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                            </div>

                            <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 text-sm font-medium">
                                Approve & Process Refund
                            </button>
                        </form>

                        <!-- Reject Form -->
                        <form action="{{ route('refunds.reject', $refund->id) }}" method="POST" class="border border-red-300 rounded-md p-4 bg-red-50">
                            @csrf
                            <h5 class="font-medium text-red-900 mb-3">Reject Refund</h5>
                            
                            <div class="mb-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Rejection Reason *</label>
                                <textarea name="admin_notes" rows="4" required minlength="10" placeholder="Explain why this refund is being rejected..." class="w-full rounded-md border-gray-300 text-sm"></textarea>
                                <p class="mt-1 text-xs text-gray-500">Minimum 10 characters</p>
                            </div>

                            <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 text-sm font-medium">
                                Reject Refund
                            </button>
                        </form>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
