@php
    $customer = $customer ?? [];
    $items = $items ?? [];
    
    // Helper for Number to Words
    if (!function_exists('numberToWords')) {
        function numberToWords($number) {
            if (class_exists('NumberFormatter')) {
                $f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
                return ucfirst($f->format($number));
            }
            return number_format($number, 2); // Fallback
        }
    }
    

    $total = (float)($sale->total ?? 0);
    $totalWords = 'Kenya Shillings ' . numberToWords($total) . ' only';
@endphp

<div class="titleRow">
    <div class="docTitle docTitle--red">INVOICE</div>
    <div class="titleMeta">
        <div class="titleMeta__line"><strong>Invoice #:</strong> {{ $sale->invoice_number ?? $sale->id ?? 'N/A' }}</div>
        <div class="titleMeta__line"><span class="muted">Date:</span> {{ $dateLabel }}</div>
        <div class="titleMeta__line"><span class="muted">Due Date:</span> {{ $sale->due_date ? $sale->due_date->format('Y-m-d') : '-' }}</div>
    </div>
</div>

<div class="divider divider--solid"></div>

<div class="twoCol">
    <div>
        <div class="boxTitle">BILL TO:</div>
        <div class="infoBox">
            <div><strong>{{ $customer['name'] ?? ($sale->customer_name ?? 'N/A') }}</strong></div>
            @if(!empty($customer['facility'])) <div>{{ $customer['facility'] }}</div> @endif
            @if(!empty($customer['address'])) <div>{{ $customer['address'] }}</div> @endif
            @if(!empty($customer['phone'])) <div>Phone: {{ $customer['phone'] }}</div> @endif
            @if(!empty($customer['email'])) <div>Email: {{ $customer['email'] }}</div> @endif
            <div style="margin-top:4px; padding-top:4px; border-top:1px solid #eee;">
               <strong>Served By:</strong> {{ $sale->seller_username ?? 'Staff' }}
            </div>
        </div>
    </div>

    <div>
        <div class="boxTitle">PATIENT / CASE INFO:</div>
        <div class="infoBox">
            <div><strong>Patient Name:</strong> {{ $data['patient_name'] ?? $sale->patient_name ?? 'N/A' }}</div>
            
            @if(!empty($data['patient_number']) || !empty($sale->patient_number))
                <div><strong>Patient No:</strong> {{ $data['patient_number'] ?? $sale->patient_number }}</div>
            @endif

            <div><strong>Patient Type:</strong> {{ $data['patient_type'] ?? $sale->patient_type ?? 'N/A' }}</div>
            
            @if(!empty($data['surgeon_name']) || !empty($sale->surgeon_name))
                <div><strong>Surgeon:</strong> {{ $data['surgeon_name'] ?? $sale->surgeon_name }}</div>
            @endif

            @if(!empty($data['facility_name']) || !empty($sale->facility_name))
                <div><strong>Facility:</strong> {{ $data['facility_name'] ?? $sale->facility_name }}</div>
            @endif
        </div>
    </div>
</div>

<div class="sectionTitle sectionTitle--caps">ITEMS &amp; SERVICES</div>

<table class="table table--blue">
    <thead>
        <tr>
            <th style="width: 70px;">Item #</th>
            <th>Description</th>
            <th style="width: 70px; text-align:center;">Qty</th>
            <th style="width: 110px; text-align:right;">Unit Price</th>
            <th style="width: 110px; text-align:right;">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $item)
            @php
                $type = $item['type'] ?? 'sale';
                $isHeader = $type === 'package_header';
                $isComponent = $type === 'package_component';
                
                // Skip package components in invoices - only show the package header
                if ($isComponent) continue;
                
                $desc = $item['product_name'] ?? 'Item';
                $snapDesc = $item['product_snapshot']['description'] ?? null;
            @endphp
            <tr style="{{ $isHeader ? 'background:#f9f9f9; font-weight:bold;' : '' }}">
                <td>{{ $isHeader ? 'PKG' : ($item['product_id'] ?? '') }}</td>
                <td>
                    <div>
                        <strong>{{ $desc }}</strong>
                        @if($snapDesc && !$isHeader)
                            <div class="muted" style="margin-top:2px;">{{ $snapDesc }}</div>
                        @endif
                    </div>
                </td>
                <td style="text-align:center;">{{ (int)($item['quantity'] ?? 0) }}</td>
                <td style="text-align:right;">
                    KSh {{ number_format((float)($item['unit_price'] ?? 0), 2) }}
                </td>
                <td style="text-align:right;">
                    KSh {{ number_format((float)($item['item_total'] ?? 0), 2) }}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="totalsWrap">
    <div class="totalsBox">
        <div class="totalsRow totalsRow--grand"><span>Total Amount:</span><span class="red">KSh {{ number_format((float)($sale->total ?? 0), 2) }}</span></div>
        <div style="text-align:right; font-size:12px; margin-top:5px; color:#555; text-transform: capitalize;">
            <strong>Amount in Words:</strong> {{ $totalWords }}
        </div>
    </div>
 </div>

<div class="footerBlock" style="margin-top: auto;">
    @if(!empty($template->template_data['footer']))
        <div>{!! nl2br(e($template->template_data['footer'])) !!}</div>
    @endif
    @if(!empty($template->template_data['terms']))
        <div style="margin-top:8px; font-size:11px;">
            <strong>Terms:</strong> {!! nl2br(e($template->template_data['terms'])) !!}
        </div>
    @endif
    
    @if(empty($template->template_data['footer']) && empty($template->template_data['terms']))
        <div><strong>Payment Terms:</strong> Payment is due within 30 days of invoice date.</div>
        <div><strong>Thank you for your business!</strong></div>
    @endif

    <div style="margin-top:5px;"><em>This is a computer-generated invoice.</em></div>
</div>





