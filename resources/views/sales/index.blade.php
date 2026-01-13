@extends('layouts.app')

@section('content')
<div x-data="salesManager()" x-init="loadSales()">
        <div class="mb-6 flex justify-between items-center">
            <h1 class="text-3xl font-bold text-gray-900">Sales History</h1>
            <div class="flex gap-2">
                <button @click="exportSales()" class="bg-indigo-100 text-indigo-700 px-4 py-2 rounded-md hover:bg-indigo-200">
                    Export CSV
                </button>
                <a href="{{ route('sales.invoices.index') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                    Invoices
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white shadow rounded-lg p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                    <input type="date" x-model="filters.date_from" @change="loadSales()" class="w-full rounded-md border-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                    <input type="date" x-model="filters.date_to" @change="loadSales()" class="w-full rounded-md border-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                    <select x-model="filters.payment_method" @change="loadSales()" class="w-full rounded-md border-gray-300">
                        <option value="">All Methods</option>
                        <option value="Cash">Cash</option>
                        <option value="M-Pesa">M-Pesa</option>
                        <option value="Bank">Bank</option>
                        <option value="Cheque">Cheque</option>
                        <option value="Credit">Credit</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Document Type</label>
                    <select x-model="filters.document_type" @change="loadSales()" class="w-full rounded-md border-gray-300">
                        <option value="">All Types</option>
                        <option value="receipt">Receipt</option>
                        <option value="invoice">Invoice</option>
                        <option value="delivery_note">Delivery Note</option>
                    </select>
                </div>

                @if(isset($sellers) && count($sellers) > 0)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Seller</label>
                    <select x-model="filters.seller" @change="loadSales()" class="w-full rounded-md border-gray-300">
                        <option value="">All Sellers</option>
                        @foreach($sellers as $seller)
                            <option value="{{ $seller }}">{{ $seller }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>
        </div>

        <!-- Sales Table -->
        <div class="bg-white shadow sm:rounded-md">
            <div x-show="loading" class="p-8 text-center">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
            </div>

            <div x-show="!loading && sales.length === 0" class="p-8 text-center text-gray-500">
                No sales found
            </div>

            <table x-show="!loading && sales.length > 0" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ref #</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Seller</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Items</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="sale in sales" :key="sale.id">
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="formatDate(sale.created_at)"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="sale.invoice_number || sale.id"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="sale.customer_name || 'Walk-in Customer'"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="sale.seller_username || 'N/A'"></td>
                            <td class="px-6 py-4 text-sm text-gray-500" x-text="(sale.sale_items || []).length + ' items'"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="sale.payment_method"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="formatCurrency(sale.total)"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span 
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    :class="{
                                        'bg-green-100 text-green-800': sale.payment_status === 'paid',
                                        'bg-yellow-100 text-yellow-800': sale.payment_status === 'pending',
                                        'bg-gray-100 text-gray-800': sale.payment_status === 'partial'
                                    }"
                                    x-text="sale.payment_status"
                                ></span>
                                
                                <!-- Refund Status Badge -->
                                <template x-if="getRefundStatus(sale)">
                                    <span 
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ml-1"
                                        :class="{
                                            'bg-red-100 text-red-800': getRefundStatus(sale) === 'Fully Refunded',
                                            'bg-orange-100 text-orange-800': getRefundStatus(sale) === 'Partially Refunded'
                                        }"
                                        x-text="getRefundStatus(sale)"
                                    ></span>
                                </template>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                                    <button @click="open = !open" type="button" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                        </svg>
                                    </button>
                                    
                                    <div x-show="open" 
                                         x-transition:enter="transition ease-out duration-100"
                                         x-transition:enter-start="transform opacity-0 scale-95"
                                         x-transition:enter-end="transform opacity-100 scale-100"
                                         x-transition:leave="transition ease-in duration-75"
                                         x-transition:leave-start="transform opacity-100 scale-100"
                                         x-transition:leave-end="transform opacity-0 scale-95"
                                         class="origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 z-50"
                                         style="display: none;">
                                        
                                        <!-- Documents Section -->
                                        <div class="py-1">
                                            <template x-if="['Cash', 'M-Pesa', 'Cheque', 'Bank'].includes(sale.payment_method)">
                                                <a :href="`/receipts/${sale.id}/print?type=receipt`" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    Download Receipt
                                                </a>
                                            </template>
                                            
                                            <template x-if="['Credit', 'Consignment'].includes(sale.payment_method)">
                                                <div>
                                                    <a :href="`/receipts/${sale.id}/print?type=invoice`" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                        Download Invoice
                                                    </a>
                                                    <a :href="`/receipts/${sale.id}/print?type=delivery_note`" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                        Download Delivery Note
                                                    </a>
                                                    <template x-if="['paid', 'Paid', 'PAID'].includes(sale.payment_status)">
                                                        <a :href="`/receipts/${sale.id}/print?type=receipt`" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 text-green-600">
                                                            Download Receipt
                                                        </a>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>

                                        <!-- Actions Section -->
                                        <div class="py-1">
                                            <!-- Record Payment -->
                                            <button
                                                type="button"
                                                x-show="!['paid', 'Paid', 'PAID'].includes(sale.payment_status) && sale.document_type === 'invoice'"
                                                @click="openPaymentModal(sale); open = false"
                                                class="w-full text-left px-4 py-2 text-sm text-green-600 hover:bg-gray-100"
                                            >
                                                Record Payment
                                            </button>

                                            <!-- Edit Payment Method -->
                                            <button 
                                                type="button" 
                                                @click.stop="openEditPaymentModal(sale); open = false" 
                                                class="w-full text-left px-4 py-2 text-sm text-blue-600 hover:bg-gray-100"
                                            >
                                                Correct Payment Method
                                            </button>

                                            <!-- Commission -->
                                            @if(auth()->user()->hasRole('admin'))
                                            <button type="button" @click="openCommissionModal(sale); open = false" class="w-full text-left px-4 py-2 text-sm text-yellow-600 hover:bg-gray-100">
                                                Add Commission
                                            </button>
                                            @endif
                                            
                                            <!-- Request Refund -->
                                            @if(auth()->user()->hasRole(['admin', 'staff', 'accountant']))
                                            <template x-if="getRefundStatus(sale) !== 'Fully Refunded'">
                                                <a :href="`/sales/${sale.id}/refund`" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                                    Request Refund
                                                </a>
                                            </template>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>


    <!-- Payment Modal -->
    <div x-show="showPaymentModal" x-cloak class="fixed inset-0 overflow-y-auto" style="z-index: 9999; display: none;">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" style="z-index: 100; display: none;" aria-hidden="true" @click="showPaymentModal = false">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full relative" style="z-index: 101;">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    Record Payment
                                </h3>
                                <div class="mt-2 text-sm text-gray-500">
                                    <p>Invoice #<span x-text="selectedSale?.id"></span></p>
                                    <p>Balance Due: <span class="font-bold" x-text="formatCurrency(selectedSale?.balance_due || selectedSale?.total)"></span></p>
                                </div>
                                
                                <div class="mt-4 space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Amount</label>
                                        <div class="mt-1 relative rounded-md shadow-sm">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <span class="text-gray-500 sm:text-sm">{{ settings('currency_symbol', 'KSh') }}</span>
                                            </div>
                                            <input type="number" step="0.01" x-model="paymentForm.amount" class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-12 sm:text-sm border-gray-300 rounded-md" placeholder="0.00">
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Payment Method</label>
                                        <select x-model="paymentForm.payment_method" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                            <option value="Cash">Cash</option>
                                            <option value="M-Pesa">M-Pesa</option>
                                            <option value="Bank Transfer">Bank Transfer</option>
                                            <option value="Cheque">Cheque</option>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Date</label>
                                        <input type="date" x-model="paymentForm.payment_date" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Reference / Notes</label>
                                        <input type="text" x-model="paymentForm.payment_reference" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="e.g. Transaction ID">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" @click="submitPayment()" :disabled="processingPayment" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
                            <span x-show="!processingPayment">Record Payment</span>
                            <span x-show="processingPayment">Processing...</span>
                        </button>
                        <button type="button" @click="showPaymentModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>


    <!-- Add Commission Modal -->
    <div x-show="showCommissionModal" class="fixed inset-0 overflow-y-auto" style="z-index: 10000; display: none;">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" style="display: none;" aria-hidden="true" @click="showCommissionModal = false">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form @submit.prevent="submitCommission">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Add Commission</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Sale Reference</label>
                                    <input type="text" x-model="commissionForm.invoice_number" readonly class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Staff Member *</label>
                                    <select x-model="commissionForm.staff_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">Select Staff</option>
                                        <template x-for="s in staffList" :key="s.id">
                                            <option :value="s.id" x-text="s.full_name"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Type</label>
                                    <select x-model="commissionForm.type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="sale">Sale Commission</option>
                                        <option value="service">Service / Procedure</option>
                                        <option value="bonus">Bonus</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Amount (KSh) *</label>
                                    <input type="number" x-model="commissionForm.amount" step="0.01" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Description</label>
                                    <input type="text" x-model="commissionForm.description" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                             <button type="submit" :disabled="processingCommission" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:w-auto sm:text-sm disabled:opacity-50">
                                <span x-show="!processingCommission">Save Commission</span>
                                <span x-show="processingCommission">Saving...</span>
                            </button>
                            <button type="button" @click="showCommissionModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Payment Method Modal -->
    <!-- Edit Payment Method Modal -->
    <!-- Edit Payment Method Modal -->
    <template x-teleport="body">
    <div x-show="showEditPaymentModal" class="fixed inset-0 overflow-y-auto" style="z-index: 10000;" x-cloak>
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" style="z-index: 100;" @click="showEditPaymentModal = false">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full relative" style="z-index: 101;">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Edit Payment Method</h3>
                    <div class="mb-4">
                        <p class="text-sm text-gray-500">
                            Correcting payment method for Sale #<span x-text="editPaymentForm.invoice_number"></span>.
                            <br>
                            <span class="text-red-500 font-bold">Warning:</span> This will adjust accounting records and may update the payment status.
                        </p>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">New Payment Method</label>
                        <select x-model="editPaymentForm.payment_method" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="Cash">Cash</option>
                            <option value="M-Pesa">M-Pesa</option>
                            <option value="Bank">Bank</option>
                            <option value="Cheque">Cheque</option>
                            <option value="Credit">Credit (Pay Later)</option>
                        </select>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                    <button type="button" @click="submitEditPaymentMethod()" :disabled="processingEditPayment" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:w-auto sm:text-sm disabled:opacity-50">
                        <span x-show="!processingEditPayment">Update Method</span>
                        <span x-show="processingEditPayment">Updating...</span>
                    </button>
                    <button type="button" @click="showEditPaymentModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
    </template>
