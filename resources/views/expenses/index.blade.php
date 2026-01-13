@extends('layouts.app')

@section('content')
<div x-data="expenseManager()" x-init="loadExpenses(); loadAccounts()" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Expenses</h1>
            <p class="mt-2 text-sm text-gray-600">Track and manage expenses and bills</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('expenses.unpaid') }}" class="bg-yellow-100 text-yellow-800 px-4 py-2 rounded-md hover:bg-yellow-200 border border-yellow-200 font-medium">
                View Unpaid Bills
            </a>
            <button @click="exportExpenses()" class="bg-indigo-100 text-indigo-700 px-4 py-2 rounded-md hover:bg-indigo-200">
                Export CSV
            </button>
            <button type="button" @click.prevent="openAddModal()" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                Record Transaction
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <input type="date" x-model="filters.date_from" @change="loadExpenses()" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <input type="date" x-model="filters.date_to" @change="loadExpenses()" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <select x-model="filters.category_id" @change="loadExpenses()" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All Categories</option>
                    <template x-for="cat in expenseCategories" :key="cat.id">
                        <option :value="cat.id" x-text="cat.name"></option>
                    </template>
                </select>
            </div>
            <div>
                <input type="text" x-model="filters.search" @input.debounce.300ms="loadExpenses()" placeholder="Search..." class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
        </div>
    </div>

    <!-- Expenses Table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payee / Vendor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <template x-for="expense in expenses" :key="expense.id">
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="formatDate(expense.expense_date)"></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <span x-text="expense.payee"></span>
                            <template x-if="expense.vendor">
                                <span class="text-xs text-gray-500 block" x-text="expense.vendor.name"></span>
                            </template>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500" x-text="expense.description"></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="expense.category?.name || '-'"></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900" x-text="formatCurrency(expense.amount)"></td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                            <span :class="{
                                'px-2 inline-flex text-xs leading-5 font-semibold rounded-full': true,
                                'bg-green-100 text-green-800': expense.status === 'paid',
                                'bg-red-100 text-red-800': expense.status === 'unpaid',
                                'bg-yellow-100 text-yellow-800': expense.status === 'partial'
                            }" x-text="expense.status.charAt(0).toUpperCase() + expense.status.slice(1)"></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button @click="editExpense(expense)" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                            <button @click="deleteExpense(expense)" class="text-red-600 hover:text-red-900">Delete</button>
                        </td>
                    </tr>
                </template>
                <template x-if="expenses.length === 0">
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">No records found</td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <!-- Add/Edit Modal -->
    <div x-show="showAddModal || showEditModal" class="fixed z-50 inset-0 overflow-y-auto" style="display: none;" x-cloak>
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeModal()"></div>
            
            <div class="relative inline-block align-middle bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form @submit.prevent="saveExpense()">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" x-text="form.id ? 'Edit Transaction' : 'Record Transaction'"></h3>
                        
                        <!-- Transaction Type Toggle -->
                        <div class="mb-5 flex rounded-md shadow-sm" role="group">
                            <button type="button" 
                                @click="form.status = 'paid'; form.payee = ''"
                                :class="{'bg-indigo-600 text-white': form.status === 'paid', 'bg-white text-gray-700 hover:bg-gray-50': form.status !== 'paid'}"
                                class="flex-1 px-4 py-2 text-sm font-medium border border-gray-300 rounded-l-md focus:z-10 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                                Direct Expense (Paid)
                            </button>
                            <button type="button" 
                                @click="form.status = 'unpaid'; form.payee = ''"
                                :class="{'bg-indigo-600 text-white': form.status === 'unpaid', 'bg-white text-gray-700 hover:bg-gray-50': form.status !== 'unpaid'}"
                                class="flex-1 px-4 py-2 text-sm font-medium border border-gray-300 rounded-r-md focus:z-10 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                                Bill (Pay Later)
                            </button>
                        </div>

                        <div class="space-y-4">
                            <!-- Vendor Selection (Visible for Bills) -->
                            <div x-show="form.status === 'unpaid'">
                                <label class="block text-sm font-medium text-gray-700">Vendor *</label>
                                <select x-model="form.vendor_id" @change="updatePayeeFromVendor()" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Select Vendor</option>
                                    <template x-for="vendor in vendors" :key="vendor.id">
                                        <option :value="vendor.id" x-text="vendor.name"></option>
                                    </template>
                                </select>
                            </div>

                            <!-- Payee (Manual entry if no vendor selected or Paid expense) -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Payee Name *</label>
                                <input type="text" x-model="form.payee" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <!-- Reference Number (Bill) -->
                            <div x-show="form.status === 'unpaid'">
                                <label class="block text-sm font-medium text-gray-700">Reference / Invoice #</label>
                                <input type="text" x-model="form.reference_number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <!-- Payment Account (Visible only for Paid Expenses) -->
                            <div x-show="form.status === 'paid'">
                                <label class="block text-sm font-medium text-gray-700">Payment Account (Source) *</label>
                                <select x-model="form.payment_account_id" :required="form.status === 'paid'" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Select Account</option>
                                    <template x-for="account in paymentAccounts" :key="account.id">
                                        <option :value="account.id" x-text="`${account.name}`"></option>
                                    </template>
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Amount *</label>
                                    <input type="number" step="0.01" x-model="form.amount" required min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Date *</label>
                                    <input type="date" x-model="form.expense_date" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            </div>

                            <!-- Due Date (Bill) -->
                            <div x-show="form.status === 'unpaid'">
                                <label class="block text-sm font-medium text-gray-700">Due Date</label>
                                <input type="date" x-model="form.due_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Category *</label>
                                <select x-model="form.category_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Select Category</option>
                                    <template x-for="cat in expenseCategories" :key="cat.id">
                                        <option :value="cat.id" x-text="cat.name"></option>
                                    </template>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Description *</label>
                                <textarea x-model="form.description" required rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm">
                            Save Transaction
                        </button>
                        <button type="button" @click="closeModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
