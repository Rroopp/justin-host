@extends('layouts.app')

@section('title', $account->name . ' - Statement')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    
    <!-- Header -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between">
        <div>
            <div class="flex items-center">
                <a href="{{ route('banking.index') }}" class="mr-3 text-gray-500 hover:text-gray-700">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <h1 class="text-2xl font-bold text-gray-900">{{ $account->name }} Statement</h1>
            </div>
            <p class="text-sm text-gray-500 mt-1 ml-8">Account Code: {{ $account->code }}</p>
        </div>
        <div class="mt-4 md:mt-0">
             <span class="inline-flex items-center px-4 py-2 rounded-md border border-gray-300 bg-white text-lg font-bold text-gray-900 shadow-sm">
                Balance: {{ number_format($account->balance, 2) }}
            </span>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg p-4 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700">Date From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Date To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <button type="submit" class="w-full px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                    Filter
                </button>
            </div>
             <div>
                <a href="{{ route('banking.show', $account->id) }}" class="w-full block text-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Statement Table -->
    <div class="bg-white shadow overflow-hidden rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ref #</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Money In (Dr)</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Money Out (Cr)</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($transactions as $line)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ \Carbon\Carbon::parse($line->journalEntry->entry_date)->format('Y-m-d') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $line->journalEntry->entry_number }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        {{ $line->journalEntry->description }}
                        @if($line->description && $line->description != $line->journalEntry->description)
                            <span class="text-gray-500 block text-xs">{{ $line->description }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium {{ $line->debit_amount > 0 ? 'text-green-600' : 'text-gray-400' }}">
                        {{ $line->debit_amount > 0 ? number_format($line->debit_amount, 2) : '-' }}
                    </td>
                     <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium {{ $line->credit_amount > 0 ? 'text-red-600' : 'text-gray-400' }}">
                        {{ $line->credit_amount > 0 ? number_format($line->credit_amount, 2) : '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center text-xs">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $line->debit_amount > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $line->debit_amount > 0 ? 'DEPOSIT' : 'WITHDRAWAL/PAYMENT' }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                        No transactions found for this period.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $transactions->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