</div>

<script>
window.salesManager = function() {
    return {
        sales: [],
        loading: false,
        filters: {
            date_from: '',
            date_to: '',
            payment_method: '',
            date_to: '',
            payment_method: '',
            document_type: '',
            seller: ''
        },
        
        // Payment Modal State
        showPaymentModal: false,
        selectedSale: null,
        processingPayment: false,
        paymentForm: {
            amount: '',
            payment_method: 'Cash',
            payment_date: new Date().toISOString().slice(0, 10),
            payment_reference: ''
        },

        // Commission Modal State
        showCommissionModal: false,
        processingCommission: false,
        staffList: [],
        commissionForm: {
            staff_id: '',
            amount: '',
            type: 'sale',
            description: '',
            invoice_number: ''
        },

        // Edit Payment Modal State
        showEditPaymentModal: false,
        processingEditPayment: false,
        editPaymentForm: {
            id: null,
            invoice_number: '',
            payment_method: ''
        },

        init() {
            this.loadSales();
            this.loadStaff();
        },

        async loadStaff() {
            try {
                const response = await axios.get('/staff', { 
                    params: { status: 'active' },
                    headers: { 'Accept': 'application/json' } 
                });
                // StaffController returns paginated data
                this.staffList = response.data.data || response.data;
            } catch (e) {
                console.error('Failed to load staff list', e);
                this.staffList = [];
            }
        },

        openCommissionModal(sale) {
            console.log('openCommissionModal called', sale);
            if (!sale) {
                console.error('No sale provided');
                alert('Error: No sale data provided');
                return;
            }
            this.commissionForm.invoice_number = sale.invoice_number || sale.id || ''; 
            this.commissionForm.amount = ''; 
            this.commissionForm.staff_id = '';
            this.commissionForm.type = 'sale';
            this.commissionForm.description = 'Commission for Sale #' + (sale.invoice_number || sale.id || '');
            this.showCommissionModal = true;
            console.log('Commission modal should be visible now');
        },

        async submitCommission() {
            this.processingCommission = true;
            try {
                await axios.post('/commissions', this.commissionForm);
                alert('Commission added successfully');
                this.showCommissionModal = false;
            } catch (error) {
                 alert('Error adding commission: ' + (error.response?.data?.message || JSON.stringify(error.response?.data) || error.message));
            } finally {
                this.processingCommission = false;
            }
        },

        openEditPaymentModal(sale) {
            console.log('openEditPaymentModal called', sale);
            if (!sale) return;

            // Simple assignment to internal form state
            this.editPaymentForm.id = sale.id;
            this.editPaymentForm.invoice_number = sale.invoice_number || sale.id;
            this.editPaymentForm.payment_method = sale.payment_method;
            
            console.log('Setting showEditPaymentModal to true');
            this.showEditPaymentModal = true;
        },

        async submitEditPaymentMethod() {
            this.processingEditPayment = true;
            try {
                await axios.post(`/sales/${this.editPaymentForm.id}/update-payment-method`, {
                    payment_method: this.editPaymentForm.payment_method
                });
                alert('Payment method updated successfully.');
                this.showEditPaymentModal = false;
                this.loadSales();
            } catch (error) {
                alert('Error updating payment method: ' + (error.response?.data?.message || error.message));
            } finally {
                this.processingEditPayment = false;
            }
        },

        async loadSales() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.filters.date_from) params.append('date_from', this.filters.date_from);
                if (this.filters.date_to) params.append('date_to', this.filters.date_to);
                if (this.filters.payment_method) params.append('payment_method', this.filters.payment_method);
                if (this.filters.payment_method) params.append('payment_method', this.filters.payment_method);
                if (this.filters.document_type) params.append('document_type', this.filters.document_type);
                if (this.filters.seller) params.append('seller', this.filters.seller);

                const response = await axios.get(`/sales?${params}`);
                this.sales = response.data.data || response.data;
            } catch (error) {
                console.error('Error loading sales:', error);
                alert('Error loading sales');
            } finally {
                this.loading = false;
            }
        },

        exportSales() {
            const params = new URLSearchParams();
            if (this.filters.date_from) params.append('date_from', this.filters.date_from);
            if (this.filters.date_to) params.append('date_to', this.filters.date_to);
            if (this.filters.payment_method) params.append('payment_method', this.filters.payment_method);
            if (this.filters.payment_method) params.append('payment_method', this.filters.payment_method);
            if (this.filters.document_type) params.append('document_type', this.filters.document_type);
            if (this.filters.seller) params.append('seller', this.filters.seller);
            params.append('export', 'true');
            
            window.location.href = `/sales?${params.toString()}`;
        },

        openPaymentModal(sale) {
            console.log('openPaymentModal called', sale);
            if (!sale) {
                alert('Error: No sale data provided');
                return;
            }
            
            // Unwrap Proxy to ensure clean object for simple assignment/reactivity
            try {
                this.selectedSale = JSON.parse(JSON.stringify(sale));
            } catch (e) {
                console.error('Error unwrapping sale:', e);
                this.selectedSale = sale; // Fallback
            }
            
            this.paymentForm.amount = sale.total || 0;
            this.paymentForm.payment_method = '';
            this.paymentForm.payment_reference = '';
            this.paymentForm.payment_date = new Date().toISOString().slice(0, 10);
            this.showPaymentModal = true;
            console.log('Payment modal should be visible now');
            
            // Force a small delay to ensure reactivity catches the data update before showing
            this.$nextTick(() => {
                // The modal is already shown by `this.showPaymentModal = true;` above.
                // This $nextTick block is not strictly necessary for showing the modal itself,
                // but could be for other reactivity updates if needed.
            });
        },

        async submitPayment() {
            if (!this.selectedSale) return;
            
            this.processingPayment = true;
            try {
                await axios.post(`/sales/invoices/${this.selectedSale.id}/payments`, this.paymentForm);
                
                // Success
                this.showPaymentModal = false;
                this.loadSales(); // Refresh list to show new status
                
                // Optional: Show toast
                alert('Payment recorded successfully');
            } catch (error) {
                alert('Error recording payment: ' + (error.response?.data?.message || error.message));
            } finally {
                this.processingPayment = false;
            }
        },

        async viewReceipt(saleId) {
            try {
                window.open(`/receipts/${saleId}/print`, '_blank');
            } catch (error) {
                alert('Error loading receipt: ' + (error.response?.data?.error || error.message));
            }
        },

        formatCurrency(amount) {
            // We don't have window.systemSettings available here easily unless we inject it, 
            // or we can fallback to server-side rendered value injection if needed.
            // For now, let's stick to a safe default but ideally we inject it.
            // Let's assume KSh for now if not injected, but I'll add a check.
            const symbol = '{{ settings("currency_symbol", "KSh") }}'; 
            return symbol + ' ' + parseFloat(amount || 0).toLocaleString('en-KE', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },

        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-KE') + ' ' + date.toLocaleTimeString('en-KE', { hour: '2-digit', minute: '2-digit' });
        },

        getRefundStatus(sale) {
            if (!sale.refunds || sale.refunds.length === 0) return null;
            
            // Only count approved/completed refunds
            const refundedAmount = sale.refunds
                .filter(r => r.status === 'completed')
                .reduce((sum, r) => sum + parseFloat(r.refund_amount), 0);
            
            if (refundedAmount >= parseFloat(sale.total) - 0.1) { // 0.1 tolerance
                return 'Fully Refunded';
            } else if (refundedAmount > 0) {
                return 'Partially Refunded';
            }
            return null;
        }
    }
}
</script>
@endsection

