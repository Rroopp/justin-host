@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                Reconcile Consignment #{{ $sale->id }}
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Enter returned quantities. The remaining items will be invoiced to {{ $sale->customer_name ?? 'the customer' }}.
            </p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <a href="{{ route('sales.consignments.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Cancel
            </a>
        </div>
    </div>

    @if (session('error'))
        <div class="rounded-md bg-red-50 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white shadow overflow-hidden sm:rounded-lg" x-data="reconcileForm()">
        <form action="{{ route('sales.consignments.reconcile', $sale->id) }}" method="POST">
            @csrf
            
            <div class="p-6 border-b border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Facility / Hospital</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $sale->facility_name ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Dispatch Date</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ \Carbon\Carbon::parse($sale->created_at)->format('F j, Y, g:i a') }}</dd>
                    </div>
                    <div>
                       <dt class="text-sm font-medium text-gray-500">Patient Details</dt>
                       <dd class="mt-1 text-sm text-gray-900">
                          {{ $sale->patient_name ?? '-' }} 
                          @if($sale->patient_number) ({{ $sale->patient_number }}) @endif
                       </dd>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Dispatched Qty</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-40">Returned Qty</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Qty Used (Sold)</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Line Total</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($sale->sale_items as $item)
                        <tr x-data="{ dispatched: {{ $item['quantity'] }}, returned: 0, price: {{ $item['unit_price'] }} }">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $item['product_name'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                <span x-text="dispatched"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <input 
                                    type="number" 
                                    name="returned_quantities[{{ $item['product_id'] }}]" 
                                    x-model.number="returned" 
                                    min="0" 
                                    :max="dispatched"
                                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md text-center"
                                >
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900 font-bold">
                                <span x-text="dispatched - returned" :class="{'text-gray-400': (dispatched - returned) === 0}"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500">
                                <span x-text="formatMoney((dispatched - returned) * price)"></span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 bg-gray-50 flex items-center justify-between border-t border-gray-200">
                <div class="text-sm text-gray-500">
                    <p>Returned items will be automatically added back to inventory stock.</p>
                </div>
                <div class="flex items-center gap-4">
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Finalize & Generate Invoice
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function reconcileForm() {
        return {
            formatMoney(amount) {
                return 'KSh ' + parseFloat(amount).toLocaleString('en-KE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }
        }
    }
</script>
@endsection
