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
        [class*="print:hidden"],
        nav,
        header,
        .sidebar,
        .ai-chat-container,
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
        
        /* Card styling for print */
        .print-card {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            background: white !important;
        }
        
        .print-card-title {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }
        
        /* AI Insight box */
        .ai-insight-print {
            border: 1px solid #6366f1;
            background: #f5f3ff !important;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .ai-insight-print h3 {
            color: #4338ca;
            font-size: 12pt;
            margin-bottom: 10px;
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

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 print-document">
    <!-- Printable Header (Visible only in print) -->
    <div class="hidden print:block mb-6">
        @include('partials.document_header')

        <!-- Report Title Section -->
        <div class="text-center mb-6">
            <h2 class="text-xl font-bold text-gray-900 uppercase tracking-wider mb-2">Deep Compounded Analysis Report</h2>
            <div class="flex justify-center items-center gap-8 text-sm text-gray-600">
                <div>PERIOD: <span class="font-medium">{{ $start_date->format('d M Y') }} - {{ $end_date->format('d M Y') }}</span></div>
                <div>GENERATED: <span class="font-medium">{{ now()->format('Y-m-d H:i') }}</span></div>
            </div>
        </div>
    </div>

    <div class="mb-6 flex justify-between items-center print:hidden">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Deep Compounded Analysis</h1>
            <p class="mt-2 text-sm text-gray-600">
                Analysis for period: 
                <span class="font-semibold">{{ $start_date->format('M d, Y') }} - {{ $end_date->format('M d, Y') }}</span>
            </p>
        </div>
        <div class="flex space-x-2">
            <button onclick="window.print()" class="px-3 py-1 rounded bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 mr-2">
                <i class="fas fa-print mr-1"></i> Print Report
            </button>
            <a href="{{ route('reports.deep-analysis', ['period' => 'month']) }}" 
               class="px-3 py-1 rounded {{ $period === 'month' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700' }}">Month</a>
            <a href="{{ route('reports.deep-analysis', ['period' => 'quarter']) }}" 
               class="px-3 py-1 rounded {{ $period === 'quarter' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700' }}">Quarter</a>
            <a href="{{ route('reports.deep-analysis', ['period' => 'year']) }}" 
               class="px-3 py-1 rounded {{ $period === 'year' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700' }}">Year</a>
        </div>
    </div>

    <!-- AI Insight Section -->
    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-lg shadow p-6 mb-8 border border-indigo-100 report-section ai-insight-print">
        <div class="flex items-center mb-4">
            <div class="bg-indigo-600 p-2 rounded-lg mr-3 print:hidden">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
            </div>
            <h2 class="text-xl font-bold text-indigo-900 print-only">AI Strategic Insight</h2>
            <h2 class="text-xl font-bold text-indigo-900 print:hidden">AI Strategic Insight</h2>
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
    <div class="bg-white shadow rounded-lg mb-8 overflow-hidden report-section">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center print:hidden">
            <h3 class="text-lg font-medium text-gray-900">Staff Performance Matrix</h3>
            <span class="text-sm text-gray-500">Ranked by contribution to profit</span>
        </div>
        <div class="print-only mb-4">
            <h3 class="report-section-title">Staff Performance Matrix</h3>
            <p class="text-sm text-gray-600">Ranked by contribution to profit</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 report-table">
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

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8 report-section">
        <!-- Most Profitable Products -->
        <div class="bg-white shadow rounded-lg overflow-hidden print-card">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 print:hidden">
                <h3 class="text-lg font-medium text-gray-900">Top 5 Most Profitable Products</h3>
            </div>
            <div class="print-only mb-3">
                <h4 class="print-card-title">Top 5 Most Profitable Products</h4>
            </div>
            <table class="min-w-full divide-y divide-gray-200 report-table">
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
        <div class="bg-white shadow rounded-lg overflow-hidden print-card">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 print:hidden">
                <h3 class="text-lg font-medium text-gray-900">Highest Margin Products (>5 sold)</h3>
            </div>
            <div class="print-only mb-3">
                <h4 class="print-card-title">Highest Margin Products (>5 sold)</h4>
            </div>
            <table class="min-w-full divide-y divide-gray-200 report-table">
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
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8 report-section">
        <!-- Customer Loyalty -->
        <div class="bg-white shadow rounded-lg overflow-hidden print-card">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 print:hidden">
                <h3 class="text-lg font-medium text-gray-900">Top Customers</h3>
            </div>
            <div class="print-only mb-3">
                <h4 class="print-card-title">Top Customers</h4>
            </div>
            <table class="min-w-full divide-y divide-gray-200 report-table">
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
        <div class="bg-white shadow rounded-lg overflow-hidden lg:col-span-2 print-card">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center print:hidden">
                <h3 class="text-lg font-medium text-gray-900">Trading Activity (Peak Hours)</h3>
                <span class="text-xs text-gray-500">Sales count by hour of day (00:00 - 23:00)</span>
            </div>
            <div class="print-only mb-3">
                <h4 class="print-card-title">Trading Activity (Peak Hours)</h4>
                <p class="text-sm text-gray-600">Sales count by hour of day (00:00 - 23:00)</p>
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
                            <div class="w-full rounded-t {{ $colorClass }} transition-all duration-500" :style="'height: ' + {{ number_format($height > 10 ? $height : 10, 2) }} + '%'"></div>
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
    <div class="bg-white shadow rounded-lg overflow-hidden mb-8 report-section">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 print:hidden">
             <h3 class="text-lg font-medium text-gray-900">Payment Channel Preference</h3>
        </div>
        <div class="print-only mb-4">
            <h3 class="report-section-title">Payment Channel Preference</h3>
        </div>
        <div class="p-6 grid grid-cols-2 md:grid-cols-4 gap-4 print:block">
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
        fetch('{{ route("reports.ai-summary") }}?period={{ $period }}&deep=true', {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
            .then(response => {
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        console.error('Non-JSON response:', text.substring(0, 500));
                        throw new Error('Server returned non-JSON response');
                    });
                }
                return response.json();
            })
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
    <!-- Print Footer -->
    <div class="hidden print:block report-footer mt-8 pt-4">
        <p>Generated by {{ settings('company_name', 'JASTENE MEDICAL LTD') }} • {{ now()->format('F d, Y H:i') }}</p>
        <p class="text-xs mt-1">This is a confidential business report. Distribution without authorization is prohibited.</p>
    </div>

    <!-- AI Chat Interface -->
    <div class="fixed bottom-6 right-6 z-50 print:hidden" x-data="aiChat()">
        <!-- Chat Window -->
        <div x-show="open" x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translateY-10"
             x-transition:enter-end="opacity-100 translateY-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translateY-0"
             x-transition:leave-end="opacity-0 translateY-10"
             class="bg-white rounded-lg shadow-2xl w-80 sm:w-96 flex flex-col mb-4 border border-gray-200 overflow-hidden">
            
            <!-- Header -->
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 p-4 flex justify-between items-center text-white">
                <h3 class="font-bold flex items-center"><i class="fas fa-robot mr-2"></i> Ask Data</h3>
                <button @click="open = false" class="text-white hover:text-gray-200"><i class="fas fa-times"></i></button>
            </div>
            
            <!-- Messages -->
            <div class="flex-1 p-4 h-80 overflow-y-auto bg-gray-50 space-y-3" id="chat-messages">
                <div class="flex items-start">
                    <div class="bg-indigo-100 rounded-lg p-2 text-sm text-gray-800 self-start max-w-[85%]">
                        Hello! I can answer questions about your sales, expenses, and inventory for this period. Try asking "What were my top sales?"
                    </div>
                </div>
                
                <template x-for="msg in messages">
                    <div class="flex items-start" :class="msg.role === 'user' ? 'justify-end' : ''">
                        <div class="rounded-lg p-2 text-sm max-w-[85%]" 
                             :class="msg.role === 'user' ? 'bg-indigo-600 text-white' : 'bg-white border border-gray-200 text-gray-800'">
                            <span x-html="msg.text"></span>
                        </div>
                    </div>
                </template>
                
                <div x-show="loading" class="flex justify-start">
                     <div class="bg-gray-200 rounded-full px-3 py-1 text-xs text-gray-500 animate-pulse">Thinking...</div>
                </div>
            </div>
            
            <!-- Input -->
            <div class="p-3 border-t bg-white">
                <form @submit.prevent="sendMessage" class="flex gap-2">
                    <input type="text" x-model="input" placeholder="Ask a question..." 
                           class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    <button type="submit" :disabled="loading || !input.trim()" 
                            class="bg-indigo-600 text-white rounded-md px-3 hover:bg-indigo-700 disabled:opacity-50">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>

        <!-- Float Button -->
        <button @click="open = !open" 
                class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-full p-4 shadow-lg transition-transform hover:scale-110 flex items-center justify-center">
            <i class="fas fa-comments text-2xl"></i>
        </button>
    </div>

    <script>
        function aiChat() {
            return {
                open: false,
                input: '',
                loading: false,
                messages: [],
                
                async sendMessage() {
                    if (!this.input.trim()) return;
                    
                    const question = this.input;
                    this.messages.push({ role: 'user', text: question });
                    this.input = '';
                    this.loading = true;
                    this.scrollToBottom();
                    
                    try {
                        const response = await fetch('{{ route("reports.chat") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({ 
                                message: question,
                                period: '{{ $period }}'
                            })
                        });
                        
                        const data = await response.json();
                        // Format the markdown response lightly if needed, or rely on x-html
                        // Simple replacement for bold and newlines
                        let answer = data.answer
                            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                            .replace(/\n/g, '<br>');

                        this.messages.push({ role: 'assistant', text: answer });
                    } catch (e) {
                        console.error(e);
                        this.messages.push({ role: 'assistant', text: 'Sorry, I encountered an error answering that.' });
                    } finally {
                        this.loading = false;
                        this.scrollToBottom();
                    }
                },
                
                scrollToBottom() {
                    this.$nextTick(() => {
                        const div = document.getElementById('chat-messages');
                        div.scrollTop = div.scrollHeight;
                    });
                }
            }
        }
    </script>
@endsection
