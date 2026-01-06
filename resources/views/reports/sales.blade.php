@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between mb-8 print:hidden">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Sales Analytics</h1>
            <p class="text-gray-600 mt-1">Detailed breakdown of sales performance.</p>
        </div>
        <div class="flex gap-2">
            <select onchange="window.location.href='?period='+this.value" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="week" {{ $period == 'week' ? 'selected' : '' }}>Last 7 Days</option>
                <option value="month" {{ $period == 'month' ? 'selected' : '' }}>Last 30 Days</option>
                <option value="quarter" {{ $period == 'quarter' ? 'selected' : '' }}>Last Quarter</option>
                <option value="year" {{ $period == 'year' ? 'selected' : '' }}>Last Year</option>
            </select>
            <button onclick="window.print()" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-print mr-2"></i> Print
            </button>
            <a href="{{ route('reports.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                Back to Reports
            </a>
        </div>
    </div>
    
    <!-- Printable Header (Visible only in print) -->
    <div class="hidden print:block mb-8 border-b pb-4">
        <h1 class="text-2xl font-bold text-gray-900">Sales Analysis Report</h1>
        <p class="text-gray-600">Period: {{ $start_date->format('M d, Y') }} - {{ $end_date->format('M d, Y') }}</p>
        <p class="text-xs text-gray-400 mt-1">Generated: {{ now()->format('Y-m-d H:i') }}</p>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6 border border-gray-100">
            <div class="text-sm font-medium text-gray-500 mb-1">Total Revenue</div>
            <div class="text-3xl font-bold text-indigo-600">KES {{ number_format($total_sales, 2) }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6 border border-gray-100">
            <div class="text-sm font-medium text-gray-500 mb-1">Transactions</div>
            <div class="text-3xl font-bold text-gray-900">{{ number_format($transaction_count) }}</div>
            <div class="text-sm text-gray-500 mt-1">
                Avg. Ticket: KES {{ $transaction_count > 0 ? number_format($total_sales / $transaction_count, 2) : 0 }}
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6 border border-gray-100">
            <div class="text-sm font-medium text-gray-500 mb-1">Top Category</div>
            @if($category_data->isNotEmpty())
            <div class="text-xl font-bold text-gray-900 truncate">{{ $category_data->sortByDesc('total')->first()->category }}</div>
            <div class="text-sm text-green-600 mt-1">
                KES {{ number_format($category_data->sortByDesc('total')->first()->total, 2) }}
            </div>
            @else
            <div class="text-xl font-bold text-gray-400">-</div>
            @endif
        </div>
    </div>

    <!-- AI Executive Summary -->
    <div class="bg-gradient-to-r from-indigo-50 to-blue-50 rounded-lg shadow p-6 mb-8 border border-indigo-100">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-indigo-900 flex items-center">
                <i class="fas fa-robot mr-2 text-indigo-600"></i> AI Executive Summary
            </h3>
            <button onclick="generateSummary()" id="generateBtn" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <i class="fas fa-magic mr-2"></i> Generate Analysis
            </button>
        </div>
        
        <div id="loadingState" class="hidden py-8 text-center">
            <div class="inline-flex items-center justify-center">
                <svg class="animate-spin -ml-1 mr-3 h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <div class="text-indigo-600 font-medium">Analyzing sales data & generating insights...</div>
            </div>
            <p class="text-sm text-gray-500 mt-2">This may take a few seconds.</p>
        </div>

        <div id="aiContent" class="prose prose-indigo max-w-none text-gray-700 hidden">
            <!-- Content will be injected here -->
        </div>
        <div id="aiError" class="hidden mt-4 p-4 bg-red-50 text-red-700 rounded-md border border-red-200 text-sm"></div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8 break-inside-avoid">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Revenue Trend</h3>
            <canvas id="revenueChart" height="200"></canvas>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Sales by Category</h3>
            <canvas id="categoryChart" height="200"></canvas>
        </div>
    </div>

    <!-- Detailed Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden break-before-page">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Daily Breakdown</h3>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Transactions</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Revenue</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Avg. Ticket</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($sales_data as $day)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ \Carbon\Carbon::parse($day->date)->format('M d, Y') }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500">{{ number_format($day->count) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-gray-900">KES {{ number_format($day->total, 2) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500">
                        KES {{ $day->count > 0 ? number_format($day->total / $day->count, 2) : 0 }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Revenue Chart
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: @json($sales_data->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('M d'))),
            datasets: [{
                label: 'Revenue',
                data: @json($sales_data->pluck('total')),
                borderColor: '#4f46e5',
                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } }
        }
    });

    // Category Chart
    new Chart(document.getElementById('categoryChart'), {
        type: 'doughnut',
        data: {
            labels: @json($category_data->pluck('category')),
            datasets: [{
                data: @json($category_data->pluck('total')),
                backgroundColor: ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899']
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });


    function generateSummary() {
        const btn = document.getElementById('generateBtn');
        const loading = document.getElementById('loadingState');
        const content = document.getElementById('aiContent');
        const error = document.getElementById('aiError');

        // Reset state
        btn.classList.add('hidden');
        loading.classList.remove('hidden');
        content.classList.add('hidden');
        error.classList.add('hidden');

        fetch(`{{ route('reports.ai-summary') }}?period={{ $period }}`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            loading.classList.add('hidden');
            if (data.html) {
                content.innerHTML = data.html;
                content.classList.remove('hidden');
                // Don't show button again on success
            } else {
                throw new Error('No content generated');
            }
        })
        .catch(err => {
            console.error(err);
            loading.classList.add('hidden');
            btn.classList.remove('hidden');
            error.textContent = 'Failed to generate analysis. Please try again.';
            error.classList.remove('hidden');
        });
    }
</script>
@endsection
