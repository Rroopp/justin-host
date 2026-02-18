@extends('layouts.app')

@section('content')
<style>
    /* Professional Print Styles */
    @media print {
        @page {
            size: A4;
            margin: 15mm 12mm;
        }
        
        body {
            background: white !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        
        .print-document {
            max-width: 100% !important;
            padding: 0 !important;
        }
        
        /* Hide UI elements */
        .print-hidden,
        nav, 
        header,
        .sidebar,
        .fixed {
            display: none !important;
        }
        
        /* Show print-only elements */
        .print-only {
            display: block !important;
        }
        
        /* Professional table styling */
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10pt;
        }
        
        .report-table th {
            background-color: #1e3a5f !important;
            color: white !important;
            padding: 10px 8px;
            text-align: left;
            font-weight: 600;
            border: 1px solid #1e3a5f;
        }
        
        .report-table td {
            padding: 8px;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        
        .report-table tr:nth-child(even) {
            background-color: #f8f9fa !important;
        }
        
        /* Section styling */
        .report-section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        
        .report-section-title {
            font-size: 14pt;
            font-weight: bold;
            color: #1e3a5f;
            border-bottom: 2px solid #1e3a5f;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        
        /* KPI Cards for print */
        .kpi-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .kpi-card {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: center;
            background: #f8f9fa !important;
        }
        
        .kpi-label {
            font-size: 9pt;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .kpi-value {
            font-size: 14pt;
            font-weight: bold;
            color: #1e3a5f;
        }
        
        /* Footer */
        .report-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8pt;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
            margin-top: 20px;
        }
        
        /* Page break utilities */
        .page-break {
            page-break-before: always;
        }
        
        .no-break {
            page-break-inside: avoid;
        }
    }
    
    /* Screen-only styles */
    @media screen {
        .print-only {
            display: none;
        }
    }
</style>

<div class="min-h-screen bg-gray-50 py-8 font-sans print-document">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header & Controls -->
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4 print:hidden">
            <div>
                <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Sales Intelligence</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Performance period: <span class="font-semibold text-indigo-600">{{ $start_date->format('M d, Y') }} - {{ $end_date->format('M d, Y') }}</span>
                </p>
            </div>
            
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative">
                    <select onchange="window.location.href='?period='+this.value" class="appearance-none block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md shadow-sm bg-white">
                        <option value="week" {{ $period == 'week' ? 'selected' : '' }}>Last 7 Days</option>
                        <option value="month" {{ $period == 'month' ? 'selected' : '' }}>Last 30 Days</option>
                        <option value="quarter" {{ $period == 'quarter' ? 'selected' : '' }}>Last Quarter</option>
                        <option value="year" {{ $period == 'year' ? 'selected' : '' }}>Last Year</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 px-2 flex items-center">
                        <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                    </div>
                </div>
                
                <button onclick="window.print()" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all">
                    <i class="fas fa-print mr-2 text-gray-400"></i> Print Report
                </button>
                
                <a href="{{ route('reports.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all">
                    Back to Hub
                </a>
            </div>
        </div>

        <!-- Printable Header -->
        <div class="hidden print:block mb-6">
            @include('partials.document_header')
            <div class="text-center mt-4">
                <h2 class="text-xl font-bold text-gray-900">Sales Performance Report</h2>
                <p class="text-sm text-gray-500 mt-1">Generated: {{ now()->format('Y-m-d H:i') }} | Period: {{ $start_date->format('M d, Y') }} - {{ $end_date->format('M d, Y') }}</p>
            </div>
        </div>
        
        <!-- Print-only KPI Summary -->
        <div class="hidden print:block kpi-grid mb-6">
            <div class="kpi-card">
                <div class="kpi-label">Total Revenue</div>
                <div class="kpi-value">KES {{ number_format($total_sales, 0) }}</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Net Profit</div>
                <div class="kpi-value">KES {{ number_format($net_profit, 0) }}</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Transactions</div>
                <div class="kpi-value">{{ number_format($transaction_count) }}</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Avg Order</div>
                <div class="kpi-value">KES {{ $transaction_count > 0 ? number_format($total_sales / $transaction_count, 0) : 0 }}</div>
            </div>
        </div>

        <!-- KPIs -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Revenue -->
            <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow p-6 border-l-4 border-indigo-500 relative overflow-hidden group">
                <div class="absolute right-0 top-0 h-24 w-24 bg-indigo-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                <div class="relative">
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Total Revenue</p>
                    <p class="mt-2 text-3xl font-extrabold text-gray-900">KES {{ number_format($total_sales, 2) }}</p>
                    <div class="mt-2 flex items-center text-sm">
                        @if($transaction_count > 0)
                        <span class="text-green-600 font-semibold flex items-center">
                            <i class="fas fa-arrow-up mr-1 text-xs"></i>
                            Avg. Order: {{ number_format($total_sales / $transaction_count, 0) }}
                        </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Net Profit -->
            <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow p-6 border-l-4 border-emerald-500 relative overflow-hidden group">
                <div class="absolute right-0 top-0 h-24 w-24 bg-emerald-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                <div class="relative">
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Est. Net Profit</p>
                    <p class="mt-2 text-3xl font-extrabold text-gray-900">KES {{ number_format($net_profit, 2) }}</p>
                    <div class="mt-2 flex items-center text-sm">
                        <span class="text-emerald-600 font-semibold">
                            Margin: {{ $total_sales > 0 ? round(($net_profit / $total_sales) * 100, 1) : 0 }}%
                        </span>
                        <span class="text-gray-400 mx-2">•</span>
                        <span class="text-gray-500">Based on COGS</span>
                    </div>
                </div>
            </div>

            <!-- Transactions -->
            <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow p-6 border-l-4 border-blue-500 relative overflow-hidden group">
                <div class="absolute right-0 top-0 h-24 w-24 bg-blue-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                <div class="relative">
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Total Orders</p>
                    <p class="mt-2 text-3xl font-extrabold text-gray-900">{{ number_format($transaction_count) }}</p>
                    <div class="mt-2 text-sm text-gray-500">
                        Completed transactions
                    </div>
                </div>
            </div>

            <!-- Top Staff/Metric -->
            <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow p-6 border-l-4 border-purple-500 relative overflow-hidden group">
                <div class="absolute right-0 top-0 h-24 w-24 bg-purple-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                <div class="relative">
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Top Performer</p>
                    @if($staff_performance->isNotEmpty())
                        <p class="mt-2 text-xl font-bold text-gray-900 truncate">{{ $staff_performance->first()['name'] }}</p>
                        <div class="mt-1 text-sm text-purple-600 font-medium">
                            KES {{ number_format($staff_performance->first()['revenue']) }}
                        </div>
                    @else
                        <p class="mt-2 text-xl font-bold text-gray-400">N/A</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Main Chart Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <!-- Revenue Over Time (Main) -->
            <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-bold text-gray-900">Revenue Evolution</h3>
                    <span class="text-xs font-medium px-2 py-1 bg-indigo-100 text-indigo-700 rounded-full">Daily Trend</span>
                </div>
                <div class="relative h-80">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <!-- Sales Distribution (Pie) -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-6">Sales by Category</h3>
                <div class="relative h-64">
                    <canvas id="categoryChart"></canvas>
                </div>
                <div class="mt-4 text-center">
                    <p class="text-sm text-gray-500">Top selling category by revenue</p>
                    <p class="text-lg font-bold text-gray-900">{{ $category_data->sortByDesc('total')->first()->category ?? 'N/A' }}</p>
                </div>
            </div>
        </div>

        <!-- Secondary Insights Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            
            <!-- Staff Performance -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Staff Performance</h3>
                <div class="space-y-4">
                    @foreach($staff_performance->take(5) as $staff)
                    <div>
                        <div class="flex justify-between items-center text-sm mb-1">
                            <span class="font-medium text-gray-700">{{ $staff['name'] }}</span>
                            <span class="font-bold text-gray-900">KES {{ number_format($staff['revenue']) }}</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            @php $percent = $staff_performance->max('revenue') > 0 ? ($staff['revenue'] / $staff_performance->max('revenue') * 100) : 0; @endphp
                            <div class="bg-indigo-500 h-2 rounded-full" style="width: {{ $percent }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Payment Methods & Peak Hours -->
            <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Payment Methods -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Payment Methods</h3>
                    <div class="relative h-48">
                        <canvas id="paymentChart"></canvas>
                    </div>
                </div>
                <!-- Peak Hours -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Peak Activity Hours</h3>
                    <div class="relative h-48">
                        <canvas id="peakChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Products Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Top Revenue -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="text-lg font-bold text-gray-900">Top Products (Revenue)</h3>
                </div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($top_products_revenue as $product)
                        <tr>
                            <td class="px-6 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $product['name'] }}</td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm text-green-600 font-bold">KES {{ number_format($product['revenue']) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="2" class="px-6 py-4 text-center text-gray-500 text-sm">No data available</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Top Volume -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="text-lg font-bold text-gray-900">Top Products (Volume)</h3>
                </div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Units Sold</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($top_products as $product)
                        <tr>
                            <td class="px-6 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $product['name'] }}</td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm text-indigo-600 font-bold">{{ number_format($product['qty']) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="2" class="px-6 py-4 text-center text-gray-500 text-sm">No data available</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- AI Analysis Section -->
        <div class="bg-gradient-to-br from-indigo-900 to-purple-800 rounded-xl shadow-lg p-8 mb-8 text-white relative print:hidden" x-data="aiAnalysis()">
             <div class="relative z-10">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-2xl font-bold flex items-center">
                            <i class="fas fa-brain mr-3 text-indigo-300"></i> AI Strategic Insight
                        </h2>
                        <p class="text-indigo-200 text-sm mt-1">Get an automated analysis of these sales figures.</p>
                    </div>
                    <button @click="generate" :disabled="loading" class="px-5 py-2.5 bg-white text-indigo-900 rounded-lg font-semibold shadow-lg hover:bg-gray-50 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center">
                        <i class="fas fa-magic mr-2" :class="loading ? 'fa-spin' : ''"></i>
                        <span x-text="loading ? 'Analyzing...' : 'Generate Report'"></span>
                    </button>
                </div>

                <div x-show="content" x-transition.opacity class="bg-white/10 backdrop-blur-md rounded-lg p-6 border border-white/20">
                    <div class="prose prose-invert max-w-none text-sm" x-html="content"></div>
                </div>
                
                <div x-show="!content && !loading" class="text-center py-8 text-indigo-300/40">
                    <i class="fas fa-chart-pie text-5xl mb-3"></i>
                    <p class="text-sm">Ready to analyze</p>
                </div>
             </div>
             
             <!-- Decor -->
             <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob"></div>
             <div class="absolute bottom-0 left-0 w-64 h-64 bg-purple-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-2000"></div>
        </div>

        <!-- Detailed Daily Data Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-8">
            <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="text-lg font-bold text-gray-900">Daily Ledger</h3>
                <span class="text-xs text-gray-500">Showing all records for period</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Transactions</th>
                            <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Revenue</th>
                            <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Avg. Order</th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Trend</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach($sales_data as $index => $day)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ \Carbon\Carbon::parse($day->date)->format('D, M d Y') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-600">{{ number_format($day->count) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-gray-900">KES {{ number_format($day->total, 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500">
                                KES {{ $day->count > 0 ? number_format($day->total / $day->count, 0) : 0 }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                <!-- Simple Trend Logic relative to average -->
                                @php $avg = $sales_data->avg('total'); $isUp = $day->total >= $avg; @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $isUp ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    <i class="fas {{ $isUp ? 'fa-arrow-up' : 'fa-minus' }} mr-1"></i> {{ $isUp ? 'Above Avg' : 'Normal' }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

@php
    $revenueLabels = $sales_data->pluck('date')->map(function($d) { return \Carbon\Carbon::parse($d)->format('M d'); });
    $categoryLabels = $category_data->pluck('category');
    $paymentLabels = $payment_trends->pluck('payment_method');
    $peakLabels = $peak_times->pluck('hour')->map(function($h) { return $h . ':00'; });
@endphp

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Common Chart Options
    Chart.defaults.font.family = "'Inter', system-ui, -apple-system, sans-serif";
    Chart.defaults.color = '#6b7280';

    // 1. Revenue Chart (Gradient Area)
    const ctxRevenue = document.getElementById('revenueChart').getContext('2d');
    const gradientRevenue = ctxRevenue.createLinearGradient(0, 0, 0, 400);
    gradientRevenue.addColorStop(0, 'rgba(79, 70, 229, 0.4)');
    gradientRevenue.addColorStop(1, 'rgba(79, 70, 229, 0.0)');

    new Chart(ctxRevenue, {
        type: 'line',
        data: {
            labels: @json($revenueLabels),
            datasets: [{
                label: 'Revenue',
                data: @json($sales_data->pluck('total')),
                borderColor: '#4f46e5',
                backgroundColor: gradientRevenue,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#4f46e5',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1f2937',
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            return 'KES ' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { borderDash: [2, 4], color: '#f3f4f6' },
                    ticks: { callback: (value) => 'KES ' + value.toLocaleString() } // Simplify large numbers
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });

    // 2. Category Chart (Doughnut)
    new Chart(document.getElementById('categoryChart'), {
        type: 'doughnut',
        data: {
            labels: @json($categoryLabels),
            datasets: [{
                data: @json($category_data->pluck('total')),
                backgroundColor: ['#4f46e5', '#3b82f6', '#10b981', '#f59e0b', '#ec4899', '#8b5cf6'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: { position: 'right', labels: { usePointStyle: true, boxWidth: 8 } }
            }
        }
    });

    // 3. Payment Methods (Pie)
    new Chart(document.getElementById('paymentChart'), {
        type: 'pie',
        data: {
            labels: @json($paymentLabels),
            datasets: [{
                data: @json($payment_trends->pluck('total')),
                backgroundColor: ['#10b981', '#f59e0b', '#6366f1', '#ef4444'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });

    // 4. Peak Hours (Bar)
    new Chart(document.getElementById('peakChart'), {
        type: 'bar',
        data: {
            labels: @json($peakLabels),
            datasets: [{
                label: 'Transactions',
                data: @json($peak_times->pluck('count')),
                backgroundColor: '#8b5cf6',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { display: false } },
                x: { grid: { display: false } }
            }
        }
    });

    // AI Alpine Component
    function aiAnalysis() {
        return {
            loading: false,
            content: null,
            generate() {
                this.loading = true;
                fetch(`{{ route('reports.ai-summary') }}?period={{ $period }}`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(r => r.json())
                .then(data => {
                    this.content = data.html;
                })
                .catch(e => {
                    console.error(e);
                    alert('Error generating analysis');
                })
                .finally(() => {
                    this.loading = false;
                });
            }
        }
    }
</script>

<!-- Print Footer -->
<div class="hidden print:block report-footer mt-8 pt-4">
    <p>Generated by {{ settings('company_name', 'JASTENE MEDICAL LTD') }} • {{ now()->format('F d, Y H:i') }}</p>
    <p class="text-xs mt-1">This is a confidential business report. Distribution without authorization is prohibited.</p>
</div>
@endsection
