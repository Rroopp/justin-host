@extends('layouts.app')

@section('content')
<div x-data="journalEntryManager()" x-init="loadEntries()">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Journal Entries</h1>
                <p class="mt-2 text-sm text-gray-600">Double-entry bookkeeping</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('accounting.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">
                    Chart of Accounts
                </a>
                <a href="{{ route('accounting.trial-balance') }}" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">
                    Trial Balance
                </a>
                <a href="{{ route('accounting.financial-statements') }}" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">
                    Financial Statements
                </a>
                <button type="button" @click.prevent="openNewEntry()" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                    New Entry
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white shadow rounded-lg p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <select x-model="filters.status" @change="loadEntries()" class="w-full rounded-md border-gray-300">
                        <option value="">All Status</option>
                        <option value="DRAFT">Draft</option>
                        <option value="POSTED">Posted</option>
                        <option value="CANCELLED">Cancelled</option>
                    </select>
                </div>
                <div>
                    <input type="date" x-model="filters.date_from" @change="loadEntries()" class="w-full rounded-md border-gray-300">
                </div>
                <div>
                    <input type="date" x-model="filters.date_to" @change="loadEntries()" class="w-full rounded-md border-gray-300">
                </div>
                <div>
                    <input type="text" x-model="filters.search" @input.debounce.300ms="loadEntries()" placeholder="Search..." class="w-full rounded-md border-gray-300">
                </div>
            </div>
        </div>

        <!-- Journal Entries Table -->
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Entry #</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Debit</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Credit</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="entry in entries" :key="entry.id">
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="entry.entry_number"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="formatDate(entry.entry_date)"></td>
                            <td class="px-6 py-4 text-sm text-gray-900" x-text="entry.description"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900" x-text="formatCurrency(entry.total_debit)"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900" x-text="formatCurrency(entry.total_credit)"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span 
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    :class="{
                                        'bg-yellow-100 text-yellow-800': entry.status === 'DRAFT',
                                        'bg-green-100 text-green-800': entry.status === 'POSTED',
                                        'bg-red-100 text-red-800': entry.status === 'CANCELLED'
                                    }"
                                    x-text="entry.status"
                                ></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button type="button" @click="openViewEntry(entry)" class="text-indigo-600 hover:text-indigo-900 mr-3">View</button>
                                <button type="button" x-show="entry.status === 'DRAFT'" @click="openEditEntry(entry)" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                                <button type="button" x-show="entry.status === 'DRAFT'" @click="postEntry(entry)" class="text-green-600 hover:text-green-900 mr-3">Post</button>
                                <button type="button" x-show="entry.status === 'POSTED'" @click="unpostEntry(entry)" class="text-yellow-600 hover:text-yellow-900 mr-3">Unpost</button>
                                <button type="button" x-show="entry.status !== 'CANCELLED'" @click="cancelEntry(entry)" class="text-red-600 hover:text-red-900">Cancel</button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="entries.length === 0">
                        <td colspan="7" class="px-6 py-10 text-center text-sm text-gray-500">No journal entries found.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="mt-4 flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6 shadow rounded-lg" x-show="pagination.total > 0">
            <div class="flex flex-1 justify-between sm:hidden">
                <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page <= 1" class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50">Previous</button>
                <button @click="changePage(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page" class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50">Next</button>
            </div>
            <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700">
                        Showing
                        <span class="font-medium" x-text="pagination.from"></span>
                        to
                        <span class="font-medium" x-text="pagination.to"></span>
                        of
                        <span class="font-medium" x-text="pagination.total"></span>
                        results
                    </p>
                </div>
                <div>
                    <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                        <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page <= 1" class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50">
                            <span class="sr-only">Previous</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <button @click="changePage(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page" class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50">
                            <span class="sr-only">Next</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </nav>
                </div>
            </div>
        </div>

    <!-- Add/Edit Entry Modal -->
    <div x-show="showEntryModal" class="fixed z-50 inset-0 overflow-y-auto" style="display: none;" x-cloak>
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="closeEntryModal()"></div>
            <div class="relative inline-block align-middle bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <form @submit.prevent="saveEntry()">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" x-text="form.id ? 'Edit Journal Entry' : 'New Journal Entry'"></h3>
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Entry Date *</label>
                                    <input type="date" x-model="form.entry_date" required class="mt-1 block w-full rounded-md border-gray-300">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Description *</label>
                                    <input type="text" x-model="form.description" required class="mt-1 block w-full rounded-md border-gray-300">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Entry Lines *</label>
                                <div class="space-y-2">
                                    <template x-for="(line, index) in form.lines" :key="index">
                                        <div class="flex gap-2 items-center">
                                            <select x-model="line.account_id" class="flex-1 rounded-md border-gray-300">
                                                <option value="">Select Account</option>
                                                <template x-for="account in accounts" :key="account.id">
                                                    <option :value="account.id" x-text="`${account.code} - ${account.name}`"></option>
                                                </template>
                                            </select>
                                            <input type="number" step="0.01" x-model="line.debit_amount" @input="onDebitChange(line)" placeholder="Debit" min="0" class="w-28 rounded-md border-gray-300">
                                            <input type="number" step="0.01" x-model="line.credit_amount" @input="onCreditChange(line)" placeholder="Credit" min="0" class="w-28 rounded-md border-gray-300">
                                            <input type="text" x-model="line.description" placeholder="Line memo (optional)" class="flex-1 rounded-md border-gray-300">
                                            <button type="button" @click="removeLine(index)" class="text-red-600 hover:text-red-900">×</button>
                                        </div>
                                    </template>
                                    <button type="button" @click="addLine()" class="text-indigo-600 hover:text-indigo-900 text-sm">+ Add Line</button>
                                </div>
                                <div class="mt-4 border-t pt-4">
                                    <div class="flex justify-between font-bold">
                                        <span>Total Debits:</span>
                                        <span x-text="formatCurrency(calculateTotalDebits())"></span>
                                    </div>
                                    <div class="flex justify-between font-bold">
                                        <span>Total Credits:</span>
                                        <span x-text="formatCurrency(calculateTotalCredits())"></span>
                                    </div>
                                    <div class="flex justify-between font-bold text-lg" :class="isBalanced() ? 'text-green-600' : 'text-red-600'">
                                        <span>Balance:</span>
                                        <span x-text="formatCurrency(Math.abs(calculateTotalDebits() - calculateTotalCredits()))"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" :disabled="!isBalanced()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed sm:ml-3 sm:w-auto sm:text-sm">
                            <span x-text="form.id ? 'Save Changes' : 'Save Entry'"></span>
                        </button>
                        <button type="button" @click="closeEntryModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Entry Modal -->
    <div x-show="showViewModal" class="fixed z-50 inset-0 overflow-y-auto" style="display:none;" x-cloak>
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="closeViewModal()"></div>
            <div class="relative inline-block align-middle bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Journal Entry Details</h3>
                            <p class="mt-1 text-sm text-gray-600">
                                <span class="font-medium" x-text="selectedEntry?.entry_number"></span>
                                <span class="text-gray-400">•</span>
                                <span x-text="formatDate(selectedEntry?.entry_date)"></span>
                            </p>
                        </div>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                              :class="{
                                'bg-yellow-100 text-yellow-800': selectedEntry?.status === 'DRAFT',
                                'bg-green-100 text-green-800': selectedEntry?.status === 'POSTED',
                                'bg-red-100 text-red-800': selectedEntry?.status === 'CANCELLED'
                              }"
                              x-text="selectedEntry?.status"></span>
                    </div>

                    <div class="mt-4">
                        <div class="text-sm text-gray-700 font-medium">Description</div>
                        <div class="text-sm text-gray-900" x-text="selectedEntry?.description"></div>
                    </div>

                    <div class="mt-6 border-t pt-4">
                        <h4 class="text-sm font-semibold text-gray-900 mb-2">Lines</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Account</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Memo</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Debit</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Credit</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template x-for="(line, idx) in (selectedEntry?.lines || [])" :key="idx">
                                        <tr>
                                            <td class="px-4 py-2 text-sm text-gray-900" x-text="`${line.account?.code || ''} - ${line.account?.name || ''}`"></td>
                                            <td class="px-4 py-2 text-sm text-gray-500" x-text="line.description || '-'"></td>
                                            <td class="px-4 py-2 text-sm text-right text-gray-900" x-text="formatCurrency(line.debit_amount)"></td>
                                            <td class="px-4 py-2 text-sm text-right text-gray-900" x-text="formatCurrency(line.credit_amount)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 flex justify-end gap-8 text-sm">
                            <div>Total Debits: <span class="font-semibold" x-text="formatCurrency(selectedEntry?.total_debit)"></span></div>
                            <div>Total Credits: <span class="font-semibold" x-text="formatCurrency(selectedEntry?.total_credit)"></span></div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-4 py-3 sm:px-6 flex justify-end gap-2">
                    <button type="button" class="px-4 py-2 rounded-md border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50" @click="closeViewModal()">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.journalEntryManager = function() {
    return {
        entries: [],
        accounts: [],
        filters: {
            status: '',
            date_from: '',
            date_to: '',
            search: ''
        },
        pagination: {
            current_page: 1,
            last_page: 1,
            from: 0,
            to: 0,
            total: 0
        },
        showEntryModal: false,
        showViewModal: false,
        selectedEntry: null,
        form: {
            id: null,
            entry_date: new Date().toISOString().split('T')[0],
            description: '',
            lines: [
                { account_id: '', debit_amount: 0, credit_amount: 0, description: '' },
                { account_id: '', debit_amount: 0, credit_amount: 0, description: '' }
            ]
        },

        async loadEntries(page = 1) {
            try {
                const params = new URLSearchParams();
                params.append('page', page);
                if (this.filters.status) params.append('status', this.filters.status);
                if (this.filters.date_from) params.append('date_from', this.filters.date_from);
                if (this.filters.date_to) params.append('date_to', this.filters.date_to);
                if (this.filters.search) params.append('search', this.filters.search);

                const response = await axios.get(`/accounting/journal-entries?${params}`);
                
                if (response.data.data) {
                    // Paginated response
                    this.entries = response.data.data;
                    this.pagination = {
                        current_page: response.data.current_page,
                        last_page: response.data.last_page,
                        from: response.data.from,
                        to: response.data.to,
                        total: response.data.total
                    };
                } else {
                    // Fallback for non-paginated (though controller uses paginate)
                    this.entries = response.data;
                    this.pagination.total = this.entries.length;
                }
            } catch (error) {
                console.error('Error loading entries:', error);
            }
        },

        changePage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.loadEntries(page);
            }
        },

        async loadAccounts() {
            try {
                const response = await axios.get('/accounting/chart-of-accounts');
                this.accounts = response.data;
            } catch (error) {
                console.error('Error loading accounts:', error);
            }
        },

        openNewEntry() {
            this.form = {
                id: null,
                entry_date: new Date().toISOString().split('T')[0],
                description: '',
                lines: [
                    { account_id: '', debit_amount: 0, credit_amount: 0, description: '' },
                    { account_id: '', debit_amount: 0, credit_amount: 0, description: '' }
                ]
            };
            this.showEntryModal = true;
            this.loadAccounts();
        },

        openEditEntry(entry) {
            this.form = {
                id: entry.id,
                entry_date: (entry.entry_date || '').split('T')[0] || new Date().toISOString().split('T')[0],
                description: entry.description || '',
                lines: (entry.lines || []).map(l => ({
                    account_id: l.account_id || '',
                    debit_amount: l.debit_amount || 0,
                    credit_amount: l.credit_amount || 0,
                    description: l.description || ''
                }))
            };
            if (this.form.lines.length < 2) {
                this.form.lines.push({ account_id: '', debit_amount: 0, credit_amount: 0, description: '' });
            }
            this.showEntryModal = true;
            this.loadAccounts();
        },

        closeEntryModal() {
            this.showEntryModal = false;
        },

        openViewEntry(entry) {
            this.selectedEntry = entry;
            this.showViewModal = true;
        },

        closeViewModal() {
            this.showViewModal = false;
            this.selectedEntry = null;
        },

        addLine() {
            this.form.lines.push({ account_id: '', debit_amount: 0, credit_amount: 0, description: '' });
        },

        removeLine(index) {
            if (this.form.lines.length > 2) {
                this.form.lines.splice(index, 1);
            }
        },

        calculateTotalDebits() {
            return this.form.lines.reduce((sum, line) => sum + (parseFloat(line.debit_amount) || 0), 0);
        },

        calculateTotalCredits() {
            return this.form.lines.reduce((sum, line) => sum + (parseFloat(line.credit_amount) || 0), 0);
        },

        isBalanced() {
            return Math.abs(this.calculateTotalDebits() - this.calculateTotalCredits()) < 0.01;
        },

        onDebitChange(line) {
            // keep entries clean: a line is either debit or credit
            if (parseFloat(line.debit_amount || 0) > 0) line.credit_amount = 0;
        },

        onCreditChange(line) {
            if (parseFloat(line.credit_amount || 0) > 0) line.debit_amount = 0;
        },

        async saveEntry() {
            if (!this.isBalanced()) {
                alert('Journal entry must be balanced!');
                return;
            }

            try {
                if (this.form.id) {
                    await axios.put(`/accounting/journal-entries/${this.form.id}`, this.form);
                } else {
                    await axios.post('/accounting/journal-entries', this.form);
                }
                this.showEntryModal = false;
                this.loadEntries();
                alert('Journal entry saved successfully');
            } catch (error) {
                alert('Error creating entry: ' + (error.response?.data?.error || error.message));
            }
        },

        async postEntry(entry) {
            if (!confirm('Post this journal entry?')) return;
            try {
                await axios.post(`/accounting/journal-entries/${entry.id}/post`);
                this.loadEntries();
                alert('Entry posted successfully');
            } catch (error) {
                alert('Error posting entry: ' + (error.response?.data?.error || error.message));
            }
        },

        async unpostEntry(entry) {
            if (!confirm('Unpost this journal entry (back to DRAFT)?')) return;
            try {
                await axios.post(`/accounting/journal-entries/${entry.id}/unpost`);
                this.loadEntries();
                alert('Entry unposted successfully');
            } catch (error) {
                alert('Error unposting entry: ' + (error.response?.data?.error || error.message));
            }
        },

        async cancelEntry(entry) {
            if (!confirm('Cancel this journal entry?')) return;
            try {
                await axios.post(`/accounting/journal-entries/${entry.id}/cancel`);
                this.loadEntries();
                alert('Entry cancelled successfully');
            } catch (error) {
                alert('Error cancelling entry: ' + (error.response?.data?.error || error.message));
            }
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

