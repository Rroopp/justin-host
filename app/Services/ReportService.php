<?php

namespace App\Services;

use App\Models\PosSale;
use App\Models\PosSalePayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Generate a Customer Statement of Accounts
     * 
     * @param int $customerId
     * @param string $startDate (Y-m-d)
     * @param string $endDate (Y-m-d)
     * @return array
     */
    public function generateCustomerStatement($customerId, $startDate, $endDate)
    {
        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate = Carbon::parse($endDate)->endOfDay();

        // 1. Calculate Opening Balance (Everything before start date)
        // Total Invoiced - Total Paid
        
        $prevSales = PosSale::where('customer_id', $customerId)
            ->where('created_at', '<', $startDate)
            ->sum('total');
            
        $prevPayments = PosSalePayment::whereHas('sale', function($q) use ($customerId) {
                $q->where('customer_id', $customerId);
            })
            ->where('payment_date', '<', $startDate)
            ->sum('amount');
            
        $openingBalance = $prevSales - $prevPayments;

        // 2. Fetch Transactions within the period
        $transactions = collect();

        // A. Sales (Invoices)
        $sales = PosSale::where('customer_id', $customerId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get()
            ->map(function ($sale) {
                return [
                    'date' => $sale->created_at->format('Y-m-d'),
                    'type' => 'INVOICE',
                    'reference' => $sale->invoice_number,
                    'description' => 'Invoice #' . $sale->invoice_number,
                    'debit' => $sale->total,  // Increase in Amount Due
                    'credit' => 0,
                    'original_obj' => $sale
                ];
            });

        // B. Payments
        // Note: We query payments linked to sales for this customer
        $payments = PosSalePayment::whereHas('sale', function($q) use ($customerId) {
                $q->where('customer_id', $customerId);
            })
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->with('sale')
            ->get()
            ->map(function ($payment) {
                return [
                    'date' => $payment->payment_date->format('Y-m-d'),
                    'type' => 'PAYMENT',
                    'reference' => $payment->payment_reference ?? 'PAY-' . $payment->id,
                    'description' => 'Payment for Inv #' . ($payment->sale->invoice_number ?? 'N/A'),
                    'debit' => 0,
                    'credit' => $payment->amount, // Decrease in Amount Due
                    'original_obj' => $payment
                ];
            });

        // 3. Merge and Sort
        $transactions = $sales->merge($payments)->sortBy('date')->values();

        // 4. Calculate Running Balance
        $runningBalance = $openingBalance;
        $statementLines = [];

        foreach ($transactions as $txn) {
            $runningBalance += $txn['debit'];
            $runningBalance -= $txn['credit'];
            
            $txn['balance'] = $runningBalance;
            $statementLines[] = $txn;
        }

        return [
            'customer_id' => $customerId,
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'opening_balance' => $openingBalance,
            'closing_balance' => $runningBalance,
            'transactions' => $statementLines
        ];
    }

    /**
     * Generate Supplier Aging Report
     * Shows outstanding payables grouped by age buckets
     * 
     * @return array
     */
    public function generateSupplierAgingReport()
    {
        $today = Carbon::now();
        
        // Fetch all unpaid/partially paid Purchase Orders
        $orders = \App\Models\PurchaseOrder::whereIn('payment_status', ['unpaid', 'partial'])
            ->where('status', 'received') // Only received orders create liability
            ->with('supplier')
            ->get();

        $agingData = [];
        
        foreach ($orders as $order) {
            $outstanding = $order->total_amount - $order->amount_paid;
            
            if ($outstanding <= 0) continue; // Skip if fully paid
            
            // Calculate age in days from order date or delivery date
            $referenceDate = $order->actual_delivery_date ?? $order->order_date;
            $ageInDays = $today->diffInDays(Carbon::parse($referenceDate));
            
            // Determine bucket
            $bucket = match(true) {
                $ageInDays <= 30 => '0-30',
                $ageInDays <= 60 => '31-60',
                $ageInDays <= 90 => '61-90',
                default => '90+'
            };
            
            $supplierName = $order->supplier_name ?? 'Unknown';
            
            // Initialize supplier if not exists
            if (!isset($agingData[$supplierName])) {
                $agingData[$supplierName] = [
                    'supplier_name' => $supplierName,
                    'total_outstanding' => 0,
                    '0-30' => 0,
                    '31-60' => 0,
                    '61-90' => 0,
                    '90+' => 0,
                    'orders' => []
                ];
            }
            
            // Add to bucket
            $agingData[$supplierName][$bucket] += $outstanding;
            $agingData[$supplierName]['total_outstanding'] += $outstanding;
            $agingData[$supplierName]['orders'][] = [
                'order_number' => $order->order_number,
                'order_date' => $referenceDate->format('Y-m-d'),
                'total_amount' => $order->total_amount,
                'amount_paid' => $order->amount_paid,
                'outstanding' => $outstanding,
                'age_days' => $ageInDays,
                'bucket' => $bucket
            ];
        }
        
        // Calculate totals
        $totals = [
            'total_outstanding' => 0,
            '0-30' => 0,
            '31-60' => 0,
            '61-90' => 0,
            '90+' => 0
        ];
        
        foreach ($agingData as $supplier) {
            $totals['total_outstanding'] += $supplier['total_outstanding'];
            $totals['0-30'] += $supplier['0-30'];
            $totals['31-60'] += $supplier['31-60'];
            $totals['61-90'] += $supplier['61-90'];
            $totals['90+'] += $supplier['90+'];
        }
        
        return [
            'generated_at' => $today->format('Y-m-d H:i:s'),
            'suppliers' => array_values($agingData),
            'totals' => $totals
        ];
    }
}
