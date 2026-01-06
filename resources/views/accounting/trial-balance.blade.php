@extends('layouts.app')

@section('content')
<div x-data="trialBalanceManager()" x-init="load()">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Trial Balance</h1>
            <p class="mt-2 text-sm text-gray-600">Balances as of a selected date</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('accounting.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">Chart of Accounts</a>
            <a href="{{ route('accounting.journal-entries') }}" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">Journal Entries</a>
            <a href="{{ route('accounting.financial-statements') }}" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">Financial Statements</a>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg p-4 mb-6">
        <div class="flex flex-col md:flex-row md:items-end gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">As of date</label>
                <input type="date" x-model="date" @change="load()" class="rounded-md border-gray-300">
            </div>
            <div class="text-sm text-gray-600">
                <div>Total Debits: <span class="font-semibold" x-text="formatCurrency(totalDebits)"></span></div>
                <div>Total Credits: <span class="font-semibold" x-text="formatCurrency(totalCredits)"></span></div>
            </div>
        </div>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Account</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Debit</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Credit</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <template x-for="row in rows" :key="row.account_code">
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="row.account_code"></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="row.account_name"></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="row.account_type"></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900" x-text="formatCurrency(row.debit)"></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900" x-text="formatCurrency(row.credit)"></td>
                    </tr>
                </template>
                <tr x-show="rows.length === 0">
                    <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">No posted entries found for this date.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
window.trialBalanceManager = function() {
    return {
        date: '{{ $date ?? now()->toDateString() }}',
        rows: [],
        totalDebits: 0,
        totalCredits: 0,

        async load() {
            try {
                const response = await axios.get(`/accounting/trial-balance?date=${encodeURIComponent(this.date)}`);
                this.rows = response.data.accounts || [];
                this.totalDebits = response.data.total_debits || 0;
                this.totalCredits = response.data.total_credits || 0;
            } catch (error) {
                alert('Error loading trial balance: ' + (error.response?.data?.message || error.message));
            }
        },

        formatCurrency(amount) {
            return 'KSh ' + parseFloat(amount || 0).toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    }
}
</script>
@endsection





