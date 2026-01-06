@extends('layouts.app')

@section('title', 'Payroll Run #' . $run->id)

@section('content')
<style>
    /* -------------------------------------------------------------------------- */
    /*                        MERGED STYLES FROM RECEIPT & HEADER                 */
    /* -------------------------------------------------------------------------- */
    :root {
        --blue:#1976d2;
        --red:#d32f2f;
        --green:#2e7d32;
        --fg:#111;
        --muted:#666;
        --border:#ddd;
        --bgSoft:#f5f5f5;
    }

    /* Print / reset styles */
    @media print {
        @page { size: A4; margin: 0; }
        aside, nav, header, button, .no-print, .relative.z-10.flex-shrink-0.flex.h-16 { display: none !important; }
        body, #app, main, .flex, .flex-col, .overflow-hidden {
            display: block !important; width: 100% !important; height: auto !important; margin: 0 !important; padding: 0 !important; overflow: visible !important; position: static !important; background: white !important;
        }
        .py-6, .max-w-7xl, .px-4 { padding: 0 !important; margin: 0 !important; max-width: none !important; }
        .doc--a4 { box-shadow: none !important; margin: 0 !important; width: 100% !important; min-height: 297mm; border: none !important; page-break-after: always; }
        .manual-page-break:last-child { page-break-after: auto; }
    }

    /* Company Header Styles - Moved to partials/document_header.blade.php */

    /* Invoice/Receipt Layout Styles */
    .doc--a4 {
        width: 210mm; min-height: 297mm; padding: 15mm; margin: 0 auto 30px;
        background: white; box-shadow: 0 0 10px rgba(0,0,0,0.1); position: relative; box-sizing: border-box;
    }
    .titleRow { display:flex; justify-content:space-between; align-items:flex-start; gap: 16px; margin: 10px 0 8px; }
    .docTitle { font-weight: 900; font-size: 28px; letter-spacing: .5px; }
    .docTitle--blue { color: var(--blue); }
    .titleMeta { text-align:right; font-size: 12px; }
    .divider { border-top: 1px solid var(--border); margin: 10px 0; }
    .divider--solid { border-top-style: solid; }
    .twoCol { display:flex; gap: 16px; justify-content:space-between; margin: 10px 0 14px; }
    .twoCol > div { width: 48%; }
    .boxTitle { font-weight: 800; color: var(--blue); margin-bottom: 4px; font-size: 12px; }
    .infoBox { background: var(--bgSoft); border-radius: 6px; padding: 10px; font-size: 12px; color: #333; }
    
    table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    .table th, .table td { border: 1px solid var(--border); padding: 6px 8px; font-size: 12px; vertical-align: top; }
    .table--blue thead th { background: var(--blue); color: #fff; }
    .amount-col { text-align: right; }

    .totalsWrap { display:flex; justify-content:flex-end; margin: 14px 0 10px; }
    .totalsBox { width: 350px; }
    .totalsRow { display:flex; justify-content:space-between; padding: 4px 0; font-size: 12px; }
    .totalsRow--grand { font-weight: 900; font-size: 14px; padding-top: 8px; border-top: 2px solid #ddd; }
    
    .signGrid { display:grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 40px; }
    .signLabel { font-weight: 800; margin-bottom: 6px; font-size: 11px; text-transform: uppercase;}
    .signLine { height: 40px; border-bottom: 1px solid #000; margin-bottom: 4px; }

    /* Print Single Logic */
    @media print {
        body.printing-single * { visibility: hidden; }
        body.printing-single .print-target, body.printing-single .print-target * { visibility: visible; }
        body.printing-single .print-target { 
            position: absolute; left: 0; top: 0; width: 100%; margin: 0; padding: 0; 
            visibility: visible !important; display: block !important;
        }
        /* Hide the print button itself on the printed copy */
        .btn-print-single { display: none !important; }
    }
    
    .btn-print-single {
        border: 1px solid #ddd; background: #fff; padding: 4px 12px; font-size: 12px; border-radius: 4px; cursor: pointer; float: right; margin-bottom: 10px;
    }
    .btn-print-single:hover { background: #f0f0f0; }

</style>

<div class="flex justify-between items-center no-print mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Payroll Run #{{ $run->id }}</h1>
        <p class="text-sm text-gray-500">{{ $run->period_start->format('M d, Y') }} - {{ $run->period_end->format('M d, Y') }}</p>
    </div>
    <div class="flex space-x-3">
        <a href="{{ route('payroll.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Back</a>
        <button onclick="window.print()" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
            <i class="fas fa-print mr-2"></i> Print All
        </button>
    </div>
</div>

<div class="no-print grid grid-cols-1 gap-5 sm:grid-cols-3 mb-8">
     <!-- Web Summary (Keep existing) -->
    <div class="bg-white overflow-hidden shadow rounded-lg p-5">
        <dt class="text-sm font-medium text-gray-500">Total Gross</dt>
        <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ number_format($run->total_gross, 2) }}</dd>
    </div>
    <div class="bg-white overflow-hidden shadow rounded-lg p-5">
        <dt class="text-sm font-medium text-gray-500">Total Net Pay</dt>
        <dd class="mt-1 text-3xl font-semibold text-green-600">{{ number_format($run->total_net, 2) }}</dd>
    </div>
</div>

<!-- PAYSLIPS LOOP -->
<div id="payslips-container">
    @foreach($run->items as $item)
    <!-- Wrapper for print targeting -->
    <div class="payslip-wrapper mb-8" id="payslip-{{ $item->id }}">
        
        <div class="no-print" style="max-width: 210mm; margin: 0 auto; text-align: right; margin-bottom: -15px; position: relative; z-index: 10;">
             <button onclick="printSingle('payslip-{{ $item->id }}')" class="btn-print-single">
                <i class="fas fa-print"></i> Print This Slip
             </button>
        </div>

        <div class="doc--a4 manual-page-break">
            
            <!-- HEADER (Copied from invoices) -->
            @include('partials.document_header')

            <div class="titleRow">
                <div class="docTitle docTitle--blue">PAYSLIP</div>
                <div class="titleMeta">
                    <div class="titleMeta__line"><strong>Run ID:</strong> #{{ $run->id }}</div>
                    <div class="titleMeta__line"><span class="muted">Period:</span> {{ $run->period_start->format('d M Y') }} - {{ $run->period_end->format('d M Y') }}</div>
                    <div class="titleMeta__line"><span class="muted">Date:</span> {{ $run->created_at->format('Y-m-d') }}</div>
                </div>
            </div>

            <div class="divider divider--solid"></div>

            <div class="twoCol">
                <div>
                    <div class="boxTitle">EMPLOYEE DETAILS:</div>
                    <div class="infoBox">
                        <div><strong>{{ $item->employee->full_name }}</strong></div>
                        <div>Designation: {{ $item->employee->designation ?? $item->employee->role ?? 'N/A' }}</div>
                        <div>Staff ID: {{ $item->employee->staff_number ?? $item->employee_id }}</div>
                        <div>Phone: {{ $item->employee->phone ?? '-' }}</div>
                    </div>
                </div>
                <div>
                    <div class="boxTitle">PAYMENT DETAILS:</div>
                    <div class="infoBox">
                        <div><strong>Status:</strong> {{ ucfirst(strtolower($run->status)) }}</div>
                        <div><strong>Payment Method:</strong> Bank Transfer</div>
                        <div><strong>Bank:</strong> {{ $item->employee->bank_name ?? '-' }}</div>
                        <div><strong>Account:</strong> {{ $item->employee->account_number ?? '-' }}</div>
                    </div>
                </div>
            </div>

            <!-- EARNINGS -->
            <div class="boxTitle" style="margin-top:20px;">EARNINGS</div>
            <table class="table table--blue">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th style="width: 150px; text-align:right;">Amount (KSh)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Basic Salary</td>
                        <td class="amount-col">{{ number_format($item->gross_pay, 2) }}</td>
                    </tr>
                    @if(false && $item->allowances)
                    <tr>
                        <td>Allowances</td>
                        <td class="amount-col">0.00</td>
                    </tr>
                    @endif
                </tbody>
            </table>

            <!-- DEDUCTIONS -->
            <div class="boxTitle" style="margin-top:20px; color: var(--red);">DEDUCTIONS</div>
            <table class="table" style="border-color: var(--red);">
                <thead style="background: var(--red); color: white;">
                    <tr>
                        <th style="border-color: var(--red);">Description</th>
                        <th style="width: 150px; text-align:right; border-color: var(--red);">Amount (KSh)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>PAYE / Tax</td>
                        <td class="amount-col">{{ number_format($item->tax_amount, 2) }}</td>
                    </tr>
                    <!-- NSSF/NHIF Placeholders -->
                </tbody>
            </table>

            <!-- TOTALS -->
            <div class="totalsWrap">
                <div class="totalsBox">
                    <div class="totalsRow"><span>Total Gross:</span><span>KSh {{ number_format($item->gross_pay, 2) }}</span></div>
                    <div class="totalsRow"><span>Total Deductions:</span><span style="color:red;">-KSh {{ number_format($item->tax_amount, 2) }}</span></div>
                    <div class="totalsRow totalsRow--grand"><span>NET PAY:</span><span style="color:var(--blue); font-size:18px;">KSh {{ number_format($item->net_pay, 2) }}</span></div>
                </div>
            </div>

            <!-- SIGNATURES -->
            <div class="signGrid">
                <div>
                    <div class="signLine"></div>
                    <div class="signLabel">Authorized Signature (Employer)</div>
                </div>
                <div>
                    <div class="signLine"></div>
                    <div class="signLabel">Employee Signature</div>
                </div>
            </div>

            <div style="text-align:center; color:#999; font-size:10px; margin-top:40px;">
                This is a system generated payslip.
            </div>

        </div>
    </div>
    @endforeach
</div>

<script>
    function printSingle(id) {
        // Reset classes
        document.querySelectorAll('.payslip-wrapper').forEach(el => el.classList.remove('print-target'));
        document.body.classList.remove('printing-single');

        // Add class to target
        const target = document.getElementById(id);
        if (target) {
            target.classList.add('print-target');
            document.body.classList.add('printing-single');
            window.print();
            
            // Clean up after print dialog closes (timeout is simplest way to guess)
            setTimeout(() => {
                document.body.classList.remove('printing-single');
                target.classList.remove('print-target');
            }, 1000);
        }
    }
    
    // Also handle regular Ctrl+P (Print All)
    window.onbeforeprint = function() {
        if (!document.body.classList.contains('printing-single')) {
            // Normal print - ensure no residual single classes
            document.querySelectorAll('.payslip-wrapper').forEach(el => el.classList.remove('print-target'));
        }
    };
</script>
@endsection
