<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\PosSale;
use App\Models\Expense;
use App\Models\Inventory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GeminiBudgetService
{
    protected $apiKey;
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key');
        
        if (empty($this->apiKey)) {
            Log::warning('Gemini Service: API Key is missing in config.');
        }
    }

    /**
     * Generate budget recommendations using Gemini AI
     */
    public function generateBudget(string $periodType, Carbon $startDate, Carbon $endDate, float $capital = 0)
    {
        if (empty($this->apiKey)) {
            Log::error('Gemini Service: API Key is missing');
            return null;
        }

        // 1. Gather historical context
        $context = $this->gatherContext($startDate, $endDate);
        
        // 2. Construct the prompt
        $prompt = $this->constructPrompt($periodType, $startDate, $endDate, $capital, $context);
        
        // 3. Call Gemini API
        try {
            $response = Http::post("{$this->baseUrl}?key={$this->apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.2, // Low temperature for more analytical/consistent results
                    'responseMimeType' => 'application/json',
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Gemini Budget Raw Response', ['data' => $data]);

                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
                
                // Clean markdown code blocks if present
                $cleanText = preg_replace('/^```json\s*|\s*```$/', '', $text);
                // Remove any leading/trailing whitespace
                $cleanText = trim($cleanText);
                
                Log::info('Gemini Budget Clean Text', ['text' => $cleanText]);

                $decoded = json_decode($cleanText, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('Gemini Budget JSON Decode Error', [
                        'error' => json_last_error_msg(),
                        'text' => $cleanText
                    ]);
                    return null;
                }

                return $decoded;
            } else {
                Log::error('Gemini API Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Gemini Service Exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Gather historical sales, expenses, and inventory data
     */
    /**
     * Gather historical sales, expenses, and inventory data
     */
    protected function gatherContext(Carbon $start, Carbon $end)
    {
        // Analyze last 12 months for context
        $lookbackStart = $start->copy()->subYear();
        
        // 1. Staff & Payroll Data (Real-time)
        $staffCount = \App\Models\Staff::where('status', 'active')->count();
        $currentMonthlyPayroll = \App\Models\Staff::where('status', 'active')->sum('salary');
        
        // 2. Specific Expense Averages (Last 3 Months)
        $threeMonthsAgo = Carbon::now()->subMonths(3);
        
        $specificExpenses = Expense::with('category')
            ->where('expense_date', '>=', $threeMonthsAgo)
            ->get()
            ->groupBy(function ($expense) {
                return $expense->category->name ?? 'Uncategorized';
            })
            ->map(function ($items) {
                return $items->sum('amount') / 3; // Average per month
            });

        $avgRent = $specificExpenses['Rent'] ?? 0; // Adjust 'Rent' if your DB uses a different name
        $avgUtilities = $specificExpenses['Utilities'] ?? 0;
        $avgMarketing = $specificExpenses['Marketing'] ?? 0;

        // Sales Summary
        $totalSales = PosSale::whereBetween('created_at', [$lookbackStart, $start])->sum('total');
        
        // Top Selling Categories
        $topCategories = PosSale::whereBetween('created_at', [$lookbackStart, $start])
            ->get()
            ->flatMap(function ($sale) {
                return $sale->sale_items ?? [];
            })
            ->groupBy('category') 
            ->map(function ($items, $category) {
                return [
                    'category' => $category,
                    'total_sales' => collect($items)->sum('item_total'),
                    'count' => collect($items)->count()
                ];
            })
            ->sortByDesc('total_sales')
            ->take(5)
            ->values()
            ->toArray();

        // Expense Summary (General)
        $expenses = Expense::with('category')
            ->whereBetween('expense_date', [$lookbackStart, $start])
            ->get()
            ->groupBy(function ($expense) {
                return $expense->category->name ?? 'Uncategorized';
            })
            ->map(function ($items, $categoryName) {
                return [
                    'category' => $categoryName,
                    'total' => $items->sum('amount')
                ];
            })
            ->values()
            ->toArray();
            
        // Current Inventory Value
        $inventoryValue = Inventory::sum(\DB::raw('price * quantity_in_stock'));

        return [
            'total_sales_last_year' => $totalSales,
            'top_categories' => $topCategories,
            'expenses_last_year' => $expenses,
            'inventory_value' => $inventoryValue,
            'real_time_data' => [
                'staff_count' => $staffCount,
                'monthly_payroll' => $currentMonthlyPayroll,
                'avg_rent' => $avgRent,
                'avg_utilities' => $avgUtilities,
                'avg_marketing' => $avgMarketing,
            ]
        ];
    }

    /**
     * Construct the prompt for Gemini
     */
    protected function constructPrompt(string $periodType, Carbon $start, Carbon $end, float $capital, array $context)
    {
        $duration = $start->diffInMonths($end) + 1;
        $contextJson = json_encode($context);
        
        // Extract real-time figures for the prompt
        $rt = $context['real_time_data'];
        $payroll = number_format($rt['monthly_payroll'], 2);
        
        return <<<EOT
You are an expert CFO. Generate a detailed "Zero-Based Budget" for a {$periodType} period ({$duration} months) from {$start->format('Y-m-d')} to {$end->format('Y-m-d')}.

Company Context:
- **Total Available Capital:** {$capital} (This MUST be fully allocated)
- **Mandatory Monthly Costs (Non-Negotiable):**
    - **Staff Salaries:** Ksh {$payroll} (for {$rt['staff_count']} active staff).
    - **Rent Avrg:** Ksh {$rt['avg_rent']}
    - **Utilities Avrg:** Ksh {$rt['avg_utilities']}
- Historical Data: {$contextJson}

Instructions:
1. **Zero-Based Budgeting**: Allocate exactly 100% of the Available Capital ({$capital}).
2. **Mandatory Allocations**: 
    - You MUST allocate **at least** Ksh {$payroll} * {$duration} for "Staff Salaries".
    - You MUST allocate **at least** Ksh {$rt['avg_rent']} * {$duration} for "Rent".
3. **Comprehensive Coverage**: Create specific line items for:
    - **Inventory Restocking**: Prioritize high-selling categories.
    - **Staff Costs**: Use the exact figure provided above.
    - **Rent & Utilities**: Use the exact averages provided.
    - **Marketing**: Allocate remaining funds strategically.
    - **Operations**: Maintenance and reserves.
4. **Precision**: Use specific subcategories.

Output must be valid JSON in this exact structure:
{
    "line_items": [
        {
            "category": "Category Name (e.g. Staff Salaries)",
            "subcategory": "Specific detail (e.g. Monthly Payroll)",
            "allocated_amount": 0.00,
            "explanation": "Based on active staff count of {$rt['staff_count']}..."
        }
    ],
    "total_allocated": 0.00,
    "summary": "Brief executive summary.",
    "projected_revenue": 0.00
}
EOT;
    }
}
