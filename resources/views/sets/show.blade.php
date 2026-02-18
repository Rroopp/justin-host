@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <style>
        .set-dashboard {
            --blue:#1976d2;
            --red:#d32f2f;
            --green:#2e7d32;
            --orange:#f57c00;
            --fg:#111;
            --muted:#666;
            --border:#ddd;
            --bgSoft:#f5f5f5;
        }
        
        /* Header Card */
        .header-card { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; border-left: 5px solid var(--blue); }
        .header-title { font-size: 24px; font-weight: 800; color: #111; margin: 0; }
        .header-meta { display: flex; gap: 20px; font-size: 13px; color: var(--muted); margin-top: 5px; }
        
        /* Status Badge */
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase; }
        .status-available { background: #e8f5e9; color: var(--green); }
        .status-in_surgery { background: #e3f2fd; color: var(--blue); }
        .status-maintenance { background: #fff3e0; color: var(--orange); }
        
        /* Sections */
        .section-title { font-size: 16px; font-weight: 700; color: #444; margin: 30px 0 10px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #ddd; padding-bottom: 5px; }
        
        .set-card { background: #fff; border-radius: 8px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 20px; }
        
        /* Tables */
        .set-table { width: 100%; border-collapse: collapse; }
        .set-table th { background: #f8f9fa; text-align: left; padding: 10px 15px; font-size: 12px; font-weight: 600; color: #555; text-transform: uppercase; border-bottom: 1px solid #eee; }
        .set-table td { padding: 10px 15px; border-bottom: 1px solid #eee; font-size: 13px; }
        .set-table tr:last-child td { border-bottom: none; }
        
        .qty-badge { background: #eee; padding: 2px 6px; border-radius: 4px; font-weight: 700; font-size: 11px; }
        .missing-row { background-color: #ffebee; }
        
        /* Buttons */
        .set-btn { padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; transition: background 0.2s; text-decoration: none; display: inline-block; }
        .set-btn-primary { background: var(--blue); color: #fff; }
        .set-btn-primary:hover { background: #1565c0; }
        .set-btn-secondary { background: #fff; border: 1px solid #ccc; color: #333; }
        .set-btn-secondary:hover { background: #f5f5f5; }

        @media print {
            .set-dashboard { background: #fff; }
            .header-card, .set-card { box-shadow: none; border: 1px solid #ccc; }
            .set-btn { display: none; }
        }
    </style>
    
    <div class="set-dashboard">
        
        <!-- Header -->
        <div class="header-card">
            <div style="display:flex; justify-content:space-between; align-items:start;">
                <div>
                    <h1 class="header-title">{{ $set->name }}</h1>
                    <div class="header-meta">
                        <span>Asset ID: {{ $set->asset->name ?? 'N/A' }}</span>
                        <span>Location: {{ $set->location->name ?? 'N/A' }}</span>
                        <span>Last Service: {{ $set->last_service_date ? $set->last_service_date->format('M d, Y') : 'Never' }}</span>
                    </div>
                </div>
                <div>
                    @php
                        $statusClass = match($set->status) {
                            'available' => 'status-available',
                            'in_surgery' => 'status-in_surgery',
                            'maintenance' => 'status-maintenance',
                            default => 'status-available'
                        };
                    @endphp
                    <span class="status-badge {{ $statusClass }}">{{ str_replace('_', ' ', $set->status) }}</span>
                </div>
            </div>
            
            <div style="margin-top: 15px; display:flex; gap: 10px;">
                <a href="{{ route('sets.index') }}" class="set-btn set-btn-secondary">← Back to Sets</a>
                <button class="set-btn set-btn-secondary" onclick="window.print()">Print Dashboard</button>
                <a href="{{ route('reservations.index') }}" class="set-btn set-btn-primary">Dispatch via Case</a>
                
                @if(auth()->user()->hasRole('admin'))
                <form action="{{ route('sets.destroy', $set->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this surgical set? This action cannot be undone and will delete the associated location and asset.');" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="set-btn set-btn-secondary" style="color:var(--red); border-color:var(--red);">Delete Set</button>
                </form>
                @endif
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            
            <!-- 1. INSTRUMENTS (Fixed Assets) -->
            <div>
                <div class="section-title">🛑 Instruments (Fixed Assets)</div>
                <div class="set-card">
                    <table class="set-table">
                        <thead>
                            <tr>
                                <th>Instrument Name</th>
                                <th>Serial No.</th>
                                <th>Condition</th>
                                <th style="text-align:right;">Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($instruments as $inst)
                                <tr class="{{ $inst->condition === 'missing' ? 'missing-row' : '' }}">
                                    <td>
                                        <strong>{{ $inst->name }}</strong>
                                        <div class="text-xs text-gray-500">{{ $inst->inventory ? $inst->inventory->product_name : '' }}</div>
                                    </td>
                                    <td>{{ $inst->serial_number ?? '-' }}</td>
                                    <td>
                                        @if($inst->condition === 'good')
                                            <span style="color:var(--green); font-weight:700;">✓ Good</span>
                                        @elseif($inst->condition === 'missing')
                                            <span style="color:var(--red); font-weight:700;">⚠ MISSING</span>
                                        @else
                                            <span style="color:var(--orange);">{{ ucfirst($inst->condition) }}</span>
                                        @endif
                                    </td>
                                    <td style="text-align:right;">{{ $inst->quantity }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" style="text-align:center; padding: 20px; color:#999;">No instruments recorded.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 2. CONSUMABLES (Inventory) -->
            <div>
                <div class="section-title">📦 Consumables Stock (Live)</div>
                <div class="set-card">
                    <table class="set-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th style="text-align:center;">Par Level</th>
                                <th style="text-align:center;">In Store</th>
                                <th style="text-align:center;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($contents as $content)
                                <tr class="{{ $content->missing_quantity > 0 ? 'missing-row' : '' }}">
                                    <td>
                                        {{ $content->inventory->product_name }}
                                        <div style="font-size:10px; color:#666;">{{ $content->inventory->code }}</div>
                                    </td>
                                    <td style="text-align:center;">{{ $content->standard_quantity }}</td>
                                    <td style="text-align:center; font-weight:700;">{{ $content->current_quantity }}</td>
                                    <td style="text-align:center;">
                                        @if($content->missing_quantity > 0)
                                            <span style="color:var(--red); font-weight:700;">-{{ $content->missing_quantity }} Missing</span>
                                        @else
                                            <span style="color:var(--green);">✓ OK</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" style="text-align:center; padding: 20px; color:#999;">No consumables defined.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    
                    @php
                    $restockUrl = route('stock-transfers.create', ['set_id' => $set->id]);
                    @endphp
                    @if($contents->sum('missing_quantity') > 0)
                    <div style="padding: 10px; text-align:center; border-top: 1px solid #eee;">
                         <button class="set-btn set-btn-secondary" style="width:100%; border-color:var(--red); color:var(--red);" 
                                 onclick="window.location.href='{{ $restockUrl }}'">
                             ⚡ Restock Missing Items
                         </button>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- 3. ADDITIONAL INVENTORY (Not in Template) -->
        <div class="section-title">➕ Additional Inventory (Extra Items Loaded)</div>
        <div class="set-card">
             <table class="set-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Batch / Expiry</th>
                        <th style="text-align:right;">Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        // Filter out items that are already in the "contents" list
                        $templateIds = $contents->pluck('inventory_id')->toArray();
                        $extras = $consumables->whereNotIn('inventory_id', $templateIds);
                    @endphp

                    @forelse($extras as $batch)
                        <tr>
                            <td>{{ $batch->inventory->product_name }}</td>
                            <td>{{ $batch->batch_number }} <span style="font-size:11px; color:#666;">({{ $batch->expiry_date ? $batch->expiry_date->format('M Y') : 'No Exp' }})</span></td>
                            <td style="text-align:right; font-weight:700;">{{ $batch->quantity }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align:center; padding: 15px; color:#999;">No extra items loaded in this set.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</div>
@endsection
