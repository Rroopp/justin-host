<?php

namespace App\Http\Controllers;

use App\Models\PosSale;
use App\Models\PosSalePayment;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use App\Services\AccountingService;

class PaymentController extends Controller
{
    /**
     * Store a payment for a specific sale (invoice).
     */
    public function store(Request $request, $saleId)
    {
        $sale = PosSale::with('customer')->findOrFail($saleId);

        if (strcasecmp($sale->payment_status, 'Paid') === 0) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'This invoice is already fully paid.'], 400);
            }
            return redirect()->back()->with('error', 'Invoice already paid.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'payment_date' => 'required|date',
            'payment_reference' => 'nullable|string',
            'payment_notes' => 'nullable|string',
            'update_customer_balance' => 'nullable|boolean',
        ]);

        // Validate amount
        $currentPaid = $sale->payments()->sum('amount');
        $balanceDue = $sale->total - $currentPaid;

        if ($validated['amount'] > ($balanceDue + 0.05)) {
            throw ValidationException::withMessages([
                'amount' => ['Payment amount cannot exceed the remaining balance.'],
            ]);
        }

        DB::beginTransaction();

        try {
            // 1. Create Payment Record
            $payment = PosSalePayment::create([
                'pos_sale_id' => $sale->id,
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'payment_date' => $validated['payment_date'],
                'payment_reference' => $validated['payment_reference'],
                'payment_notes' => $validated['payment_notes'],
                'received_by' => $request->user()?->username ?? 'system',
            ]);

            // 2. Update Sale Status
            $newTotalPaid = $currentPaid + $validated['amount'];
            $newStatus = $sale->payment_status;

            if (abs($newTotalPaid - $sale->total) < 0.01) {
                $newStatus = 'Paid';
            } else {
                $newStatus = 'Partial';
            }

            // 3. Update Receipt Data (for printing)
            $receipt = $sale->receipt_data ?? [];
            if (is_array($receipt)) {
                $receipt['payment_status'] = $newStatus;
                $receipt['amount_paid'] = round($newTotalPaid, 2);
                $receipt['balance_due'] = round(($sale->total - $newTotalPaid), 2);
                $receipt['last_payment'] = [
                    'amount' => round($validated['amount'], 2),
                    'payment_method' => $payment->payment_method,
                    'payment_date' => $payment->payment_date?->toDateString(),
                    'payment_reference' => $payment->payment_reference,
                    'payment_notes' => $payment->payment_notes,
                ];
            }

            $sale->update([
                'payment_status' => $newStatus,
                'payment_date' => $payment->payment_date,
                'payment_reference' => $payment->payment_reference,
                'payment_notes' => $payment->payment_notes,
                'receipt_data' => $receipt
            ]);

            // 4. Update Customer Balance
            $shouldUpdateBalance = (bool) ($validated['update_customer_balance'] ?? false);
            if ($shouldUpdateBalance && $sale->customer && Schema::hasColumn('customers', 'current_balance')) {
                $customer = $sale->customer;
                $current = (float) ($customer->current_balance ?? 0);
                $customer->current_balance = max($current - $validated['amount'], 0);
                $customer->save();
            }

            // 5. Accounting Integration (Auto-post Journal Entry)
            $accounting = new AccountingService();
            $accounting->recordInvoicePayment($payment, $request->user());

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Payment recorded successfully.',
                    'payment' => $payment,
                    'new_status' => $newStatus,
                    'balance_due' => $sale->total - $newTotalPaid
                ]);
            }

            return redirect()->back()->with('success', 'Payment recorded successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Failed: ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Failed: ' . $e->getMessage());
        }
    }

}
