@extends('layouts.app')

@section('content')
<style>
    @media print {
        @page { size: A4; margin: 15mm; }
        
        /* Hide UI elements */
        aside, nav, header, button, .no-print { 
            display: none !important; 
        }

        /* Reset all layout constraints that might clip content */
        html, body, #app, main, div { 
            display: block !important; 
            width: 100% !important; 
            height: auto !important; 
            margin: 0 !important; 
            padding: 0 !important;
            overflow: visible !important;
            position: static !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Ensure text is black */
        * {
            color: black !important;
        }

        .print-container { 
            background: white !important;
            box-shadow: none !important; 
            margin: 0 !important; 
            padding: 0 !important;
        }
    }

    .print-container {
        max-width: 210mm;
        margin: 0 auto;
        background: white;
        padding: 20px;
    }

    .stock-take-title {
        font-size: 24px;
        font-weight: bold;
        color: #0047AB;
        margin-bottom: 10px;
        text-align: center;
    }

    .count-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
        font-size: 11px;
    }

    .count-table th,
    .count-table td {
        border: 1px solid #ddd;
        padding: 6px;
        text-align: left;
        color: black !important; /* Force black text for readability */
    }

    .count-table th {
        background-color: #0047AB;
        color: white;
        font-weight: bold;
    }

    .count-table tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    .signature-section {
        margin-top: 40px;
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 30px;
    }

    .signature-box {
        border-top: 1px solid #000;
        padding-top: 5px;
    }

    .signature-label {
        font-size: 10px;
        font-weight: bold;
        text-transform: uppercase;
    }
</style>

<div class="no-print mb-4 text-center">
    <button onclick="window.print()" class="bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700">
        <i class="fas fa-print mr-2"></i>Print Stock Take Sheet
    </button>
    <a href="{{ route('stock-takes.show', $stockTake) }}" class="ml-3 bg-gray-600 text-white px-6 py-2 rounded-md hover:bg-gray-700">
        Back to Stock Take
    </a>
</div>

