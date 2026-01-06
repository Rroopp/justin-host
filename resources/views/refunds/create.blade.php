@extends('layouts.app')

@section('content')
<div class="py-6" x-data="refundForm()">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 md:px-8">
        <div class="mb-6">
            <a href="{{ route('sales.index') }}" class="text-indigo-600 hover:text-indigo-900">
                ← Back to Sales
            </a>
        </div>

        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                    Request Refund for Sale #{{ $sale->invoice_number ?? $sale->id }}
                </h3>

                <!-- Sale Info -->
                <div class="bg-gray-50 p-4 rounded-md mb-6">
                    <dl class="grid grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Sale Date</dt>
                            <dd class="text-sm text-gray-900">{{ $sale->created_at->format('Y-m-d H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Total Amount</dt>
                            <dd class="text-sm text-gray-900">{{ settings('currency_symbol', 'KSh') }} {{ number_format($sale->total, 2) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Customer</dt>
                            <dd class="text-sm text-gray-900">{{ $sale->customer_name ?? 'Walk-in' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Payment Method</dt>
                            <dd class="text-sm text-gray-900">{{ $sale->payment_method }}</dd>
                        </div>
                    </dl>
                </div>

                <form action="{{ route('refunds.store', $sale->id) }}" method="POST">
                    @csrf

                    <!-- Refund Type -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Refund Type</label>
                        <div class="flex gap-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="refund_type" value="full" x-model="refundType" class="form-radio" checked>
                                <span class="ml-2">Full Refund</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="refund_type" value="partial" x-model="refundType" class="form-radio">
                                <span class="ml-2">Partial Refund</span>
                            </label>
                        </div>
                    </div>

                    <!-- Items Selection -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Items to Refund</label>
                        <div class="border rounded-md divide-y">
                            @foreach($sale->sale_items as $index => $item)
                                <div class="p-4" x-data="{ selected: true, quantity: {{ $item['quantity'] }} }">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center flex-1">
                                            <input type="checkbox" 
                                                   x-model="selected"
                                                   @change="updateRefundAmount()"
                                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                            <div class="ml-3 flex-1">
                                                <p class="text-sm font-medium text-gray-900">{{ $item['product_name'] }}</p>
                                                <p class="text-sm text-gray-500">
                                                    Original Qty: {{ $item['quantity'] }} × {{ settings('currency_symbol', 'KSh') }} {{ number_format($item['unit_price'], 2) }}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="ml-4" x-show="selected && refundType === 'partial'">
                                            <label class="text-xs text-gray-500">Refund Qty</label>
                                            <input type="number" 
                                                   x-model="quantity"
                                                   @input="updateRefundAmount()"
                                                   min="0.01" 
                                                   :max="{{ $item['quantity'] }}"
                                                   step="0.01"
                                                   class="w-20 rounded-md border-gray-300 text-sm">
                                        </div>

                                        <div class="ml-4 text-right">
                                            <p class="text-sm font-medium text-gray-900" x-text="'{{ settings('currency_symbol', 'KSh') }} ' + (selected ? (quantity * {{ $item['unit_price'] }}).toFixed(2) : '0.00')"></p>
                                        </div>
                                    </div>

                                    <!-- Hidden inputs for selected items -->
                                    <template x-if="selected">
                                        <div>
                                            <input type="hidden" :name="'refund_items[' + {{ $index }} + '][product_id]'" value="{{ $item['product_id'] ?? $item['id'] }}">
                                            <input type="hidden" :name="'refund_items[' + {{ $index }} + '][product_name]'" value="{{ $item['product_name'] }}">
                                            <input type="hidden" :name="'refund_items[' + {{ $index }} + '][quantity]'" :value="quantity">
                                            <input type="hidden" :name="'refund_items[' + {{ $index }} + '][unit_price]'" value="{{ $item['unit_price'] }}">
                                            <input type="hidden" :name="'refund_items[' + {{ $index }} + '][type]'" value="{{ $item['type'] ?? 'sale' }}">
                                        </div>
                                    </template>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Refund Amount -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Refund Amount</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-gray-500">{{ settings('currency_symbol', 'KSh') }}</span>
                            <input type="number" 
                                   name="refund_amount" 
                                   x-model="refundAmount"
                                   step="0.01" 
                                   min="0.01"
                                   max="{{ $sale->total }}"
                                   required
                                   class="pl-12 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <p class="mt-1 text-sm text-gray-500">Maximum: {{ settings('currency_symbol', 'KSh') }} {{ number_format($sale->total, 2) }}</p>
                    </div>

                    <!-- Refund Method -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Refund Method</label>
                        <select name="refund_method" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="Cash">Cash</option>
                            <option value="M-Pesa">M-Pesa</option>
                            <option value="Bank">Bank Transfer</option>
                            <option value="Credit Note">Credit Note</option>
                        </select>
                    </div>

                    <!-- Reason -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Reason for Refund *</label>
                        <textarea name="reason" 
                                  rows="4" 
                                  required
                                  minlength="10"
                                  placeholder="Please provide a detailed reason for this refund..."
                                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        <p class="mt-1 text-sm text-gray-500">Minimum 10 characters</p>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3">
                        <a href="{{ route('sales.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Cancel
                        </a>
                        <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                            Submit Refund Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function refundForm() {
    return {
        refundType: 'full',
        refundAmount: {{ $sale->total }},
        
        updateRefundAmount() {
            // This would calculate based on selected items
            // For now, keeping it simple
        }
    }
}
</script>
@endsection
