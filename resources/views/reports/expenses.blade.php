@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8 flex flex-col md:flex-row justify-between items-end gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Expense Analysis</h1>
            <p class="text-gray-600 mt-1">Deep dive into operational spending and optimization opportunities.</p>
        </div>
        
        <!-- Date Filter Form -->
        <form action="{{ route('reports.expenses') }}" method="GET" class="flex items-end gap-4 bg-white p-4 rounded-lg shadow-sm border border-gray-200">
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 text-sm font-medium shadow-sm transition-colors">
                <i class="fas fa-filter mr-2"></i>Update Report
            </button>
            <button type="button" onclick="window.print()" class="bg-white text-gray-700 border border-gray-300 px-4 py-2 rounded-md hover:bg-gray-50 text-sm font-medium shadow-sm transition-colors">
                <i class="fas fa-print mr-2"></i>Print
            </button>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-red-500">
            <h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider">Total Expenses</h3>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($totalExpenses, 2) }}</p>
            <p class="text-xs text-gray-400 mt-1">For selected period</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-yellow-500">
            <h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider">Expense Ratio</h3>
            <div class="flex items-end">
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($expenseRatio, 1) }}%</p>
                <span class="text-xs font-medium text-gray-500 mb-1 ml-2">of Revenue</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-1.5 mt-2">
                <div class="bg-yellow-500 h-1.5 rounded-full" style="width: {{ min($expenseRatio, 100) }}%"></div>
            </div>
        </div>
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-blue-500">
            <h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider">Avg. Daily Spend</h3>
            @php
                $days = \Carbon\Carbon::parse($startDate)->diffInDays(\Carbon\Carbon::parse($endDate)) + 1;
            @endphp
            <p class="text-3xl font-bold text-gray-900 mt-2">
                {{ number_format($totalExpenses / max($days, 1), 2) }}
            </p>
            <p class="text-xs text-gray-400 mt-1">Over {{ $days }} days</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-purple-500">
            <h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider">Top Category</h3>
            @php $top = $byCategory->first(); @endphp
            <p class="text-xl font-bold text-gray-900 mt-2 truncate" title="{{ $top['name'] ?? 'N/A' }}">
                {{ $top['name'] ?? 'None' }}
            </p>
            @if(isset($top))
                <p class="text-xs text-purple-600 font-medium mt-1">
                    {{ number_format(($top['value'] / max($totalExpenses, 1)) * 100, 1) }}% of total
                </p>
            @else
                <p class="text-xs text-gray-400 mt-1">No data</p>
            @endif
        </div>
    </div>

    <!-- Anomaly Alerts -->
    @if(isset($anomalies) && $anomalies->count() > 0)
    <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-8 rounded-r-md">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-red-400"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">High Spending Detected</h3>
                <div class="mt-2 text-sm text-red-700">
                    <p>Unusual spending spikes detected on the following days (>2.5x average):</p>
                    <ul class="list-disc pl-5 mt-1 space-y-1">
                        @foreach($anomalies as $date => $data)
                            <li>
                                <strong>{{ \Carbon\Carbon::parse($date)->format('M d, Y') }}:</strong> 
                                {{ number_format($data['amount'], 2) }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        <!-- Breakdown Chart -->
        <div class="lg:col-span-1 bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Cost Distribution</h3>
            <div class="relative h-64 w-full">
                <canvas id="categoryChart"></canvas>
            </div>
            <div class="mt-4 space-y-3">
                @foreach($byCategory->take(5) as $cat)
                    <div class="flex justify-between items-center text-sm">
                        <div class="flex items-center">
                            <span class="w-3 h-3 rounded-full mr-2" style="background-color: {{ $cat['color'] }}"></span>
                            <span class="text-gray-600 truncate max-w-[120px]">{{ $cat['name'] }}</span>
                        </div>
                        <span class="font-medium text-gray-900">{{ number_format($cat['value'], 2) }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Trend Chart -->
        <div class="lg:col-span-2 bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-900">Spending Trend</h3>
            </div>
            <div class="relative h-80 w-full">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Expense List & AI -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- List -->
        <div class="lg:col-span-2 bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900">Detailed Expense Log</h3>
                <span class="text-sm text-gray-500">{{ $expenses->count() }} transactions</span>
            </div>
            <div class="overflow-x-auto max-h-[600px]">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 sticky top-0 z-10">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($expenses as $expense)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ \Carbon\Carbon::parse($expense->date)->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 font-medium">
                                    {{ $expense->description }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" 
                                          style="background-color: {{ $byCategory->where('name', $expense->category)->first()['color'] }}20; color: {{ $byCategory->where('name', $expense->category)->first()['color'] }}">
                                        {{ $expense->category }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right">
                                    {{ number_format($expense->amount, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-receipt text-4xl mb-3 text-gray-300"></i>
                                    <p>No expenses found for this period.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- AI Insight Module -->
        <div class="lg:col-span-1">
            <div class="bg-gradient-to-br from-indigo-900 to-purple-800 rounded-xl shadow-xl text-white overflow-hidden">
                <div class="p-6">
                    <h3 class="text-xl font-bold flex items-center mb-2">
                        <i class="fas fa-robot mr-3"></i> AI Cost Optimizer
                    </h3>
                    <p class="text-indigo-200 text-sm mb-6">Analyze spending patterns to find saving opportunities.</p>
                    
                    <div id="ai-insight-content" class="prose prose-sm prose-invert max-w-none bg-black/20 rounded-lg p-4 min-h-[200px] text-sm leading-relaxed">
                        <div class="animate-pulse space-y-3">
                            <div class="h-2 bg-indigo-400/30 rounded w-3/4"></div>
                            <div class="h-2 bg-indigo-400/30 rounded"></div>
                            <div class="h-2 bg-indigo-400/30 rounded w-5/6"></div>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 bg-black/20 flex justify-between items-center">
                    <span class="text-xs text-indigo-300">Powered by Gemini AI</span>
                    <button id="regenerate-ai" class="text-xs bg-indigo-500 hover:bg-indigo-400 text-white px-3 py-1 rounded transition-colors">
                        Refresh Analysis
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Category Chart
    const ctxCat = document.getElementById('categoryChart').getContext('2d');
    new Chart(ctxCat, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($byCategory->pluck('name')) !!},
            datasets: [{
                data: {!! json_encode($byCategory->pluck('value')) !!},
                backgroundColor: {!! json_encode($byCategory->pluck('color')) !!},
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            cutout: '70%'
        }
    });

    // 2. Trend Chart
    const ctxTrend = document.getElementById('trendChart').getContext('2d');
    new Chart(ctxTrend, {
        type: 'line',
        data: {
            labels: {!! json_encode($dailyTrend->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('M d'))) !!},
            datasets: [{
                label: 'Daily Spending',
                data: {!! json_encode($dailyTrend->pluck('amount')) !!},
                borderColor: '#4f46e5',
                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                tension: 0.4,
                fill: true,
                pointRadius: 3,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return ' ' + new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(context.raw);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { borderDash: [2, 4], color: '#f3f4f6' }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });

    // 3. AI Insight Loader
    function loadAIInsight() {
        const container = document.getElementById('ai-insight-content');
        container.innerHTML = '<div class="animate-pulse space-y-3"><div class="h-2 bg-indigo-400/30 rounded w-3/4"></div><div class="h-2 bg-indigo-400/30 rounded"></div><div class="h-2 bg-indigo-400/30 rounded w-5/6"></div></div>';
        
        // Simulate fetch (replace with real endpoint later)
        fetch('{{ route("reports.ai-summary") }}?period={{ request("start_date") ? "custom" : "month" }}&type=expense&start={{ $startDate }}&end={{ $endDate }}')
            .then(res => res.json())
            .then(data => {
                container.innerHTML = data.html || 'No insights available.';
            })
            .catch(err => {
                container.innerHTML = '<p class="text-red-300">Analysis currently unavailable.</p>';
            });
    }
    
    // Initial Load
    loadAIInsight();

    document.getElementById('regenerate-ai').addEventListener('click', loadAIInsight);
});
</script>
@endsection
