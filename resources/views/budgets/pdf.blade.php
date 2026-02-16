<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $budget->name }} - Budget Report</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10pt;
            color: #333;
        }
        .header {
            width: 100%;
            border-bottom: 2px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .company-name {
            font-size: 18pt;
            font-weight: bold;
            color: #2c3e50;
        }
        .report-title {
            font-size: 14pt;
            color: #7f8c8d;
            margin-top: 5px;
        }
        .meta-info {
            width: 100%;
            margin-bottom: 20px;
        }
        .meta-table td {
            padding: 5px;
            vertical-align: top;
        }
        .label {
            font-weight: bold;
            color: #555;
            width: 120px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        table.data-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            color: #2c3e50;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .status-badge {
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 8pt;
            color: white;
            display: inline-block;
        }
        .status-active { background-color: #2ecc71; }
        .status-draft { background-color: #95a5a6; }
        .status-archived { background-color: #7f8c8d; }
        .status-completed { background-color: #3498db; }
        
        .variance-positive { color: #2ecc71; }
        .variance-negative { color: #e74c3c; }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            font-size: 8pt;
            text-align: center;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
        .summary-box {
            background-color: #f9f9f9;
            border: 1px solid #eee;
            padding: 15px;
            margin-bottom: 20px;
        }
        .summary-item {
            display: inline-block;
            margin-right: 30px;
        }
        .summary-label {
            font-size: 9pt;
            color: #7f8c8d;
            display: block;
        }
        .summary-value {
            font-size: 14pt;
            font-weight: bold;
            color: #2c3e50;
            display: block;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ settings('company_name', 'Hospital POS') }}</div>
        <div class="report-title">Budget Report</div>
    </div>

    <table class="meta-table">
        <tr>
            <td class="label">Budget Name:</td>
            <td>{{ $budget->name }}</td>
            <td class="label">Reference:</td>
            <td>{{ $budget->reference_number }}</td>
        </tr>
        <tr>
            <td class="label">Period:</td>
            <td>{{ ucfirst($budget->period_type) }}</td>
            <td class="label">Date Range:</td>
            <td>{{ $budget->start_date->format('M d, Y') }} - {{ $budget->end_date->format('M d, Y') }}</td>
        </tr>
        <tr>
            <td class="label">Status:</td>
            <td>
                <span class="status-badge status-{{ $budget->status }}">
                    {{ ucfirst($budget->status) }}
                </span>
            </td>
            <td class="label">Generated:</td>
            <td>{{ now()->format('M d, Y H:i') }}</td>
        </tr>
    </table>

    <div class="summary-box">
        <div class="summary-item">
            <span class="summary-label">Total Allocated</span>
            <span class="summary-value">{{ number_format($budget->total_allocated, 2) }}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Total Spent</span>
            <span class="summary-value">{{ number_format($budget->total_spent, 2) }}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Remaining</span>
            <span class="summary-value">{{ number_format($budget->total_remaining, 2) }}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Utilization</span>
            <span class="summary-value">{{ number_format($budget->getUtilizationPercentage(), 1) }}%</span>
        </div>
    </div>

    @if($budget->description)
    <div style="margin-bottom: 20px;">
        <strong>Description:</strong><br>
        {{ $budget->description }}
    </div>
    @endif

    <h3>Line Items Breakdown</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Category</th>
                <th>Subcategory</th>
                <th class="text-right">Allocated</th>
                <th class="text-right">Spent</th>
                <th class="text-right">Remaining</th>
                <th class="text-right">% Used</th>
            </tr>
        </thead>
        <tbody>
            @foreach($budget->lineItems as $item)
            <tr>
                <td>{{ $item->category }}</td>
                <td>{{ $item->subcategory ?? '-' }}</td>
                <td class="text-right">{{ number_format($item->allocated_amount, 2) }}</td>
                <td class="text-right">{{ number_format($item->spent_amount, 2) }}</td>
                <td class="text-right">
                    <span class="{{ $item->remaining_amount < 0 ? 'variance-negative' : '' }}">
                        {{ number_format($item->remaining_amount, 2) }}
                    </span>
                </td>
                <td class="text-right">
                    <span class="{{ $item->getUtilizationPercentage() > 100 ? 'variance-negative' : '' }}">
                        {{ number_format($item->getUtilizationPercentage(), 1) }}%
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="2" class="text-right">Totals</th>
                <th class="text-right">{{ number_format($budget->total_allocated, 2) }}</th>
                <th class="text-right">{{ number_format($budget->total_spent, 2) }}</th>
                <th class="text-right">{{ number_format($budget->total_remaining, 2) }}</th>
                <th class="text-right">{{ number_format($budget->getUtilizationPercentage(), 1) }}%</th>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Generated by {{ Auth::user()->name }} | Page <script type="text/php">if (isset($pdf)) { echo $pdf->get_page_number(); }</script>
    </div>
</body>
</html>
