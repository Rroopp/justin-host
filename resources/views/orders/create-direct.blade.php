@extends('layouts.app')

@section('title', 'Receive New Stock')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="directOrderForm()">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Receive New Stock (Direct Invoice)</h1>
        <p class="mt-1 text-sm text-gray-600">Record incoming stock and create a supplier invoice in one step.</p>
    </div>

    <form action="{{ route('orders.store-direct') }}" method="POST" @submit.prevent="submitForm">
        @csrf
        
        <!-- Header: Supplier & Invoice Info -->
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Invoice Details</h3>
            <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-3">
                
                <!-- Supplier Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Supplier *</label>
                    <select name="supplier_id" x-model="form.supplier_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select Supplier</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Invoice Number -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Supplier Invoice # *</label>
                    <input type="text" name="invoice_number" x-model="form.invoice_number" required placeholder="e.g. INV-2024-001"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <!-- Invoice Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Invoice Date *</label>
                    <input type="date" name="order_date" x-model="form.order_date" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>
            
            <div class="mt-4">
                 <label class="block text-sm font-medium text-gray-700">Notes / Remarks</label>
                 <textarea name="notes" x-model="form.notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
            </div>
        </div>

        <!-- Items Table -->
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Items to Receive</h3>
                <button type="button" @click="showProductSearch = true" class="bg-indigo-600 text-white px-3 py-1.5 rounded text-sm hover:bg-indigo-700">
                    <i class="fas fa-plus mr-1"></i> Add Item
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase w-1/3">Product</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase w-24">Current Stock</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase w-32">Qty to Add *</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase w-32">Unit Cost *</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase w-32">Total Line</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase w-16">Remove</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="(item, index) in form.items" :key="index">
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900" x-text="item.product_name"></div>
                                    <input type="hidden" :name="`items[${index}][product_id]`" :value="item.product_id">
                                    <input type="hidden" :name="`items[${index}][product_name]`" :value="item.product_name">
                                </td>
                                <td class="px-6 py-4 text-right text-sm text-gray-500" x-text="item.current_stock"></td>
                                <td class="px-6 py-4">
                                    <input type="number" :name="`items[${index}][quantity]`" x-model="item.quantity" min="1" required
                                        class="w-full text-right rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </td>
                                <td class="px-6 py-4">
                                    <input type="number" :name="`items[${index}][unit_cost]`" x-model="item.unit_cost" step="0.01" min="0" required
                                        class="w-full text-right rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium text-gray-900" x-text="formatCurrency(item.quantity * item.unit_cost)"></td>
                                <td class="px-6 py-4 text-center">
                                    <button type="button" @click="removeItem(index)" class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="form.items.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                No items added. Click "Add Item" to start.
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-gray-50" x-show="form.items.length > 0">
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-right font-medium text-gray-900">Total Invoice Amount:</td>
                            <td class="px-6 py-4 text-right font-bold text-indigo-600 text-lg" x-text="formatCurrency(calculateTotal())"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="flex justify-end gap-4">
            <a href="{{ route('suppliers.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancel</a>
            <button type="submit" class="px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                Record Purchase & Bill
            </button>
        </div>
    </form>

    <!-- Product Search Modal -->
    <div x-show="showProductSearch" class="fixed z-50 inset-0 overflow-y-auto" style="display: none;" x-cloak>
         <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="showProductSearch = false"></div>
            <div class="inline-block relative z-50 align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6">
                <div>
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Search Products</h3>
                    <input type="text" x-model="searchQuery" @input.debounce.300ms="searchProducts" placeholder="Type product name..." class="w-full rounded-md border-gray-300 mb-4 focus:ring-indigo-500 focus:border-indigo-500">
                    
                    <div class="max-h-60 overflow-y-auto border border-gray-200 rounded-md">
                        <ul class="divide-y divide-gray-200" x-show="searchResults.length > 0">
                            <template x-for="product in searchResults" :key="product.id">
                                <li class="p-4 hover:bg-gray-50 cursor-pointer flex justify-between items-center" @click="addItem(product)">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900" x-text="product.product_name"></p>
                                        <p class="text-xs text-gray-500">Code: <span x-text="product.code"></span> | Stock: <span x-text="product.quantity_in_stock"></span></p>
                                    </div>
                                    <span class="text-sm text-gray-900 font-medium" x-text="formatCurrency(product.price)"></span>
                                </li>
                            </template>
                        </ul>
                        <div x-show="!loading && searchResults.length === 0 && searchQuery.length > 2" class="p-4 text-center text-gray-500">No products found.</div>
                         <div x-show="loading" class="p-4 text-center text-gray-500">Searching...</div>
                    </div>
                </div>
                <div class="mt-5 sm:mt-6">
                    <button type="button" @click="showProductSearch = false" class="inline-flex justify-center w-full rounded-md border border-gray-300 px-4 py-2 bg-white text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none sm:text-sm">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
window.directOrderForm = function() {
    return {
        showProductSearch: false,
        searchQuery: '',
        searchResults: [],
        loading: false,
        form: {
            supplier_id: '{{ request('supplier_id') }}',
            invoice_number: '',
            order_date: new Date().toISOString().split('T')[0],
            notes: '',
            items: []
        },

        async searchProducts() {
            if (this.searchQuery.length < 2) {
                this.searchResults = [];
                return;
            }
            this.loading = true;
            try {
                const res = await axios.get(`/inventory?search=${this.searchQuery}&per_page=10`, { headers: { 'Accept': 'application/json' } });
                this.searchResults = res.data.data || res.data; 
            } catch (e) {
                console.error(e);
            } finally {
                this.loading = false;
            }
        },

        addItem(product) {
            // Check if exists
            const existing = this.form.items.find(i => i.product_id === product.id);
            if (existing) {
                alert('Product already added to list.');
                return;
            }

            this.form.items.push({
                product_id: product.id,
                product_name: product.product_name,
                current_stock: product.quantity_in_stock,
                quantity: 1,
                unit_cost: parseFloat(product.price || 0) // Default to standard price/cost
            });
            
            this.showProductSearch = false;
            this.searchQuery = '';
            this.searchResults = [];
        },

        removeItem(index) {
            this.form.items.splice(index, 1);
        },

        calculateTotal() {
            return this.form.items.reduce((sum, item) => sum + (item.quantity * item.unit_cost), 0);
        },

        formatCurrency(amount) {
            let val = parseFloat(amount || 0);
            return 'KSh ' + val.toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        async submitForm(e) {
            if (this.form.items.length === 0) {
                alert('Please add at least one item.');
                return;
            }
            e.target.submit();
        }
    }
}
</script>
@endsection
