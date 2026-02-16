@extends('layouts.app')

@section('content')
<div x-data="ordersManager({{ $suppliers }})" x-init="init()" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Purchase Orders</h1>
                <p class="mt-2 text-sm text-gray-600">Manage purchase orders and suppliers</p>
            </div>
            @if(auth()->user()->hasRole(['admin', 'accountant']))
            <button type="button" @click.prevent="showAddModal = true" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                New Order
            </button>
            @endif
        </div>

        <!-- Filters -->
        <div class="bg-white shadow rounded-lg p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <select x-model="filters.status" @change="loadOrders()" class="w-full rounded-md border-gray-300">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="received">Received</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div>
                    <input type="date" x-model="filters.date_from" @change="loadOrders()" placeholder="Date From" class="w-full rounded-md border-gray-300">
                </div>
                <div>
                    <input type="date" x-model="filters.date_to" @change="loadOrders()" placeholder="Date To" class="w-full rounded-md border-gray-300">
                </div>
                <div>
                    <button @click="loadOrders()" class="w-full bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200">
                        Refresh
                    </button>
                </div>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="bg-white shadow sm:rounded-md">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order #</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Supplier</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="order in orders" :key="order.id">
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="order.order_number"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="order.supplier_name"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="formatDate(order.order_date)"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="formatCurrency(order.total_amount)"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span 
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    :class="{
                                        'bg-yellow-100 text-yellow-800': order.status === 'pending',
                                        'bg-blue-100 text-blue-800': order.status === 'approved',
                                        'bg-green-100 text-green-800': order.status === 'received',
                                        'bg-red-100 text-red-800': order.status === 'cancelled'
                                    }"
                                    x-text="order.status"
                                ></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button @click="viewOrder(order)" class="text-indigo-600 hover:text-indigo-900 mr-3">View</button>
                                
                                <template x-if="order.status === 'pending'">
                                    <span>
                                        <button @click="updateStatus(order, 'approved')" class="text-green-600 hover:text-green-900 mr-3">Approve</button>
                                        <button @click="updateStatus(order, 'cancelled')" class="text-red-600 hover:text-red-900">Cancel</button>
                                    </span>
                                </template>

                                <template x-if="order.status === 'approved'">
                                    <button @click="updateStatus(order, 'received')" class="text-blue-600 hover:text-blue-900">Receive</button>
                                </template>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

     <!-- Add Order Modal -->
    <div x-show="showAddModal" class="fixed z-50 inset-0 overflow-y-auto" style="display: none;" x-cloak>
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="showAddModal = false"></div>
            <div class="relative inline-block align-middle bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <form @submit.prevent="saveOrder()">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Create Purchase Order</h3>
                        <!-- Form Content same as before -->
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Supplier</label>
                                    <select x-model="form.supplier_id" @change="loadSupplierInfo()" class="mt-1 block w-full rounded-md border-gray-300">
                                        <option value="">Select Supplier</option>
                                        <template x-for="supplier in suppliers" :key="supplier.id">
                                            <option :value="supplier.id" x-text="supplier.name"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Supplier Name *</label>
                                    <input type="text" x-model="form.supplier_name" required class="mt-1 block w-full rounded-md border-gray-300">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Expected Delivery Date</label>
                                    <input type="date" x-model="form.expected_delivery_date" class="mt-1 block w-full rounded-md border-gray-300">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Payment Terms</label>
                                    <input type="text" x-model="form.payment_terms" class="mt-1 block w-full rounded-md border-gray-300">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Items</label>
                                <div class="mt-2 space-y-2">
                                    <template x-for="(item, index) in form.items" :key="index">
                                        <div class="flex gap-2">
                                            <select x-model="item.product_id" @change="loadProductInfo(index)" class="flex-1 rounded-md border-gray-300">
                                                <option value="">Select Product</option>
                                                <template x-for="product in products" :key="product.id">
                                                    <option :value="product.id" x-text="product.product_name"></option>
                                                </template>
                                            </select>
                                            <input type="number" x-model="item.quantity" placeholder="Qty" min="1" class="w-20 rounded-md border-gray-300">
                                            <input type="number" step="0.01" x-model="item.unit_cost" placeholder="Cost" min="0" class="w-24 rounded-md border-gray-300">
                                            <button type="button" @click="removeItem(index)" class="text-red-600 hover:text-red-900">×</button>
                                        </div>
                                    </template>
                                    <button type="button" @click="addItem()" class="text-indigo-600 hover:text-indigo-900 text-sm">+ Add Item</button>
                                </div>
                            </div>
                            <div class="border-t pt-4">
                                <div class="flex justify-between font-bold">
                                    <span>Total:</span>
                                    <span x-text="formatCurrency(calculateTotal())"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm">
                            Create Order
                        </button>
                        <button type="button" @click="showAddModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Order Modal -->
    <div x-show="viewOrderModal" class="fixed z-50 inset-0 overflow-y-auto" style="display: none;" x-cloak>
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
             <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="viewOrderModal = false"></div>
             <div class="relative inline-block align-middle bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex justify-between mb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" x-text="'Order #' + (selectedOrder ? selectedOrder.order_number : '')"></h3>
                        <span 
                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                            :class="{
                                'bg-yellow-100 text-yellow-800': selectedOrder?.status === 'pending',
                                'bg-blue-100 text-blue-800': selectedOrder?.status === 'approved',
                                'bg-green-100 text-green-800': selectedOrder?.status === 'received',
                                'bg-red-100 text-red-800': selectedOrder?.status === 'cancelled'
                            }"
                            x-text="selectedOrder?.status"
                        ></span>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4 text-sm" x-show="selectedOrder">
                        <div>
                            <p class="text-gray-500">Supplier</p>
                            <p class="font-medium" x-text="selectedOrder?.supplier_name"></p>
                        </div>
                        <div>
                             <p class="text-gray-500">Date</p>
                             <p class="font-medium" x-text="formatDate(selectedOrder?.order_date)"></p>
                        </div>
                        <div>
                             <p class="text-gray-500">Expected Delivery</p>
                             <p class="font-medium" x-text="formatDate(selectedOrder?.expected_delivery_date) || '-'"></p>
                        </div>
                         <div>
                             <p class="text-gray-500">Total Amount</p>
                             <p class="font-medium" x-text="formatCurrency(selectedOrder?.total_amount)"></p>
                        </div>
                    </div>

                    <table class="min-w-full divide-y divide-gray-200 mb-4">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unit Cost</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                             <template x-for="item in selectedOrder?.items || []" :key="item.id">
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900" x-text="item.product_name"></td>
                                    <td class="px-6 py-4 text-sm text-gray-900" x-text="item.quantity"></td>
                                    <td class="px-6 py-4 text-sm text-gray-900" x-text="formatCurrency(item.unit_cost)"></td>
                                    <td class="px-6 py-4 text-sm text-gray-900" x-text="formatCurrency(item.total_cost)"></td>
                                </tr>
                             </template>
                        </tbody>
                    </table>
                </div>

                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse" x-show="selectedOrder">
                    <button type="button" @click="viewOrderModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Close
                    </button>
                    
                    @if(auth()->user()->hasRole(['admin', 'accountant']))
                    <!-- Actions -->
                    <template x-if="selectedOrder?.status === 'pending'">
                        <span class="flex sm:ml-3">
                            <button @click="updateStatus(selectedOrder, 'approved')" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 sm:w-auto sm:text-sm">
                                Approve
                            </button>
                            <button @click="updateStatus(selectedOrder, 'cancelled')" class="ml-2 w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </span>
                    </template>
                     <template x-if="selectedOrder?.status === 'approved'">
                        <button @click="updateStatus(selectedOrder, 'received')" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 sm:ml-3 sm:w-auto sm:text-sm">
                            Receive (Update Stock)
                        </button>
                    </template>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.ordersManager = function(initialSuppliers) {
    window.ordersManager = ordersManager;
    return {
        orders: [],
        suppliers: initialSuppliers || [],
        products: [],
        loading: false,
        loading: false,
        showAddModal: false,
        viewOrderModal: false,
        selectedOrder: null,
        filters: {
            status: '',
            date_from: '',
            date_to: ''
        },
        form: {
            supplier_id: null,
            supplier_name: '',
            expected_delivery_date: '',
            payment_terms: '',
            items: [{ product_id: '', product_name: '', quantity: 1, unit_cost: 0 }]
        },

        init() {
            this.loadOrders();
            this.$watch('showAddModal', (value) => {
                if (value && this.products.length === 0) {
                    this.loadProducts();
                }
            });
        },

        async loadOrders() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.filters.status) params.append('status', this.filters.status);
                if (this.filters.date_from) params.append('date_from', this.filters.date_from);
                if (this.filters.date_to) params.append('date_to', this.filters.date_to);

                const response = await axios.get(`/orders?${params}`);
                this.orders = response.data.data || response.data;

                // Check for 'create' action from suggestions
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.get('action') === 'create' && urlParams.get('items')) {
                    try {
                        const items = JSON.parse(urlParams.get('items'));
                        this.form.items = items;
                        this.showAddModal = true;
                        
                        // Clear URL without reload
                        window.history.replaceState({}, document.title, window.location.pathname);
                        
                        // Load products for dropdowns
                        await this.loadProducts();
                        await this.loadSuppliers();
                    } catch (e) {
                        console.error('Failed to parse items from URL', e);
                    }
                }
            } catch (error) {
                console.error('Error loading orders:', error);
            } finally {
                this.loading = false;
            }
        },

        async loadSuppliers() {
            try {
                const response = await axios.get('/suppliers');
                this.suppliers = response.data.data || response.data;
            } catch (error) {
                console.error('Error loading suppliers:', error);
            }
        },

        async loadProducts() {
            try {
                const response = await axios.get('/inventory', { headers: { 'Accept': 'application/json' } });
                this.products = response.data.data || response.data;
            } catch (error) {
                console.error('Error loading products:', error);
            }
        },

        addItem() {
            this.form.items.push({ product_id: '', product_name: '', quantity: 1, unit_cost: 0 });
        },

        removeItem(index) {
            this.form.items.splice(index, 1);
        },

        async loadProductInfo(index) {
            const productId = this.form.items[index].product_id;
            const product = this.products.find(p => p.id == productId);
            if (product) {
                this.form.items[index].product_name = product.product_name;
                this.form.items[index].unit_cost = product.price || 0;
            }
        },

        calculateTotal() {
            return this.form.items.reduce((sum, item) => {
                return sum + (parseFloat(item.quantity || 0) * parseFloat(item.unit_cost || 0));
            }, 0);
        },

        async saveOrder() {
            try {
                await axios.post('/orders', this.form);
                this.showAddModal = false;
                this.loadOrders();
                alert('Order created successfully');
            } catch (error) {
                alert('Error creating order: ' + (error.response?.data?.error || error.message));
            }
        },

        viewOrder(order) {
            this.selectedOrder = order;
            this.viewOrderModal = true;
        },

        async updateStatus(order, newStatus) {
            if (!confirm(`Are you sure you want to mark this order as ${newStatus}?`)) return;
            
            try {
                await axios.put(`/orders/${order.id}/status`, { status: newStatus });
                await axios.put(`/orders/${order.id}/status`, { status: newStatus });
                this.loadOrders();
                
                // Update local status immediately for UI reflex
                if (this.selectedOrder && this.selectedOrder.id === order.id) {
                    this.selectedOrder.status = newStatus;
                }

                alert(`Order marked as ${newStatus}`);
            } catch (error) {
                alert('Error updating status: ' + (error.response?.data?.error || error.message));
            }
        },

        formatCurrency(amount) {
            return 'KSh ' + parseFloat(amount || 0).toLocaleString('en-KE', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },

        formatDate(dateString) {
            if (!dateString) return '';
            return new Date(dateString).toLocaleDateString('en-KE');
        }
    }
}
</script>
@endsection

