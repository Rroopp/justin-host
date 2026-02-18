@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Analytics & Reports</h1>
        <p class="text-gray-600 mt-1">Detailed business insights and AI-powered analysis.</p>
    </div>

    <!-- Reports Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <!-- Sales Report -->
        <a href="{{ route('reports.sales') }}" class="block p-6 bg-white rounded-lg shadow hover:shadow-md transition-shadow">
            <div class="flex items-center mb-4">
                <div class="p-3 rounded-full bg-indigo-100 text-indigo-600">
                    <i class="fas fa-chart-line text-xl"></i>
                </div>
                <h3 class="ml-4 text-xl font-semibold text-gray-900">Sales Analytics</h3>
            </div>
            <p class="text-gray-600">Deep dive into sales performance, trends over time, and category breakdowns.</p>
        </a>

        <!-- Inventory Report -->
        <a href="{{ route('reports.inventory') }}" class="block p-6 bg-white rounded-lg shadow hover:shadow-md transition-shadow">
            <div class="flex items-center mb-4">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <i class="fas fa-boxes text-xl"></i>
                </div>
                <h3 class="ml-4 text-xl font-semibold text-gray-900">Inventory Health</h3>
            </div>
            <p class="text-gray-600">Stock valuation, low stock alerts, and category distribution analysis.</p>
        </a>

        <!-- Budgets -->
        <a href="{{ route('budgets.dashboard') }}" class="block p-6 bg-white rounded-lg shadow hover:shadow-md transition-shadow">
            <div class="flex items-center mb-4">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <i class="fas fa-wallet text-xl"></i>
                </div>
                <h3 class="ml-4 text-xl font-semibold text-gray-900">Budget Performance</h3>
            </div>
            <p class="text-gray-600">Track spending against allocated budgets and view variance reports.</p>
        </a>

        <!-- Profit & Loss -->
        <a href="{{ route('reports.profit-loss') }}" class="block p-6 bg-white rounded-lg shadow hover:shadow-md transition-shadow">
            <div class="flex items-center mb-4">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-file-invoice-dollar text-xl"></i>
                </div>
                <h3 class="ml-4 text-xl font-semibold text-gray-900">Profit & Loss</h3>
            </div>
            <p class="text-gray-600">Official statement of Revenue, COGS, and Expenses.</p>
        </a>

        <!-- Expense Analysis -->
        <a href="{{ route('reports.expenses') }}" class="block p-6 bg-white rounded-lg shadow hover:shadow-md transition-shadow">
            <div class="flex items-center mb-4">
                <div class="p-3 rounded-full bg-red-100 text-red-600">
                    <i class="fas fa-receipt text-xl"></i>
                </div>
                <h3 class="ml-4 text-xl font-semibold text-gray-900">Expense Analysis</h3>
            </div>
            <p class="text-gray-600">Deep dive into operational spending and categories.</p>
        </a>

        <!-- Deep Compounded Analysis -->
        <a href="{{ route('reports.deep-analysis') }}" class="block p-6 bg-white rounded-lg shadow hover:shadow-md transition-shadow relative group">
            <div class="absolute top-0 right-0 -mr-4 -mt-4 w-16 h-16 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full opacity-10 group-hover:opacity-20 transition-opacity"></div>
            <div class="flex items-center mb-4">
                <div class="p-3 rounded-full bg-indigo-100 text-indigo-600">
                    <i class="fas fa-brain text-xl"></i>
                </div>
                <h3 class="ml-4 text-xl font-semibold text-gray-900">Deep Compounded Analysis</h3>
            </div>
            <p class="text-gray-600">System-generated insights on staff profitability, product margins, and strategic opportunities.</p>
        </a>
        <!-- Supplier Performance -->
        <a href="{{ route('reports.suppliers') }}" class="block p-6 bg-white rounded-lg shadow hover:shadow-md transition-shadow">
            <div class="flex items-center mb-4">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <i class="fas fa-truck text-xl"></i>
                </div>
                <h3 class="ml-4 text-xl font-semibold text-gray-900">Supplier Performance</h3>
            </div>
            <p class="text-gray-600">Analyze vendor reliability, total spend, and order history.</p>
        </a>

        <!-- Inventory Aging -->
        <a href="{{ route('reports.inventory-aging') }}" class="block p-6 bg-white rounded-lg shadow hover:shadow-md transition-shadow">
            <div class="flex items-center mb-4">
                <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                    <i class="fas fa-hourglass-half text-xl"></i>
                </div>
                <h3 class="ml-4 text-xl font-semibold text-gray-900">Inventory Aging</h3>
            </div>
            <p class="text-gray-600">Identify slow-moving stock and potential dead capital.</p>
        </a>

        <!-- Movement History -->
        <a href="{{ route('inventory.movements.index') }}" class="block p-6 bg-white rounded-lg shadow hover:shadow-md transition-shadow">
            <div class="flex items-center mb-4">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-exchange-alt text-xl"></i>
                </div>
                <h3 class="ml-4 text-xl font-semibold text-gray-900">Movement History</h3>
            </div>
            <p class="text-gray-600">Track all stock movements, transfers, and adjustments.</p>
        </a>

        <!-- Inventory Valuation -->
        <a href="{{ route('inventory.valuation.index') }}" class="block p-6 bg-white rounded-lg shadow hover:shadow-md transition-shadow">
            <div class="flex items-center mb-4">
                <div class="p-3 rounded-full bg-indigo-100 text-indigo-600">
                    <i class="fas fa-tags text-xl"></i>
                </div>
                <h3 class="ml-4 text-xl font-semibold text-gray-900">Inventory Valuation</h3>
            </div>
            <p class="text-gray-600">Current value of stock on hand and valuation reports.</p>
        </a>
    </div>

    <!-- AI Executive Summary Generator -->
    <div class="bg-gradient-to-br from-indigo-900 to-purple-900 rounded-xl shadow-xl p-8 text-white relative" x-data="aiReports()">
        <div class="relative z-10">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                <div>
                    <h2 class="text-2xl font-bold flex items-center">
                        <i class="fas fa-robot mr-3"></i> AI Executive Board Report
                    </h2>
                    <p class="text-indigo-200 mt-1">Generate a comprehensive meeting-ready summary of your business health.</p>
                </div>
                <div class="flex items-center gap-2">
                    <select x-model="period" class="bg-indigo-800 border-indigo-700 text-white rounded-md text-sm focus:ring-indigo-500">
                        <option value="week">Last 7 Days</option>
                        <option value="month">Last 30 Days</option>
                        <option value="quarter">Last Quarter</option>
                    </select>
                    <button @click="generateSummary" :disabled="loading" class="px-4 py-2 bg-white text-indigo-900 rounded-md font-semibold hover:bg-gray-100 transition-colors disabled:opacity-75">
                        <span x-show="!loading"><i class="fas fa-magic mr-2"></i>Generate Report</span>
                        <span x-show="loading"><i class="fas fa-spinner fa-spin mr-2"></i>Analyzing...</span>
                    </button>
                    <button x-show="summary" onclick="window.print()" class="px-4 py-2 bg-indigo-700 text-white rounded-md hover:bg-indigo-600 transition-colors">
                        <i class="fas fa-print mr-2"></i>Print
                    </button>
                </div>
            </div>

            <!-- Report Output -->
            <div x-show="summary" x-transition class="bg-white text-gray-900 rounded-lg p-8 shadow-inner print:shadow-none print:p-0">
                <div class="prose max-w-none" x-html="summary"></div>
                <p class="text-xs text-gray-400 mt-6 pt-4 border-t text-center uppercase tracking-wide">
                    Generated by JustinePOS AI Assistant • {{ now()->format('Y-m-d H:i') }}
                </p>
            </div>
            
            <div x-show="!summary && !loading" class="text-center py-12 text-indigo-300/50">
                <i class="fas fa-file-invoice-dollar text-6xl mb-4"></i>
                <p>Select a period and click Generate to create a report.</p>
            </div>
        </div>

        <!-- Decorative pulsing circles -->
        <div class="absolute top-0 right-0 -mr-20 -mt-20 w-80 h-80 rounded-full bg-indigo-500 blur-3xl opacity-20 animate-pulse"></div>
        <div class="absolute bottom-0 left-0 -ml-20 -mb-20 w-60 h-60 rounded-full bg-purple-500 blur-3xl opacity-20 animate-pulse delay-700"></div>
    </div>
</div>

<script>
function aiReports() {
    return {
        period: 'month',
        loading: false,
        summary: null,
        
        async generateSummary() {
            this.loading = true;
            this.summary = null;
            try {
                const response = await fetch('{{ route("reports.ai-summary") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ period: this.period })
                });
                
                // Check if response is JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Server returned non-JSON response:', text.substring(0, 500));
                    throw new Error('Server error: ' + response.status + '. Please check the logs.');
                }
                
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'Failed to generate report');
                }
                this.summary = data.html;
            } catch (e) {
                console.error(e);
                alert('Failed to generate report: ' + e.message);
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>

<style>
    @media print {
        nav, header, footer, .no-print { display: none !important; }
        body { background: white; }
        .max-w-7xl { max-width: none !important; padding: 0 !important; }
        .bg-gradient-to-br { background: none !important; color: black !important; box-shadow: none !important; }
        .text-indigo-200 { color: #666 !important; }
        .text-white { color: black !important; }
    }
</style>
@endsection
