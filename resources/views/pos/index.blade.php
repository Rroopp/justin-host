@extends('layouts.app')

@section('content')
<script>
    window.systemSettings = {
        taxRate: {{ settings('default_tax_rate', 16) }},
        currencySymbol: "{{ settings('currency_symbol', 'KSh') }}"
    };
</script>
<script>
    window.initialPOSData = {
        products: @json($products),
        customers: @json($customers),
        packages: @json($packages ?? []),
        staff: @json($staff ?? [])
    };

    window.posSystem = function() {
        const initialProducts = window.initialPOSData.products || [];
        const initialCustomers = window.initialPOSData.customers || [];
        const initialPackages = window.initialPOSData.packages || [];
        const initialStaff = window.initialPOSData.staff || [];
        
        console.log('POS Init:', { products: initialProducts.length, customers: initialCustomers.length, packages: initialPackages.length });
        
        return {
            products: initialProducts,
            filteredProducts: initialProducts, 
            customers: initialCustomers,
            packages: initialPackages,
            filteredPackages: initialPackages,
            activeTab: 'products', // 'products' or 'packages'
            cart: [],
            productSearch: '',
            // paymentMethod: window.preferencesManager ? window.preferencesManager.get('pos_default_payment') : 'Credit',
            paymentMethod: '', // Force explicit selection
            documentType: 'receipt',
            dueDate: '',
            selectedCustomerId: null,
            customerQuery: '',
            showCustomerResults: false,
            customerResultsSuppressedUntil: 0,
            showQuickAddCustomer: false,
            creatingCustomer: false,
            newCustomer: {
                name: '',
                phone: '',
                email: '',
                facility: ''
            },
            caseDetails: {
                patient_name: '',
                patient_number: '',
                patient_type: '',
                facility_name: '',
                surgeon_name: '',
                nurse_name: ''
            },
            availableLpos: [],
            selectedLpoId: '',
            lpoNumber: '',
            processing: false,
            lastSaleId: null,
            discountRate: 0,
            taxRate: window.systemSettings?.taxRate || 16,
            showLoadCartModal: false,
            showSuccessModal: false,
            savedCarts: [],
            viewMode: 'table', // 'table' or 'grid'

            // Commission Data
            staff: initialStaff,
            commission: {
                staff_id: '',
                amount: '',
                note: ''
            },

            initData() {
                if (this.products.length === 0) {
                     this.loadProducts();
                }
                if (this.customers.length === 0) {
                     this.loadCustomers();
                }
                
                // Watch for customer changes to update package prices
                this.$watch('selectedCustomerId', (newId) => {
                    this.updateCartPricesForCustomer(newId);
                });
            },

            async loadProducts() {
                try {
                    const response = await axios.get('/inventory');
                    this.products = response.data.data || response.data;
                    this.filteredProducts = this.products; 
                } catch (error) {
                    console.error('Error loading products:', error);
                }
            },
            
            async loadCustomers() {
                try {
                    const response = await axios.get('/customers', { headers: { 'Accept': 'application/json' } });
                    this.customers = response.data.data || response.data;
                } catch (error) {
                    console.error('Error loading customers:', error);
                }
            },

            filterProducts() {
                const search = this.productSearch.toLowerCase();
                
                if (this.activeTab === 'products') {
                    this.filteredProducts = this.products.filter(p => {
                        return (p.product_name && p.product_name.toLowerCase().includes(search)) || 
                               (p.code && p.code.toLowerCase().includes(search));
                    });
                } else {
                    this.filteredPackages = this.packages.filter(p => {
                        return (p.name && p.name.toLowerCase().includes(search)) || 
                               (p.code && p.code.toLowerCase().includes(search));
                    });
                }
            },
            
            async addPackageToCart(pkg) {
                // Fetch package details (items & customer price)
                this.processing = true; // Show loading?
                try {
                     const customerId = this.selectedCustomerId || '';
                     const response = await axios.get(`/api/packages/${pkg.id}/details?customer_id=${customerId}`);
                     const details = response.data;
                     
                     // 1. Add Package Header (Price Wrapper)
                     this.cart.push({
                        id: pkg.id, // ID of package
                        product_id: null,
                        product_name: `📦 ${details.name}`,
                        unit_price: details.price, // Contract Price
                        quantity: 1,
                        type: 'package_header',
                        is_rentable: false, 
                        is_package: true
                    });
                    
                    // 2. Add Package Components (Inventory Deductions)
                    // Note: API returns items with quantities.
                    details.items.forEach(item => {
                        this.cart.push({
                            product_id: item.inventory_id,
                            product_name: `  ↳ ${item.name}`,
                            unit_price: 0, // Zero price for component
                            quantity: item.quantity,
                            type: 'package_component',
                            is_rentable: false, 
                            size: '', 
                            manufacturer: '',
                            stock: item.stock // For validation
                        });
                    });
                    
                } catch (error) {
                    console.error("Error fetching package details", error);
                    alert("Failed to load package details.");
                } finally {
                    this.processing = false;
                }
            },

            async updateCartPricesForCustomer(customerId) {
                // Iterate through cart and update prices for any package headers
                // We need to fetch the price again. 
                // Optimization: fetch in parallel or uniquely.
                
                const packageHeaders = this.cart.filter(item => item.type === 'package_header');
                if (packageHeaders.length === 0) return;

                this.processing = true;
                try {
                    // Create an array of promises
                    const updates = packageHeaders.map(async (headerItem) => {
                        try {
                            const response = await axios.get(`/api/packages/${headerItem.id}/details?customer_id=${customerId || ''}`);
                            // Update the price in the cart
                            headerItem.unit_price = response.data.price;
                            // Note: We don't update components here as their quantity/existence is user-managed 
                            // after adding. We only care about the financial header price.
                        } catch (err) {
                            console.error(`Failed to update price for package ${headerItem.id}`, err);
                        }
                    });

                    await Promise.all(updates);
                } catch (error) {
                    console.error("Error updating package prices", error);
                } finally {
                    this.processing = false;
                }
            },

            addToCart(product) {
                const type = product.is_rentable ? 'rental' : 'sale';
                const existingItem = this.cart.find(item => item.product_id === product.id && item.type === type);
                if (existingItem) {
                    if (product.quantity_in_stock > existingItem.quantity) {
                        existingItem.quantity++;
                    } else {
                        alert('Insufficient stock!');
                    }
                } else {
                    this.cart.push({
                        product_id: product.id,
                        product_name: product.product_name,
                        unit_price: product.selling_price,
                        quantity: 1,
                        type: type,
                        is_rentable: product.is_rentable,
                        size: product.size,
                        size_unit: product.size_unit || '',
                        manufacturer: product.manufacturer
                    });
                }
            },

            updateQuantity(index, newQty) {
                let qty = parseFloat(newQty);
                if (isNaN(qty) || qty <= 0) qty = 1;
                
                // Allow decimals, maybe round to 4 places if needed, but keeping raw is safer for now
                // qty = parseFloat(qty.toFixed(4)); 

                const item = this.cart[index];
                
                // If package header or infinite stock, just update
                if (!item.product_id) {
                    item.quantity = qty;
                    return;
                }
                
                // Check stock for products
                const product = this.products.find(p => p.id === item.product_id);
                // Note: item.stock might be populated for package components from details
                const maxStock = product ? product.quantity_in_stock : (item.stock || 999999);
                
                if (qty > maxStock) {
                    alert(`Insufficient stock! Max available: ${maxStock}`);
                    qty = maxStock;
                }
                
                item.quantity = qty;
            },

            removeFromCart(index) {
                this.cart.splice(index, 1);
            },

            async saveCart() {
                 const name = prompt("Enter a name for this cart:");
                 if (!name) return;
                 try {
                     await axios.post('/pos/cart/save', { name: name, cart: this.cart, customer_id: this.selectedCustomerId });
                     alert('Cart saved!');
                     this.loadSavedCarts();
                 } catch (e) {
                     alert('Error saving cart');
                 }
            },

            async openLoadCartModal() {
                 this.showLoadCartModal = true;
                 await this.loadSavedCarts();
            },

            async loadSavedCarts() {
                 try {
                    const response = await axios.get('/pos/cart/list');
                    this.savedCarts = response.data;
                 } catch (e) { console.error(e); }
            },

            loadCart(id) {
                 const cart = this.savedCarts.find(c => c.id === id);
                 if (cart && cart.cart_data) {
                     this.cart = cart.cart_data.cart || [];
                     this.selectedCustomerId = cart.customer_id;
                     this.showLoadCartModal = false;
                 }
            },

            async deleteCartSummary(id) {
                 if(!confirm('Delete this saved cart?')) return;
                 try {
                     await axios.delete(`/pos/cart/${id}`);
                     this.loadSavedCarts();
                 } catch (e) { alert('Error deleting cart'); }
            },

            clearCart() {
                this.cart = [];
                this.selectedCustomerId = null;
                this.customerQuery = '';
                this.paymentMethod = 'Cash';
                this.caseDetails = {
                    patient_name: '',
                    patient_number: '',
                    patient_type: '',
                    facility_name: '',
                    surgeon_name: '',
                    nurse_name: ''
                };
                this.availableLpos = [];
                this.selectedLpoId = '';
                this.lpoNumber = '';
                this.commission = { staff_id: '', amount: '', note: '' };
            },

            async dispatchConsignment() {
                if (!this.selectedCustomerId) {
                    alert('Please select a customer before dispatching a consignment.');
                    return;
                }
                const confirmMessage = `Dispatch items for surgery consignment?\n\nThis will:\n✓ Deduct stock immediately\n✓ Generate a Packing Slip\n✓ Mark as "Consignment" (pending reconciliation)\n✗ NOT book revenue yet\n\nYou'll reconcile returns later in: Sales > Surgery Consignments\n\nContinue?`;
                if (!confirm(confirmMessage)) return;

                this.paymentMethod = 'Credit';
                this.documentType = 'packing_slip';
                const futureDate = new Date();
                futureDate.setDate(futureDate.getDate() + 60);
                this.dueDate = futureDate.toISOString().split('T')[0];
                this.completeSale('consignment');
            },

            async completeSale(status = 'completed') {
                if (this.cart.length === 0) return;
                this.syncDocumentRules();
                if (!this.paymentMethod) {
                    alert('Please select a payment method.');
                    this.processing = false;
                    return;
                }

                if (this.paymentMethod === 'Credit' && !this.dueDate) {
                    alert('Please select a Due Date for Credit sales.');
                    return;
                }

                this.processing = true;
                try {
                    const saleData = {
                        items: this.cart.map(item => ({
                            // Use item.id for packages (since product_id is null)
                            id: item.type === 'package_header' ? item.id : item.product_id,
                            quantity: item.quantity,
                            type: item.type,
                            price: item.unit_price
                        })),
                        payment_method: this.paymentMethod,
                        subtotal: this.subtotal,
                        discount_percentage: this.discountRate,
                        discount_amount: this.discountAmount,
                        vat: this.vat,
                        total: this.total,
                        customer_id: this.selectedCustomerId || null,
                        customer_info: this.selectedCustomerId ? 
                            this.customers.find(c => c.id === this.selectedCustomerId) : {},
                        document_type: this.documentType,
                        due_date: this.paymentMethod === 'Credit' ? this.dueDate : null,
                        sale_status: status,
                        lpo_id: this.selectedLpoId || null,
                        lpo_number: this.lpoNumber,
                        lpo_id: this.selectedLpoId || null,
                        lpo_number: this.lpoNumber,
                        
                        // Commission Data
                        commission_staff_id: this.commission.staff_id || null,
                        commission_amount: this.commission.amount || null,
                        commission_note: this.commission.note || null,

                        ...this.caseDetails
                    };

                    const response = await axios.post('/pos', saleData);
                    
                    if (response.data.success) {
                        this.lastSaleId = response.data.sale_id;
                        this.clearCart(); 
                        await this.loadProducts(); 
                        
                        if (status === 'consignment') {
                            alert('Consignment dispatched successfully! Printing Packing Slip...');
                            this.printDocument('packing_slip');
                        } else {
                            this.showSuccessModal = true;
                        }
                    }
                } catch (error) {
                    alert('Error processing sale: ' + (error.response?.data?.error || error.message));
                } finally {
                    this.processing = false;
                }
            },
            
            printDocument(type) {
                if (this.lastSaleId) {
                    window.open(`/receipts/${this.lastSaleId}/print?type=${type}`, '_blank');
                }
            },
            
            closeSuccessModal() {
                this.showSuccessModal = false;
                this.lastSaleId = null;
            },

            filteredCustomers(query) {
                if (!query || query.trim() === '') return this.customers;
                const search = query.toLowerCase();
                return this.customers.filter(c => 
                    (c.name && c.name.toLowerCase().includes(search)) ||
                    (c.phone && c.phone.toLowerCase().includes(search)) ||
                    (c.email && c.email.toLowerCase().includes(search))
                );
            },

            selectCustomer(customer) {
                this.selectedCustomerId = customer.id;
                this.customerQuery = customer.name;
                this.showCustomerResults = false;
                this.customerResultsSuppressedUntil = Date.now() + 300;
                this.fetchLpos(customer.id);
            },

            async fetchLpos(customerId) {
                try {
                    this.availableLpos = [];
                    const response = await axios.get(`/api/customers/${customerId}/lpos`);
                    this.availableLpos = response.data;
                } catch (e) {
                    console.error("Error fetching LPOs", e);
                }
            },

            selectWalkInCustomer() {
                this.selectedCustomerId = null;
                this.customerQuery = '';
                this.showCustomerResults = false;
                this.customerResultsSuppressedUntil = Date.now() + 300;
            },

            selectedCustomerName() {
                if (!this.selectedCustomerId) return 'Walk-in';
                const customer = this.customers.find(c => c.id === this.selectedCustomerId);
                return customer ? customer.name : 'Walk-in';
            },

            openQuickAddCustomer() {
                this.showQuickAddCustomer = true;
                this.$nextTick(() => {
                    if (this.$refs.newCustomerName) {
                        this.$refs.newCustomerName.focus();
                    }
                });
            },

            closeQuickAddCustomer() {
                this.showQuickAddCustomer = false;
                this.newCustomer = { name: '', phone: '', email: '', facility: '' };
            },

            async createCustomerQuick() {
                if (!this.newCustomer.name.trim()) {
                    alert('Customer name is required');
                    return;
                }
                this.creatingCustomer = true;
                try {
                    const response = await axios.post('/customers', this.newCustomer, {
                        headers: { 'Accept': 'application/json' }
                    });
                    if (response.data && response.data.id) {
                        await this.loadCustomers();
                        this.selectCustomer(response.data);
                        this.closeQuickAddCustomer();
                    }
                } catch (error) {
                    alert('Error creating customer: ' + (error.response?.data?.message || error.message));
                } finally {
                    this.creatingCustomer = false;
                }
            },

            syncDocumentRules() {
                if (this.paymentMethod === 'Credit') {
                    this.documentType = 'invoice';
                    if (!this.dueDate) {
                        const futureDate = new Date();
                        futureDate.setDate(futureDate.getDate() + 30);
                        this.dueDate = futureDate.toISOString().split('T')[0];
                    }
                } else {
                    this.documentType = 'receipt';
                    this.dueDate = '';
                }
            },

            formatCurrency(amount) {
                const symbol = window.systemSettings?.currencySymbol || 'KSh';
                return symbol + ' ' + parseFloat(amount || 0).toLocaleString('en-KE', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            },

            get subtotal() {
                return this.cart.reduce((sum, item) => sum + (item.unit_price * item.quantity), 0);
            },

            get discountAmount() {
                return (this.subtotal * this.discountRate) / 100;
            },

            get vat() {
                const taxableAmount = this.subtotal - this.discountAmount;
                return (taxableAmount * this.taxRate) / (100 + this.taxRate);
            },

            get total() {
                return this.subtotal - this.discountAmount;
            }
        } // End object
    } // End function
</script>

<div x-data="posSystem()" x-init="initData()" class="h-[calc(100vh-6rem)] flex flex-col">
    <!-- Compact Header -->
    <div class="flex-none flex items-center justify-between mb-2">
        <h1 class="text-xl font-bold text-gray-900">Point of Sale</h1>
        <div class="flex gap-2">
            <a href="{{ route('rentals.index') }}" class="px-2 py-1 bg-indigo-100 text-indigo-700 rounded hover:bg-indigo-200 text-xs font-medium transition-colors">
                Rentals
            </a>
            <button
                @click="clearCart()"
                x-show="cart.length > 0"
                class="px-2 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200 text-xs font-medium transition-colors"
                x-cloak>
                Clear
            </button>
        </div>
    </div>

    <!-- Main Content Grid - New Layout: Products | Cart | Checkout -->
    <div class="flex-1 min-h-0 grid grid-cols-1 lg:grid-cols-12 gap-4 h-full">
        
        <!-- LEFT SIDE: PRODUCTS & CART (Span 9) -->
        <div class="lg:col-span-9 flex flex-col md:flex-row gap-4 h-full min-h-0">
             
            <!-- 1. PRODUCTS SECTION (Flex-grow, approx 55%) -->
            <div class="flex-[1.3] flex flex-col bg-white shadow rounded-lg overflow-hidden min-w-0 h-full">
                <!-- Search & Filters -->
                <div class="p-2 border-b flex-none bg-white z-10 gap-2 flex flex-col">
                    <div class="flex gap-2">
                        <input 
                            type="text" 
                            x-model="productSearch" 
                            @input="filterProducts()"
                            placeholder="Search products..." 
                            class="flex-1 rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm py-1.5"
                        >
                        <div class="flex rounded-md shadow-sm" role="group">
                            <button 
                                @click="viewMode = 'table'"
                                :class="viewMode === 'table' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
                                class="px-2 py-1.5 text-xs font-medium border border-gray-300 rounded-l-md transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                            </button>
                            <button 
                                @click="viewMode = 'grid'"
                                :class="viewMode === 'grid' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
                                class="px-2 py-1.5 text-xs font-medium border border-l-0 border-gray-300 rounded-r-md transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="px-3 border-b bg-gray-50 flex gap-4">
                    <button 
                        @click="activeTab = 'products'; filterProducts()"
                        :class="activeTab === 'products' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-2 px-1 border-b-2 font-medium text-xs whitespace-nowrap transition-colors"
                    >
                        Products
                    </button>
                    <button 
                        @click="activeTab = 'packages'; filterProducts()"
                        :class="activeTab === 'packages' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-2 px-1 border-b-2 font-medium text-xs whitespace-nowrap transition-colors"
                    >
                        Packages
                    </button>
                </div>

                <!-- Product List (Scrollable) -->
                <div class="flex-1 overflow-y-auto custom-scrollbar bg-white">
                    
                    <!-- Table View: Products -->
                    <div x-show="viewMode === 'table' && activeTab === 'products'" class="h-full">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 sticky top-0 z-10 shadow-sm">
                                <tr>
                                    <th class="px-3 py-2 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">Product</th>
                                    <th class="px-3 py-2 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">Details</th>
                                    <th class="px-3 py-2 text-right text-[10px] font-bold text-gray-500 uppercase tracking-wider">Stock</th>
                                    <th class="px-3 py-2 text-right text-[10px] font-bold text-gray-500 uppercase tracking-wider">Price</th>
                                    <th class="px-3 py-2 text-center text-[10px] font-bold text-gray-500 uppercase tracking-wider w-16"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="product in filteredProducts" :key="product.id">
                                    <tr class="hover:bg-indigo-50 transition-colors cursor-pointer group" 
                                        :class="{'opacity-50': product.quantity_in_stock <= 0}"
                                        @click="addToCart(product)">
                                        <td class="px-3 py-2">
                                            <div class="text-xs font-bold text-gray-900 group-hover:text-indigo-700" x-text="product.product_name"></div>
                                            <div class="text-[10px] text-gray-500" x-show="product.manufacturer" x-text="product.manufacturer"></div>
                                        </td>
                                        <td class="px-3 py-2">
                                            <div class="text-[10px] text-gray-600 font-mono mb-1" x-text="product.code"></div>
                                            <div class="flex flex-wrap gap-1">
                                                <span x-show="product.size" class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-gray-100 text-gray-800" x-text="product.size + (product.size_unit || '')"></span>
                                                <template x-if="product.attributes">
                                                    <template x-for="(value, key) in product.attributes" :key="key">
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-medium border border-gray-100 bg-white text-gray-600" x-text="value"></span>
                                                    </template>
                                                </template>
                                                <span x-show="product.is_rentable" class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-blue-100 text-blue-800">RENT</span>
                                            </div>
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            <span class="text-xs font-medium"
                                                :class="{
                                                    'text-red-600 font-bold': product.quantity_in_stock <= 0,
                                                    'text-yellow-600': product.quantity_in_stock > 0 && product.quantity_in_stock <= 10,
                                                    'text-green-600': product.quantity_in_stock > 10
                                                }"
                                                x-text="product.quantity_in_stock"></span>
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            <div class="text-xs font-bold text-gray-900" x-text="formatCurrency(product.selling_price)"></div>
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <button 
                                                @click.stop="addToCart(product)"
                                                :disabled="product.quantity_in_stock <= 0"
                                                class="text-indigo-600 hover:text-indigo-900 font-bold text-lg leading-none p-1 block w-full hover:bg-white rounded">
                                                +
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                        <div x-show="filteredProducts.length === 0" class="text-center text-gray-500 py-10 text-sm">No products found.</div>
                    </div>

                    <!-- Table View: Packages -->
                    <div x-show="viewMode === 'table' && activeTab === 'packages'" class="h-full">
                         <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 sticky top-0 z-10 shadow-sm">
                                <tr>
                                    <th class="px-3 py-2 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">Package</th>
                                    <th class="px-3 py-2 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">Content</th>
                                    <th class="px-3 py-2 text-right text-[10px] font-bold text-gray-500 uppercase tracking-wider">Price</th>
                                    <th class="px-3 py-2 text-center text-[10px] font-bold text-gray-500 uppercase tracking-wider w-16"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="pkg in filteredPackages" :key="pkg.id">
                                    <tr class="hover:bg-purple-50 transition-colors cursor-pointer" @click="addPackageToCart(pkg)">
                                        <td class="px-3 py-2">
                                            <div class="text-xs font-medium text-gray-900" x-text="pkg.name"></div>
                                            <div class="text-[10px] text-gray-500 font-mono" x-text="pkg.code"></div>
                                        </td>
                                        <td class="px-3 py-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-purple-100 text-purple-800">
                                                <span x-text="pkg.items_count || 0"></span> Items
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            <div class="text-xs font-bold text-gray-900" x-text="formatCurrency(pkg.base_price)"></div>
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <button @click.stop="addPackageToCart(pkg)" class="text-purple-600 hover:text-purple-900 font-bold text-[10px] border border-purple-200 px-2 py-0.5 rounded bg-white">
                                                Select
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                         <div x-show="filteredPackages.length === 0" class="text-center text-gray-500 py-10 text-sm">No packages found.</div>
                    </div>


                    <!-- Grid View: Products -->
                    <div x-show="viewMode === 'grid' && activeTab === 'products'" class="p-4 grid grid-cols-2 lg:grid-cols-3 gap-3">
                        <template x-for="product in filteredProducts" :key="product.id">
                            <button 
                                @click="addToCart(product)"
                                :disabled="product.quantity_in_stock <= 0"
                                class="p-3 border rounded-lg hover:border-indigo-300 hover:shadow-md transition-all disabled:opacity-50 text-left flex flex-col h-full bg-white relative group">
                                <div class="mb-2">
                                    <div class="font-medium text-gray-900 text-sm leading-tight group-hover:text-indigo-700" x-text="product.product_name"></div>
                                    <div class="text-[10px] text-gray-400 mt-1 font-mono" x-text="product.code"></div>
                                </div>
                                <div class="mt-auto flex justify-between items-end border-t pt-2 border-gray-100 w-full">
                                    <div>
                                        <div class="text-[10px] font-medium" 
                                             :class="product.quantity_in_stock <= 0 ? 'text-red-600' : 'text-green-600'"
                                             x-text="`Stock: ${product.quantity_in_stock}`"></div>
                                        <div class="text-sm font-bold text-gray-900" x-text="formatCurrency(product.selling_price)"></div>
                                    </div>
                                    <div class="bg-indigo-50 text-indigo-700 rounded p-1 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    </div>
                                </div>
                            </button>
                        </template>
                    </div>

                     <!-- Grid View: Packages -->
                     <div x-show="viewMode === 'grid' && activeTab === 'packages'" class="p-4 grid grid-cols-2 lg:grid-cols-3 gap-3">
                         <template x-for="pkg in filteredPackages" :key="pkg.id">
                             <button @click="addPackageToCart(pkg)" class="p-3 border rounded-lg hover:border-purple-300 hover:shadow-md transition-all text-left flex flex-col h-full bg-white group">
                                <div class="mb-2">
                                    <div class="font-bold text-gray-900 text-sm" x-text="pkg.name"></div>
                                    <div class="text-[10px] text-gray-500 font-mono mt-0.5" x-text="pkg.code"></div>
                                </div>
                                <div class="mt-auto flex justify-between items-end border-t pt-2 border-gray-100">
                                    <span class="text-[10px] bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded font-medium" x-text="`${pkg.items_count || 0} Items`"></span>
                                    <div class="text-sm font-bold text-gray-900" x-text="formatCurrency(pkg.base_price)"></div>
                                </div>
                             </button>
                         </template>
                     </div>

                </div>
            </div>

            <!-- 2. CART SECTION (Flex-grow 1, approx 45%) -->
            <div class="flex-1 flex flex-col bg-white shadow rounded-lg overflow-hidden min-w-0 h-full">
                 <div class="px-3 py-2 border-b bg-gray-50 flex justify-between items-center flex-none">
                    <h2 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        Current Cart (<span x-text="cart.length"></span>)
                    </h2>
                    <div class="flex gap-3">
                        <button @click="openLoadCartModal()" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium hover:underline">Load Saved</button>
                        <button @click="saveCart()" x-show="cart.length > 0" class="text-xs text-green-600 hover:text-green-800 font-medium hover:underline">Save Cart</button>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-2 custom-scrollbar bg-gray-50/50">
                    <template x-for="(item, index) in cart" :key="index">
                        <div class="flex flex-col bg-white border border-gray-200 rounded mb-2 shadow-sm relative group overflow-hidden">
                            <!-- Stripe for type -->
                            <div class="absolute left-0 top-0 bottom-0 w-1" :class="item.type === 'rental' ? 'bg-blue-500' : 'bg-indigo-500'"></div>
                            
                            <div class="p-2 pl-3 flex justify-between items-start gap-2">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between">
                                        <div class="text-sm font-medium text-gray-900 leading-tight" x-text="item.product_name"></div>
                                        <div class="text-xs font-bold text-gray-900" x-text="formatCurrency(item.unit_price * item.quantity)"></div>
                                    </div>
                                    
                                    <div class="mt-1 flex flex-wrap gap-2 text-[10px] text-gray-500">
                                        <span x-show="item.size" class="bg-gray-100 px-1.5 rounded text-gray-700" x-text="`${item.size}${item.size_unit || ''}`"></span>
                                        <span x-show="item.type==='rental'" class="bg-blue-50 text-blue-700 px-1.5 rounded border border-blue-100">RENTAL</span>
                                        <span class="text-gray-400" x-show="item.manufacturer" x-text="item.manufacturer"></span>
                                        <span class="text-gray-400 ml-auto" x-text="`@ ${formatCurrency(item.unit_price)}`"></span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Actions footer -->
                            <div class="bg-gray-50 px-2 py-1.5 flex justify-between items-center border-t border-gray-100">
                                <div class="flex items-center space-x-0.5 shadow-sm rounded border bg-white">
                                    <button @click="updateQuantity(index, Number(item.quantity) - 1)" class="w-6 h-6 flex items-center justify-center text-gray-500 hover:text-indigo-600 hover:bg-gray-50 transition-colors font-bold">-</button>
                                    <input 
                                        type="number" 
                                        x-model.number="item.quantity" 
                                        class="w-10 h-6 text-center border-0 p-0 text-xs font-medium focus:ring-0 text-gray-800"
                                    >
                                    <button @click="updateQuantity(index, Number(item.quantity) + 1)" class="w-6 h-6 flex items-center justify-center text-gray-500 hover:text-indigo-600 hover:bg-gray-50 transition-colors font-bold">+</button>
                                </div>
                                
                                <button @click="removeFromCart(index)" class="text-red-400 hover:text-red-600 hover:bg-red-50 p-1 rounded transition-colors group-hover:text-red-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </div>
                    </template>

                     <div x-show="cart.length === 0" class="flex flex-col items-center justify-center h-48 text-gray-400 mt-10">
                        <svg class="w-12 h-12 mb-2 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        <span class="text-sm">Cart is empty</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT SIDE: CHECKOUT & INFO (Span 3) -->
        <div class="lg:col-span-3 bg-white shadow rounded-lg flex flex-col h-full overflow-hidden border border-gray-200">
            <div class="p-4 flex-1 overflow-y-auto space-y-6">
                <!-- 1. Customer Selection -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-2">Customer Details</label>
                    <div class="relative" @click.outside="showCustomerResults = false">
                        <div class="flex shadow-sm rounded-md">
                            <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            </span>
                            <input
                                type="text"
                                x-model="customerQuery"
                                @focus="if (Date.now() > customerResultsSuppressedUntil) showCustomerResults = true"
                                @input="showCustomerResults = true"
                                placeholder="Search customer or Walk-in..."
                                class="flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-r-md border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                            >
                        </div>
                        
                        <!-- Customer Dropdown -->
                         <div x-show="showCustomerResults" class="absolute z-50 mt-1 w-full bg-white shadow-xl rounded-md border border-gray-200 max-h-60 overflow-y-auto" style="display: none;">
                             <button type="button" class="w-full text-left px-4 py-3 hover:bg-gray-50 border-b text-sm text-gray-700" @click="selectWalkInCustomer()">
                                 <span class="font-bold">Walk-in Customer</span>
                             </button>
                             <template x-for="c in filteredCustomers(customerQuery).slice(0, 10)" :key="c.id">
                                 <button type="button" @click="selectCustomer(c)" class="w-full text-left px-4 py-3 hover:bg-indigo-50 border-b flex justify-between items-center group">
                                     <div>
                                         <div class="text-sm font-medium text-gray-900 group-hover:text-indigo-700" x-text="c.name"></div>
                                         <div class="text-xs text-gray-500" x-text="c.phone"></div>
                                     </div>
                                     <div class="text-[10px] text-gray-400 bg-gray-100 px-1.5 py-0.5 rounded" x-text="c.facility || 'Individual'"></div>
                                 </button>
                             </template>
                             <button type="button" class="w-full text-left px-4 py-3 hover:bg-indigo-50 text-indigo-700 text-sm font-bold flex items-center justify-center gap-2 bg-indigo-50/50" @click="openQuickAddCustomer(); showCustomerResults = false">
                                 <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                 Create New Customer
                             </button>
                         </div>
                    </div>

                    <!-- Selected Customer Info -->
                    <div x-show="selectedCustomerId" class="mt-2 text-xs text-indigo-700 bg-indigo-50 p-2 rounded border border-indigo-100 flex justify-between items-center">
                        <span class="font-medium">Verified Customer</span>
                        <button class="text-[10px] underline text-indigo-900 hover:text-indigo-600" @click="selectedCustomerId=null; customerQuery='';">Change</button>
                    </div>
                </div>

                <!-- 2. Payment Details -->
                <div class="pt-4 border-t border-gray-100">
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-2">Payment Method</label>
                    
                    <div class="grid grid-cols-2 gap-2">
                        <template x-for="method in ['Cash', 'M-Pesa', 'Bank', 'Cheque', 'Credit']">
                            <button 
                                type="button"
                                @click="paymentMethod = method; syncDocumentRules()"
                                :class="paymentMethod === method ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                class="px-3 py-2 border rounded-md text-sm font-medium transition-all text-center"
                                x-text="method"
                            ></button>
                        </template>
                    </div>
                </div>

                <!-- 3. Additional Info (LPO / Patient) -->
                <div class="pt-4 border-t border-gray-100" x-show="selectedCustomerId || paymentMethod === 'Credit'">
                    <!-- LPO -->
                    <div x-show="selectedCustomerId" class="mb-3">
                         <label class="block text-xs font-semibold text-gray-500 mb-1">LPO / Contract Reference</label>
                         <div x-show="availableLpos.length > 0">
                            <select x-model="selectedLpoId" class="w-full rounded-md border-gray-300 text-sm py-1.5 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">-- No LPO --</option>
                                <template x-for="lpo in availableLpos" :key="lpo.id">
                                    <option :value="lpo.id" x-text="`${lpo.lpo_number} (Bal: ${formatCurrency(lpo.remaining_balance)})`"></option>
                                </template>
                            </select>
                        </div>
                        <input x-show="availableLpos.length === 0" type="text" x-model="lpoNumber" placeholder="Enter LPO Number" class="w-full rounded border-gray-300 text-sm py-1.5">
                    </div>

                    <!-- Due Date -->
                    <div x-show="paymentMethod === 'Credit'" class="mb-3">
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Payment Due Date</label>
                        <input type="date" x-model="dueDate" class="w-full rounded border-gray-300 text-sm py-1.5">
                    </div>

                    <!-- Patient Info Toggle -->
                    <div x-data="{ showPatient: false }">
                        <button @click="showPatient = !showPatient" class="flex items-center text-xs text-gray-500 hover:text-gray-700">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="showPatient ? 'M19 9l-7 7-7-7' : 'M9 5l7 7-7 7'"></path></svg>
                            Add Patient / Case Details
                        </button>
                        <div x-show="showPatient" class="mt-2 space-y-2 p-3 bg-gray-50 rounded text-xs border border-gray-100">
                            <input type="text" x-model="caseDetails.patient_name" placeholder="Patient Name" class="w-full rounded border-gray-300 text-xs">
                            <input type="text" x-model="caseDetails.patient_number" placeholder="Patient No./File No." class="w-full rounded border-gray-300 text-xs">
                            <input type="text" x-model="caseDetails.facility_name" placeholder="Hospital/Facility" class="w-full rounded border-gray-300 text-xs">
                            <input type="text" x-model="caseDetails.surgeon_name" placeholder="Surgeon" class="w-full rounded border-gray-300 text-xs">
                        </div>
                    </div>
                </div>

            </div>

            <!-- Footer: Totals & Final Action -->
            <div class="p-4 bg-gray-50 border-t flex-none">
                <div class="space-y-2 mb-4">
                     <div class="flex justify-between text-sm text-gray-600">
                        <span>Subtotal</span>
                        <span class="font-medium" x-text="formatCurrency(subtotal)"></span>
                    </div>
                    
                    <div class="flex justify-between items-center text-sm text-gray-600">
                        <span class="flex items-center">
                            Discount
                            <input type="number" x-model="discountRate" class="w-12 ml-2 p-0.5 text-right border-gray-300 rounded text-xs">
                            <span class="ml-1">%</span>
                        </span>
                        <span class="text-red-500" x-show="discountAmount > 0" x-text="`- ${formatCurrency(discountAmount)}`"></span>
                    </div>

                    <div class="flex justify-between items-center text-sm text-gray-600">
                         <span class="flex items-center">
                            VAT
                            <input type="number" x-model="taxRate" class="w-12 ml-6 p-0.5 text-right border-gray-300 rounded text-xs">
                            <span class="ml-1">%</span>
                        </span>
                        <span x-text="formatCurrency(vat)"></span>
                    </div>

                    <div class="flex justify-between text-xl font-black text-gray-900 pt-3 border-t border-gray-200">
                        <span>Total</span>
                        <span x-text="formatCurrency(total)"></span>
                    </div>
                </div>

                <div class="grid grid-cols-4 gap-2">
                    <button 
                        @click="completeSale()"
                        :disabled="cart.length === 0 || processing"
                        class="col-span-3 bg-indigo-600 text-white rounded-md py-3 font-bold shadow-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed flex justify-center items-center gap-2 transform active:scale-[0.98] transition-all"
                    >
                        <svg x-show="!processing" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        <span x-show="!processing">COMPLETE SALE</span>
                        <span x-show="processing">Processing...</span>
                    </button>
                    
                    <button 
                         @click="dispatchConsignment()"
                         :disabled="cart.length === 0 || processing"
                         class="col-span-1 bg-white border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 flex flex-col items-center justify-center p-1 text-[10px] uppercase font-bold tracking-tighter leading-tight"
                         title="Dispatch as Consignment"
                    >
                        <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                        Dispatch
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals (Quick Add, Success) stay unchanged outside the grid -->
    <div x-show="showQuickAddCustomer" ...> ... </div>
    <!-- Note: Closing div for root container matches user code structure implicitly handled by replace logic usually, but here we are replacing a large block. ensuring tags match. -->

    <!-- Quick Add Customer Modal (inside Alpine scope) -->
    <div x-show="showQuickAddCustomer" x-transition.opacity class="fixed z-50 inset-0 overflow-y-auto" style="display: none;" x-cloak @keydown.escape.window="closeQuickAddCustomer()">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="closeQuickAddCustomer()"></div>
            <div class="relative inline-block align-middle bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form @submit.prevent="createCustomerQuick()">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Add Customer</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Name *</label>
                                <input type="text" x-model="newCustomer.name" required class="mt-1 block w-full rounded-md border-gray-300" x-ref="newCustomerName">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Phone</label>
                                    <input type="text" x-model="newCustomer.phone" class="mt-1 block w-full rounded-md border-gray-300">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Email</label>
                                    <input type="email" x-model="newCustomer.email" class="mt-1 block w-full rounded-md border-gray-300">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Facility</label>
                                <input type="text" x-model="newCustomer.facility" class="mt-1 block w-full rounded-md border-gray-300">
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                        <button type="submit" :disabled="creatingCustomer" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed sm:w-auto sm:text-sm">
                            <span x-show="!creatingCustomer">Save & Select</span>
                            <span x-show="creatingCustomer">Saving...</span>
                        </button>
                        <button type="button" @click="closeQuickAddCustomer()" class="w-full inline-flex justify-center rounded-md border border-gray-300 px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Sale Success Modal -->
    <div x-show="showSuccessModal" x-transition.opacity class="fixed z-50 inset-0 overflow-y-auto" style="display: none;" x-cloak>
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity"></div>

            <div class="relative inline-block align-middle bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-sm sm:w-full p-6">
                
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4 animate-[bounce_1s_ease-in-out_1]">
                    <svg class="h-10 w-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>
                
                <div class="text-center">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Sale Successful!</h3>
                    <p class="text-sm text-gray-500 mb-6">
                        Transaction recorded successfully.
                    </p>
                    
                    <div class="flex flex-col gap-3">
                        <button 
                            @click="printDocument('receipt')"
                            class="w-full inline-flex justify-center items-center px-4 py-3 border border-transparent shadow-sm text-sm font-bold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2-4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2-2v4h10z"></path></svg>
                            Print Receipt
                        </button>
                        
                        <button 
                            @click="closeSuccessModal()"
                            class="w-full inline-flex justify-center items-center px-4 py-3 border border-gray-300 shadow-sm text-sm font-bold rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
                        >
                            Start New Sale
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
