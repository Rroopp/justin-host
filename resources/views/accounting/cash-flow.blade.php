@extends('layouts.app')

@section('content')
<div class="px-4 py-6 sm:px-0">
    <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="flex-1 min-w-0">
            <h1 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                Statement of Cash Flows
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Period: {{ $dateFrom }} to {{ $dateTo }}
            </p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <form method="GET" class="flex gap-2 items-center">
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <span class="text-gray-500">to</span>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Filter
                </button>
            </form>
        </div>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg max-w-4xl mx-auto">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Cash Flow Statement (Indirect Method*)
            </h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">
                *Simplified estimation based on account type changes.
            </p>
        </div>
        <div class="px-4 py-5 sm:p-6 space-y-6">
            
            <!-- Operating Activities -->
            <div>
                <h4 class="text-md font-bold text-gray-900 mb-2 uppercase tracking-wide">Cash Flows from Operating Activities</h4>
                <div class="pl-4 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-700">Net Income</span>
                        <span class="font-medium text-gray-900">{{ number_format($netIncome, 2) }}</span>
                    </div>
                    
                    <div class="text-sm font-medium text-gray-500 mt-2">Adjustments for changes in operating assets and liabilities:</div>
                    @foreach($operatingAdjustments as $name => $amount)
                    <div class="flex justify-between text-sm pl-4">
                        <span class="text-gray-600">{{ $amount < 0 ? 'Increase' : 'Decrease' }} in {{ $name }}</span>
                        <span class="text-gray-900">{{ number_format($amount, 2) }}</span>
                    </div>
                    @endforeach

                    <div class="flex justify-between text-sm font-bold border-t border-gray-200 pt-2 mt-2">
                        <span class="text-indigo-900">Net Cash from Operating Activities</span>
                        <span class="text-indigo-900">{{ number_format($operatingCashFlow, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Investing Activities -->
            <div>
                <h4 class="text-md font-bold text-gray-900 mb-2 uppercase tracking-wide">Cash Flows from Investing Activities</h4>
                <div class="pl-4 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-700">Purchase/Sale of Fixed Assets (Net)</span>
                        <span class="text-gray-900">{{ number_format($investingCashFlow, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm font-bold border-t border-gray-200 pt-2 mt-2">
                        <span class="text-indigo-900">Net Cash used in Investing Activities</span>
                        <span class="text-indigo-900">{{ number_format($investingCashFlow, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Financing Activities -->
            <div>
                <h4 class="text-md font-bold text-gray-900 mb-2 uppercase tracking-wide">Cash Flows from Financing Activities</h4>
                <div class="pl-4 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-700">Proceeds from Equity/Loans (Net)</span>
                        <span class="text-gray-900">{{ number_format($financingCashFlow, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm font-bold border-t border-gray-200 pt-2 mt-2">
                        <span class="text-indigo-900">Net Cash from Financing Activities</span>
                        <span class="text-indigo-900">{{ number_format($financingCashFlow, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Summary -->
            <div class="bg-gray-50 rounded-md p-4 mt-6">
                <div class="flex justify-between text-lg font-bold">
                    <span class="text-gray-900">Net Increase (Decrease) in Cash and Cash Equivalents</span>
                    <span class="{{ $netCashChange >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ number_format($netCashChange, 2) }}</span>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
