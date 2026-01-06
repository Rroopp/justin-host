@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900">Return Items #RNT-{{ $rental->id }}</h1>
</div>

<div class="bg-white shadow rounded-lg p-6">
    <form action="{{ route('rentals.process-return', $rental->id) }}" method="POST">
        @csrf
        
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700">Returned Date</label>
            <input type="datetime-local" name="returned_at" value="{{ now()->format('Y-m-d\TH:i') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
        </div>

        <h3 class="text-lg font-medium text-gray-900 mb-4">Items Condition Check</h3>
        <table class="min-w-full divide-y divide-gray-200 mb-6 border">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Qty</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Condition In</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($rental->rentalItems as $index => $item)
                    <tr>
                        <td class="px-4 py-2 text-sm font-medium text-gray-900">
                            {{ $item->inventory->product_name ?? 'Unknown Item' }}
                            <input type="hidden" name="items[{{ $index }}][inventory_id]" value="{{ $item->inventory_id }}">
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-500 text-center">
                            {{ $item->quantity }}
                        </td>
                        <td class="px-4 py-2 text-sm">
                            <select name="items[{{ $index }}][condition_in]" class="block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
                                <option value="Good">Good</option>
                                <option value="Damaged">Damaged</option>
                                <option value="Lost">Lost</option>
                                <option value="Needs Cleaning">Needs Cleaning</option>
                            </select>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="bg-yellow-50 p-4 rounded-md mb-6">
            <p class="text-sm text-yellow-700">
                <strong>Note:</strong> Returning these items will add them back to your inventory stock count immediately.
                If items are damaged or lost, please adjust inventory manually afterwards.
            </p>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('rentals.show', $rental->id) }}" class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Confirm Return</button>
        </div>
    </form>
</div>
@endsection
