<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\BudgetLineItem;
use App\Models\BudgetForecast;
use App\Services\BudgetForecastService;
use App\Services\GeminiBudgetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BudgetController extends Controller
{
    protected $forecastService;
    protected $geminiService;

    public function __construct(BudgetForecastService $forecastService, GeminiBudgetService $geminiService)
    {
        $this->forecastService = $forecastService;
        $this->geminiService = $geminiService;
    }

    /**
     * Display a listing of budgets
     */
    public function index(Request $request)
    {
        $query = Budget::with(['creator', 'lineItems']);

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('period_type')) {
            $query->where('period_type', $request->period_type);
        }

        if ($request->filled('year')) {
            $query->whereYear('start_date', $request->year);
        }

        $budgets = $query->orderBy('created_at', 'desc')->paginate(20);

        // Calculate summary stats
        $stats = [
            'total_budgets' => Budget::count(),
            'active_budgets' => Budget::active()->count(),
            'total_allocated' => Budget::active()->sum('total_allocated'),
            'total_spent' => Budget::active()->sum('total_spent'),
            'avg_utilization' => Budget::active()->avg(DB::raw('(total_spent / NULLIF(total_allocated, 0)) * 100')),
        ];

        return view('budgets.index', compact('budgets', 'stats'));
    }

    /**
     * Show the form for creating a new budget
     */
    public function create()
    {
        $categories = $this->getCategories();
        return view('budgets.create', compact('categories'));
    }

    /**
     * Store a newly created budget
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'period_type' => 'required|in:annual,quarterly,monthly,custom',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'notes' => 'nullable|string',
            'line_items' => 'required|array|min:1',
            'line_items.*.category' => 'required|string',
            'line_items.*.subcategory' => 'nullable|string',
            'line_items.*.description' => 'nullable|string',
            'line_items.*.allocated_amount' => 'required|numeric|min:0',
            'line_items.*.forecast_basis' => 'nullable|in:historical_average,growth_projection,manual',
        ]);

        DB::beginTransaction();
        try {
            // Create budget
            $budget = Budget::create([
                'reference_number' => Budget::generateReferenceNumber(),
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'period_type' => $validated['period_type'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => Auth::id(),
                'status' => 'draft',
            ]);

            // Create line items
            foreach ($validated['line_items'] as $item) {
                $lineItem = $budget->lineItems()->create([
                    'category' => $item['category'],
                    'subcategory' => $item['subcategory'] ?? null,
                    'description' => $item['description'] ?? null,
                    'allocated_amount' => $item['allocated_amount'],
                    'remaining_amount' => $item['allocated_amount'],
                    'forecast_basis' => $item['forecast_basis'] ?? 'manual',
                ]);
            }

            // Calculate totals
            $budget->calculateTotals();

            DB::commit();

            return redirect()->route('budgets.show', $budget)
                ->with('success', 'Budget created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Failed to create budget: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified budget
     */
    public function show(Budget $budget)
    {
        $budget->load(['lineItems', 'creator', 'approver', 'forecasts']);

        // Calculate variance analysis
        $varianceData = $budget->lineItems->map(function ($item) {
            return [
                'category' => $item->category,
                'allocated' => $item->allocated_amount,
                'spent' => $item->spent_amount,
                'remaining' => $item->remaining_amount,
                'utilization' => $item->getUtilizationPercentage(),
                'variance' => $item->getVariance(),
                'status' => $item->isOverBudget() ? 'over' : 'under',
            ];
        });

        return view('budgets.show', compact('budget', 'varianceData'));
    }

    /**
     * Show the form for editing the specified budget
     */
    public function edit(Budget $budget)
    {
        if (!$budget->canEdit()) {
            return back()->with('error', 'This budget cannot be edited.');
        }

        $categories = $this->getCategories();
        return view('budgets.edit', compact('budget', 'categories'));
    }

    /**
     * Update the specified budget
     */
    public function update(Request $request, Budget $budget)
    {
        if (!$budget->canEdit()) {
            return back()->with('error', 'This budget cannot be edited.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'line_items' => 'required|array|min:1',
            'line_items.*.id' => 'nullable|exists:budget_line_items,id',
            'line_items.*.category' => 'required|string',
            'line_items.*.subcategory' => 'nullable|string',
            'line_items.*.description' => 'nullable|string',
            'line_items.*.allocated_amount' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $budget->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Update or create line items
            $existingIds = [];
            foreach ($validated['line_items'] as $item) {
                if (isset($item['id'])) {
                    $lineItem = BudgetLineItem::find($item['id']);
                    $lineItem->update([
                        'category' => $item['category'],
                        'subcategory' => $item['subcategory'] ?? null,
                        'description' => $item['description'] ?? null,
                        'allocated_amount' => $item['allocated_amount'],
                        'remaining_amount' => $item['allocated_amount'] - $lineItem->spent_amount,
                    ]);
                    $existingIds[] = $item['id'];
                } else {
                    $newItem = $budget->lineItems()->create([
                        'category' => $item['category'],
                        'subcategory' => $item['subcategory'] ?? null,
                        'description' => $item['description'] ?? null,
                        'allocated_amount' => $item['allocated_amount'],
                        'remaining_amount' => $item['allocated_amount'],
                    ]);
                    $existingIds[] = $newItem->id;
                }
            }

            // Delete removed line items
            $budget->lineItems()->whereNotIn('id', $existingIds)->delete();

            // Recalculate totals
            $budget->calculateTotals();

            DB::commit();

            return redirect()->route('budgets.show', $budget)
                ->with('success', 'Budget updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Failed to update budget: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified budget
     */
    public function destroy(Budget $budget)
    {
        if ($budget->status === 'active') {
            return back()->with('error', 'Cannot delete an active budget.');
        }

        $budget->delete();
        return redirect()->route('budgets.index')
            ->with('success', 'Budget deleted successfully!');
    }

    /**
     * Approve a budget
     */
    public function approve(Budget $budget)
    {
        if (!$budget->canApprove()) {
            return back()->with('error', 'This budget cannot be approved.');
        }

        $budget->approve(Auth::id());

        return back()->with('success', 'Budget approved and activated!');
    }

    /**
     * Complete a budget
     */
    public function complete(Budget $budget)
    {
        $budget->complete();
        return back()->with('success', 'Budget marked as completed!');
    }

    /**
     * Archive a budget
     */
    public function archive(Budget $budget)
    {
        $budget->archive();
        return back()->with('success', 'Budget archived!');
    }

    /**
     * Generate budget using AI
     */
    public function generateAiBudget(Request $request)
    {
        $validated = $request->validate([
            'period_type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'capital' => 'nullable|numeric|min:0',
        ]);

        $budgetData = $this->geminiService->generateBudget(
            $validated['period_type'],
            Carbon::parse($validated['start_date']),
            Carbon::parse($validated['end_date']),
            $validated['capital'] ?? 0
        );

        if (!$budgetData) {
            return response()->json(['error' => 'Failed to generate budget recommendations.'], 500);
        }

        return response()->json($budgetData);
    }

    /**
     * Generate forecast for budget creation
     */
    public function generateForecast(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'basis' => 'required|in:historical_average,growth_projection',
        ]);

        $forecast = $this->forecastService->generateForecast(
            $validated['category'],
            Carbon::parse($validated['start_date']),
            Carbon::parse($validated['end_date']),
            $validated['basis']
        );

        return response()->json($forecast);
    }

    /**
     * Budget dashboard
     */
    public function dashboard()
    {
        $activeBudgets = Budget::active()->with('lineItems')->get();
        
        $stats = [
            'total_allocated' => $activeBudgets->sum('total_allocated'),
            'total_spent' => $activeBudgets->sum('total_spent'),
            'total_remaining' => $activeBudgets->sum('total_remaining'),
            'avg_utilization' => $activeBudgets->avg(function ($budget) {
                return $budget->getUtilizationPercentage();
            }),
            'over_budget_count' => $activeBudgets->filter(function ($budget) {
                return $budget->isOverBudget();
            })->count(),
        ];

        // Top over-budget categories
        $overBudgetCategories = BudgetLineItem::whereHas('budget', function ($query) {
            $query->active();
        })->where('spent_amount', '>', DB::raw('allocated_amount'))
        ->orderByRaw('(spent_amount - allocated_amount) DESC')
        ->limit(5)
        ->get();

        return view('budgets.dashboard', compact('activeBudgets', 'stats', 'overBudgetCategories'));
    }

    /**
     * Export budget report
     */
    public function export(Budget $budget)
    {
        // TODO: Implement PDF/Excel export
        return back()->with('info', 'Export feature coming soon!');
    }

    /**
     * Get available categories
     */
    protected function getCategories()
    {
        return [
            'Inventory' => 'Inventory & Supplies',
            'Salaries' => 'Staff Salaries',
            'Rent' => 'Rent & Utilities',
            'Marketing' => 'Marketing & Advertising',
            'Equipment' => 'Equipment & Maintenance',
            'Operations' => 'Operational Expenses',
            'Other' => 'Other Expenses',
        ];
    }
}
