<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SURGICAL SET DASHBOARD - {{ $set->name }}</title>
    <style>
        :root {
            --blue:#1976d2;
            --red:#d32f2f;
            --green:#2e7d32;
            --orange:#f57c00;
            --fg:#111;
            --muted:#666;
            --border:#ddd;
            --bgSoft:#f5f5f5;
        }
        body { font-family: ui-sans-serif, system-ui, -apple-system, sans-serif; color: var(--fg); margin: 0; background:#f0f2f5; }
        
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        
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
        
        .card { background: #fff; border-radius: 8px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 20px; }
        
        /* Tables */
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8f9fa; text-align: left; padding: 10px 15px; font-size: 12px; font-weight: 600; color: #555; text-transform: uppercase; border-bottom: 1px solid #eee; }
        td { padding: 10px 15px; border-bottom: 1px solid #eee; font-size: 13px; }
        tr:last-child td { border-bottom: none; }
        
        .qty-badge { background: #eee; padding: 2px 6px; border-radius: 4px; font-weight: 700; font-size: 11px; }
        .missing-row { background-color: #ffebee; }
        
        /* Buttons */
        .btn { padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; transition: background 0.2s; text-decoration: none; display: inline-block; }
        .btn-primary { background: var(--blue); color: #fff; }
        .btn-primary:hover { background: #1565c0; }
        .btn-secondary { background: #fff; border: 1px solid #ccc; color: #333; }
        .btn-secondary:hover { background: #f5f5f5; }

        @media print {
            body { background: #fff; }
            .container { padding: 0; max-width: 100%; }
            .header-card, .card { box-shadow: none; border: 1px solid #ccc; }
            .btn { display: none; }
        }
    </style>
</head>
<body>
    <div class="container">
        
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
                <a href="{{ route('sets.index') }}" class="btn btn-secondary">← Back to Sets</a>
                <button class="btn btn-secondary" onclick="window.print()">Print Dashboard</button>
                <a href="{{ route('reservations.index') }}" class="btn btn-primary">Dispatch via Case</a>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            
            <!-- 1. INSTRUMENTS (Fixed Assets) -->
            <div>
                <div class="section-title">🛑 Instruments (Fixed Assets)</div>
                <div class="card">
                    <table>
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
                <div class="card">
                    <table>
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
                    
                    @if($contents->sum('missing_quantity') > 0)
                    <div style="padding: 10px; text-align:center; border-top: 1px solid #eee;">
                         <button class="btn btn-secondary" style="width:100%; border-color:var(--red); color:var(--red);" 
                                 onclick="window.location.href='{{ route('stock-transfers.create') }}?set_id={{ $set->id }}'">
                             ⚡ Restock Missing Items
                         </button>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- 3. ADDITIONAL INVENTORY (Not in Template) -->
        <div class="section-title">➕ Additional Inventory (Extra Items Loaded)</div>
        <div class="card">
             <table>
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
</body>
</html>
