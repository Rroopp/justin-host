@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Deep Compounded Analysis</h1>
            <p class="mt-2 text-sm text-gray-600">
                Analysis for period: 
                <span class="font-semibold">{{ $start_date->format('M d, Y') }} - {{ $end_date->format('M d, Y') }}</span>
            </p>
        </div>
        <div class="flex space-x-2">
            <a href="{{ route('reports.deep-analysis', ['period' => 'month']) }}" 
               class="px-3 py-1 rounded {{ $period === 'month' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700' }}">Month</a>
            <a href="{{ route('reports.deep-analysis', ['period' => 'quarter']) }}" 
               class="px-3 py-1 rounded {{ $period === 'quarter' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700' }}">Quarter</a>
            <a href="{{ route('reports.deep-analysis', ['period' => 'year']) }}" 
               class="px-3 py-1 rounded {{ $period === 'year' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700' }}">Year</a>
        </div>
    </div>

    <!-- AI Insight Section -->
    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-lg shadow p-6 mb-8 border border-indigo-100">
        <div class="flex items-center mb-4">
            <div class="bg-indigo-600 p-2 rounded-lg mr-3">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
            </div>
            <h2 class="text-xl font-bold text-indigo-900">AI Strategic Insight</h2>
        </div>
        
        <div id="ai-insight-content" class="prose prose-indigo max-w-none text-gray-700 text-sm">
            <div class="animate-pulse flex space-x-4">
                <div class="flex-1 space-y-4 py-1">
                    <div class="h-4 bg-indigo-200 rounded w-3/4"></div>
                    <div class="space-y-2">
                        <div class="h-4 bg-indigo-200 rounded"></div>
                        <div class="h-4 bg-indigo-200 rounded w-5/6"></div>
                    </div>
                </div>
            </div>
            <p class="mt-2 text-indigo-500 font-medium">Generating deep strategic analysis...</p>
        </div>
    </div>

    <!-- Staff Performance -->
    <div class="bg-white shadow rounded-lg mb-8 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-900">Staff Performance Matrix</h3>
            <span class="text-sm text-gray-500">Ranked by contribution to profit</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Staff Member</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Transactions</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Sales (Revenue)</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Gross Profit</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Margin %</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Avg Ticket</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($staff_performance as $staff)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $staff['name'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">{{ number_format($staff['transactions']) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-medium">
                                {{ number_format($staff['revenue'], 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 text-right font-bold">
                                {{ number_format($staff['gross_profit'], 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $staff['margin_percent'] > 20 ? 'bg-green-100 text-green-800' : ($staff['margin_percent'] > 10 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                    {{ $staff['margin_percent'] }}%
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                {{ number_format($staff['avg_ticket'], 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Most Profitable Products -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg font-medium text-gray-900">Top 5 Most Profitable Products</h3>
            </div>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Profit Gen.</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($top_profitable->take(5) as $product)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $product['name'] }}</td>
                            <td class="px-6 py-4 text-sm text-right font-medium text-green-600">
                                {{ number_format($product['profit'], 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-6 py-4 text-center text-sm text-gray-500">No data</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- High Margin / Strategic Products -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg font-medium text-gray-900">Highest Margin Products (>5 sold)</h3>
            </div>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Margin %</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($high_margin->take(5) as $product)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $product['name'] }}</td>
                            <td class="px-6 py-4 text-sm text-right font-medium text-indigo-600">
                                {{ $product['margin'] }}%
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-6 py-4 text-center text-sm text-gray-500">No data</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Phase 2: Customer & Operational Insights -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        <!-- Customer Loyalty -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg font-medium text-gray-900">Top Customers</h3>
            </div>
            <table class="min-w-full divide-y divide-gray-200">
                <tbody class="divide-y divide-gray-200">
                    @forelse($customer_insights as $customer)
                        <tr>
                            <td class="px-6 py-3">
                                <p class="text-sm font-medium text-gray-900">{{ $customer->customer_name }}</p>
                                <p class="text-xs text-gray-500">{{ $customer->transaction_count }} visits</p>
                            </td>
                            <td class="px-6 py-3 text-right text-sm font-bold text-gray-900">
                                {{ number_format($customer->total_spent) }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-6 py-4 text-center text-sm text-gray-500">No data</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Peak Hours Heatmap -->
        <div class="bg-white shadow rounded-lg overflow-hidden lg:col-span-2">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900">Trading Activity (Peak Hours)</h3>
                <span class="text-xs text-gray-500">Sales count by hour of day (00:00 - 23:00)</span>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-12 gap-1 h-32 items-end">
                    @for($i = 6; $i <= 20; $i++) 
                        @php
                            $hourData = $peak_times->firstWhere('hour', $i);
                            $count = $hourData ? $hourData->count : 0;
                            $max = $peak_times->max('count') > 0 ? $peak_times->max('count') : 1;
                            $height = ($count / $max) * 100;
                            // Coloring based on intensity
                            $colorClass = $height > 75 ? 'bg-red-500' : ($height > 40 ? 'bg-indigo-500' : 'bg-indigo-200');
                        @endphp
                        <div class="flex flex-col items-center group relative">
                            <div class="w-full rounded-t {{ $colorClass }} transition-all duration-500" style="height: {{ $height > 10 ? $height : 10 }}%"></div>
                            <span class="text-xs text-gray-500 mt-1">{{ sprintf('%02d', $i) }}</span>
                            
                            <!-- Tooltip -->
                            <div class="absolute bottom-full mb-2 hidden group-hover:block bg-gray-900 text-white text-xs rounded py-1 px-2 z-10 whitespace-nowrap">
                                {{ $i }}:00 - {{ $count }} sales
                            </div>
                        </div>
                    @endfor
                </div>
            </div>
        </div>
    </div>
    
    <!-- Payment Methods -->
    <div class="bg-white shadow rounded-lg overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
             <h3 class="text-lg font-medium text-gray-900">Payment Channel Preference</h3>
        </div>
        <div class="p-6 grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach($payment_trends as $payment)
                <div class="bg-gray-50 rounded-lg p-4 text-center border border-gray-100">
                    <p class="text-sm text-gray-500 uppercase tracking-wide">{{ $payment->payment_method ?? 'Unknown' }}</p>
                    <p class="text-xl font-bold text-gray-900 mt-1">{{ number_format($payment->total) }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $payment->count }} txns</p>
                </div>
            @endforeach
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        fetch('{{ route("reports.ai-summary") }}?period={{ $period }}&deep=true')
            .then(response => response.json())
            .then(data => {
                document.getElementById('ai-insight-content').innerHTML = data.html;
            })
            .catch(error => {
                console.error('Error fetching AI insight:', error);
                document.getElementById('ai-insight-content').innerHTML = 
                    '<p class="text-red-500">Failed to generate AI analysis. Please try again later.</p>';
            });
    });
</script>
@endsection
