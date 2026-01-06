@extends('layouts.app')

@section('content')
<div x-data="packageForm()" x-init="init()">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Create Package</h1>
            <p class="mt-2 text-sm text-gray-600">Define a new procedure bundle.</p>
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

    <form action="{{ route('packages.store') }}" method="POST" class="bg-white shadow rounded-lg p-6">
        @csrf
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Package Name *</label>
                <input type="text" name="name" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Package Code (Optional)</label>
                <input type="text" name="code" placeholder="Auto-generated if empty" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Base Price (KSh) *</label>
                <input type="number" name="base_price" step="0.01" min="0" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <p class="text-xs text-gray-500 mt-1">Default price. Can be overridden per customer.</p>
            </div>
        </div>

        <hr class="my-6">

        <h3 class="text-lg font-medium text-gray-900 mb-4">Package Items (Template)</h3>
        <p class="text-sm text-gray-600 mb-4">Define the default items consumed in this procedure. This lists can be edited during the sale.</p>

        <div class="space-y-4">
            <template x-for="(item, index) in items" :key="index">
                <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-md">
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-gray-500 uppercase">Item</label>
                        <!-- Simple Search/Select for Inventory -->
                        <select :name="`items[${index}][inventory_id]`" x-model="item.inventory_id" required class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                            <option value="">Select Product...</option>
                            <template x-for="p in products" :key="p.id">
                                <option :value="p.id" x-text="`${p.product_name} (${p.code})`"></option>
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

        <div class="mt-8 flex justify-end">
            <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700">
                Save Package
            </button>
        </div>
    </form>
</div>

<script>
function packageForm() {
    return {
        items: [{ inventory_id: '', quantity: 1 }],
        products: @json($inventory),

        async init() {
            // Inventory loaded
        },
        
        addItem() {
            this.items.push({ inventory_id: '', quantity: 1 });
        },
        
        removeItem(index) {
            this.items.splice(index, 1);
        }
    }
}
</script>
@endsection
