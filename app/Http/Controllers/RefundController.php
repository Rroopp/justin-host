<?php

namespace App\Http\Controllers;

use App\Models\Refund;
use App\Models\PosSale;
use App\Models\Customer;
use App\Models\Inventory;
use App\Services\AccountingService;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RefundController extends Controller
{
    /**
     * Display refunds list (admin only)
     */
    public function index(Request $request)
    {
        $query = Refund::with(['posSale', 'requestedBy', 'approvedBy'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('refund_type')) {
            $query->where('refund_type', $request->refund_type);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('refund_number', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%")
                  ->orWhereHas('posSale', function($sq) use ($search) {
                      $sq->where('invoice_number', 'like', "%{$search}%");
                  });
            });
        }

        $refunds = $query->paginate(20);

        return view('refunds.index', compact('refunds'));
    }

    /**
     * Show refund request form for a sale
     */
    public function create($saleId)
    {
        $sale = PosSale::with('customer')->findOrFail($saleId);
        
        // Check if sale can be refunded
        if ($sale->payment_status === 'Pending' && $sale->payment_method !== 'Credit') {
            return redirect()->back()->with('error', 'Cannot refund unpaid sales.');
        }

        // Calculate already refunded amount (approved/completed only? or pending too? safely pending too to avoid double request)
        $refundedAmount = $sale->refunds()
            ->whereIn('status', ['pending', 'completed'])
            ->sum('refund_amount');
            
        $refundableAmount = max($sale->total - $refundedAmount, 0);

        if ($refundableAmount < 0.01) {
             return redirect()->back()->with('error', 'Sale is already fully refunded.');
        }

        return view('refunds.create', compact('sale', 'refundedAmount', 'refundableAmount'));
    }

    /**
     * Store refund request
     */
    public function store(Request $request, $saleId)
    {
        $validated = $request->validate([
            'refund_type' => 'required|in:full,partial',
            'refund_items' => 'required|array|min:1',
            'refund_items.*.product_id' => 'required',
            'refund_items.*.quantity' => 'required|numeric|min:0.01',
            'refund_items.*.unit_price' => 'required|numeric|min:0',
            'refund_amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|min:10',
            'refund_method' => 'required|in:Cash,M-Pesa,Bank,Credit Note',
                'refund_items.*.product_name' => 'nullable|string', // Allow it, but we'll override or ensure it exists
        ]);

        $sale = PosSale::findOrFail($saleId);

        // Calculate already refunded amount
        $refundedTotal = $sale->refunds()
            ->whereIn('status', ['pending', 'completed'])
            ->sum('refund_amount');
            
        $maxRefundable = max($sale->total - $refundedTotal, 0);

        // Validate refund amount doesn't exceed refundable balance
        if ($validated['refund_amount'] > $maxRefundable + 0.01) { // 0.01 tolerance
            return redirect()->back()->with('error', 'Refund amount cannot exceed remaining refundable balance (' . number_format($maxRefundable, 2) . ').');
        }

        // Enrich items with product details from DB to ensure accuracy
        $enrichedItems = [];
        foreach ($validated['refund_items'] as $item) {
            $productName = $item['product_name'] ?? 'Unknown Product';
            
            // Try to fetch real name from inventory if product_id exists
            if (!empty($item['product_id'])) {
                $inventory = Inventory::find($item['product_id']);
                if ($inventory) {
                    $productName = $inventory->product_name;
                }
            }
            
            $item['product_name'] = $productName;
            $enrichedItems[] = $item;
        }

        // Validate Total Calculation
        // Ensuring the refund amount closely matches the item values (consistency check)
        $calculatedTotal = 0;
        foreach ($enrichedItems as $item) {
            $qty = $item['quantity'] ?? 0;
            $price = $item['unit_price'] ?? 0;
            $calculatedTotal += $qty * $price;
        }

        // Allow a small float tolerance (e.g. 0.05) or strict check?
        // Let's rely on strict equality if possible, or generous tolerance if rounding is tricky.
        // User asked to "make sure they tally", suggesting strictness.
        if (abs($calculatedTotal - $validated['refund_amount']) > 0.1) {
            return redirect()->back()->with('error', "Refund Amount (" . number_format($validated['refund_amount'], 2) . ") does not tally with the selected items total (" . number_format($calculatedTotal, 2) . ").");
        }

        DB::beginTransaction();
        try {
            $refund = Refund::create([
                'pos_sale_id' => $sale->id,
                'refund_number' => Refund::generateRefundNumber(),
                'refund_type' => $validated['refund_type'],
                'status' => 'pending',
                'refund_amount' => $validated['refund_amount'],
                'refund_items' => $enrichedItems,
                'reason' => $validated['reason'],
                'requested_by' => auth()->user()->staff->id ?? null,
                'refund_method' => $validated['refund_method'],
            ]);

            // Audit log
            $auditService = app(AuditService::class);
            $auditService->log(
                auth()->user(),
                'refund_requested',
                'refunds',
                $refund->id,
                "Refund {$refund->refund_number} requested for Sale #{$sale->invoice_number}",
                Refund::class,
                null,
                [
                    'refund_number' => $refund->refund_number,
                    'refund_amount' => $refund->refund_amount,
                    'refund_type' => $refund->refund_type,
                    'items_count' => count($validated['refund_items']),
                ]
            );

            DB::commit();

            return redirect()->route('refunds.show', $refund->id)
                ->with('success', 'Refund request submitted successfully. Awaiting admin approval.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to create refund: ' . $e->getMessage());
        }
    }

    /**
     * Show refund details
     */
    public function show($id)
    {
        $refund = Refund::with(['posSale', 'requestedBy', 'approvedBy', 'journalEntry'])
            ->findOrFail($id);

        return view('refunds.show', compact('refund'));
    }

    /**
     * Approve refund (admin only)
     */
    public function approve(Request $request, $id)
    {
        $validated = $request->validate([
            'admin_notes' => 'nullable|string',
            'refund_method' => 'nullable|in:Cash,M-Pesa,Bank,Credit Note',
            'reference_number' => 'nullable|string',
        ]);

        $refund = Refund::with('posSale')->findOrFail($id);

        if ($refund->status !== 'pending') {
            return redirect()->back()->with('error', 'Refund has already been processed.');
        }

        DB::beginTransaction();
        try {
            $sale = $refund->posSale;

            // 1. Restore Inventory
            foreach ($refund->refund_items as $item) {
                $productId = $item['product_id'] ?? null;
                $quantity = $item['quantity'] ?? 0;
                $type = $item['type'] ?? 'sale';

                // Skip rentals (they don't reduce inventory permanently)
                if ($type === 'rental') continue;

                if ($productId && $quantity > 0) {
                    $inventory = Inventory::find($productId);
                    if ($inventory) {
                        $inventory->increment('quantity_in_stock', $quantity);
                    }
                }
            }

            // 2. Record Accounting Reversal
            $accountingService = new AccountingService();
            $journalEntry = $accountingService->recordRefund($refund, $sale, auth()->user());

            if (!$journalEntry) {
                throw new \Exception('Failed to create accounting reversal entry.');
            }

            // 3. Update Customer Balance (if applicable)
            if ($sale->customer_id) {
                $customer = Customer::find($sale->customer_id);
                if ($customer) {
                    $customer->decrement('current_balance', $refund->refund_amount);
                }
            }

            // 4. Update Refund Record
            $refund->update([
                'status' => 'completed',
                'approved_by' => auth()->user()->staff->id ?? null,
                'approved_at' => now(),
                'admin_notes' => $validated['admin_notes'] ?? null,
                'refund_method' => $validated['refund_method'] ?? $refund->refund_method,
                'reference_number' => $validated['reference_number'] ?? null,
                'journal_entry_id' => $journalEntry->id,
                'inventory_restored' => true,
                'accounting_reversed' => true,
            ]);

            // 5. Audit Log
            $auditService = app(AuditService::class);
            $auditService->log(
                auth()->user(),
                'refund_approved',
                'refunds',
                $refund->id,
                "Refund {$refund->refund_number} approved and processed",
                Refund::class,
                ['status' => 'pending'],
                [
                    'status' => 'completed',
                    'refund_amount' => $refund->refund_amount,
                    'inventory_restored' => true,
                    'accounting_reversed' => true,
                ]
            );

            DB::commit();

            return redirect()->route('refunds.show', $refund->id)
                ->with('success', 'Refund approved and processed successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to approve refund: ' . $e->getMessage());
        }
    }

    /**
     * Reject refund (admin only)
     */
    public function reject(Request $request, $id)
    {
        $validated = $request->validate([
            'admin_notes' => 'required|string|min:10',
        ]);

        $refund = Refund::findOrFail($id);

        if ($refund->status !== 'pending') {
            return redirect()->back()->with('error', 'Refund has already been processed.');
        }

        $refund->update([
            'status' => 'rejected',
            'approved_by' => auth()->user()->staff->id ?? null,
            'approved_at' => now(),
            'admin_notes' => $validated['admin_notes'],
        ]);

        // Audit Log
        $auditService = app(AuditService::class);
        $auditService->log(
            auth()->user(),
            'refund_rejected',
            'refunds',
            $refund->id,
            "Refund {$refund->refund_number} rejected",
            Refund::class,
            ['status' => 'pending'],
            ['status' => 'rejected', 'admin_notes' => $validated['admin_notes']]
        );

        return redirect()->route('refunds.show', $refund->id)
            ->with('success', 'Refund rejected.');
    }

    /**
     * Export refunds to CSV
     */
    public function export(Request $request)
    {
        $query = Refund::with(['posSale', 'requestedBy', 'approvedBy'])
            ->orderBy('created_at', 'desc');

        // Apply same filters as index
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        $refunds = $query->get();

        $filename = 'refunds_' . now()->format('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($refunds) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'Refund Number',
                'Date',
                'Sale Number',
                'Type',
                'Amount',
                'Status',
                'Reason',
                'Requested By',
                'Approved By',
                'Refund Method',
            ]);

            foreach ($refunds as $refund) {
                fputcsv($file, [
                    $refund->refund_number,
                    $refund->created_at->format('Y-m-d H:i:s'),
                    $refund->posSale->invoice_number ?? 'N/A',
                    ucfirst($refund->refund_type),
                    $refund->refund_amount,
                    ucfirst($refund->status),
                    $refund->reason,
                    $refund->requestedBy->full_name ?? 'N/A',
                    $refund->approvedBy->full_name ?? 'N/A',
                    $refund->refund_method,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
