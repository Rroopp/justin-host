<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inventory Report</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 12px; color: #333; background: #fff; line-height: 1.4; }
        .container { width: 100%; max-width: 210mm; margin: 0 auto; padding: 10mm; }
        
        /* Header Styles - Table Based for DomPDF Compatibility */
        .header-table { width: 100%; border-bottom: 4px solid #0047AB; margin-bottom: 24px; padding-bottom: 12px; }
        .header-logo-cell { width: 150px; vertical-align: top; }
        .header-content-cell { text-align: right; vertical-align: top; }
        
        .ch-logo-img { height: 80px; width: auto; }
        .ch-title { font-size: 26px; font-weight: 900; text-transform: uppercase; margin: 0 0 5px 0; color: #111; text-align: center; }
        .ch-details-table { width: 100%; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .ch-detail-left { text-align: left; vertical-align: top; }
        .ch-detail-right { text-align: right; vertical-align: top; }
        
        .invoice-title { font-size: 24px; font-weight: bold; color: #0047AB; text-align: center; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px; margin-top: 20px;}
        
        .meta-box { margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 15px; }
        .meta-row-table { width: 100%; }
        .meta-label { font-weight: bold; color: #555; font-size: 11px; text-transform: uppercase; }
        .meta-value { font-size: 13px; font-weight: bold; color: #000; }
        
        /* Table */
        .items-table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 20px; }
        .items-table th { text-align: left; padding: 10px 8px; border-bottom: 2px solid #ddd; color: #555; font-weight: 700; text-transform: uppercase; font-size: 11px; }
        .items-table td { padding: 10px 8px; border-bottom: 1px solid #eee; color: #333; font-size: 12px; }
        .items-table tr:nth-child(even) { background-color: #f9f9f9; }
        
        .right { text-align: right; }
        .mono { font-family: monospace; font-size: 11px; }
        
        /* Total */
        .total-section { width: 100%; text-align: right; margin-top: 20px; }
        .total-table { width: 300px; margin-left: auto; }
        .grand-total { font-size: 18px; font-weight: 900; color: #0047AB; border-top: 2px solid #0047AB; padding-top: 10px; margin-top: 10px; }
        
        .footer { margin-top: 50px; text-align: center; color: #999; font-size: 10px; border-top: 1px solid #eee; padding-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Replaced partial with Table-based layout for robust PDF rendering -->
        <table class="header-table">
            <tr>
                <td class="header-logo-cell">
                    <?php
                        // robust logo loading using base64 to bypass dompdf path issues
                        $logoPath = public_path('images/logo.jpg');
                        $logoData = '';
                        
                        if (file_exists($logoPath)) {
                            $type = pathinfo($logoPath, PATHINFO_EXTENSION);
                            $data = file_get_contents($logoPath);
                            $logoData = 'data:image/' . $type . ';base64,' . base64_encode($data);
                        }
                    ?>
                    @if(!empty($logoData))
                        <img src="{{ $logoData }}" class="ch-logo-img" alt="Logo">
                    @else
                         <div style="font-weight:bold; font-size:20px; color:#0047AB;">{{ config('app.name')[0] ?? 'L' }}</div>
                    @endif
                </td>
                <td class="header-content-cell">
                    <div class="ch-title">{{ settings('company_name', 'JASTENE MEDICAL LTD') }}</div>
                    <table class="ch-details-table">
                        <tr>
                            <td class="ch-detail-left">
                                {!! nl2br(e(settings('company_address', 'TOP FLOOR, KIKI BUILDING,' . "\n" . 'KISII-NYAMIRA HIGHWAY.'))) !!}
                            </td>
                            <td class="ch-detail-right">
                                <div>TEL: {{ settings('company_phone', '(+254) 737019207') }}</div>
                                <div>EMAIL: <span style="text-transform: lowercase;">{{ settings('company_email', 'info@jastenemedical.com') }}</span></div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <div class="invoice-title">Inventory Stock Report</div>

        <div class="meta-box">
            <table class="meta-row-table">
                <tr>
                    <td>
                        <div class="meta-label">Total Products</div>
                        <div class="meta-value">{{ number_format($items->count()) }}</div>
                    </td>
                    <td style="text-align: right;">
                        <div class="meta-label">Generated Date</div>
                        <div class="meta-value">{{ now()->format('d M Y, h:i A') }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 15%;">Item Code</th>
                    <th style="width: 35%;">Product</th>
                    <th style="width: 20%;">Category</th>
                    <th class="right" style="width: 10%;">Stock</th>
                    <th class="right" style="width: 10%;">Cost</th>
                    <th class="right" style="width: 10%;">Value</th>
                </tr>
            </thead>
            <tbody>
                @php $grandTotal = 0; $totalItems = 0; @endphp
                @foreach($items as $item)
                    @php 
                        $value = $item->quantity_in_stock * $item->selling_price;
                        $grandTotal += $value;
                        $totalItems += $item->quantity_in_stock;
                    @endphp
                    <tr>
                        <td class="mono">{{ $item->code }}</td>
                        <td>
                            <div style="font-weight: bold;">{{ $item->product_name }}</div>
                            @if($item->manufacturer)
                                <div style="font-size: 10px; color: #777;">Mfr: {{ $item->manufacturer }}</div>
                            @endif
                        </td>
                        <td>{{ $item->category }}</td>
                        <td class="right">
                            {{ $item->quantity_in_stock }}
                        </td>
                        <td class="right">{{ number_format($item->selling_price, 2) }}</td>
                        <td class="right font-bold">{{ number_format($value, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="total-section">
            <table class="total-table">
                <tr>
                    <td class="right" style="padding-bottom:5px;">Total Stock Units: <strong>{{ number_format($totalItems) }}</strong></td>
                </tr>
                <tr>
                    <td class="grand-total">
                        Total Value: KSh {{ number_format($grandTotal, 2) }}
                    </td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <p>Generated by {{ settings('company_name', config('app.name')) }} Inventory System</p>
        </div>
    </div>
    
    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->get_font("Helvetica", "normal");
            $pdf->page_text(520, 800, "Page {PAGE_NUM} of {PAGE_COUNT}", $font, 9, array(0.5,0.5,0.5));
        }
    </script>
</body>
</html>
