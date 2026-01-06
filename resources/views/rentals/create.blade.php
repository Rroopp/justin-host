@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900">New Rental</h1>
    <p class="mt-2 text-sm text-gray-600">Checkout surgical sets or equipment.</p>
</div>

<div class="bg-white shadow rounded-lg p-6" x-data="rentalForm()">
    @if ($errors->any())
        <div class="mb-4 bg-red-50 p-4 rounded-md">
            <ul class="list-disc list-inside text-sm text-red-600">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('rentals.store') }}" method="POST">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Customer Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Customer / Hospital</label>
                <select name="customer_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Select Customer</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->facility ?? 'N/A' }})</option>
                    @endforeach
                </select>
            </div>

            <!-- Date -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Rented At</label>
                <input type="datetime-local" name="rented_at" value="{{ now()->format('Y-m-d\TH:i') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <!-- Expected Return -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Expected Return</label>
                <input type="date" name="expected_return_at" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
        </div>

        <!-- Items Selection -->
        <div class="mb-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Items to Rent</h3>
            
            <div class="bg-gray-50 p-4 rounded-md mb-4">
                <div class="flex gap-4 items-end">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700">Item</label>
                        <select x-model="selectedItem" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">Select Item</option>
                            @foreach($rentables as $item)
                                <option value="{{ $item->id }}" data-name="{{ $item->product_name }}" data-stock="{{ $item->quantity_in_stock }}">
                                    {{ $item->product_name }} (Stock: {{ $item->quantity_in_stock }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-24">
                        <label class="block text-sm font-medium text-gray-700">Qty</label>
                        <input type="number" x-model="qty" min="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <button type="button" @click="addItem()" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                        Add
                    </button>
                </div>
            </div>

            <table class="min-w-full divide-y divide-gray-200 border">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="(item, index) in items" :key="index">
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-900" x-text="item.name"></td>
                            <td class="px-4 py-2 text-sm text-gray-900">
                                <input type="hidden" :name="'items[' + index + '][inventory_id]'" :value="item.id">
                                <input type="hidden" :name="'items[' + index + '][quantity]'" :value="item.qty">
                                <span x-text="item.qty"></span>
                            </td>
                            <td class="px-4 py-2 text-right">
                                <button type="button" @click="removeItem(index)" class="text-red-600 hover:text-red-900">Remove</button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="items.length === 0">
                        <td colspan="3" class="px-4 py-4 text-center text-gray-500 text-sm">No items added yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700">Notes</label>
            <textarea name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('rentals.index') }}" class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Create Rental</button>
        </div>
    </form>
</div>

<script>
    function rentalForm() {
        return {
            selectedItem: '',
            qty: 1,
            items: [],
            
            addItem() {
                if (!this.selectedItem) return;
                
                const select = document.querySelector(`select[x-model="selectedItem"]`);
                const option = select.options[select.selectedIndex];
                const name = option.getAttribute('data-name');
                const stock = parseInt(option.getAttribute('data-stock'));
                
                if (parseInt(this.qty) > stock) {
                    alert('Quantity exceeds available stock!');
                    // Note: In real app, check against existing items in cart too
                    return;
                }

                // Check if already exists
                const existing = this.items.find(i => i.id === this.selectedItem);
                if (existing) {
                    if (existing.qty + parseInt(this.qty) > stock) {
                        alert('Total quantity exceeds available stock!');
                        return;
                    }
                    existing.qty += parseInt(this.qty);
                } else {
                    this.items.push({
                        id: this.selectedItem,
                        name: name,
                        qty: parseInt(this.qty)
                    });
                }
                
                this.selectedItem = '';
                this.qty = 1;
            },
            
            removeItem(index) {
                this.items.splice(index, 1);
            }
        }
    }
</script>
@endsection
