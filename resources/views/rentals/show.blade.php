@extends('layouts.app')

@section('content')
<div class="mb-6 flex justify-between items-start">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Rental #RNT-{{ $rental->id }}</h1>
        <p class="mt-1 text-sm text-gray-600">
            Status: 
            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                {{ $rental->status === 'active' ? 'bg-blue-100 text-blue-800' : '' }}
                {{ $rental->status === 'returned' ? 'bg-green-100 text-green-800' : '' }}
                {{ $rental->status === 'overdue' ? 'bg-red-100 text-red-800' : '' }}">
                {{ ucfirst($rental->status) }}
            </span>
        </p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('rentals.index') }}" class="btn btn-outline">Back</a>
        @if($rental->status === 'active' || $rental->status === 'overdue')
            <a href="{{ route('rentals.return-form', $rental->id) }}" class="btn btn-primary">
                Return Items
            </a>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Info -->
    <div class="lg:col-span-1 space-y-6">
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Customer Details</h3>
            <div class="space-y-3 text-sm">
                <div>
                    <span class="block text-gray-500">Name</span>
                    <span class="font-medium">{{ $rental->customer->name }}</span>
                </div>
                <div>
                    <span class="block text-gray-500">Phone</span>
                    <span>{{ $rental->customer->phone ?? '-' }}</span>
                </div>
                <div>
                    <span class="block text-gray-500">Facility</span>
                    <span>{{ $rental->customer->facility ?? '-' }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Rental Info</h3>
            <div class="space-y-3 text-sm">
                <div>
                    <span class="block text-gray-500">Rented At</span>
                    <span class="font-medium">{{ $rental->rented_at->format('M d, Y H:i') }}</span>
                </div>
                <div>
                    <span class="block text-gray-500">Expected Return</span>
                    <span class="font-medium {{ $rental->expected_return_at && $rental->expected_return_at->isPast() && $rental->status == 'active' ? 'text-red-600' : '' }}">
                        {{ $rental->expected_return_at ? $rental->expected_return_at->format('M d, Y') : '-' }}
                    </span>
                </div>
                @if($rental->returned_at)
                <div>
                    <span class="block text-gray-500">Returned At</span>
                    <span class="font-medium">{{ $rental->returned_at->format('M d, Y H:i') }}</span>
                </div>
                @endif
                <div>
                    <span class="block text-gray-500">Notes</span>
                    <p class="text-gray-700 mt-1 whitespace-pre-line">{{ $rental->notes ?? 'No notes' }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Items -->
    <div class="lg:col-span-2">
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Rented Items</h3>
            </div>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Qty</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Condition Out</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Condition In</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                <tbody class="divide-y divide-gray-200">
                    @foreach($rental->rentalItems as $item)
                        <tr>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                {{ $item->inventory->product_name ?? 'Unknown Item' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 text-center">
                                {{ $item->quantity }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ $item->condition_out ?? 'Good' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ $item->condition_in ?? '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
