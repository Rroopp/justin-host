@extends('layouts.app')

@section('content')
<div x-data="packageForm()" x-init="init()">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Edit Package: {{ $package->name }}</h1>
        </div>
        <a href="{{ route('packages.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300">
            Cancel
        </a>
    </div>

    @if($errors->any())
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
            <p class="font-bold">Please correct the errors below:</p>
            <ul class="list-disc pl-5 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('packages.update', $package) }}" method="POST" class="bg-white shadow rounded-lg p-6">
        @csrf
        @method('PUT')
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Package Name *</label>
                <input type="text" name="name" value="{{ old('name', $package->name) }}" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Package Code</label>
                <input type="text" name="code" value="{{ old('code', $package->code) }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $package->description) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Base Price (KSh) *</label>
                <input type="number" name="base_price" value="{{ old('base_price', $package->base_price) }}" step="0.01" min="0" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
        </div>

        <hr class="my-6">

        <h3 class="text-lg font-medium text-gray-900 mb-4">Package Items (Template)</h3>
        
        <div class="space-y-4">
            <template x-for="(item, index) in items" :key="index">
                <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-md">
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-gray-500 uppercase">Item</label>
                        <select :name="`items[${index}][inventory_id]`" x-model="item.inventory_id" required class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                            <option value="">Select Product...</option>
                            <template x-for="p in products" :key="p.id">
                                <option :value="p.id" x-text="`${p.product_name} (${p.code})`" :selected="p.id == item.inventory_id"></option>
                            </template>
                        </select>
                    </div>
                    <div class="w-32">
                        <label class="block text-xs font-medium text-gray-500 uppercase">Quantity</label>
                        <input type="number" :name="`items[${index}][quantity]`" x-model="item.quantity" step="0.01" min="0.01" required class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                    </div>
                    <div class="pt-5">
                        <button type="button" @click="removeItem(index)" class="text-red-600 hover:text-red-900">
                            Remove
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <button type="button" @click="addItem()" class="mt-4 flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <svg class="h-5 w-5 mr-2 -ml-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add Another Item
        </button>

        <hr class="my-6">

        <h3 class="text-lg font-medium text-gray-900 mb-4">Customer Pricing (Exceptions)</h3>
        <p class="text-sm text-gray-600 mb-4">Set specific prices for certain customers. If not set, the Base Price is used.</p>
        
        <div class="space-y-4">
            <template x-for="(cp, index) in customerPrices" :key="'cp-'+index">
                <div class="flex items-center gap-4 p-4 bg-blue-50 rounded-md border border-blue-100">
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-gray-500 uppercase">Customer</label>
                        <select :name="`customer_prices[${index}][customer_id]`" x-model="cp.customer_id" required class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                            <option value="">Select Customer...</option>
                            <template x-for="c in customers" :key="c.id">
                                <option :value="c.id" x-text="c.name" :selected="c.id == cp.customer_id"></option>
                            </template>
                        </select>
                    </div>
                    <div class="w-32">
                        <label class="block text-xs font-medium text-gray-500 uppercase">Price</label>
                        <input type="number" :name="`customer_prices[${index}][price]`" x-model="cp.price" step="0.01" min="0" required class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                    </div>
                    <div class="pt-5">
                        <button type="button" @click="removeCustomerPrice(index)" class="text-red-600 hover:text-red-900">
                            Remove
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <button type="button" @click="addCustomerPrice()" class="mt-4 flex items-center px-4 py-2 border border-blue-300 shadow-sm text-sm font-medium rounded-md text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <svg class="h-5 w-5 mr-2 -ml-1 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add Customer Price
        </button>

        <div class="mt-8 flex justify-end">
            <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700">
                Update Package
            </button>
        </div>
    </form>
</div>

<script>
function packageForm() {
    return {
        items: @json($package->items),
        products: @json($inventory),
        customers: @json($customers),
        customerPrices: @json($package->customerPricing),

        async init() {
             // Data loaded
             if (!this.customerPrices) this.customerPrices = [];
        },
        
        addItem() {
            this.items.push({ inventory_id: '', quantity: 1 });
        },
        
        removeItem(index) {
            this.items.splice(index, 1);
        },

        addCustomerPrice() {
            this.customerPrices.push({ customer_id: '', price: {{ $package->base_price }} });
        },

        removeCustomerPrice(index) {
            this.customerPrices.splice(index, 1);
        }
    }
}
</script>
@endsection
