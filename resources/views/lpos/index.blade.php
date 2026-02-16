@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">LPO Management</h1>
        <a href="{{ route('lpos.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
            + New LPO
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-lg shadow mb-6">
        <form action="{{ route('lpos.index') }}" method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="rounded border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Customer</label>
                <select name="customer_id" class="rounded border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    <option value="">All Customers</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                            {{ $customer->name }} {{ $customer->facility ? "($customer->facility)" : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-semibold py-2 px-4 rounded border border-gray-300">
                Filter
            </button>
            <a href="{{ route('lpos.index') }}" class="text-indigo-600 hover:text-indigo-900 text-sm ml-2">Clear</a>
        </form>
    </div>

    <!-- LPO Table -->
    <div class="bg-white rounded-lg shadow">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">LPO Number</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer / Facility</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Validity</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($lpos as $lpo)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                        {{ $lpo->lpo_number }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $lpo->customer->name }}
                        @if($lpo->customer->facility)
                            <span class="block text-xs text-gray-400">{{ $lpo->customer->facility }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ number_format($lpo->amount, 2) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium {{ $lpo->remaining_balance < ($lpo->amount * 0.1) ? 'text-red-600' : 'text-green-600' }}">
                        {{ number_format($lpo->remaining_balance, 2) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            {{ $lpo->status === 'active' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $lpo->status === 'completed' ? 'bg-blue-100 text-blue-800' : '' }}
                            {{ $lpo->status === 'expired' ? 'bg-red-100 text-red-800' : '' }}
                        ">
                            {{ ucfirst($lpo->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        @if($lpo->valid_until)
                            <span class="{{ $lpo->valid_until < now() ? 'text-red-600' : '' }}">
                                {{ $lpo->valid_until->format('M d, Y') }}
                            </span>
                        @else
                            -
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="{{ route('lpos.show', $lpo) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">View</a>
                        <!-- Add edit/delete if needed -->
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-10 text-center text-gray-500">
                        No LPOs found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $lpos->links() }}
    </div>
</div>
@endsection
