<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ strtoupper($requested_type ?? $sale->document_type ?? 'RECEIPT') }} #{{ ($requested_type ?? $sale->document_type) === 'invoice' ? ($sale->invoice_number ?? $sale->id) : $sale->id }}</title>
    <style>
        :root {
            --blue:#1976d2;
            --red:#d32f2f;
            --green:#2e7d32;
            --fg:#111;
            --muted:#666;
            --border:#ddd;
            --bgSoft:#f5f5f5;
        }
        body { font-family: Arial, ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial; color: var(--fg); margin: 0; background:#fff; }
        .muted { color: var(--muted); }

        /* Containers */
        .doc { margin: 0 auto; background:#fff; }
        .doc--receipt { width: 600px; padding: 24px; border: 1px solid var(--blue); box-shadow: 0 2px 10px rgba(0,0,0,.08); }
        /* Reduced padding for A4 to 10mm to ensure better fit for long lists */
        .doc--a4 { 
            width: 210mm; 
            min-height: 297mm; 
            padding: 10mm; 
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
        }

        /* Company header (matches reference app) */
        .company { display:flex; align-items:center; gap: 16px; margin-bottom: 12px; }

        /* Document Header Styling */
        .doc-header { text-align: center; border-bottom: 2px solid #ccc; padding-bottom: 12px; margin-bottom: 16px; }
        .doc-header__logo-container { display: flex; justify-content: center; margin-bottom: 8px; }
        
        /* Default logo size (Receipts) - approx 64px */
        .doc-header__logo { height: 60px; width: auto; object-fit: contain; }

        /* A4 specific logo size - slightly smaller to save space */
        .doc--a4 .doc-header__logo { height: 80px; }

        .doc-header__content { line-height: 1.3; }
        .doc-header__title { font-size: 20px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; color: #111; margin: 0 0 4px 0; }
        
        /* A4 specific header adjustments */
        .doc--a4 .doc-header__title { font-size: 24px; margin-bottom: 4px; }
        .doc--a4 .doc-header__address { font-size: 13px; }
        .doc--a4 .doc-header__meta { font-size: 12px; flex-direction: row; justify-content: center; gap: 12px; }

        .doc-header__address { font-size: 13px; color: #444; margin: 0 0 6px 0; }
        .doc-header__meta { font-size: 12px; color: #666; display: flex; flex-direction: column; align-items: center; gap: 4px; }
        .doc-header__meta-item { font-weight: 500; }
        .doc-header__meta-label { font-weight: 700; color: #333; margin-right: 4px; }

        /* Titles */
        .titleRow { display:flex; justify-content:space-between; align-items:flex-start; gap: 16px; margin: 10px 0 8px; }
        .docTitle { font-weight: 900; font-size: 28px; letter-spacing: .5px; }
        .docTitle--blue { color: var(--blue); text-align:center; }
        .docTitle--red { color: var(--red); }
        .docTitle--green { color: var(--green); }
        .titleMeta { text-align:right; font-size: 12px; }
        .titleMeta__line { margin-bottom: 3px; color: #333; }

        /* Layout helpers */
        .divider { border-top: 1px solid var(--border); margin: 10px 0; }
        .divider--solid { border-top-style: solid; }
        .sectionTitle { font-weight: 700; color: var(--blue); margin: 10px 0 6px; font-size: 14px; }
        .sectionTitle--caps { letter-spacing: .08em; }
        .twoCol { display:flex; gap: 16px; justify-content:space-between; margin: 10px 0 14px; }
        .twoCol > div { width: 48%; }
        .boxTitle { font-weight: 800; color: var(--blue); margin-bottom: 4px; font-size: 12px; }
        .infoBox { background: var(--bgSoft); border-radius: 6px; padding: 10px; font-size: 12px; color: #333; }

        /* Tables */
        table { width: 100%; border-collapse: collapse; }
        .table { border: 1px solid var(--border); }
        .table th, .table td { border: 1px solid var(--border); padding: 6px 8px; font-size: 12px; vertical-align: top; }
        .table--blue thead th { background: var(--blue); color: #fff; }
        .table--green thead th { background: var(--green); color: #fff; }

        /* Totals */
        .totalsWrap { display:flex; justify-content:flex-end; margin: 14px 0 10px; }
        .totalsBox { width: 300px; }
        .totalsRow { display:flex; justify-content:space-between; padding: 4px 0; font-size: 12px; }
        .totalsRow--grand { font-weight: 900; font-size: 14px; }
        .red { color: var(--red); }

        /* Receipt specifics */
        .infoGrid--compact { font-size: 12px; margin: 8px 0; }
        .infoGrid__row { margin-bottom: 4px; }
        .label { color: var(--muted); font-weight: 700; }
        .value { color: #111; font-weight: 600; }
        .receiptItems { margin: 6px 0 0; padding-left: 18px; font-size: 12px; }
        .receiptItems__li { margin-bottom: 4px; }
        .rowBetween { display:flex; justify-content:space-between; align-items:center; margin-top: 4px; }
        .totalLabel { font-weight: 800; font-size: 14px; }
        .totalValue { font-weight: 900; font-size: 15px; }
        .footerNote { text-align:center; color: var(--muted); font-size: 11px; margin-top: 6px; }

        /* Signatures */
        .signGrid { display:grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 8px; }
        .signLabel { font-weight: 800; margin-bottom: 6px; font-size: 11px; }
        .signLine { height: 40px; border-bottom: 1px solid #000; margin-bottom: 4px; }

        /* Footer blocks */
        .footerBlock { margin-top: 14px; padding-top: 10px; border-top: 1px solid var(--border); text-align:center; color: var(--muted); font-size: 11px; line-height: 1.4; }

        /* Actions */
        .actions { display:flex; gap: 8px; margin-top: 14px; }
        .btn { border: 1px solid var(--border); padding: 8px 12px; background: #fff; border-radius: 8px; cursor: pointer; font-size: 12px; }

        @media print {
            @page { margin: 5mm; size: auto; } /* Minimal printer margins */
            .actions { display:none; }
            .doc--receipt { box-shadow: none; border: none; }
            body { margin: 0; background: #fff; }
            
            /* CRITICAL: Overwrite A4 simulation styles for actual print to prevent spillover */
            .doc--a4 { 
                width: 100%; 
                box-sizing: border-box;
                display: flex;
                flex-direction: column;
                min-height: 100vh; /* Enforce full page height on print */
            }
            
            .doc { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    @php
        $type = $requested_type ?? ($sale->document_type ?? 'receipt');
        $isA4 = in_array($type, ['invoice', 'delivery_note'], true);
        $rawCustomer = $data['customer_info'] ?? [];
        $customer = is_array($rawCustomer) ? $rawCustomer : [];
        $items = $data['items'] ?? ($sale->sale_items ?? []);
        $dateLabel = \Illuminate\Support\Carbon::parse($data['date'] ?? $sale->created_at)->format('Y-m-d H:i');
    @endphp

    <div class="doc {{ $isA4 ? 'doc--a4' : 'doc--receipt' }}">
        @include('pos.documents._company_header', ['showFullHeader' => true])

        @if($type === 'invoice')
            @include('pos.documents.invoice', compact('sale', 'data', 'customer', 'items', 'dateLabel'))
        @elseif($type === 'delivery_note')
            @include('pos.documents.delivery_note', compact('sale', 'data', 'customer', 'items', 'dateLabel'))
        @elseif($type === 'packing_slip')
            @include('pos.documents.packing_slip', compact('sale', 'data', 'customer', 'items', 'dateLabel'))
        @else
            @include('pos.documents.receipt', compact('sale', 'data', 'customer', 'items', 'dateLabel'))
        @endif

        <div class="actions">
            <button class="btn" onclick="window.print()">Print</button>
            <button class="btn" onclick="window.close()">Close</button>
        </div>
    </div>
</body>
</html>


