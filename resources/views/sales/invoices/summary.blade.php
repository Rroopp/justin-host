@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8" x-data="summaryInvoiceApp()">
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6 bg-gray-50 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Generate Summary Invoice
            </h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">
                Select a facility to see their outstanding invoices, then select which ones to include in the summary.
            </p>
        </div>
        
        <div class="p-6">
            <form action="{{ route('sales.invoices.summary.print') }}" method="GET" target="_blank" class="space-y-6">
                
                <!-- Facility / Customer Select -->
                <div>
                    <label for="customer_id" class="block text-sm font-medium text-gray-700">Facility / Customer</label>
                    <div class="mt-1">
                        <select id="customer_id" name="customer_id" x-model="selectedCustomer" @change="fetchPendingInvoices()" required
                                class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                            <option value="">Select a facility...</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Filters -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50 p-4 rounded-md border border-gray-200">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Date Range</label>
                        <div class="mt-1 flex gap-2">
                            <div class="w-1/2">
                                <label for="date_from" class="sr-only">From</label>
                                <input type="date" x-model="filters.date_from" @change="fetchPendingInvoices()" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                            </div>
                            <div class="w-1/2">
                                <label for="date_to" class="sr-only">To</label>
                                <input type="date" x-model="filters.date_to" @change="fetchPendingInvoices()" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Quick Filters</label>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" @click="setRange('all')" class="px-3 py-1 bg-white border border-gray-300 rounded-md text-xs font-medium text-gray-700 hover:bg-gray-50">All Time</button>
                            <button type="button" @click="setRange('this_month')" class="px-3 py-1 bg-white border border-gray-300 rounded-md text-xs font-medium text-gray-700 hover:bg-gray-50">This Month</button>
                            <button type="button" @click="setRange('last_month')" class="px-3 py-1 bg-white border border-gray-300 rounded-md text-xs font-medium text-gray-700 hover:bg-gray-50">Last Month</button>
                            <button type="button" @click="setRange('this_year')" class="px-3 py-1 bg-white border border-gray-300 rounded-md text-xs font-medium text-gray-700 hover:bg-gray-50">This Year</button>
                            <button type="button" @click="setRange('last_3_months')" class="px-3 py-1 bg-white border border-gray-300 rounded-md text-xs font-medium text-gray-700 hover:bg-gray-50">Last 3 Months</button>
                        </div>
                    </div>
                </div>

                <!-- Loading State -->
                <div x-show="loading" class="text-center py-4 text-gray-500">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-indigo-500 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Fetching pending invoices...
                </div>

                <!-- Invoice List -->
                <div x-show="!loading && invoices.length > 0" class="border rounded-md overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 border-b flex justify-between items-center">
                        <h4 class="font-medium text-sm text-gray-700">Outstanding Invoices</h4>
                        <div class="text-sm">
                            <button type="button" @click="selectAll()" class="text-indigo-600 hover:text-indigo-800 mr-3">Select All</button>
                            <button type="button" @click="deselectAll()" class="text-gray-500 hover:text-gray-700">Select None</button>
                        </div>
                    </div>
                    <div class="max-h-96 overflow-y-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Include</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice #</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="inv in invoices" :key="inv.id">
                                    <tr class="hover:bg-gray-50 cursor-pointer" @click="toggleInvoice(inv.id)">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <input type="checkbox" name="invoice_ids[]" :value="inv.id" x-model="selectedInvoices" @click.stop
                                                   class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="inv.date"></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="inv.invoice_number"></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-indigo-600 font-bold text-right" x-text="formatCurrency(inv.balance)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 border-t flex justify-between items-center">
                        <span class="text-sm text-gray-700">Selected: <span class="font-bold" x-text="selectedInvoices.length"></span></span>
                        <span class="text-sm text-gray-700">Total Selected: <span class="font-bold text-indigo-700" x-text="calculateTotal()"></span></span>
                    </div>
                </div>

                <div x-show="!loading && selectedCustomer && invoices.length === 0" class="text-center py-8 bg-gray-50 rounded-md border border-dashed border-gray-300">
                    <p class="text-gray-500">No pending invoices found for this facility.</p>
                </div>

                <div class="flex items-center justify-end pt-4" x-show="invoices.length > 0">
                    <button type="submit" :disabled="selectedInvoices.length === 0"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="mr-2 -ml-1 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Generate Summary Invoice
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function summaryInvoiceApp() {
        return {
            selectedCustomer: '',
            invoices: [],
            selectedInvoices: [],
            loading: false,
            filters: {
                date_from: '',
                date_to: ''
            },
            
            async fetchPendingInvoices() {
                if (!this.selectedCustomer) {
                    this.invoices = [];
                    this.selectedInvoices = [];
                    return;
                }
                
                this.loading = true;
                this.invoices = [];
                // this.selectedInvoices = []; // Optional: clear on filter change? Maybe keep selection if possible? 
                // Better reset to avoid confusion about hidden items being selected.
                this.selectedInvoices = []; 
                
                try {
                    const params = new URLSearchParams();
                    if (this.filters.date_from) params.append('date_from', this.filters.date_from);
                    if (this.filters.date_to) params.append('date_to', this.filters.date_to);

                    const response = await axios.get(`/sales/invoices/pending/${this.selectedCustomer}?${params.toString()}`);
                    this.invoices = response.data;
                    this.selectAll();
                } catch (error) {
                    console.error('Error fetching invoices:', error);
                    alert('Failed to load pending invoices.');
                } finally {
                    this.loading = false;
                }
            },

            setRange(range) {
                const today = new Date();
                let from = null;
                let to = null;

                switch(range) {
                    case 'all':
                        from = '';
                        to = '';
                        break;
                    case 'this_month':
                        from = new Date(today.getFullYear(), today.getMonth(), 1);
                        to = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                        break;
                    case 'last_month':
                        from = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                        to = new Date(today.getFullYear(), today.getMonth(), 0);
                        break;
                    case 'this_year':
                        from = new Date(today.getFullYear(), 0, 1);
                        to = new Date(today.getFullYear(), 11, 31);
                        break;
                    case 'last_3_months':
                         from = new Date(today.getFullYear(), today.getMonth() - 3, 1);
                         to = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                         break;
                }

                if (from instanceof Date) {
                    this.filters.date_from = from.toISOString().split('T')[0];
                } else {
                     this.filters.date_from = '';
                }

                if (to instanceof Date) {
                    this.filters.date_to = to.toISOString().split('T')[0];
                } else {
                    this.filters.date_to = '';
                }

                this.fetchPendingInvoices();
            },
            
            selectAll() {
                this.selectedInvoices = this.invoices.map(inv => inv.id);
            },
            
            deselectAll() {
                this.selectedInvoices = [];
            },
            
            toggleInvoice(id) {
                if (this.selectedInvoices.includes(id)) {
                    this.selectedInvoices = this.selectedInvoices.filter(i => i !== id);
                } else {
                    this.selectedInvoices.push(id);
                }
            },
            
            calculateTotal() {
                const total = this.invoices
                    .filter(inv => this.selectedInvoices.includes(inv.id))
                    .reduce((sum, inv) => sum + inv.balance, 0);
                return this.formatCurrency(total);
            },
            
            formatCurrency(value) {
                return 'KSh ' + (parseFloat(value) || 0).toLocaleString('en-KE', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }
        }
    }
</script>
@endsection
