<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Summary Invoice - {{ $customer->name }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 14px; color: #333; background: #fff; line-height: 1.4; }
        .container { width: 100%; max-width: 210mm; margin: 0 auto; padding: 10mm; }
        
        /* Header reusing standard company style */
        /* .ch-container styles removed as they are now in the partial */
        
        /* Invoice Info */
        .invoice-title { font-size: 28px; font-weight: bold; color: #0056b3; text-align: center; margin-bottom: 40px; text-transform: uppercase; letter-spacing: 1px; }
        
        .client-box { float: left; width: 45%; }
        .client-label { font-size: 12px; color: #777; font-weight: bold; text-transform: uppercase; margin-bottom: 4px; }
        .client-name { font-size: 18px; font-weight: bold; margin-bottom: 4px; }
        .client-detail { color: #555; }
        
        .date-box { float: right; text-align: right; width: 45%; }
        .range { font-size: 14px; font-weight: bold; color: #444; }
        
        .clearfix { clear: both; }
        
        /* Table */
        table { width: 100%; border-collapse: collapse; margin-top: 40px; margin-bottom: 30px; }
        th { text-align: left; padding: 12px 8px; border-bottom: 2px solid #ddd; color: #555; font-weight: 700; text-transform: uppercase; font-size: 12px; }
        td { padding: 12px 8px; border-bottom: 1px solid #eee; color: #333; }
        .right { text-align: right; }
        .mono { font-family: monospace; font-size: 13px; }
        
        /* Total */
        .total-section { float: right; width: 300px; text-align: right; }
        .grand-total { font-size: 20px; font-weight: 900; color: #0056b3; border-top: 2px solid #0056b3; padding-top: 10px; margin-top: 10px; }
        
        .footer { margin-top: 80px; text-align: center; color: #777; font-size: 12px; border-top: 1px solid #eee; padding-top: 20px; }
        
        @media print {
            @page { margin: 0; size: auto; }
            .no-print { display: none; }
            body { margin: 1.5cm; } 
        }
    </style>
</head>
<body>
    <div class="no-print" style="padding: 15px; background: #f8f9fa; border-bottom: 1px solid #ddd; text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" style="background: #0056b3; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;">Print Invoice</button>
    </div>

    <div class="container">
        @include('pos.documents._company_header')

        <div class="invoice-title">Summary Invoice</div>

        <div>
            <div class="client-box">
                <div class="client-label">Billed To:</div>
                <div class="client-name">{{ $customer->name }}</div>
                @if($customer->address)
                <div class="client-detail">{{ $customer->address }}</div>
                @endif
                <div class="client-detail">{{ $customer->phone }}</div>
            </div>
            
            <div class="date-box">
                <div class="client-label">Statement Period</div>
                <div class="range">
                    {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} &mdash; {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}
                </div>
                <div style="margin-top: 8px; font-size: 12px; color: #888;">Generated: {{ now()->format('d M Y H:i') }}</div>
            </div>
            <div class="clearfix"></div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 150px;">Date</th>
                    <th style="width: 150px;">Invoice #</th>
                    <th>Reference</th>
                    <th class="right">Total Amount</th>
                    <th class="right">Outstanding Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoices as $invoice)
                @php
                    $paid = $invoice->payments->sum('amount');
                    $balance = $invoice->total - $paid;
                @endphp
                <tr>
                    <td>{{ $invoice->created_at->format('d M Y') }}</td>
                    <td class="mono font-bold">{{ $invoice->invoice_number ?? $invoice->id }}</td>
                    <td>{{ $invoice->payment_reference ?? '-' }}</td>
                    <td class="right text-gray-500">{{ number_format($invoice->total, 2) }}</td>
                    <td class="right font-bold">{{ number_format($balance, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="total-section">
            <div class="grand-total">
                Total Due: KSh {{ number_format($totalDue, 2) }}
            </div>
        </div>

        <div class="footer">
            <p>Thank you for your business.</p>
            <p><strong>Payment Details:</strong> Please make payments to {{ $company['company_name'] ?? 'JASTENE MEDICAL LTD' }}.</p>
        </div>
    </div>
</body>
</html>
