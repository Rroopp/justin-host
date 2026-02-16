@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-8 font-sans">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between mb-8 print:hidden">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Profit & Loss Statement</h1>
                <p class="text-gray-600 mt-1">Financial performance snapshot.</p>
            </div>
            
            <!-- Filter Form -->
            <form action="{{ route('reports.profit-loss') }}" method="GET" class="flex flex-col md:flex-row items-end gap-4 bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                <div>
                    <label for="start_date" class="block text-xs font-medium text-gray-700 uppercase">Start Date</label>
                    <input type="date" name="start_date" value="{{ $startDate }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label for="end_date" class="block text-xs font-medium text-gray-700 uppercase">End Date</label>
                    <input type="date" name="end_date" value="{{ $endDate }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 text-sm font-medium transition-colors">
                        Update
                    </button>
                    <button type="button" onclick="window.print()" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 text-sm font-medium transition-colors">
                        <i class="fas fa-print mr-2"></i> Print
                    </button>
                </div>
            </form>
        </div>

        <!-- Print Header -->
        <div class="hidden print:block mb-8">
            @include('partials.document_header')
            <div class="text-center mt-4">
                <h2 class="text-xl font-bold text-gray-900">Statement of Profit or Loss</h2>
                <p class="text-sm text-gray-500 mt-1">Period: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>
                <p class="text-xs text-gray-400">Generated: {{ now()->format('d M Y, h:i A') }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Main Financial Statement -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Revenue Card -->
                <div class="bg-white rounded-lg shadow border-l-4 border-indigo-500 print:shadow-none print:border">
                    <div class="p-6">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">REVENUE</h3>
                                <p class="text-sm text-gray-500">Total Sales from Operations</p>
                            </div>
                            <span class="text-2xl font-bold text-gray-900">{{ number_format($revenue, 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- COGS Card -->
                <div class="bg-white rounded-lg shadow border-l-4 border-red-400 print:shadow-none print:border">
                     <div class="p-6">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">COST OF GOODS SOLD</h3>
                                <p class="text-sm text-gray-500">Cost basis of inventory sold</p>
                            </div>
                            <span class="text-2xl font-bold text-red-600">({{ number_format($cogs, 2) }})</span>
                        </div>
                    </div>
                </div>

                <!-- Gross Profit Summary -->
                <div class="bg-gray-50 rounded-lg p-6 border border-gray-200 flex justify-between items-center print:bg-white print:border-t-2 print:border-b-2 print:border-gray-800">
                     <div>
                        <h3 class="text-xl font-extrabold text-gray-800 uppercase">Gross Profit</h3>
                        <p class="text-sm text-gray-600 font-medium">Gross Margin: {{ number_format($grossMargin, 1) }}%</p>
                    </div>
                    <span class="text-3xl font-extrabold text-green-700">{{ number_format($grossProfit, 2) }}</span>
                </div>

                <!-- Expenses List -->
                <div class="bg-white rounded-lg shadow border-l-4 border-orange-400 print:shadow-none print:border">
                    <div class="p-6 border-b border-gray-100">
                         <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">OPERATING EXPENSES</h3>
                                <p class="text-sm text-gray-500">Fixed and variable overheads</p>
                            </div>
                            <span class="text-2xl font-bold text-red-600">({{ number_format($totalExpenses, 2) }})</span>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-gray-50">
                        <div class="space-y-2 text-sm">
                             @forelse($expensesByCategory as $category => $amount)
                            <div class="flex justify-between items-center border-b border-gray-200 last:border-0 pb-1 last:pb-0">
                                <span class="text-gray-600">{{ $category ?: 'General / Uncategorized' }}</span>
                                <span class="font-medium text-gray-800">{{ number_format($amount, 2) }}</span>
                            </div>
                            @empty
                            <p class="text-gray-400 text-center italic">No expenses recorded for this period.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Net Profit Summary -->
                <div class="rounded-xl p-8 border-2 {{ $netProfit >= 0 ? 'bg-indigo-50 border-indigo-200' : 'bg-red-50 border-red-200' }} print:bg-white print:border-4 print:border-double print:border-gray-900">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-2xl font-black {{ $netProfit >= 0 ? 'text-indigo-900' : 'text-red-900' }} uppercase">Net Profit</h3>
                            <p class="{{ $netProfit >= 0 ? 'text-indigo-600' : 'text-red-600' }} font-bold mt-1">Net Margin: {{ number_format($netMargin, 1) }}%</p>
                        </div>
                        <span class="text-4xl font-black {{ $netProfit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format($netProfit, 2) }}
                        </span>
                    </div>
                </div>

            </div>

            <!-- Charts Sidebar (Hidden on Print if space needed, but we keep it for now) -->
            <div class="space-y-8 break-inside-avoid">
                
                <!-- Financial Waterfall Chart -->
                <div class="bg-white p-6 rounded-lg shadow border border-gray-100 print:border hover:shadow-lg transition-shadow">
                    <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wide mb-4">Financial Flow</h3>
                     <div class="relative h-64">
                        <canvas id="waterfallChart"></canvas>
                    </div>
                </div>

                <!-- Expense Breakdown Chart -->
                <div class="bg-white p-6 rounded-lg shadow border border-gray-100 print:border hover:shadow-lg transition-shadow">
                    <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wide mb-4">Expense Breakdown</h3>
                     <div class="relative h-64">
                         @if($totalExpenses > 0)
                            <canvas id="expenseChart"></canvas>
                         @else
                            <div class="flex h-full items-center justify-center text-gray-400 text-sm">No expense data</div>
                         @endif
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // Waterfall / Bar Chart (Revenue vs COGS vs Expenses -> Net)
        new Chart(document.getElementById('waterfallChart'), {
            type: 'bar',
            data: {
                labels: ['Revenue', 'COGS', 'Gross Profit', 'Expenses', 'Net Profit'],
                datasets: [{
                    label: 'Amount (KES)',
                    data: [
                        {{ $revenue }}, 
                        {{ $cogs }}, // displayed as positive bar but logically subtractive
                        {{ $grossProfit }}, 
                        {{ $totalExpenses }}, 
                        {{ $netProfit }}
                    ],
                    backgroundColor: [
                        '#4f46e5', // Revenue (Indigo)
                        '#ef4444', // COGS (Red)
                        '#10b981', // Gross (Green)
                        '#f97316', // Expenses (Orange)
                        '{{ $netProfit >= 0 ? "#15803d" : "#b91c1c" }}'  // Net (Dark Green/Red)
                    ],
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Expense Chart
        @if($totalExpenses > 0)
        new Chart(document.getElementById('expenseChart'), {
            type: 'doughnut',
            data: {
                labels: @json($expensesByCategory->keys()),
                datasets: [{
                    data: @json($expensesByCategory->values()),
                    backgroundColor: [
                        '#f97316', '#fbbf24', '#ef4444', '#84cc16', '#06b6d4', '#8b5cf6'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
        @endif
    });
</script>
@endsection
