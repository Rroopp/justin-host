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

    <div class="space-y-8">
        <!-- Profit & Loss -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-900">Profit & Loss Statement</h2>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Income Section -->
                <div>
                    <h3 class="font-medium text-gray-700 mb-3 uppercase tracking-wide text-xs">Income / Revenue</h3>
                    <table class="min-w-full">
                        <tbody>
                            <template x-for="acc in pl.details?.income || []" :key="acc.code">
                                <tr class="text-sm">
                                    <td class="py-1 text-gray-600" x-text="acc.name"></td>
                                    <td class="py-1 text-gray-900 text-right font-mono" x-text="formatCurrency(acc.balance)"></td>
                                </tr>
                            </template>
                            <tr class="font-semibold border-t border-gray-200">
                                <td class="py-2 text-gray-900 pt-3">Total Income</td>
                                <td class="py-2 text-gray-900 text-right pt-3" x-text="formatCurrency(pl.income)"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Expense Section -->
                <div>
                    <h3 class="font-medium text-gray-700 mb-3 uppercase tracking-wide text-xs">Operating Expenses</h3>
                    <table class="min-w-full">
                        <tbody>
                            <template x-for="acc in pl.details?.expenses || []" :key="acc.code">
                                <tr class="text-sm">
                                    <td class="py-1 text-gray-600" x-text="acc.name"></td>
                                    <td class="py-1 text-gray-900 text-right font-mono" x-text="formatCurrency(acc.balance)"></td>
                                </tr>
                            </template>
                            <tr class="font-semibold border-t border-gray-200">
                                <td class="py-2 text-gray-900 pt-3">Total Expenses</td>
                                <td class="py-2 text-gray-900 text-right pt-3" x-text="formatCurrency(pl.expenses)"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-between items-center">
                <span class="text-lg font-bold text-gray-900">Net Income</span>
                <span class="text-lg font-bold" :class="pl.net_income >= 0 ? 'text-green-700' : 'text-red-700'" x-text="formatCurrency(pl.net_income)"></span>
            </div>
        </div>

        <!-- Balance Sheet -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-900">Balance Sheet</h2>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Assets -->
                <div>
                    <h3 class="font-medium text-gray-700 mb-3 uppercase tracking-wide text-xs">Assets</h3>
                    <table class="min-w-full">
                        <tbody>
                            <template x-for="acc in bs.details?.assets || []" :key="acc.code">
                                <tr class="text-sm">
                                    <td class="py-1 text-gray-600" x-text="acc.name"></td>
                                    <td class="py-1 text-gray-900 text-right font-mono" x-text="formatCurrency(acc.balance)"></td>
                                </tr>
                            </template>
                            <tr class="font-semibold border-t border-gray-200">
                                <td class="py-2 text-gray-900 pt-3">Total Assets</td>
                                <td class="py-2 text-gray-900 text-right pt-3" x-text="formatCurrency(bs.assets)"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Liabilities & Equity -->
                <div class="space-y-6">
                    <!-- Liabilities -->
                    <div>
                        <h3 class="font-medium text-gray-700 mb-3 uppercase tracking-wide text-xs">Liabilities</h3>
                        <table class="min-w-full">
                            <tbody>
                                <template x-for="acc in bs.details?.liabilities || []" :key="acc.code">
                                    <tr class="text-sm">
                                        <td class="py-1 text-gray-600" x-text="acc.name"></td>
                                        <td class="py-1 text-gray-900 text-right font-mono" x-text="formatCurrency(acc.balance)"></td>
                                    </tr>
                                </template>
                                <tr class="font-semibold border-t border-gray-200">
                                    <td class="py-2 text-gray-900 pt-3">Total Liabilities</td>
                                    <td class="py-2 text-gray-900 text-right pt-3" x-text="formatCurrency(bs.liabilities)"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Equity -->
                    <div>
                        <h3 class="font-medium text-gray-700 mb-3 uppercase tracking-wide text-xs">Equity</h3>
                        <table class="min-w-full">
                            <tbody>
                                <template x-for="acc in bs.details?.equity || []" :key="acc.code">
                                    <tr class="text-sm">
                                        <td class="py-1 text-gray-600" x-text="acc.name"></td>
                                        <td class="py-1 text-gray-900 text-right font-mono" x-text="formatCurrency(acc.balance)"></td>
                                    </tr>
                                </template>
                                <!-- Net Income Line -->
                                <tr class="text-sm italic text-gray-500">
                                    <td class="py-1">Net Income (Current Period)</td>
                                    <td class="py-1 text-right font-mono" x-text="formatCurrency(pl.net_income)"></td>
                                </tr>
                                <tr class="font-semibold border-t border-gray-200">
                                    <td class="py-2 text-gray-900 pt-3">Total Equity</td>
                                    <td class="py-2 text-gray-900 text-right pt-3" x-text="formatCurrency(bs.equity)"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
             <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-between items-center">
                <span class="text-lg font-bold text-gray-900">Total Liabilities & Equity</span>
                <span class="text-lg font-bold text-gray-900" x-text="formatCurrency(bs.total_liabilities_equity)"></span>
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





