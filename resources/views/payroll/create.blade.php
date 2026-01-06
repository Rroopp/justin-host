@extends('layouts.app')

@section('title', 'New Payroll Run')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="payrollForm()">
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-900">New Payroll Run</h1>
            <a href="{{ route('payroll.index') }}" class="text-indigo-600 hover:text-indigo-900">Back to List</a>
        </div>

        @if(session('error'))
            <div class="bg-red-50 border-l-4 border-red-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <form action="{{ route('payroll.store') }}" method="POST" class="bg-white shadow rounded-lg p-6 space-y-6">
            @csrf

            <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
                <div>
                    <label for="period_start" class="block text-sm font-medium text-gray-700">Period Start</label>
                    <input type="date" name="period_start" id="period_start" value="{{ $defaultStart }}" required
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label for="period_end" class="block text-sm font-medium text-gray-700">Period End</label>
                    <input type="date" name="period_end" id="period_end" value="{{ $defaultEnd }}" required
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
            </div>

            <div class="border-t pt-4">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Staff Details</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gross Pay</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tax/Deductions</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Net Pay</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Include</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($staff as $emp)
                                <tr x-data="{ 
                                    included: true, 
                                    gross: {{ $emp->salary ?? 0 }}, 
                                    tax: 0,
                                    get net() { return Math.max(0, this.gross - this.tax) }
                                }">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $emp->full_name }}
                                        <input type="hidden" name="items[{{ $loop->index }}][staff_id]" value="{{ $emp->id }}" x-bind:disabled="!included">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <input type="number" step="0.01" name="items[{{ $loop->index }}][gross_pay]" x-model.number="gross" class="w-32 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" x-bind:disabled="!included">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <input type="number" step="0.01" name="items[{{ $loop->index }}][tax_amount]" x-model.number="tax" class="w-32 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" x-bind:disabled="!included">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                        <span x-text="net.toFixed(2)"></span>
                                        <input type="hidden" name="items[{{ $loop->index }}][net_pay]" x-bind:value="net" x-bind:disabled="!included">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <input type="checkbox" x-model="included" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex justify-end pt-5">
                <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Create Run
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function payrollForm() {
        return {
            // Can add global helpers here if needed
        }
    }
</script>
@endsection
