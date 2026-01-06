@extends('layouts.app')

@section('content')
<div class="px-4 py-4 sm:px-0">
    <!-- Header & Quick Actions Row -->
    <div class="mb-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 animate-pulse">
                    <span class="w-1.5 h-1.5 mr-1 bg-green-400 rounded-full"></span>
                    Live
                </span>
            </div>
            <p class="text-xs text-gray-500">Welcome to Hospital POS System</p>
        </div>
        
        <!-- Compact Quick Actions -->
        <div class="flex gap-2 text-sm">
            <a href="{{ route('pos.index') }}" class="inline-flex items-center px-3 py-1.5 border border-transparent rounded bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm">
                <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                New Sale
            </a>
            <a href="{{ route('inventory.index') }}" class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded bg-white text-gray-700 hover:bg-gray-50 shadow-sm">
                <svg class="h-4 w-4 mr-1.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                Add Stock
            </a>
            <a href="{{ route('orders.index') }}" class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded bg-white text-gray-700 hover:bg-gray-50 shadow-sm">
                <svg class="h-4 w-4 mr-1.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                Orders
            </a>
        </div>
    </div>

    <div x-data="dashboardData()" x-init="init()" class="space-y-4">
        <!-- Stats Grid (Compact) -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white overflow-hidden shadow-sm rounded border border-gray-200 p-4 flex items-center">
                <div class="p-2 rounded bg-indigo-50 text-indigo-600 mr-3">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 truncate">Today's Sales</dt>
                    <dd class="text-lg font-bold text-gray-900 leading-none">
                        <span x-text="formatCurrency(data.today_revenue || 0)"></span>
                    </dd>
                    <span class="text-xs text-gray-400" x-text="`${data.today_sales || 0} txns`"></span>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded border border-gray-200 p-4 flex items-center">
                <div class="p-2 rounded bg-green-50 text-green-600 mr-3">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 truncate">Inventory Value</dt>
                    <dd class="text-lg font-bold text-gray-900 leading-none" x-text="formatCurrency(data.inventory_value || 0)"></dd>
                    <span class="text-xs text-gray-400">Total Asset</span>
                </div>
            </div>

            <a href="{{ route('inventory.index', ['stock_level' => 'low']) }}" class="bg-white overflow-hidden shadow-sm rounded border border-gray-200 p-4 flex items-center hover:bg-gray-50">
                <div class="p-2 rounded bg-yellow-50 text-yellow-600 mr-3">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 truncate">Low Stock</dt>
                    <dd class="text-lg font-bold text-gray-900 leading-none">
                        <span x-text="data.low_stock_count || 0"></span>
                    </dd>
                    <span class="text-xs text-red-600 font-medium" x-show="data.out_of_stock_count > 0" x-text="`${data.out_of_stock_count || 0} Empty`"></span>
                </div>
            </a>

            <div class="bg-white overflow-hidden shadow-sm rounded border border-gray-200 p-4 flex items-center">
                <div class="p-2 rounded bg-purple-50 text-purple-600 mr-3">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 truncate">Mo. Revenue</dt>
                    <dd class="text-lg font-bold text-gray-900 leading-none" x-text="formatCurrency(data.month_revenue || 0)"></dd>
                    <span class="text-xs text-gray-400" x-text="new Date().toLocaleString('default', { month: 'short' })"></span>
                </div>
            </div>
        </div>

        <!-- Charts & Lists Combine -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 h-[calc(100vh-280px)] min-h-[500px]">
            <!-- Trend - 2 Cols height 50% -->
            <div class="lg:col-span-2 flex flex-col gap-4 h-full">
                <!-- Sales Trend -->
                <div class="bg-white shadow-sm rounded border border-gray-200 p-4 flex-1">
                    <h2 class="text-sm font-semibold text-gray-900 mb-2">{{ auth()->user()->role === 'admin' ? 'Sales Trend (7 Days)' : 'My Sales Trend' }}</h2>
                    <div class="h-[180px] w-full relative">
                        <canvas id="salesTrendChart"></canvas>
                    </div>
                </div>
                
                <!-- Bottom Row: Payment & Top Products -->
                <div class="flex-1 grid grid-cols-2 gap-4">
                    <div class="bg-white shadow-sm rounded border border-gray-200 p-4">
                        <h2 class="text-sm font-semibold text-gray-900 mb-2">Payment Methods</h2>
                         <div class="h-[140px] w-full relative flex justify-center">
                            <canvas id="paymentMethodChart"></canvas>
                        </div>
                    </div>
                    <div class="bg-white shadow-sm rounded border border-gray-200 p-4 overflow-y-auto">
                        <h2 class="text-sm font-semibold text-gray-900 mb-2">Top Products</h2>
                        <div class="space-y-1">
                            <template x-for="(quantity, product) in data.top_selling_products || {}" :key="product">
                                <div class="flex justify-between items-center p-1.5 bg-gray-50 rounded text-xs">
                                    <span class="font-medium text-gray-700 truncate max-w-[120px]" x-text="product"></span>
                                    <span class="text-gray-500" x-text="`${quantity}`"></span>
                                </div>
                            </template>
                            <div x-show="!data.top_selling_products || Object.keys(data.top_selling_products || {}).length === 0" class="text-center text-gray-400 text-xs py-2">
                                No data
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Sales - 1 Col Full Height -->
            <div class="bg-white shadow-sm rounded border border-gray-200 p-4 flex flex-col h-full overflow-hidden">
                <div class="flex justify-between items-center mb-3">
                    <h2 class="text-sm font-semibold text-gray-900">Recent Sales</h2>
                    <a href="{{ route('sales.index') }}" class="text-xs text-indigo-600 hover:text-indigo-800">View All</a>
                </div>
                <div class="space-y-2 overflow-y-auto flex-1 pr-1 custom-scrollbar">
                    <template x-for="sale in (data.recent_sales || [])" :key="sale.id">
                        <div class="flex justify-between items-center p-2 bg-gray-50 hover:bg-gray-100 rounded border border-gray-100 transition-colors">
                            <div class="min-w-0">
                                <div class="text-sm font-medium text-gray-900 truncate" x-text="sale.customer_name || 'Walk-in'"></div>
                                <div class="text-xs text-gray-400 flex items-center gap-1">
                                    <span x-text="new Date(sale.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})"></span>
                                    <span>•</span>
                                    <span x-text="sale.items_count + ' items'"></span>
                                </div>
                            </div>
                            <div class="text-sm font-bold text-gray-800" x-text="formatCurrency(sale.total)"></div>
                        </div>
                    </template>
                    <div x-show="!data.recent_sales?.length" class="text-center text-gray-500 py-8 text-sm">
                        No recent sales found
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.dashboardData = function() {
    return {
        data: {
            today_sales: {{ $todaySalesCount ?? 0 }},
            today_revenue: {{ $todayRevenue ?? 0 }},
            month_revenue: {{ $monthRevenue ?? 0 }},
            low_stock_count: {{ $lowStockCount ?? 0 }},
            out_of_stock_count: {{ $outOfStockCount ?? 0 }},
            expiring_items_count: {{ $expiringItemsCount ?? 0 }},
            inventory_value: {{ $inventoryValue ?? 0 }},
            pending_orders_value: {{ $pendingOrdersValue ?? 0 }},
            sales_by_day: @json($salesByDay ?? []),
            sales_by_payment_method: @json($salesByPaymentMethod ?? []),
            top_selling_products: @json($topSellingProducts ?? []),
            recent_sales: @json($recentSales ?? [])
        },
        loading: false,
        salesChart: null,
        paymentChart: null,
        pollingInterval: null,

        init() {
            // Initialize charts with server-side data
            this.$nextTick(() => this.initCharts());
            // Start polling for updates
            this.startPolling();
        },

        startPolling() {
            // Poll every 30 seconds for updates
            this.pollingInterval = setInterval(() => {
                this.loadData();
            }, 30000);
        },

        async loadData() {
            try {
                const response = await axios.get('/dashboard', {
                    headers: { 'Accept': 'application/json' }
                });
                this.data = response.data;
                
                // Update charts if they exist
                if (this.salesChart || this.paymentChart) {
                    this.updateCharts();
                } else {
                    this.$nextTick(() => this.initCharts());
                }
            } catch (error) {
                console.error('Error loading dashboard data:', error);
            }
        },

        updateCharts() {
            // Update Sales Chart
            if (this.salesChart && this.data.sales_by_day) {
                const salesData = this.data.sales_by_day;
                this.salesChart.data.labels = salesData.map(item => new Date(item.date).toLocaleDateString('en-KE', { weekday: 'short', day: 'numeric' }));
                this.salesChart.data.datasets[0].data = salesData.map(item => parseFloat(item.revenue || 0));
                this.salesChart.update('none'); // 'none' mode prevents full re-animation
            }

            // Update Payment Chart
            if (this.paymentChart && this.data.sales_by_payment_method) {
                const paymentData = this.data.sales_by_payment_method;
                this.paymentChart.data.labels = paymentData.map(item => item.payment_method);
                this.paymentChart.data.datasets[0].data = paymentData.map(item => parseFloat(item.revenue || 0));
                this.paymentChart.update('none');
            }
        },

        initCharts() {
            // Destroy existing charts if they exist on the canvas, even if we lost the reference
            const salesCtxElement = document.getElementById('salesTrendChart');
            if (salesCtxElement) {
                const existingChart = Chart.getChart(salesCtxElement);
                if (existingChart) existingChart.destroy();
            }
            const paymentCtxElement = document.getElementById('paymentMethodChart');
            if (paymentCtxElement) {
                const existingChart = Chart.getChart(paymentCtxElement);
                if (existingChart) existingChart.destroy();
            }

            // Also reset local references
            this.salesChart = null;
            this.paymentChart = null;

            // Sales Trend Chart
            const salesCtx = document.getElementById('salesTrendChart');
            if (salesCtx && this.data.sales_by_day) {
                const salesData = this.data.sales_by_day || [];
                this.salesChart = new Chart(salesCtx, {
                    type: 'line',
                    data: {
                        labels: salesData.map(item => new Date(item.date).toLocaleDateString('en-KE', { weekday: 'short', day: 'numeric' })),
                        datasets: [{
                            label: 'Revenue (KSh)',
                            data: salesData.map(item => parseFloat(item.revenue || 0)),
                            borderColor: 'rgb(99, 102, 241)',
                            backgroundColor: 'rgba(99, 102, 241, 0.1)',
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    font: { size: 10 }
                                }
                            },
                            x: {
                                ticks: {
                                    font: { size: 10 }
                                }
                            }
                        }
                    }
                });
            }

            // Payment Method Chart
            const paymentCtx = document.getElementById('paymentMethodChart');
            if (paymentCtx && this.data.sales_by_payment_method) {
                const paymentData = this.data.sales_by_payment_method || [];
                this.paymentChart = new Chart(paymentCtx, {
                    type: 'doughnut',
                    data: {
                        labels: paymentData.map(item => item.payment_method),
                        datasets: [{
                            data: paymentData.map(item => parseFloat(item.revenue || 0)),
                            backgroundColor: [
                                'rgba(99, 102, 241, 0.8)',
                                'rgba(16, 185, 129, 0.8)',
                                'rgba(245, 158, 11, 0.8)',
                                'rgba(239, 68, 68, 0.8)'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '60%',
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    boxWidth: 10,
                                    font: { size: 10 }
                                }
                            }
                        }
                    }
                });
            }
        },

        formatCurrency(amount) {
            return 'KSh ' + parseFloat(amount || 0).toLocaleString('en-KE', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
    }
}
</script>
@endsection

