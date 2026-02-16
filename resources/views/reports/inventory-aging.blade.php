@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-8 font-sans">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between mb-8 print:hidden">
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                    Batch Age & Expiry Report
                </h2>
                <p class="mt-1 text-sm text-gray-500">Track shelf life, expiry dates, and dead stock.</p>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
                 <button onclick="window.print()" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none">
                    <i class="fas fa-print mr-2"></i> Print Report
                </button>
            </div>
        </div>

        <!-- Print Header -->
        <div class="hidden print:block mb-6">
            @include('partials.document_header')
            <div class="text-center mt-4">
                <h2 class="text-xl font-bold text-gray-900">Inventory Aging Analysis</h2>
                <p class="text-sm text-gray-500 mt-1">Generated: {{ now()->format('d M Y, h:i A') }}</p>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
            <!-- Total Value -->
            <div class="bg-white shadow rounded-lg border-l-4 border-gray-500">
                <div class="px-4 py-5 sm:p-6">
                    <dt class="text-sm font-medium text-gray-500 truncate">Total Batch Value</dt>
                    <dd class="mt-1 text-3xl font-bold text-gray-900">KES {{ number_format($total_inventory_value) }}</dd>
                </div>
            </div>

            <!-- Expired Loss -->
            <div class="bg-white shadow rounded-lg border-l-4 border-red-600">
                <div class="px-4 py-5 sm:p-6">
                    <dt class="text-sm font-medium text-gray-500 truncate">Already Expired</dt>
                    <dd class="mt-1 text-3xl font-bold text-red-600">KES {{ number_format($total_expired_value) }}</dd>
                    <p class="text-xs text-red-500 font-medium">Immediate write-off needed</p>
                </div>
            </div>

            <!-- At Risk -->
            <div class="bg-white shadow rounded-lg border-l-4 border-yellow-500">
                <div class="px-4 py-5 sm:p-6">
                    <dt class="text-sm font-medium text-gray-500 truncate">Expiring (Next 90d)</dt>
                    <dd class="mt-1 text-3xl font-bold text-yellow-600">KES {{ number_format($total_risk_value) }}</dd>
                    <p class="text-xs text-yellow-600 font-medium">Prioritize sales / returns</p>
                </div>
            </div>
             <!-- Old Stock -->
            <div class="bg-white shadow rounded-lg border-l-4 border-purple-500">
                <div class="px-4 py-5 sm:p-6">
                    <dt class="text-sm font-medium text-gray-500 truncate">Old Stock (>1 Year)</dt>
                    <dd class="mt-1 text-3xl font-bold text-purple-600">KES {{ number_format($shelf_age_buckets['Over 1 year']['value']) }}</dd>
                    <p class="text-xs text-purple-500 font-medium">Slow moving inventory</p>
                </div>
            </div>
        </div>

        <!-- Charts Dashboard -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8 break-inside-avoid">
            
            <!-- Expiry Chart -->
            <div class="bg-white rounded-lg shadow p-6 print:border print:shadow-none">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Expiry Risk Distribution (By Value)</h3>
                <div class="relative h-64">
                     <canvas id="expiryChart"></canvas>
                </div>
            </div>

            <!-- Shelf Age Chart -->
            <div class="bg-white rounded-lg shadow p-6 print:border print:shadow-none">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Shelf Age (Time since entry)</h3>
                <div class="relative h-64">
                     <canvas id="ageChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Critical Expiry Table -->
        <div class="bg-white rounded-lg shadow break-before-page mb-8">
            <div class="px-6 py-4 border-b border-gray-200 bg-red-50 flex justify-between items-center">
                 <h3 class="text-lg font-semibold text-red-800">
                    <i class="fas fa-biohazard mr-2"></i> Critical Expiry List (Expired or < 30 Days)
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                     <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Batch/Serial</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Expiry Date</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Qty</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Values</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                         @forelse($critical_expiry_items as $batch)
                         @php 
                            $isExpired = $batch->expiry_date->isPast();
                         @endphp
                        <tr class="{{ $isExpired ? 'bg-red-50' : '' }}">
                            <td class="px-6 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ optional($batch->inventory)->product_name ?? 'Unknown Item' }}
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-xs text-gray-500">
                                {{ $batch->batch_number }} <br>
                                <span class="text-gray-400">{{ $batch->serial_number }}</span>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-bold {{ $isExpired ? 'text-red-700' : 'text-yellow-600' }}">
                                {{ $batch->expiry_date->format('d M Y') }} <br>
                                <span class="text-xs font-normal">{{ $batch->expiry_date->diffForHumans() }}</span>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm text-gray-900">{{ $batch->quantity }}</td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm text-gray-500">
                                KES {{ number_format($batch->quantity * $batch->cost_price) }}
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-center text-sm">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $isExpired ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ $isExpired ? 'EXPIRED' : 'Expiring Soon' }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No critical expiry issues found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

         <!-- Stagnant Stock Table -->
         <div class="bg-white rounded-lg shadow break-before-page">
            <div class="px-6 py-4 border-b border-gray-200 bg-purple-50">
                 <h3 class="text-lg font-semibold text-purple-900">
                    <i class="fas fa-history mr-2"></i> Oldest Shelf Stock (> 1 Year)
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                     <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Batch</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Entry Date</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Qty</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Value</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                         @forelse($shelf_age_buckets['Over 1 year']['items'] as $batch)
                        <tr>
                            <td class="px-6 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ optional($batch->inventory)->product_name ?? 'Unknown Item' }}
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-xs text-gray-500">
                                {{ $batch->batch_number }}
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm text-gray-500">
                                {{ $batch->created_at->format('d M Y') }} <br>
                                <span class="text-xs text-gray-400">{{ $batch->created_at->diffForHumans() }}</span>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm text-gray-900">{{ $batch->quantity }}</td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm text-gray-500">
                                KES {{ number_format($batch->quantity * $batch->cost_price) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">No stock older than 1 year found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // Expiry Chart
        const expiryCtx = document.getElementById('expiryChart').getContext('2d');
        new Chart(expiryCtx, {
            type: 'doughnut',
            data: {
                labels: @json(array_keys($expiry_buckets)),
                datasets: [{
                    data: @json(array_column($expiry_buckets, 'value')),
                    backgroundColor: ['#ef4444', '#f59e0b', '#fbbf24', '#10b981'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right' }
                }
            }
        });

        // Age Chart
        const ageCtx = document.getElementById('ageChart').getContext('2d');
        new Chart(ageCtx, {
            type: 'bar',
            data: {
                labels: @json(array_keys($shelf_age_buckets)),
                datasets: [{
                    label: 'Stock Value',
                    data: @json(array_column($shelf_age_buckets, 'value')),
                    backgroundColor: '#6366f1',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    });
</script>
@endsection
