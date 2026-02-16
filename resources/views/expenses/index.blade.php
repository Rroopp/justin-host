@extends('layouts.app')

@section('content')
<div x-data="expenseManager({{ $categories }}, {{ $paymentAccounts }}, {{ $vendors }})" x-init="loadExpenses()" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
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
    <div class="bg-white shadow sm:rounded-md">
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
                                'bg-yellow-100 text-yellow-800': expense.status === 'partial',
                                'bg-gray-100 text-gray-800': expense.status === 'draft'
                            }" x-text="expense.status.charAt(0).toUpperCase() + expense.status.slice(1)"></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <template x-if="expense.status === 'draft'">
                                <button @click="approveExpense(expense)" class="text-green-600 hover:text-green-900 mr-3">Approve</button>
                            </template>
                            <template x-if="['paid', 'partial'].includes(expense.status)">
                                <span>
                                    <span class="text-gray-400 cursor-not-allowed mr-3" title="Cannot edit paid expense">Edit</span>
                                    <span class="text-gray-400 cursor-not-allowed" title="Cannot delete paid expense">Delete</span>
                                </span>
                            </template>
                            <template x-if="!['paid', 'partial'].includes(expense.status)">
                                <span>
                                    <button @click="editExpense(expense)" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                                    <button @click="deleteExpense(expense)" class="text-red-600 hover:text-red-900">Delete</button>
                                </span>
                            </template>
                            <template x-if="expense.invoice_file_path">
                                <a :href="'/storage/' + expense.invoice_file_path" target="_blank" class="text-gray-500 hover:text-gray-700 ml-3" title="View Invoice">
                                    <svg class="h-5 w-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                </a>
                            </template>
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
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="closeModal()"></div>
            
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
                                Paid
                            </button>
                            <button type="button" 
                                @click="form.status = 'unpaid'; form.payee = ''"
                                :class="{'bg-indigo-600 text-white': form.status === 'unpaid', 'bg-white text-gray-700 hover:bg-gray-50': form.status !== 'unpaid'}"
                                class="flex-1 px-4 py-2 text-sm font-medium border border-gray-300 focus:z-10 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                                Bill
                            </button>
                            <button type="button" 
                                @click="form.status = 'draft'; form.payee = ''"
                                :class="{'bg-indigo-600 text-white': form.status === 'draft', 'bg-white text-gray-700 hover:bg-gray-50': form.status !== 'draft'}"
                                class="flex-1 px-4 py-2 text-sm font-medium border border-gray-300 rounded-r-md focus:z-10 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                                Draft
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

                            <!-- Invoice File Upload -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Invoice File (PDF/Image)</label>
                                <input type="file" @change="handleFileUpload($event)" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full text-sm text-gray-500
                                  file:mr-4 file:py-2 file:px-4
                                  file:rounded-full file:border-0
                                  file:text-sm file:font-semibold
                                  file:bg-indigo-50 file:text-indigo-700
                                  hover:file:bg-indigo-100">
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
window.expenseManager = function(initialCategories, initialAccounts, initialVendors) {
    return {
        expenses: [],
        expenseCategories: initialCategories || [],
        paymentAccounts: initialAccounts || [],
        vendors: initialVendors || [],
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
            reference_number: '',
            invoice_file: null
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
                // Categories and Payment Accounts are now passed from Controller
                
                // Load Vendors (passed from controller)
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

        handleFileUpload(event) {
            this.form.invoice_file = event.target.files[0];
        },

        async saveExpense() {
            try {
                const formData = new FormData();
                formData.append('payee', this.form.payee);
                formData.append('description', this.form.description);
                formData.append('amount', this.form.amount);
                formData.append('expense_date', this.form.expense_date);
                formData.append('category_id', this.form.category_id);
                formData.append('status', this.form.status);
                
                if (this.form.payment_account_id) formData.append('payment_account_id', this.form.payment_account_id);
                if (this.form.vendor_id) formData.append('vendor_id', this.form.vendor_id);
                if (this.form.due_date) formData.append('due_date', this.form.due_date);
                if (this.form.reference_number) formData.append('reference_number', this.form.reference_number);
                if (this.form.invoice_file) formData.append('invoice_file', this.form.invoice_file);
                
                // If editing, use POST with _method=PUT to handle file upload
                if (this.form.id) {
                    formData.append('_method', 'PUT');
                    await axios.post(`/expenses/${this.form.id}`, formData, {
                        headers: { 'Content-Type': 'multipart/form-data' }
                    });
                } else {
                    await axios.post('/expenses', formData, {
                         headers: { 'Content-Type': 'multipart/form-data' }
                    });
                }
                
                this.closeModal();
                this.loadExpenses();
                // window.location.reload(); // Reload needed for flash message? Alternatively just loadExpenses
                // Let's reload to be safe and clear form state cleanly
                window.location.reload();
            } catch (error) {
                alert('Error saving transaction: ' + (error.response?.data?.message || error.response?.data?.error || error.message));
            }
        },
        
        async approveExpense(expense) {
            if (!confirm(`Are you sure you want to approve this draft? It will be posted to the ledger.`)) return;
            try {
                await axios.post(`/expenses/${expense.id}/approve`);
                window.location.reload();
            } catch (error) {
                 alert('Error approving expense: ' + (error.response?.data?.message || error.message));
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