window.expenseManager = function() {
    return {
        expenses: [],
        expenseCategories: [],
        paymentAccounts: [],
        vendors: [],
        filters: {
            date_from: '',
            date_to: '',
            category_id: '',
            search: ''
        },
        showAddModal: false,
        showEditModal: false,
        form: {
            id: null,
            status: 'paid', // paid or unpaid
            payee: '',
            description: '',
            amount: 0,
            expense_date: new Date().toISOString().split('T')[0],
            category_id: null,
            payment_account_id: null,
            vendor_id: null,
            due_date: null,
            reference_number: ''
        },

        init() {
            // Initial component setup if needed
        },

        async loadExpenses() {
            try {
                const params = new URLSearchParams();
                if (this.filters.date_from) params.append('date_from', this.filters.date_from);
                if (this.filters.date_to) params.append('date_to', this.filters.date_to);
                if (this.filters.category_id) params.append('category_id', this.filters.category_id);
                if (this.filters.search) params.append('search', this.filters.search);

                const response = await axios.get(`/expenses?${params}`);
                this.expenses = response.data.data || response.data;
            } catch (error) {
                console.error('Error loading expenses:', error);
            }
        },

        async loadAccounts() {
            try {
                // Load expense categories
                const categoriesResponse = await axios.get('/accounting/chart-of-accounts?account_type=Expense');
                this.expenseCategories = categoriesResponse.data;

                // Load payment accounts (Assets)
                const accountsResponse = await axios.get('/accounting/chart-of-accounts?account_type=Asset');
                this.paymentAccounts = accountsResponse.data;

                // Load Vendors (assuming you have an API endpoint or using the generic index)
                // For now, let's try to fetch vendors if the endpoint exists, otherwise empty
                // We exposed vendors resource, so likely /vendors return HTML. 
                // Ideally we need a JSON endpoint. Let's assume we can add one or use a workaround.
                // For this step, I'll attempt to fetch /vendors with Accept: application/json header
                // If not, we might need to add a specific API route.
                
                // HACK: Since VendorController::index returns view by default:
                // Let's rely on standard Laravel resource; usually it checks expectsJson().
                const vendorsResponse = await axios.get('/vendors', { headers: { 'Accept': 'application/json' } });
                // If it returns HTML (the view), this might fail gracefully or assign garbage.
                // VendorController::index in our code: returns view('vendors.index',...). 
                // It does NOT check expectsJson() in the code I wrote earlier. 
                // I need to update VendorController to support JSON return first!
                // But wait, I can modify the controller in a separate step. For now, let's assume it works or fix it.
                // Actually, let's fix the Controller to return JSON properly.
                
                if (vendorsResponse.headers['content-type'].includes('json')) {
                     this.vendors = vendorsResponse.data.data; 
                }
            } catch (error) {
                console.warn('Error loading auxiliary data:', error);
            }
        },

        openAddModal() {
            this.form = {
                id: null,
                status: 'paid',
                payee: '',
                description: '',
                amount: 0,
                expense_date: new Date().toISOString().split('T')[0],
                category_id: null,
                payment_account_id: null,
                vendor_id: null,
                due_date: null,
                reference_number: ''
            };
            this.showAddModal = true;
        },

        editExpense(expense) {
            this.form = {
                id: expense.id,
                status: expense.status,
                payee: expense.payee,
                description: expense.description,
                amount: expense.amount,
                expense_date: expense.expense_date,
                category_id: expense.category_id,
                payment_account_id: expense.payment_account_id,
                vendor_id: expense.vendor_id,
                due_date: expense.due_date,
                reference_number: expense.reference_number
            };
            this.showEditModal = true;
        },

        updatePayeeFromVendor() {
            const vendor = this.vendors.find(v => v.id == this.form.vendor_id);
            if (vendor) {
                this.form.payee = vendor.name;
            }
        },

        async saveExpense() {
            try {
                const method = this.form.id ? 'put' : 'post';
                const url = this.form.id ? `/expenses/${this.form.id}` : '/expenses';
                
                await axios[method](url, this.form);
                
                this.closeModal();
                this.loadExpenses();
                // alert('Transaction saved successfully'); // Optional, better UI feedback is nice
                window.location.reload(); // Simple reload to show flash messages from session
            } catch (error) {
                alert('Error saving transaction: ' + (error.response?.data?.message || error.response?.data?.error || error.message));
            }
        },

        async deleteExpense(expense) {
            if (!confirm(`Are you sure you want to delete this record?`)) return;
            try {
                await axios.delete(`/expenses/${expense.id}`);
                this.loadExpenses();
            } catch (error) {
                alert('Error deleting record: ' + (error.response?.data?.message || error.message));
            }
        },

        closeModal() {
            this.showAddModal = false;
            this.showEditModal = false;
        },

        exportExpenses() {
             const params = new URLSearchParams();
            if (this.filters.date_from) params.append('date_from', this.filters.date_from);
            if (this.filters.date_to) params.append('date_to', this.filters.date_to);
            if (this.filters.category_id) params.append('category_id', this.filters.category_id);
            if (this.filters.search) params.append('search', this.filters.search);
            params.append('export', 'true');
            
            window.location.href = `/expenses?${params.toString()}`;
        },

        formatCurrency(amount) {
            return 'KSh ' + parseFloat(amount || 0).toLocaleString('en-KE', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },

        formatDate(dateString) {
            if (!dateString) return '';
            return new Date(dateString).toLocaleDateString('en-KE');
        }
    }
}
</script>
@endsection

