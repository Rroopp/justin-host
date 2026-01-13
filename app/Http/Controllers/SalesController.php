<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\PosSale;
use App\Models\PosSalePayment;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SalesController extends Controller
{
    use \App\Traits\CsvExportable;

    private function canManageInvoice(Request $request, PosSale $sale): bool
    {
        $role = $request->user()?->role;
        if (in_array($role, ['admin', 'supervisor', 'accountant'], true)) {
            return true;
        }
        return $sale->seller_username === $request->user()?->username;
    }
    /**
     * Display a listing of sales.
     */
    public function index(Request $request)
    {
        $query = PosSale::query();

        // Date filters
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Product filter
        if ($request->filled('product_name')) {
            // SQLite JSON path with wildcard can be brittle; do a safe text search in JSON.
            $needle = '"product_name":"' . str_replace('"', '\"', $request->product_name) . '"';
            $query->where('sale_items', 'like', '%' . $needle . '%');
        }

        // Seller filter (role-based)
        // Admin and Accountant can see all
        if (!in_array($request->user()->role, ['admin', 'accountant'], true)) {
             // Staff sees only their own
            $query->where('seller_username', $request->user()->username);
        } elseif ($request->has('seller')) {
            // Admin/Accountant can filter by specific seller if provided
            $query->where('seller_username', $request->seller);
        }

        // Payment method filter
        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Document type filter
        if ($request->has('document_type')) {
            $query->where('document_type', $request->document_type);
        }

        if ($request->has('export')) {
            $sales = $query->orderBy('created_at', 'desc')->get();
            $data = $sales->map(function($sale) {
                return [
                    'Sale ID' => $sale->id,
                    'Date' => $sale->created_at->format('Y-m-d H:i:s'),
                    'Customer' => $sale->customer_name ?? 'Walk-in',
                    'Amount' => $sale->total,
                    'Paid' => $sale->payments->sum('amount'), // N+1 but acceptable for export
                    'Status' => $sale->payment_status,
                    'Method' => $sale->payment_method,
                    'Items' => count($sale->sale_items ?? []),
                ];
            });
            
            return $this->streamCsv('sales_report.csv', ['Sale ID', 'Date', 'Customer', 'Amount', 'Paid', 'Status', 'Method', 'Items'], $data, 'Sales Report');
        }

        $sales = $query->with('refunds')->orderBy('created_at', 'desc')->paginate(50);

        if ($request->expectsJson()) {
            return response()->json($sales);
        }

        // Get keys for filter dropdown (all unique sellers)
        // Ideally join with users table for full names, but username is what's stored
        $sellers = PosSale::distinct()->pluck('seller_username')->filter()->values();

        return view('sales.index', compact('sales', 'sellers'));
    }

    /**
     * Get sales summary/analytics
     */
    public function summary(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth());
        $dateTo = $request->get('date_to', now()->endOfMonth());

        $baseQuery = PosSale::whereBetween('created_at', [$dateFrom, $dateTo]);

        // Role-based filter
        if (!in_array($request->user()->role, ['admin', 'accountant'], true)) {
            $baseQuery->where('seller_username', $request->user()->username);
        }

        // Clone queries to avoid stacking WHEREs across metrics.
        $q = fn () => (clone $baseQuery);

        $summary = [
            'total_sales' => $q()->count(),
            'total_revenue' => $q()->sum('total'),
            'total_subtotal' => $q()->sum('subtotal'),
            'total_vat' => $q()->sum('vat'),
            'total_discount' => $q()->sum('discount_amount'),
            'cash_sales' => $q()->where('payment_method', 'Cash')->sum('total'),
            'mpesa_sales' => $q()->where('payment_method', 'M-Pesa')->sum('total'),
            'bank_sales' => $q()->where('payment_method', 'Bank')->sum('total'),
            'cheque_sales' => $q()->where('payment_method', 'Cheque')->sum('total'),
            'credit_sales' => $q()->where('payment_method', 'Credit')->sum('total'),
            'pending_payments' => $q()->where('payment_status', 'pending')->sum('total'),
        ];

        return response()->json($summary);
    }

    /**
     * List Credit invoices (with outstanding status filters).
     */
    public function invoices(Request $request)
    {
        $query = PosSale::query()
            ->where('document_type', 'invoice')
            ->where('payment_method', 'Credit')
            ->with('customer')
            ->withSum('payments', 'amount');

        // Admin/supervisor/accountant can see all invoices; others only their own.
        if (!in_array($request->user()->role, ['admin', 'supervisor', 'accountant'], true)) {
            $query->where('seller_username', $request->user()->username);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('customer')) {
            $needle = $request->customer;
            $query->where(function ($q) use ($needle) {
                $q->where('customer_name', 'like', "%{$needle}%")
                    ->orWhere('invoice_number', 'like', "%{$needle}%");
            });
        }

        $invoices = $query->orderBy('created_at', 'desc')->paginate(50)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($invoices);
        }

        return view('sales.invoices.index', compact('invoices'));
    }

    /**
     * Show the Summary Invoice page (filter form).
     */
    public function summaryInvoice(Request $request)
    {
        $customers = Customer::orderBy('name')->get();

        return view('sales.invoices.summary', compact('customers'));
    }

    /**
     * API: Get pending invoices for a customer.
     */
    public function getPendingInvoices(Request $request, $customerId)
    {
        $query = PosSale::where('customer_id', $customerId)
            ->where('document_type', 'invoice')
            ->where('payment_method', 'Credit')
            ->where(function ($q) {
                // Show pending or partial
                $q->whereIn('payment_status', ['pending', 'partial']);
                // OR checking balance manually if status isn't reliable
                $q->orWhereRaw('(total - (select coalesce(sum(amount),0) from pos_sale_payments where pos_sale_payments.pos_sale_id = pos_sales.id)) > 0.01');
            });

        // Apply date filters if provided
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $invoices = $query->with(['payments']) // for display info if needed
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($inv) {
                $paid = $inv->payments->sum('amount');
                return [
                    'id' => $inv->id,
                    'invoice_number' => $inv->invoice_number ?? $inv->id,
                    'date' => $inv->created_at->format('Y-m-d'),
                    'total' => (float) $inv->total,
                    'paid' => (float) $paid,
                    'balance' => round($inv->total - $paid, 2),
                    'ref' => $inv->payment_reference ?? '',
                ];
            });

        // Filter out fully paid ones just in case logic above leaked
        $invoices = $invoices->filter(fn($i) => $i['balance'] > 0.01)->values();

        return response()->json($invoices);
    }

    /**
     * Generate/Print the Summary Invoice.
     */
    public function printSummaryInvoice(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'invoice_ids' => 'required|array|min:1',
            'invoice_ids.*' => 'exists:pos_sales,id',
        ]);

        $customer = Customer::findOrFail($request->customer_id);
        
        // Fetch selected invoices
        $invoices = PosSale::whereIn('id', $request->invoice_ids)
            ->where('customer_id', $customer->id) // Security check
            ->with(['payments'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Calculate total due from these specific invoices
        $totalDue = 0;
        foreach ($invoices as $invoice) {
            $paid = $invoice->payments->sum('amount');
            $balance = $invoice->total - $paid;
            if ($balance > 0.01) {
                $totalDue += $balance;
            }
        }
        
        // Determine date range from the actual selected invoices
        $dateFrom = $invoices->min('created_at')->format('Y-m-d');
        $dateTo = $invoices->max('created_at')->format('Y-m-d');

        return view('sales.invoices.print_summary', compact('customer', 'invoices', 'dateFrom', 'dateTo', 'totalDue'));
    }

    /**
     * Show a single invoice + payment history.
     */
    public function showInvoice(Request $request, PosSale $sale)
    {
        if ($sale->document_type !== 'invoice' || $sale->payment_method !== 'Credit') {
            abort(404);
        }

        if (!$this->canManageInvoice($request, $sale)) {
            abort(403);
        }

        $sale->load([
            'customer',
            'payments' => fn ($q) => $q->orderBy('created_at', 'desc'),
        ]);

        $amountPaid = (float) ($sale->payments->sum('amount') ?? 0);
        $total = (float) ($sale->total ?? 0);
        $balanceDue = max($total - $amountPaid, 0);

        if ($request->expectsJson()) {
            return response()->json([
                'sale' => $sale,
                'amount_paid' => $amountPaid,
                'balance_due' => $balanceDue,
            ]);
        }

        return view('sales.invoices.show', [
            'sale' => $sale,
            'amountPaid' => $amountPaid,
            'balanceDue' => $balanceDue,
        ]);
    }



    /**
     * Helper to get account by name or close match
     */
    private function getAccountId(string $name, string $type)
    {
        // Try exact match
        $account = ChartOfAccount::where('name', $name)->first();
        if ($account) return $account->id;

        // Try 'like' match
        $account = ChartOfAccount::where('name', 'like', "%{$name}%")->first();
        if ($account) return $account->id;

        // Fallback: Any account of that type (risky but better than crash? No, better to null)
        // Let's try to find a generic one
        if ($type === 'Asset') {
             // Fallback for payment methods to "Cash" or "Bank" if distinct not found
             if (str_contains($name, 'M-Pesa') || str_contains($name, 'Bank')) {
                 $account = ChartOfAccount::where('name', 'like', '%Bank%')->first();
             } else {
                 $account = ChartOfAccount::where('name', 'like', '%Cash%')->first();
             }
             if ($account) return $account->id;
        }

        return null;
    }

    /**
     * Record a payment against an invoice (supports partial payments).
     */
    public function addInvoicePayment(Request $request, PosSale $sale)
    {
        if ($sale->document_type !== 'invoice' || $sale->payment_method !== 'Credit') {
            abort(404);
        }

        if (!$this->canManageInvoice($request, $sale)) {
            abort(403);
        }

        /**
         * DEPRECATED LOGIC:
         * Logic has been centralized in PaymentController::store
         * This ensures consistent validation, receipt updates, and accounting entries.
         */ 
        $paymentController = new \App\Http\Controllers\PaymentController();
        return $paymentController->store($request, $sale->id);
    }
    /**
     * Update payment method for an existing sale.
     * Triggers accounting correction.
     */
    public function updatePaymentMethod(Request $request, PosSale $sale, \App\Services\AccountingService $accounting)
    {
        // 1. Authorization
        if (!in_array($request->user()->role, ['admin', 'supervisor', 'accountant', 'staff'])) {
            // Check ownership if staff
            if ($sale->seller_username !== $request->user()->username) {
                abort(403, 'Unauthorized action.');
            }
        }

        $request->validate([
            'payment_method' => 'required|string',
        ]);

        $newMethod = $request->payment_method;
        $oldMethod = $sale->payment_method;

        if ($newMethod === $oldMethod) {
            return response()->json(['message' => 'No change needed.']);
        }

        // 2. Perform Accounting Correction
        // Log current state
        \Illuminate\Support\Facades\Log::info("Updating Sale #{$sale->id} Payment Method: {$oldMethod} -> {$newMethod}");

        $success = $accounting->correctSalePaymentMethod($sale, $oldMethod, $newMethod, $request->user());
        
        if (!$success) {
            return response()->json(['message' => 'Failed to update accounting records.'], 500);
        }

        // 3. Update Sale Record Logic
        // Hande Status Changes
        if ($newMethod === 'Credit') {
            // Moving to Credit -> Pending
            $sale->payment_method = 'Credit';
            $sale->payment_status = 'pending';
            $sale->document_type = 'invoice';
            
            // Delete existing payments as they are now invalid/reversed
            // Note: Journal entry reversal handled the accounting side. We just clean up the "Payment" record.
            $sale->payments()->delete(); 
            
        } elseif (in_array($newMethod, ['Cash', 'M-Pesa', 'Bank', 'Cheque'])) {
            // Moving to Immediate Payment -> Paid
            $sale->payment_method = $newMethod;
            $sale->payment_status = 'paid';
            $sale->document_type = 'receipt';

            // Create or Update Payment Record
            // If previous was Credit, no payment record exists -> Create one.
            // If previous was Cash/M-Pesa, a payment record likely exists -> Update it.
            
            $existingPayment = $sale->payments()->first();
            
            if ($existingPayment) {
                $existingPayment->update([
                    'payment_method' => $newMethod,
                    'amount' => $sale->total // Ensure full amount is reflected
                ]);
            } else {
                \App\Models\PosSalePayment::create([
                    'pos_sale_id' => $sale->id,
                    'amount' => $sale->total,
                    'payment_method' => $newMethod,
                    'payment_date' => now(),
                    'reference' => 'Correction',
                    'user_id' => $request->user()->id
                ]);
            }
        } else {
             // Fallback for custom methods (Treat as paid generally, or keep status if unknown)
             // Default to paid for generic methods
             $sale->payment_method = $newMethod;
             if ($sale->payment_status !== 'paid') {
                 $sale->payment_status = 'paid';
                 $sale->document_type = 'receipt';
                 \App\Models\PosSalePayment::create([
                    'pos_sale_id' => $sale->id,
                    'amount' => $sale->total,
                    'payment_method' => $newMethod,
                    'payment_date' => now(),
                    'reference' => 'Correction',
                    'user_id' => $request->user()->id
                ]);
             }
        }

        $sale->save();

        return response()->json([
            'message' => 'Payment method updated successfully',
            'sale' => $sale
        ]);
    }
}
