@extends('layouts.app')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row justify-between items-center gap-4">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Rentals</h1>
        <div class="mt-2 flex gap-2">
            <a href="{{ route('rentals.index') }}" class="text-sm text-gray-600 hover:text-gray-900 {{ !request('status') ? 'font-bold underline' : '' }}">All</a>
            <span class="text-gray-300">|</span>
            <a href="{{ route('rentals.index', ['status' => 'active']) }}" class="text-sm text-indigo-600 hover:text-indigo-900 {{ request('status') == 'active' ? 'font-bold underline' : '' }}">Active</a>
            <span class="text-gray-300">|</span>
            <a href="{{ route('rentals.index', ['status' => 'due_today']) }}" class="text-sm text-yellow-600 hover:text-yellow-900 {{ request('status') == 'due_today' ? 'font-bold underline' : '' }}">Due Today</a>
            <span class="text-gray-300">|</span>
            <a href="{{ route('rentals.index', ['status' => 'overdue']) }}" class="text-sm text-red-600 hover:text-red-900 {{ request('status') == 'overdue' ? 'font-bold underline' : '' }}">Overdue</a>
            <span class="text-gray-300">|</span>
            <a href="{{ route('rentals.index', ['status' => 'returned']) }}" class="text-sm text-green-600 hover:text-green-900 {{ request('status') == 'returned' ? 'font-bold underline' : '' }}">Returned</a>
        </div>
    </div>
    <div class="flex gap-2">
         <a href="{{ route('rentals.index', ['export' => 'true'] + request()->all()) }}" class="bg-indigo-100 text-indigo-700 px-4 py-2 rounded-md hover:bg-indigo-200">
            Export CSV
        </a>
        <a href="{{ route('rentals.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
            New Rental
        </a>
    </div>
</div>

<div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rented At</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Exp. Return</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($rentals as $rental)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        #RNT-{{ $rental->id }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $rental->customer->name ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $rental->rented_at->format('M d, Y H:i') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $rental->expected_return_at ? $rental->expected_return_at->format('M d, Y') : '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            {{ $rental->status === 'active' ? 'bg-blue-100 text-blue-800' : '' }}
                            {{ $rental->status === 'returned' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $rental->status === 'overdue' ? 'bg-red-100 text-red-800' : '' }}">
                            {{ ucfirst($rental->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="{{ route('rentals.show', $rental) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                        No rentals found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t border-gray-200">
        {{ $rentals->links() }}
    </div>
</div>
@endsection
