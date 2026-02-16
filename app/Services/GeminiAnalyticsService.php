<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\PosSale;
use App\Models\Expense;
use App\Models\Inventory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GeminiAnalyticsService
{
    protected $apiKey;
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key');
    }

    /**
     * Generate an executive summary of business performance
     */
    public function generateExecutiveSummary(string $period, Carbon $startDate, Carbon $endDate)
    {
        // 1. Gather Data
        $data = $this->gatherAnalyticsData($startDate, $endDate);
        
        // 2. Build Prompt
        $prompt = $this->buildExecutivePrompt($period, $startDate, $endDate, $data);
        
        // 3. Call API
        return $this->callGemini($prompt);
    }

    /**
     * Generate deep strategic analysis based on compounded data
     */
    public function generateDeepAnalysis(string $period, Carbon $startDate, Carbon $endDate, array $deepData)
    {
        // 1. Build Prompt with deep data
        $prompt = $this->buildDeepPrompt($period, $startDate, $endDate, $deepData);
        
        // 2. Call API
        return $this->callGemini($prompt);
    }

    /**
     * Answer natural language queries about business data
     */
    public function askData(string $question, Carbon $startDate, Carbon $endDate)
    {
        // 1. Gather comprehensive context
        $contextData = $this->gatherAnalyticsData($startDate, $endDate);
        
        // Add more granular data for chat context if needed (e.g. daily sales)
        $contextData['daily_sales'] = PosSale::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, SUM(total) as total')
            ->groupBy('date')
            ->get();

        // 2. Build Prompt
        $json = json_encode($contextData);
        $prompt = <<<EOT
You are an intelligent business assistant for a retail POS system.
Context Data (Period: {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}):
{$json}

User Question: "{$question}"

Instructions:
- Answer the user's question based ONLY on the provided data.
- If the answer is not in the data, state that you don't have that specific information.
- Be concise, professional, and friendly.
- Format numbers as Ksh (Kenya Shillings).
- Use simple markdown (*bold*, lists) for readability.
EOT;

        // 3. Call API
        return $this->callGemini($prompt);
    }

    protected function gatherAnalyticsData(Carbon $start, Carbon $end)
    {
        // Sales
        $totalSales = PosSale::whereBetween('created_at', [$start, $end])->sum('total');
        $transactionCount = PosSale::whereBetween('created_at', [$start, $end])->count();
        $averageTicket = $transactionCount > 0 ? $totalSales / $transactionCount : 0;
        
        // Expenses
        $totalExpenses = Expense::whereBetween('date', [$start, $end])->sum('amount');
        
        // Top Products
        $topProducts = PosSale::whereBetween('created_at', [$start, $end])
            ->get()
            ->flatMap(function ($sale) {
                return $sale->sale_items ?? [];
            })
            ->groupBy('product_name')
            ->map(function ($items, $name) {
                return [
                    'name' => $name,
                    'total' => collect($items)->sum('item_total')
                ];
            })
            ->sortByDesc('total')
            ->take(10) // Increased for chat context
            ->values();

        // Inventory Health
        $lowStock = Inventory::where('quantity_in_stock', '<=', 'reorder_level')->count();
        $outOfStock = Inventory::where('quantity_in_stock', 0)->count();
        $totalInventoryValue = Inventory::sum(DB::raw('price * quantity_in_stock'));

        return [
            'sales' => $totalSales,
            'transactions' => $transactionCount,
            'avg_ticket' => $averageTicket,
            'expenses' => $totalExpenses,
            'net_approx' => $totalSales - $totalExpenses,
            'top_products' => $topProducts,
            'inventory_health' => [
                'low_stock' => $lowStock,
                'out_of_stock' => $outOfStock,
                'total_valuation' => $totalInventoryValue
            ]
        ];
    }

    protected function buildExecutivePrompt($period, $start, $end, $data)
    {
        $json = json_encode($data);
        return <<<EOT
You are a Chief Financial Officer (CFO) assistant for a Kenyan retail business. Analyze the following business data for the period {$start->format('Y-m-d')} to {$end->format('Y-m-d')} ({$period}).

Data: {$json}

Generate a comprehensive "Executive Analysis Report" suitable for the board.
IMPORTANT: 
- All monetary values MUST be in **Ksh** (Kenya Shillings). Do not use $.
- Use proper HTML formatting (<h3>, <p>, <ul>, <li>, <strong>).
- Use HTML tables (<table class="min-w-full divide-y divide-gray-200 my-4">...</table>) for data breakdowns.

Structure the report as follows:

<h3>1. Executive Overview</h3>
<p>Provide a high-level summary of the period's performance, mentioning total sales and net approximate.</p>

<h3>2. Sales Performance Analysis</h3>
<p>Analyze transaction volume vs average ticket size. What does this say about the customer base (high volume/low value or low volume/high value)?</p>

<h3>3. Product Performance Breakdown</h3>
<p>Analyze the top performing products. Are we reliant on a few SKUs?</p>
<table class="min-w-full divide-y divide-gray-200 my-4 border">
    <thead class="bg-gray-50">
        <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product Name</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Revenue (Ksh)</th>
        </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
        <!-- AI: Populate rows here based on Top Products data -->
    </tbody>
</table>

<h3>4. Operational & Inventory Risks</h3>
<p>Assess inventory health. Highlight any low stock or out-of-stock items and the potential revenue impact.</p>

<h3>5. Strategic Recommendations</h3>
<ul>
    <li><strong>Action 1:</strong> [Specific recommendation]</li>
    <li><strong>Action 2:</strong> [Specific recommendation]</li>
    <li><strong>Action 3:</strong> [Specific recommendation]</li>
</ul>
EOT;
    }

    protected function buildDeepPrompt($period, $start, $end, $data)
    {
        $json = json_encode($data);
        return <<<EOT
You are a strategic business consultant analyzing deep performance metrics for a Kenyan retail business. Period: {$start->format('Y-m-d')} to {$end->format('Y-m-d')} ({$period}).

Deep Data: {$json}

Generate a "Strategic Deep Dive" analysis.
IMPORTANT:
- Focus on **Profitability** and **Staff Efficiency**, not just revenue.
- Identify "Hidden Gems" (high margin, low volume) vs "Cash Cows" (high volume, low margin).
- Analyze Staff: Who is driving actual profit? Who is busy but not profitable?
- Provide actionable, specific advice for the manager.

Structure:
<h3>1. Profitability & Staff Efficiency</h3>
<p>[Analyze the intersection of staff effort vs profit generation]</p>

<h3>2. Product Strategy</h3>
<p>[Identify which products should be pushed, which should be dropped, and pricing opportunities]</p>

<h3>3. Operational & Customer Insights</h3>
<p>[Analyze Peak Hours for staffing and Customer Loyalty trends]</p>

<h3>4. Key Action Items</h3>
<ul class="list-disc pl-5">
    <li><strong>[Action]:</strong> [Detail]</li>
    <li><strong>[Action]:</strong> [Detail]</li>
</ul>
EOT;
    }

    /**
     * Generate expense optimization analysis
     */
    public function generateExpenseAnalysis(string $period, Carbon $startDate, Carbon $endDate, array $expenseData)
    {
        // 1. Build Prompt
        $prompt = $this->buildExpensePrompt($period, $startDate, $endDate, $expenseData);
        
        // 2. Call API
        return $this->callGemini($prompt);
    }

    protected function buildExpensePrompt($period, $start, $end, $data)
    {
        $json = json_encode($data);
        return <<<EOT
You are a Cost Control Specialist for a retail business. Analyze the provided expense data for the period {$start->format('Y-m-d')} to {$end->format('Y-m-d')} ({$period}).

Expense Data: {$json}

Generate an "Operational Cost Optimization Report".
IMPORTANT:
- Focus strictly on **COST REDUCTION** and **EFFICIENCY**.
- Identify anomalies or categories that seem too high relative to total spending.
- Suggest specific ways to reduce the top 3 expense categories.
- If "Anomalies" are present, explicitly warn about them.

Structure:
<h3>1. Spending Overview</h3>
<p>[Brief summary of total spend and primary cost drivers]</p>

<h3>2. Anomaly Detection</h3>
<p>[Discuss any flagged anomalies or unusual spikes if present in the data]</p>

<h3>3. Category Analysis & Savings</h3>
<ul>
    <li><strong>[Top Category]:</strong> [Analysis and specific saving tip]</li>
    <li><strong>[Second Category]:</strong> [Analysis and specific saving tip]</li>
</ul>

<h3>4. Efficiency Recommendations</h3>
<p>[General advice on improving operational efficiency based on the spending pattern]</p>
EOT;
    }

    protected function callGemini($prompt)
    {
        try {
            $response = Http::post("{$this->baseUrl}?key={$this->apiKey}", [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['temperature' => 0.3]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Gemini Success Response', ['data' => $data]);
                
                $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                
                if (!$content) {
                    Log::error('Gemini Content Missing', ['structure' => $data]);
                    return 'Analysis generated but content was empty.';
                }
                
                return $content;
            }

            Log::error('Gemini API Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            return 'AI Analysis unavailable at the moment.';
        } catch (\Exception $e) {
            Log::error('Gemini Analytics Error', ['error' => $e->getMessage()]);
            return 'AI Analysis failed to generate.';
        }
    }
}
