@extends('layouts.app')

@section('content')
<div x-data="expenseManager()" x-init="loadExpenses(); loadAccounts()">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Expenses</h1>
                <p class="mt-2 text-sm text-gray-600">Track and manage expenses</p>
            </div>
            <div class="flex gap-2">
                <button @click="exportExpenses()" class="bg-indigo-100 text-indigo-700 px-4 py-2 rounded-md hover:bg-indigo-200">
                    Export CSV
                </button>
                <button type="button" @click.prevent="showAddModal = true" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                    Add Expense
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white shadow rounded-lg p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <input type="date" x-model="filters.date_from" @change="loadExpenses()" placeholder="Date From" class="w-full rounded-md border-gray-300">
                </div>
                <div>
                    <input type="date" x-model="filters.date_to" @change="loadExpenses()" placeholder="Date To" class="w-full rounded-md border-gray-300">
                </div>
                <div>
                    <select x-model="filters.category_id" @change="loadExpenses()" class="w-full rounded-md border-gray-300">
                        <option value="">All Categories</option>
                        <template x-for="cat in expenseCategories" :key="cat.id">
                            <option :value="cat.id" x-text="cat.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <input type="text" x-model="filters.search" @input.debounce.300ms="loadExpenses()" placeholder="Search..." class="w-full rounded-md border-gray-300">
                </div>
            </div>
        </div>

        <!-- Expenses Table -->
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payee</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="expense in expenses" :key="expense.id">
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="formatDate(expense.expense_date)"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="expense.payee"></td>
                            <td class="px-6 py-4 text-sm text-gray-500" x-text="expense.description"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="expense.category?.name || '-'"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900" x-text="formatCurrency(expense.amount)"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button @click="editExpense(expense)" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</button>
                                <button @click="deleteExpense(expense)" class="text-red-600 hover:text-red-900">Delete</button>
                            </td>
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
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" x-text="showAddModal ? 'Add Expense' : 'Edit Expense'"></h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Payee *</label>
                                <input type="text" x-model="form.payee" required class="mt-1 block w-full rounded-md border-gray-300">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Description *</label>
                                <textarea x-model="form.description" required rows="2" class="mt-1 block w-full rounded-md border-gray-300"></textarea>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Amount *</label>
                                    <input type="number" step="0.01" x-model="form.amount" required min="0" class="mt-1 block w-full rounded-md border-gray-300">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Expense Date *</label>
                                    <input type="date" x-model="form.expense_date" required class="mt-1 block w-full rounded-md border-gray-300">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Expense Category</label>
                                <select x-model="form.category_id" class="mt-1 block w-full rounded-md border-gray-300">
                                    <option value="">Select Category</option>
                                    <template x-for="cat in expenseCategories" :key="cat.id">
                                        <option :value="cat.id" x-text="cat.name"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Payment Account</label>
                                <select x-model="form.payment_account_id" class="mt-1 block w-full rounded-md border-gray-300">
                                    <option value="">Select Payment Account</option>
                                    <template x-for="account in paymentAccounts" :key="account.id">
                                        <option :value="account.id" x-text="`${account.code} - ${account.name}`"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" x-model="form.create_journal_entry" class="rounded border-gray-300 text-indigo-600">
                                    <span class="ml-2 text-sm text-gray-700">Create journal entry automatically</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm">
                            Save
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
    window.expenseManager = expenseManager;
    return {
        expenses: [],
        expenseCategories: [],
        paymentAccounts: [],
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
            payee: '',
            description: '',
            amount: 0,
            expense_date: new Date().toISOString().split('T')[0],
            category_id: null,
            payment_account_id: null,
            create_journal_entry: true
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

                // Load payment accounts (Assets like Cash, Bank)
                const accountsResponse = await axios.get('/accounting/chart-of-accounts?account_type=Asset');
                this.paymentAccounts = accountsResponse.data;
            } catch (error) {
                console.error('Error loading accounts:', error);
            }
        },

        editExpense(expense) {
            this.form = {
                id: expense.id,
                payee: expense.payee,
                description: expense.description,
                amount: expense.amount,
                expense_date: expense.expense_date,
                category_id: expense.category_id,
                payment_account_id: expense.payment_account_id,
                create_journal_entry: false
            };
            this.showEditModal = true;
        },

        async saveExpense() {
            try {
                if (this.form.id) {
                    await axios.put(`/expenses/${this.form.id}`, this.form);
                } else {
                    await axios.post('/expenses', this.form);
                }
                this.closeModal();
                this.loadExpenses();
                alert('Expense saved successfully');
            } catch (error) {
                alert('Error saving expense: ' + (error.response?.data?.message || error.message));
            }
        },

        async deleteExpense(expense) {
            if (!confirm(`Are you sure you want to delete this expense?`)) return;
            try {
                await axios.delete(`/expenses/${expense.id}`);
                this.loadExpenses();
                alert('Expense deleted successfully');
            } catch (error) {
                alert('Error deleting expense: ' + (error.response?.data?.message || error.message));
            }
        },

        closeModal() {
            this.showAddModal = false;
            this.showEditModal = false;
            this.form = {
                id: null,
                payee: '',
                description: '',
                amount: 0,
                expense_date: new Date().toISOString().split('T')[0],
                category_id: null,
                payment_account_id: null,
                create_journal_entry: true
            };
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

