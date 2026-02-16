@extends('layouts.app')

@section('title', $staff->full_name . ' - Profile')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                {{ $staff->full_name }}
            </h2>
            <p class="mt-1 text-sm text-gray-500">{{ $staff->designation ?? 'Staff Member' }} | {{ $staff->username }}</p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <a href="{{ route('staff.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                Back to List
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-green-50 border-l-4 border-green-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Staff Details Card -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Personal Details</h3>
            </div>
            <div class="border-t border-gray-200 px-4 py-5 sm:p-0">
                <dl class="sm:divide-y sm:divide-gray-200">
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Full Name</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $staff->full_name }}</dd>
                    </div>
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Email</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $staff->email ?? '-' }}</dd>
                    </div>
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Phone</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $staff->phone ?? '-' }}</dd>
                    </div>
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Base Salary</dt>
                        <dd class="mt-1 text-sm font-bold text-gray-900 sm:mt-0 sm:col-span-2">KSh {{ number_format($staff->salary, 2) }}</dd>
                    </div>
                     <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Bank Details</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $staff->bank_name ?? 'N/A' }} - {{ $staff->account_number ?? 'N/A' }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Recurring Deductions -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- List -->
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Recurring Deductions</h3>
                    <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">Autos-added to payroll</span>
                </div>
                <div class="border-t border-gray-200">
                     @if($staff->recurringDeductions->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($staff->recurringDeductions as $deduction)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {{ $deduction->deductionType->name }}
                                                @if($deduction->notes)
                                                    <p class="text-xs text-gray-500">{{ Str::limit($deduction->notes, 30) }}</p>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                KSh {{ number_format($deduction->amount, 2) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                @if($deduction->balance !== null)
                                                    KSh {{ number_format($deduction->balance, 2) }}
                                                @else
                                                    <span class="text-gray-400">-</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <form action="{{ route('staff.deductions.destroy', [$staff->id, $deduction->id]) }}" method="POST" onsubmit="return confirm('Remove this deduction?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900">Remove</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="p-6 text-center text-gray-500 text-sm">
                            No active recurring deductions found.
                        </div>
                    @endif
                </div>
            </div>

            <!-- Add Form -->
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Add New Deduction</h3>
                    <form action="{{ route('staff.deductions.store', $staff->id) }}" method="POST">
                        @csrf
                        <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                            
                            <div class="sm:col-span-3">
                                <label for="deduction_type_id" class="block text-sm font-medium text-gray-700">Type</label>
                                <select id="deduction_type_id" name="deduction_type_id" required 
                                    class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    onchange="document.getElementById('new-deduction-fields').style.display = this.value === 'new' ? 'block' : 'none'">
                                    <option value="">Select Type...</option>
                                    @foreach($deductionTypes as $type)
                                        <option value="{{ $type->id }}">{{ $type->name }} ({{ $type->code }})</option>
                                    @endforeach
                                    <option value="new">+ Create New Type</option>
                                </select>
                            </div>

                            <!-- Dynamic fields for new type -->
                            <div id="new-deduction-fields" class="sm:col-span-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6 p-4 bg-gray-50 rounded-md border border-gray-200" style="display: none;">
                                <div class="sm:col-span-3">
                                    <label for="new_deduction_name" class="block text-sm font-medium text-gray-700">New Deduction Name</label>
                                    <input type="text" name="new_deduction_name" id="new_deduction_name" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                                <div class="sm:col-span-3">
                                    <label for="new_liability_account_id" class="block text-sm font-medium text-gray-700">Liability Account (GL)</label>
                                    <select name="new_liability_account_id" id="new_liability_account_id" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <option value="">Select Account...</option>
                                        @foreach($liabilityAccounts as $account)
                                            <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                                        @endforeach
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500">Account to credit when deduction is made.</p>
                                </div>
                            </div>

                            <div class="sm:col-span-3">
                                <label for="amount" class="block text-sm font-medium text-gray-700">Amount per Period</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">KSh</span>
                                    </div>
                                    <input type="number" name="amount" id="amount" step="0.01" required class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-12 sm:text-sm border-gray-300 rounded-md" placeholder="0.00">
                                </div>
                            </div>

                            <div class="sm:col-span-3">
                                <label for="balance" class="block text-sm font-medium text-gray-700">Total Balance (Optional)</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">KSh</span>
                                    </div>
                                    <input type="number" name="balance" id="balance" step="0.01" class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-12 sm:text-sm border-gray-300 rounded-md" placeholder="For loans only">
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Leave empty for indefinite deductions.</p>
                            </div>

                             <div class="sm:col-span-3">
                                <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                                <input type="text" name="notes" id="notes" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                            </div>

                        </div>
                        <div class="mt-5">
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Add Deduction
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
