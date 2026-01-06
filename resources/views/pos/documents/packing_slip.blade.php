@php
    $customer = $customer ?? [];
    $items = $items ?? [];
@endphp

<div class="titleRow">
    <div class="docTitle docTitle--green">PACKING SLIP</div>
    <div class="titleMeta">
        <div class="titleMeta__line"><strong>Ref #:</strong> PS-{{ $sale->id }}</div>
        <div class="titleMeta__line"><span class="muted">Date:</span> {{ $dateLabel }}</div>
        <div class="titleMeta__line"><span class="muted">Sale ID:</span> #{{ $sale->id }}</div>
    </div>
</div>

<div class="divider divider--solid"></div>

<div class="twoCol">
    <div>
        <div class="boxTitle">SHIP TO:</div>
        <div class="infoBox">
            <div><strong>{{ $customer['name'] ?? ($sale->customer_name ?? 'N/A') }}</strong></div>
            @if(!empty($customer['facility'])) <div>{{ $customer['facility'] }}</div> @endif
            @if(!empty($customer['address'])) <div>{{ $customer['address'] }}</div> @endif
            @if(!empty($customer['phone'])) <div>Phone: {{ $customer['phone'] }}</div> @endif
            @if(!empty($customer['email'])) <div>Email: {{ $customer['email'] }}</div> @endif
        </div>
    </div>

    <div>
        <div class="boxTitle">ORDER DETAILS:</div>
        <div class="infoBox">
            <div><strong>Packed Date:</strong> {{ now()->format('Y-m-d') }}</div>
            <div><strong>Packed By:</strong> {{ $sale->seller_username ?? 'N/A' }}</div>
            
            @if(!empty($data['patient_name']) || !empty($sale->patient_name))
                <div><strong>Patient Name:</strong> {{ $data['patient_name'] ?? $sale->patient_name }}</div>
            @endif
            
            @if(!empty($data['patient_number']) || !empty($sale->patient_number))
                <div><strong>Patient No:</strong> {{ $data['patient_number'] ?? $sale->patient_number }}</div>
            @endif

            @if(!empty($data['surgeon_name']) || !empty($sale->surgeon_name))
                <div><strong>Surgeon:</strong> {{ $data['surgeon_name'] ?? $sale->surgeon_name }}</div>
            @endif
            
            @if(!empty($data['nurse_name']) || !empty($sale->nurse_name))
                <div><strong>Nurse:</strong> {{ $data['nurse_name'] ?? $sale->nurse_name }}</div>
            @endif
        </div>
    </div>
</div>

<div class="sectionTitle sectionTitle--caps">CONTENTS</div>

<table class="table table--green">
    <thead>
        <tr>
            <th style="width: 70px;">Item #</th>
            <th style="width: 220px;">Product Name</th>
            <th>Description</th>
            <th style="width: 60px; text-align:center;">Type</th>
            <th style="width: 90px; text-align:center;">Qty</th>
            <th style="width: 90px; text-align:center;">Check</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $item)
            @php
                // Better description fallback - use product name if description is empty
                $desc = $item['product_snapshot']['description'] ?? $item['description'] ?? $item['product_name'] ?? '';
                $type = ucfirst($item['type'] ?? 'Sale');
            @endphp
            <tr>
                <td>{{ $item['product_id'] ?? '' }}</td>
                <td><strong>{{ $item['product_name'] ?? 'Item' }}</strong></td>
                <td>{{ $desc ?: '-' }}</td>
                <td style="text-align:center; font-size:11px;">{{ $type }}</td>
                <td style="text-align:center;">{{ (int)($item['quantity'] ?? 0) }}</td>
                <td style="text-align:center; border:1px solid #ddd;"></td>
            </tr>
        @endforeach

        @for($i = 0; $i < 3; $i++)
            <tr>
                <td style="height:40px;">&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>
        @endfor
    </tbody>
</table>

<div class="sectionTitle sectionTitle--caps">VERIFICATION</div>
<div class="signGrid">
    <div>
        <div class="signLabel">PACKED BY:</div>
        <div class="signLine"></div>
        <div class="muted">Signature &amp; Date</div>
    </div>
    <div>
        <div class="signLabel">CHECKED BY:</div>
        <div class="signLine"></div>
        <div class="muted">Signature &amp; Date</div>
    </div>
</div>

<div class="footerBlock">
    <div><em>Please verify contents against this packing slip immediately upon receipt.</em></div>
</div>
