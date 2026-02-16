@extends('layouts.app')

@section('content')
<div x-data="billsManager()" x-init="init()" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Unpaid Bills</h1>
        @if(request('vendor_id'))
            @php
                $vendorName = \App\Models\Vendor::find(request('vendor_id'))?->name;
            @endphp
            @if($vendorName)
                <p class="mt-2 text-sm text-indigo-600 font-medium bg-indigo-50 inline-block px-3 py-1 rounded-full">
                    Filtered by Supplier: {{ $vendorName }} 
                    <a href="{{ route('expenses.unpaid') }}" class="ml-2 text-indigo-400 hover:text-indigo-800"><i class="fas fa-times"></i></a>
                </p>
            @endif
        @else
            <p class="mt-2 text-sm text-gray-600">Manage and pay outstanding bills</p>
        @endif
    </div>

    @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vendor/Payee</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($bills as $bill)
                <tr class="{{ $bill->due_date && $bill->due_date->isPast() ? 'bg-red-50' : '' }}">
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <span class="{{ $bill->due_date && $bill->due_date->isPast() ? 'text-red-600 font-semibold' : 'text-gray-900' }}">
                            {{ $bill->due_date ? $bill->due_date->format('M d, Y') : '-' }}
                        </span>
                        @if($bill->due_date && $bill->due_date->isPast())
                            <span class="ml-2 text-xs text-red-600">(Overdue)</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <div class="font-medium text-gray-900">{{ $bill->payee }}</div>
                        @if($bill->vendor)
                            <div class="text-xs text-gray-500">{{ $bill->vendor->name }}</div>
                        @endif
                        @if($bill->reference_number)
                            <div class="text-xs text-gray-500">Ref: {{ $bill->reference_number }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">{{ Str::limit($bill->description, 50) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $bill->category->name ?? '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold text-gray-900">
                        KSh {{ number_format($bill->amount, 2) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button @click="openPaymentModal({{ $bill->id }}, '{{ $bill->payee }}', {{ $bill->amount }})" 
                            class="text-green-600 hover:text-green-900 font-semibold">
                            Pay Bill
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="mt-2">No unpaid bills</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        
        <div class="px-6 py-4 bg-gray-50 border-t">
            {{ $bills->links() }}
        </div>
    </div>

    <!-- Payment Modal -->
    <div x-show="showPaymentModal" x-cloak class="fixed inset-0 overflow-y-auto z-50" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="showPaymentModal = false"></div>
            
            <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Pay Bill</h3>
                
                <form :action="`/expenses/${selectedBill}/pay`" method="POST">
                    @csrf
                    
                    <div class="mb-4">
                        <p class="text-sm text-gray-600">Paying bill to: <span class="font-semibold" x-text="selectedPayee"></span></p>
                        <p class="text-sm text-gray-600">Total Amount: <span class="font-semibold">KSh <span x-text="selectedAmount.toLocaleString('en-KE', {minimumFractionDigits: 2})"></span></span></p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Payment Amount *</label>
                        <input type="number" name="amount" step="0.01" :max="selectedAmount" x-model="paymentAmount" required
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <p class="mt-1 text-xs text-gray-500">Enter full or partial payment amount</p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Payment Source *</label>
                        <select name="payment_account_id" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select Account</option>
                            @foreach($paymentAccounts as $account)
                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Payment Date *</label>
                        <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" required
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" @click="showPaymentModal = false" 
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" 
                            class="px-4 py-2 bg-green-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-green-700">
                            Record Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function billsManager() {
    return {
        showPaymentModal: false,
        selectedBill: null,
        selectedPayee: '',
        selectedAmount: 0,
        paymentAmount: 0,

        init() {
            console.log('Bills Manager initialized');
        },

        openPaymentModal(billId, payee, amount) {
            this.selectedBill = billId;
            this.selectedPayee = payee;
            this.selectedAmount = parseFloat(amount);
            this.paymentAmount = parseFloat(amount); // Default to full payment
            this.showPaymentModal = true;
        }
    }
}
</script>
@endsection
