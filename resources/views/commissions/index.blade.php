@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Staff Commissions</h1>
        
        <!-- Summary Cards -->
        <div class="flex gap-4">
            <div class="bg-yellow-100 p-3 rounded-lg border border-yellow-200">
                <div class="text-xs text-yellow-800 uppercase font-bold">Pending Payout</div>
                <div class="text-xl font-bold text-yellow-900">KSh {{ number_format($totals['pending'], 2) }}</div>
            </div>
            <div class="bg-green-100 p-3 rounded-lg border border-green-200">
                <div class="text-xs text-green-800 uppercase font-bold">Total Paid</div>
                <div class="text-xl font-bold text-green-900">KSh {{ number_format($totals['paid'], 2) }}</div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-lg shadow mb-6">
        <form method="GET" action="{{ route('commissions.index') }}" class="flex gap-4 items-end">
            @if(!auth()->user()->hasRole('staff'))
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Staff Member</label>
                <select name="staff_id" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    <option value="">All Staff</option>
                    @foreach(\App\Models\Staff::orderBy('full_name')->get() as $staff)
                        <option value="{{ $staff->id }}" {{ request('staff_id') == $staff->id ? 'selected' : '' }}>
                            {{ $staff->full_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                </select>
            </div>
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 text-sm font-medium">
                Filter
            </button>
            @if(request()->anyFilled(['staff_id', 'status']))
                <a href="{{ route('commissions.index') }}" class="text-gray-600 hover:text-gray-900 text-sm underline pb-2">Clear</a>
            @endif
        </form>
    </div>

    <!-- Commissions Table -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Staff</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type / Description</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sale Ref</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($commissions as $commission)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $commission->created_at->format('d M Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $commission->staff->full_name }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium 
                                {{ $commission->type === 'sale' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $commission->type === 'service' ? 'bg-purple-100 text-purple-800' : '' }}
                                {{ $commission->type === 'locum' ? 'bg-indigo-100 text-indigo-800' : '' }}
                                {{ $commission->type === 'bonus' ? 'bg-green-100 text-green-800' : '' }}">
                                {{ ucfirst($commission->type) }}
                            </span>
                            <div class="text-xs mt-1">{{Str::limit($commission->description, 30)}}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            @if($commission->pos_sale_id)
                                <a href="{{ route('receipts.print', $commission->pos_sale_id) }}" target="_blank" class="text-indigo-600 hover:text-indigo-900 hover:underline">
                                    #{{ $commission->sale->invoice_number ?? $commission->pos_sale_id }}
                                </a>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-gray-900">
                            {{ number_format($commission->amount, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @if($commission->status === 'paid')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Paid
                                </span>
                                <div class="text-[10px] text-gray-400 mt-0.5">{{ $commission->paid_at->format('d M Y') }}</div>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    Pending
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                            @if($commission->status === 'pending')
                                @if(!auth()->user()->hasRole('staff'))
                                <form action="{{ route('commissions.update', $commission) }}" method="POST" class="inline-block" onsubmit="return confirm('Mark this commission as PAID?');">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="paid">
                                    <button type="submit" class="text-indigo-600 hover:text-indigo-900 font-bold border border-indigo-200 bg-indigo-50 px-3 py-1 rounded hover:bg-indigo-100 transition-colors">
                                        Pay
                                    </button>
                                </form>
                                @else
                                <span class="text-yellow-600 italic text-xs">Pending Admin Action</span>
                                @endif
                            @else
                                <span class="text-gray-400 cursor-not-allowed">Paid</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-10 text-center text-gray-500">
                            No commissions found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="mt-4">
        {{ $commissions->links() }}
    </div>

    @if(!auth()->user()->hasRole('staff'))
    <!-- Add Manual Commission / Locum Modal -->
    <div x-data="{ open: false }" class="mt-8">
        <button @click="open = true" class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-700 text-sm font-medium">
            + Add Manual Commission / Locum
        </button>

        <div x-show="open" class="fixed z-50 inset-0 overflow-y-auto" style="display: none;" x-cloak>
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="open = false"></div>
                <div class="relative inline-block align-middle bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form action="{{ route('commissions.store') }}" method="POST">
                        @csrf
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Add Manual Commission</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Staff Member</label>
                                    <select name="staff_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        @foreach(\App\Models\Staff::where('status', 'active')->orderBy('full_name')->get() as $staff)
                                            <option value="{{ $staff->id }}">{{ $staff->full_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Type</label>
                                    <select name="type" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="locum">Locum (Night Shift)</option>
                                        <option value="bonus">Bonus</option>
                                        <option value="service">Service</option>
                                        <option value="sale">Sale Adjustment</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Amount (KSh)</label>
                                    <input type="number" name="amount" required min="0" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Description</label>
                                    <textarea name="description" required rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="e.g. Night Shift 25th Dec"></textarea>
                                </div>
                                <div class="border-t pt-2 mt-2">
                                     <label class="block text-sm font-medium text-gray-700">Link to Sale (Optional)</label>
                                     <input type="text" name="invoice_number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Invoice Number (e.g. INV-2023-001)">
                                     <p class="text-xs text-gray-500 mt-1">If this commission is for a specific past sale.</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:w-auto sm:text-sm">
                                Save
                            </button>
                            <button type="button" @click="open = false" class="w-full inline-flex justify-center rounded-md border border-gray-300 px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