<div class="print-container">
    <!-- Company Header -->
    @php
        $company = [];  // Use default values from the partial
    @endphp
    @include('pos.documents._company_header')

    <!-- Stock Take Title and Info -->
    <div class="stock-take-title">STOCK TAKE SHEET</div>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; font-size: 12px; color: black !important;">
        <div>
            <strong>Reference:</strong> {{ $stockTake->reference_number }}<br>
            <strong>Date:</strong> {{ $stockTake->date->format('d M Y') }}<br>
            <strong>Created By:</strong> {{ $stockTake->creator->full_name ?? 'N/A' }}
        </div>
        <div>
            <strong>Total Items:</strong> {{ $stockTake->items->count() }}<br>
            @if($stockTake->category_filter)
                <strong>Categories:</strong> {{ implode(', ', $stockTake->category_filter) }}
            @else
                <strong>Categories:</strong> All
            @endif
        </div>
    </div>

    @if($stockTake->notes)
        <div style="background: #f0f0f0; padding: 10px; margin-bottom: 15px; font-size: 11px;">
            <strong>Notes:</strong> {{ $stockTake->notes }}
        </div>
    @endif

    <!-- Instructions -->
    <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 10px; margin-bottom: 15px; font-size: 11px;">
        <strong>Instructions:</strong> Count each item physically and record the quantity in the "Physical Count" column. 
        Note any discrepancies or issues in the "Notes" column.
    </div>

    <!-- Items by Category -->
    @if($itemsByCategory && $itemsByCategory->count() > 0)
    @foreach($itemsByCategory as $category => $items)
        <div style="page-break-inside: avoid; margin-bottom: 20px;">
            <h3 style="background: #0047AB; color: white; padding: 8px; margin-top: 15px; font-size: 13px;">
                {{ $category }} ({{ $items->count() }} items)
            </h3>
            
            <table class="count-table">
                <thead>
                    <tr>
                        <th style="width: 2%;">#</th>
                        <th style="width: 6%;">Code</th>
                        <th style="width: 18%;">Product Name</th>
                        <th style="width: 8%;">Category</th>
                        <th style="width: 6%;">Size</th>
                        <th style="width: 6%;">Type</th>
                        <th style="width: 7%;">Material</th>
                        <th style="width: 5%;">Side</th>
                        <th style="width: 8%;">Mfr</th>
                        <th style="width: 6%;">Sys Qty</th>
                        <th style="width: 6%;">Physical</th>
                        <th style="width: 22%;">Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td style="font-family: monospace; font-size: 9px;">{{ $item->inventory->code ?? 'N/A' }}</td>
                            <td style="font-size: 10px;">
                                <strong>{{ $item->inventory->product_name ?? 'Unknown Item' }}</strong>
                                @if($item->inventory && $item->inventory->subcategory)
                                    <br><span style="color: #666; font-size: 8px;">{{ $item->inventory->subcategory }}</span>
                                @endif
                            </td>
                            <td style="font-size: 9px;">{{ $item->inventory->category ?? '-' }}</td>
                            <td style="font-size: 9px;">
                                @if($item->inventory && $item->inventory->size)
                                    <span style="background: #4F46E5; color: white; padding: 1px 3px; border-radius: 2px; font-weight: bold; display: inline-block;">
                                        {{ $item->inventory->size }}{{ $item->inventory->size_unit }}
                                    </span>
                                @endif
                            </td>
                            <td style="font-size: 9px;">{{ $item->inventory->type ?? '-' }}</td>
                            <td style="font-size: 9px;">
                                @if($item->inventory && $item->inventory->attributes && isset($item->inventory->attributes['material']))
                                    <span style="
                                        @if(str_contains($item->inventory->attributes['material'], 'Titanium') || $item->inventory->attributes['material'] === 'Ti')
                                            background: #DBEAFE; color: #1E40AF;
                                        @elseif(str_contains($item->inventory->attributes['material'], 'Steel') || $item->inventory->attributes['material'] === 'SS')
                                            background: #E5E7EB; color: #374151;
                                        @else
                                            background: #FCE7F3; color: #9F1239;
                                        @endif
                                        padding: 1px 3px; border-radius: 2px; display: inline-block; font-size: 8px;">
                                        {{ $item->inventory->attributes['material'] }}
                                    </span>
                                @endif
                            </td>
                            <td style="font-size: 9px; text-align: center;">
                                @if($item->inventory && $item->inventory->attributes && isset($item->inventory->attributes['side']))
                                    <span style="
                                        @if(str_contains($item->inventory->attributes['side'], 'Left') || $item->inventory->attributes['side'] === 'L')
                                            background: #D1FAE5; color: #065F46;
                                        @elseif(str_contains($item->inventory->attributes['side'], 'Right') || $item->inventory->attributes['side'] === 'R')
                                            background: #FEE2E2; color: #991B1B;
                                        @elseif(str_contains($item->inventory->attributes['side'], 'Bilateral') || $item->inventory->attributes['side'] === 'Bilat')
                                            background: #E9D5FF; color: #6B21A8;
                                        @else
                                            background: #E5E7EB; color: #374151;
                                        @endif
                                        padding: 1px 4px; border-radius: 2px; display: inline-block; font-weight: bold; font-size: 8px;">
                                        {{ $item->inventory->attributes['side'] }}
                                    </span>
                                @endif
                            </td>
                            <td style="font-size: 9px;">{{ $item->inventory->manufacturer ?? '-' }}</td>
                            <td style="text-align: right; font-weight: bold; font-size: 10px;">{{ number_format($item->system_quantity, 0) }}</td>
                            <td style="background: #f0f0f0;"></td>
                            <td style="background: #f0f0f0; font-size: 9px;">
                                @if($item->inventory && $item->inventory->is_rentable)
                                    <span style="background: #DBEAFE; color: #1E40AF; padding: 1px 3px; border-radius: 2px; font-weight: bold; display: inline-block; font-size: 7px; border: 1px solid #3B82F6;">
                                        RENTAL
                                    </span>
                                @endif
                                {{-- Other attributes that don't have dedicated columns --}}
                                @if($item->inventory && $item->inventory->attributes)
                                    @foreach($item->inventory->attributes as $key => $value)
                                        @if($value && !in_array($key, ['material', 'side']))
                                            <span style="background: #FEF3C7; color: #92400E; padding: 1px 3px; border-radius: 2px; display: inline-block; font-size: 7px; margin: 1px;">
                                                {{ ucfirst($key) }}: {{ $value }}
                                            </span>
                                        @endif
                                    @endforeach
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
    @else
        <div style="padding: 20px; text-align: center; color: #999;">
            No items found for this stock take.
        </div>
    @endif

    <!-- Signature Section -->
    <div class="signature-section">
        <div>
            <div style="height: 40px;"></div>
            <div class="signature-box">
                <div class="signature-label">Counted By</div>
                <div style="font-size: 9px; margin-top: 3px;">Name: ___________________</div>
                <div style="font-size: 9px;">Date: ___________________</div>
            </div>
        </div>
        <div>
            <div style="height: 40px;"></div>
            <div class="signature-box">
                <div class="signature-label">Verified By</div>
                <div style="font-size: 9px; margin-top: 3px;">Name: ___________________</div>
                <div style="font-size: 9px;">Date: ___________________</div>
            </div>
        </div>
        <div>
            <div style="height: 40px;"></div>
            <div class="signature-box">
                <div class="signature-label">Approved By</div>
                <div style="font-size: 9px; margin-top: 3px;">Name: ___________________</div>
                <div style="font-size: 9px;">Date: ___________________</div>
            </div>
        </div>
    </div>

    <div style="text-align: center; color: #999; font-size: 9px; margin-top: 30px;">
        This is a system-generated stock take sheet. Reference: {{ $stockTake->reference_number }}
    </div>
</div>
@endsection
