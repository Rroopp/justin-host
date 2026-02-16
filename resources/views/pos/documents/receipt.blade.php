@php
    $customer = $customer ?? [];
    $items = $items ?? [];
    
    // Helper for Number to Words (if not already defined)
    if (!function_exists('numberToWords')) {
        function numberToWords($number) {
            if (class_exists('NumberFormatter')) {
                $f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
                return ucfirst($f->format($number));
            }
            return number_format($number, 2); 
        }
    }
    
    $total = (float)($sale->total ?? 0);
    $totalWords = 'Kenya Shillings ' . numberToWords($total) . ' only';
@endphp

<div class="docTitle docTitle--blue">RECEIPT</div>

<div class="infoGrid infoGrid--compact">
    <div class="infoGrid__row"><span class="label">Date:</span> <span class="value">{{ $dateLabel }}</span></div>
    <div class="infoGrid__row"><span class="label">Receipt No:</span> <span class="value">{{ $sale->id }}</span></div>
    <div class="infoGrid__row"><span class="label">Served By:</span> <span class="value">{{ $sale->seller_username ?? 'Staff' }}</span></div>
    <div class="infoGrid__row"><span class="label">Phone:</span> <span class="value">{{ $customer['phone'] ?? ($sale->customer_phone ?? '---') }}</span></div>
</div>

<div class="divider"></div>

<div class="sectionTitle">Items</div>
<ul class="receiptItems">
    @foreach($items as $item)
        @php
            $name = $item['product_name'] ?? $item['name'] ?? 'Item';
            $qty = (int) ($item['quantity'] ?? 0);
            $unit = (float) ($item['unit_price'] ?? 0);
            $lineTotal = (float) ($item['item_total'] ?? $item['total'] ?? ($unit * $qty));
        @endphp
@php
    $symbol = \App\Services\SettingsService::currencySymbol();
    // Use format_currency helper if available, else manual
@endphp
        <li class="receiptItems__li">
            {{ $name }} x {{ $qty }} @ {{ format_currency($unit) }}
            = <strong>{{ format_currency($lineTotal) }}</strong>
        </li>
    @endforeach
</ul>

<div class="divider"></div>

<div class="rowBetween">
    <div class="muted">Payment:</div>
    <div class="muted">{{ $sale->payment_method }} ({{ $sale->payment_status }})</div>
</div>
<div class="rowBetween">
    <div class="totalLabel">Total:</div>
    <div class="totalValue">{{ format_currency($sale->total ?? 0) }}</div>
</div>
<div style="text-align:right; font-size:11px; color:#666; margin-top:5px; text-transform: capitalize;">
    {{ \App\Services\SettingsService::currencyCode() }} {{ numberToWords((float)($sale->total ?? 0)) }} only
</div>

<div class="divider"></div>

<div class="footerNote">
    @if(!empty($template->template_data['footer']))
        {!! nl2br(e($template->template_data['footer'])) !!}
    @else
        Thank you for choosing {{ settings('company_name', 'Jastene Medical Ltd') }}.
    @endif
</div>





