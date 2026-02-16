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

            <!-- Date Selection -->
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

            <!-- Staff Selection -->
            <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Select Employees</h3>
                <div class="bg-gray-50 p-4 rounded-md border border-gray-200">
                    <div class="flex items-center mb-4">
                        <input type="checkbox" id="select_all" 
                            @change="toggleAll($el.checked)" 
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label for="select_all" class="ml-2 block text-sm font-medium text-gray-700">
                            Select All Active Staff
                        </label>
                    </div>

                    <div class="overflow-x-auto max-h-96">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-100 sticky top-0">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10">
                                        Select
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Employee
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Role
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Base Salary
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($staff as $employee)
                                <tr class="{{ ($employee->salary <= 0) ? 'bg-yellow-50' : '' }}">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="checkbox" name="staff_ids[]" value="{{ $employee->id }}"
                                            class="staff-checkbox h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                            {{ ($employee->salary > 0) ? 'checked' : '' }}>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $employee->full_name }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500">{{ $employee->role ?? 'Staff' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        @if($employee->salary > 0)
                                            <div class="text-sm text-gray-900">{{ number_format($employee->salary, 2) }}</div>
                                        @else
                                            <div class="text-sm text-yellow-600 font-medium">
                                                <i class="fas fa-exclamation-triangle mr-1"></i> Not Set
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                        No active staff found.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="alert alert-info bg-blue-50 p-4 rounded text-sm text-blue-700">
                <i class="fas fa-info-circle mr-2"></i> 
                Payroll will be calculated only for the selected employees.
                You can review and approve the run on the next page.
            </div>

            <div class="flex justify-end pt-5">
                <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Generate Payroll
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function payrollForm() {
        return {
            toggleAll(checked) {
                document.querySelectorAll('.staff-checkbox').forEach(el => {
                    el.checked = checked;
                });
            }
        }
    }
</script>
@endsection
