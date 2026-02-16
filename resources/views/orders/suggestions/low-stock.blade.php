@extends('layouts.app')

@section('title', 'Low Stock Suggestions')

@section('content')
<div class="px-4 py-6 sm:px-0" x-data="{ 
    selectedItems: [],
    toggleSelection(item) {
        if (this.selectedItems.some(i => i.id === item.id)) {
            this.selectedItems = this.selectedItems.filter(i => i.id !== item.id);
        } else {
            this.selectedItems.push(item);
        }
    },
    createOrder() {
        if (this.selectedItems.length === 0) return;
        const items = this.selectedItems.map(i => ({
            product_id: i.id,
            product_name: i.product_name,
            quantity: Math.max(1, (i.max_stock || 0) - i.quantity_in_stock),
            unit_cost: i.price
        }));
        
        // Redirect to orders page with pre-filled data
        const params = new URLSearchParams();
        params.append('action', 'create');
        params.append('items', JSON.stringify(items));
        window.location.href = '{{ route('orders.index') }}?' + params.toString();
    }
}">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Low Stock Suggestions</h1>
            <p class="mt-1 text-sm text-gray-500">Items below minimum stock levels</p>
        </div>
        <div class="flex items-center space-x-4">
            <a href="{{ route('orders.suggestions.index') }}" class="text-indigo-600 hover:text-indigo-900">Back to Suggestions</a>
            
            @if(auth()->user()->hasRole(['admin', 'accountant']))
            <button 
                @click="createOrder()" 
                x-show="selectedItems.length > 0"
                class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition shadow-sm"
                style="display: none;"
                x-transition>
                Create Order (<span x-text="selectedItems.length"></span>)
            </button>
            @endif
        </div>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    @if(auth()->user()->hasRole(['admin', 'accountant']))
                    <th scope="col" class="px-6 py-3 text-left w-10">
                        <input type="checkbox" 
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                            @change="
                            if ($el.checked) {
                                selectedItems = {{ $lowStockItems->map(fn($i) => ['id' => $i->id, 'product_name' => $i->product_name, 'price' => $i->price, 'quantity_in_stock' => $i->quantity_in_stock, 'max_stock' => $i->max_stock ?? ($i->min_stock_level * 2)]) }};
                            } else {
                                selectedItems = [];
                            }
                        ">
                    </th>
                    @endif
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Stock</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reorder / Min Level</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Suggested Reorder</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($lowStockItems as $item)
                <tr>
                    @if(auth()->user()->hasRole(['admin', 'accountant']))
                    <td class="px-6 py-4 whitespace-nowrap">
                         <input type="checkbox" 
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                            :value="{{ $item->id }}" 
                            :checked="selectedItems.some(i => i.id === {{ $item->id }})" 
                            @change="toggleSelection({ id: {{ $item->id }}, product_name: '{{ addslashes($item->product_name) }}', price: {{ $item->price }}, quantity_in_stock: {{ $item->quantity_in_stock }}, max_stock: {{ $item->max_stock ?? ($item->min_stock_level * 2) }} })">
                    </td>
                    @endif
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $item->product_name }}</div>
                        <div class="text-sm text-gray-500">{{ $item->code }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-500">{{ $item->category }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-bold text-red-600">{{ $item->quantity_in_stock }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">Min: {{ $item->min_stock_level }}</div>
                        @if($item->reorder_threshold)
                        <div class="text-xs text-gray-500">Threshold: {{ $item->reorder_threshold }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @php
                            $suggestion = max(0, ($item->max_stock ?? ($item->min_stock_level * 2)) - $item->quantity_in_stock);
                        @endphp
                        <div class="text-sm text-gray-900">{{ $suggestion }}</div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">No low stock items found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
