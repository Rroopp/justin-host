@extends('layouts.app')

@section('content')
<div x-data="financialStatementsManager()" x-init="load()">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Financial Statements</h1>
            <p class="mt-2 text-sm text-gray-600">Profit & Loss and Balance Sheet as of a selected date</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('accounting.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">Chart of Accounts</a>
            <a href="{{ route('accounting.journal-entries') }}" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">Journal Entries</a>
            <a href="{{ route('accounting.trial-balance') }}" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">Trial Balance</a>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg p-4 mb-6 flex flex-col md:flex-row md:items-end gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">As of date</label>
            <input type="date" x-model="date" @change="load()" class="rounded-md border-gray-300">
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Profit & Loss -->
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Profit &amp; Loss</h2>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span>Total Income</span>
                    <span class="font-medium" x-text="formatCurrency(pl.income)"></span>
                </div>
                <div class="flex justify-between">
                    <span>Total Expenses</span>
                    <span class="font-medium" x-text="formatCurrency(pl.expenses)"></span>
                </div>
                <div class="border-t pt-3 flex justify-between text-base font-semibold" :class="pl.net_income >= 0 ? 'text-green-700' : 'text-red-700'">
                    <span>Net Income</span>
                    <span x-text="formatCurrency(pl.net_income)"></span>
                </div>
            </div>
        </div>

        <!-- Balance Sheet -->
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Balance Sheet</h2>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span>Total Assets</span>
                    <span class="font-medium" x-text="formatCurrency(bs.assets)"></span>
                </div>
                <div class="flex justify-between">
                    <span>Total Liabilities</span>
                    <span class="font-medium" x-text="formatCurrency(bs.liabilities)"></span>
                </div>
                <div class="flex justify-between">
                    <span>Total Equity (incl. net income)</span>
                    <span class="font-medium" x-text="formatCurrency(bs.equity)"></span>
                </div>
                <div class="border-t pt-3 flex justify-between text-base font-semibold">
                    <span>Liabilities + Equity</span>
                    <span x-text="formatCurrency(bs.total_liabilities_equity)"></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.financialStatementsManager = function() {
    return {
        date: '{{ $date ?? now()->toDateString() }}',
        pl: { income: 0, expenses: 0, net_income: 0 },
        bs: { assets: 0, liabilities: 0, equity: 0, total_liabilities_equity: 0 },

        async load() {
            try {
                const response = await axios.get(`/accounting/financial-statements?date=${encodeURIComponent(this.date)}`);
                this.pl = response.data.profit_loss || this.pl;
                this.bs = response.data.balance_sheet || this.bs;
            } catch (error) {
                alert('Error loading financial statements: ' + (error.response?.data?.message || error.message));
            }
        },

        formatCurrency(amount) {
            return 'KSh ' + parseFloat(amount || 0).toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    }
}
</script>
@endsection





