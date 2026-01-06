@extends('layouts.app')

@section('content')
<div x-data="inventoryManager()">
        <!-- Header -->
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Inventory Management</h1>
                <p class="mt-2 text-sm text-gray-600">Manage products and stock levels</p>
            </div>
            <div class="flex gap-2">
                <button @click="exportInventory()" class="bg-indigo-100 text-indigo-700 px-4 py-2 rounded-md hover:bg-indigo-200">
                    Export CSV
                </button>
                <button type="button" @click.prevent="showAddModal = true; restoreDraft();" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                    Add Product
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white shadow rounded-lg p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <input 
                        type="text" 
                        x-model="filters.search" 
                        @input.debounce.300ms="loadInventory()"
                        placeholder="Search products..." 
                        class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                    >
                </div>
                <div>
                    <select x-model="filters.category" @change="loadInventory()" class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Categories</option>
                        <template x-for="cat in categories" :key="cat.id">
                            <option :value="cat.name" x-text="cat.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <select x-model="filters.stock_level" @change="loadInventory()" class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Stock Levels</option>
                        <option value="low">Low Stock (≤ Min Level)</option>
                        <option value="out">Out of Stock</option>
                        <option value="expiring">Expiring Soon (90 Days)</option>
                    </select>
                </div>
                <div>
                    <button @click="loadInventory()" class="w-full bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200">
                        Refresh
                    </button>
                </div>
            </div>
        </div>

        <!-- Inventory Table -->
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <div x-show="loading" class="p-8 text-center">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                <p class="mt-2 text-gray-600">Loading...</p>
            </div>

            <div x-show="!loading && inventory.length === 0" class="p-8 text-center text-gray-500">
                No products found
            </div>

            <table x-show="!loading && inventory.length > 0" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Characteristics</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Manufacturer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="item in inventory" :key="item.id">
                        <tr :class="{'bg-red-50': item.quantity_in_stock <= 0, 'bg-yellow-50': item.quantity_in_stock > 0 && item.quantity_in_stock <= 10}">
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <div class="flex items-center gap-1 flex-wrap">
                                        <span class="text-sm font-medium text-gray-900" x-text="item.product_name"></span>
                                        <span x-show="item.is_rentable" class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-indigo-100 text-indigo-800 border border-indigo-200">Rentable</span>
                                        <!-- Material Badge -->
                                        <!-- Dynamic Attribute Badges -->
                                        <template x-if="item.attributes">
                                            <template x-for="(value, key) in item.attributes" :key="key">
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold border uppercase tracking-wider bg-indigo-50 text-indigo-700 border-indigo-100 mr-1" 
                                                      x-text="key + ': ' + value"></span>
                                            </template>
                                        </template>
                                    </div>
                                    <span class="text-xs text-gray-400 font-mono mt-0.5" x-text="item.code || 'No Code'"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-col gap-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800" x-text="item.category || 'Uncategorized'"></span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800" x-show="item.subcategory" x-text="item.subcategory"></span>
                                    <span class="text-xs text-gray-500 mt-1" x-show="item.type">Type: <span class="font-medium" x-text="item.type"></span></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div x-show="item.size" class="text-sm text-gray-900 font-medium">
                                    <span x-text="item.size"></span>
                                    <span x-show="item.size_unit" x-text="item.size_unit" class="text-xs text-gray-500 ml-0.5"></span>
                                </div>
                                <div x-show="!item.size" class="text-xs text-gray-400">-</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-900" x-text="item.manufacturer || '-'"></span>
                                <div x-show="item.country_of_manufacture" class="text-xs text-gray-500" x-text="item.country_of_manufacture"></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-col items-start gap-1">
                                    <span 
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                        :class="{
                                            'bg-red-100 text-red-800': item.quantity_in_stock <= 0,
                                            'bg-yellow-100 text-yellow-800': item.quantity_in_stock > 0 && item.quantity_in_stock <= (item.min_stock_level || 10),
                                            'bg-green-100 text-green-800': item.quantity_in_stock > (item.min_stock_level || 10)
                                        }"
                                        x-text="item.quantity_in_stock + ' ' + (item.unit || 'pcs')"
                                    ></span>
                                    <span x-show="item.quantity_in_stock <= (item.min_stock_level || 10)" class="text-[10px] text-red-500 font-medium">Low Stock</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium" x-text="formatCurrency(item.selling_price)"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button @click="editItem(item)" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</button>
                                <button @click="restockItem(item)" class="text-green-600 hover:text-green-900 mr-4">Restock</button>
                                <button @click="openAdjustModal(item)" class="text-amber-600 hover:text-amber-900 mr-4">Adjust</button>
                                <button @click="openAdjustmentsModal(item)" class="text-slate-600 hover:text-slate-900 mr-4">History</button>
                                <button @click="deleteItem(item)" class="text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

    <!-- Add/Edit Modal -->
    <div x-show="showAddModal || showEditModal" class="fixed z-50 inset-0 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="closeModal()"></div>
            <div class="relative bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:w-full sm:max-w-6xl flex flex-col max-h-[92vh]">
                <form @submit.prevent="saveItem()" class="flex flex-col flex-1 min-h-0">
                    <div class="bg-white px-4 pt-3 pb-2 overflow-y-auto flex-1">
                        <h3 class="text-base leading-5 font-medium text-gray-900 mb-2" x-text="showAddModal ? 'Add Product' : 'Edit Product'"></h3>
                        
                        <!-- Progress Steps Indicator -->
                        <div class="flex items-center justify-between mb-3 px-2">
                            <div class="flex items-center" :class="form.category ? 'text-indigo-600' : 'text-gray-400'">
                                <div class="flex items-center justify-center w-8 h-8 rounded-full border-2" :class="form.category ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-gray-300'">
                                    <span class="text-xs font-bold">1</span>
                                </div>
                                <span class="ml-2 text-xs font-medium">Category</span>
                            </div>
                            <div class="flex-1 h-0.5 mx-2" :class="form.category && form.subcategory ? 'bg-indigo-600' : 'bg-gray-300'"></div>
                            <div class="flex items-center" :class="form.category && form.subcategory ? 'text-indigo-600' : 'text-gray-400'">
                                <div class="flex items-center justify-center w-8 h-8 rounded-full border-2" :class="form.category && form.subcategory ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-gray-300'">
                                    <span class="text-xs font-bold">2</span>
                                </div>
                                <span class="ml-2 text-xs font-medium">Specifications</span>
                            </div>
                            <div class="flex-1 h-0.5 mx-2" :class="currentAttributes.length > 0 && Object.keys(form.attributes).length > 0 ? 'bg-indigo-600' : 'bg-gray-300'"></div>
                            <div class="flex items-center" :class="currentAttributes.length > 0 && Object.keys(form.attributes).length > 0 ? 'text-indigo-600' : 'text-gray-400'">
                                <div class="flex items-center justify-center w-8 h-8 rounded-full border-2" :class="currentAttributes.length > 0 && Object.keys(form.attributes).length > 0 ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-gray-300'">
                                    <span class="text-xs font-bold">3</span>
                                </div>
                                <span class="ml-2 text-xs font-medium">Pricing</span>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <!-- STEP 1: Category Selection -->
                            <div class="bg-white border-2 border-indigo-100 rounded-lg p-2">
                                <div class="flex items-center mb-2">
                                    <div class="flex items-center justify-center w-5 h-5 rounded-full bg-indigo-600 text-white text-xs font-bold mr-1.5">1</div>
                                    <h4 class="text-xs font-bold text-gray-900">Select Category & Subcategory</h4>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Category *</label>
                                        <select 
                                            x-model="form.category" 
                                            @change="loadSubcategoriesForCategory(); form.subcategory = ''; form.type = ''; currentAttributes = []; form.attributes = {}; $nextTick(() => $refs.subcategorySelect?.focus());"
                                            required
                                            x-init="$nextTick(() => $el.focus())"
                                            class="block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="">Choose a category...</option>
                                            <template x-for="cat in categories" :key="cat.id">
                                                <option :value="cat.name" x-text="cat.name"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Subcategory *</label>
                                        <select 
                                            x-model="form.subcategory" 
                                            x-ref="subcategorySelect"
                                            :disabled="!form.category || subcategories.length === 0"
                                            required
                                            class="block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100 disabled:cursor-not-allowed">
                                            <option value="">Choose a subcategory...</option>
                                            <template x-for="sub in subcategories" :key="sub.id">
                                                <option :value="sub.name" x-text="sub.name"></option>
                                            </template>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Auto-generated Product Name Display -->
                            <div x-show="form.subcategory" x-transition class="bg-blue-50 border border-blue-200 rounded-md p-2">
                                <label class="block text-xs font-medium text-blue-700 mb-0.5">Generated Product Name Preview</label>
                                <div class="text-xs font-semibold text-blue-900" x-text="generateProductName()"></div>
                            </div>

                            <!-- STEP 2: Dynamic Attributes/Specifications -->
                            <div x-show="form.category && form.subcategory && currentAttributes.length > 0" x-transition class="bg-white border-2 border-indigo-100 rounded-lg p-2">
                                <div class="flex items-center mb-2">
                                    <div class="flex items-center justify-center w-5 h-5 rounded-full bg-indigo-600 text-white text-xs font-bold mr-1.5">2</div>
                                    <h4 class="text-xs font-bold text-gray-900">Product Specifications</h4>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                    <template x-for="attr in currentAttributes" :key="attr.id">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700" x-text="attr.name + (attr.is_required ? ' *' : '')"></label>
                                            
                                            <!-- Select Input -->
                                            <template x-if="attr.type === 'select'">
                                                <select 
                                                    x-model="form.attributes[attr.slug]" 
                                                    @change="saveDraft()"
                                                    :required="attr.is_required"
                                                    class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                    <option value="">Select...</option>
                                                    <template x-for="option in attr.options" :key="option.id">
                                                        <option :value="option.value" x-text="option.value"></option>
                                                    </template>
                                                </select>
                                            </template>

                                            <!-- Text/Number Input -->
                                            <template x-if="attr.type === 'text' || attr.type === 'number'">
                                                <div class="relative rounded-md shadow-sm mt-1">
                                                    <input 
                                                        :type="attr.type" 
                                                        x-model="form.attributes[attr.slug]" 
                                                        @input="saveDraft()"
                                                        :required="attr.is_required"
                                                        class="block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                        :class="{'pr-12': attr.unit}">
                                                    <div x-show="attr.unit" class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                                        <span class="text-gray-500 sm:text-sm" x-text="attr.unit"></span>
                                                    </div>
                                                </div>
                                            </template>

                                            <!-- Date Input -->
                                            <template x-if="attr.type === 'date'">
                                                <input 
                                                    type="date" 
                                                    x-model="form.attributes[attr.slug]" 
                                                    @input="saveDraft()"
                                                    :required="attr.is_required"
                                                    class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            </template>

                                            <!-- Boolean Checkbox -->
                                            <template x-if="attr.type === 'boolean'">
                                                <div class="mt-2 flex items-center">
                                                    <input 
                                                        type="checkbox" 
                                                        :id="'attr_' + attr.slug"
                                                        x-model="form.attributes[attr.slug]"
                                                        @change="saveDraft()"
                                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                                    <label :for="'attr_' + attr.slug" class="ml-2 block text-sm text-gray-900">
                                                        Yes
                                                    </label>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            
                            <!-- STEP 3: Additional Details (Type & Size) -->
                            <div x-show="form.subcategory" x-transition class="bg-white border-2 border-gray-100 rounded-lg p-2">
                                <div class="flex items-center mb-2">
                                    <div class="flex items-center justify-center w-5 h-5 rounded-full bg-gray-400 text-white text-xs font-bold mr-1.5">3</div>
                                    <h4 class="text-xs font-semibold text-gray-700">Additional Details (Optional)</h4>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div x-show="showType">
                                        <label class="block text-xs font-medium text-gray-700">Type</label>
                                        <input 
                                            type="text" 
                                            x-model="form.type" 
                                            list="type-list" 
                                            class="mt-1 block w-full rounded-md border-gray-300 text-sm"
                                            placeholder="e.g., Cannulated">
                                        <datalist id="type-list">
                                            <template x-for="t in types" :key="t">
                                                <option :value="t"></option>
                                            </template>
                                        </datalist>
                                    </div>        <div x-show="showSize">
                                        <label class="block text-xs font-medium text-gray-700">Size</label>
                                        <div class="flex mt-1">
                                            <input 
                                                type="text" 
                                                x-model="form.size" 
                                                list="size-list" 
                                                class="block w-full rounded-l-md border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                                                placeholder="Value">
                                            <select x-model="form.size_unit" class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                                <option value="">Unit</option>
                                                <option value="mm">mm</option>
                                                <option value="cm">cm</option>
                                                <option value="holes">holes</option>
                                                <option value="">S/M/L</option>
                                            </select>
                                        </div>
                                        <datalist id="size-list">
                                            <template x-for="s in sizes" :key="s">
                                                <option :value="s"></option>
                                            </template>
                                        </datalist>
                                    </div>
                                </div>
                            </div>

                            <!-- Main Product Attributes - 3 columns (This section is removed as its content is moved to steps) -->
                            <!-- <div class="grid grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">Category *</label>
                                    <input 
                                        type="text" 
                                        x-model="form.category" 
                                        @input="loadSubcategoriesForCategory(); saveDraft()" 
                                        list="category-list" 
                                        required
                                        class="mt-1 block w-full rounded-md border-gray-300 text-sm"
                                        placeholder="e.g., Implant">
                                    <datalist id="category-list">
                                        <template x-for="cat in categories" :key="cat.id">
                                            <option :value="cat.name"></option>
                                        </template>
                                    </datalist>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">Subcategory *</label>
                                    <input 
                                        type="text" 
                                        x-model="form.subcategory" 
                                        @input="saveDraft()" 
                                        list="subcategory-list" 
                                        required
                                        class="mt-1 block w-full rounded-md border-gray-300 text-sm"
                                        placeholder="e.g., Screw">
                                    <datalist id="subcategory-list">
                                        <template x-for="sub in subcategories" :key="sub.id">
                                            <option :value="sub.name"></option>
                                        </template>
                                    </datalist>
                                </div>
                                <div x-show="showType">
                                    <label class="block text-xs font-medium text-gray-700">Type *</label>
                                    <input 
                                        type="text" 
                                        x-model="form.type" 
                                        @input="saveDraft()" 
                                        list="type-list" 
                                        required
                                        class="mt-1 block w-full rounded-md border-gray-300 text-sm"
                                        placeholder="e.g., Cannulated">
                                    <datalist id="type-list">
                                        <template x-for="t in types" :key="t">
                                            <option :value="t"></option>
                                        </template>
                                    </datalist>
                            </div>
                                    <div x-show="showSize">
                                        <label class="block text-xs font-medium text-gray-700">Size</label>
                                        <div class="flex mt-1">
                                            <input 
                                                type="text" 
                                                x-model="form.size" 
                                                list="size-list" 
                                                class="block w-full rounded-l-md border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                                                placeholder="Value">
                                            <select x-model="form.size_unit" class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                                <option value="">Unit</option>
                                                <option value="mm">mm</option>
                                                <option value="cm">cm</option>
                                                <option value="holes">holes</option>
                                                <option value="">S/M/L</option>
                                            </select>
                                        </div>
                                        <datalist id="size-list">
                                            <template x-for="s in sizes" :key="s">
                                                <option :value="s"></option>
                                            </template>
                                        </datalist>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2 pt-2">
                                    <input id="is_rentable" type="checkbox" x-model="form.is_rentable" class="h-4 w-4 rounded border-gray-300 text-indigo-600">
                                    <label for="is_rentable" class="text-xs text-gray-700 font-medium">Mark as Rentable Asset</label>
                                </div>
                            </div>
                            -->

                            
                            <!-- STEP 4: Stock & Pricing -->
                            <div x-show="form.subcategory" x-transition class="bg-white border-2 border-indigo-100 rounded-lg p-2">
                                <div class="flex items-center mb-2">
                                    <div class="flex items-center justify-center w-5 h-5 rounded-full bg-indigo-600 text-white text-xs font-bold mr-1.5">4</div>
                                    <h4 class="text-xs font-bold text-gray-900">Pricing & Stock Levels</h4>
                                </div>
                            <div class="grid grid-cols-3 gap-2">
                                <div x-show="showStock">
                                    <label class="block text-xs font-medium text-gray-700">Stock *</label>
                                    <input type="number" x-model="form.quantity_in_stock" @input="saveDraft()" required min="0" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">Buying Price *</label>
                                    <input type="number" step="0.01" x-model="form.price" @input="saveDraft()" required min="0" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">Selling Price *</label>
                                    <input type="number" step="0.01" x-model="form.selling_price" @input="saveDraft()" required min="0" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                                </div>
                                
                                <!-- Stock Levels -->
                                <div class="grid grid-cols-3 gap-3 mt-3 col-span-3" x-show="showStock">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">Min Stock</label>
                                    <input type="number" x-model="form.min_stock_level" @input="saveDraft()" min="0" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">Reorder Threshold</label>
                                    <input type="number" x-model="form.reorder_threshold" @input="saveDraft()" min="0" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">Max Stock</label>
                                    <input type="number" x-model="form.max_stock" @input="saveDraft()" min="0" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                                </div>
                            </div>
                            
                            <!-- Advanced Details Toggle -->
                            <div class="flex items-center justify-between border-t border-b border-gray-100 py-2">
                                <button type="button" @click="showAdvanced = !showAdvanced" class="text-xs text-indigo-600 font-semibold hover:text-indigo-800 flex items-center focus:outline-none">
                                    <span x-text="showAdvanced ? 'Hide Advanced Details' : 'Show Advanced Details (Manufacturer, Batch, Expiry)'"></span>
                                    <svg class="w-4 h-4 ml-1 transform transition-transform" :class="{'rotate-180': showAdvanced}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </button>
                            </div>

                            <!-- Advanced Fields -->
                            <div x-show="showAdvanced" class="grid grid-cols-3 gap-3 bg-gray-50 p-3 rounded-md animate-fade-in-down" x-transition>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">Unit</label>
                                    <input type="text" x-model="form.unit" class="mt-1 block w-full rounded-md border-gray-300 text-sm" placeholder="pcs">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">Manufacturer</label>
                                    <input type="text" x-model="form.manufacturer" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">Country of Origin</label>
                                    <input type="text" x-model="form.country_of_manufacture" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">Batch Number</label>
                                    <input type="text" x-model="form.batch_number" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">Expiry Date</label>
                                    <input type="date" x-model="form.expiry_date" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700">Packaging Unit</label>
                                    <input type="text" x-model="form.packaging_unit" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                                </div>
                                <div class="col-span-3">
                                    <label class="block text-xs font-medium text-gray-700">Description/Notes</label>
                                    <textarea x-model="form.description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 text-sm" placeholder="Additional specifications..."></textarea>
                                </div>
                            </div>
                            </div>
                        </div>
                    </div>
                    </div>
                    <div class="bg-white border-t border-gray-100 px-4 py-2 flex justify-end gap-2 flex-none shrink-0 z-10 relative">
                        <button type="button" @click="closeModal()" class="inline-flex justify-center rounded-lg border border-gray-300 px-4 py-2 bg-white text-xs font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-sm transition-colors">
                            Cancel
                        </button>
                        <button type="submit" class="inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-xs font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                            Save Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Restock Modal -->
    <div x-show="showRestockModal" class="fixed z-50 inset-0 overflow-y-auto" style="display: none;" @keydown.escape.window="showRestockModal = false">
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="showRestockModal = false"></div>
            <div class="relative bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:w-full sm:max-w-lg flex flex-col">
                <form @submit.prevent="confirmRestock()">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Restock Product</h3>
                        <p class="text-sm text-gray-600 mb-4" x-text="`Adding stock to: ${targetRestockItem.product_name || ''}`"></p>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Quantity to Add *</label>
                            <input type="number" x-model="restockQuantity" required min="1" class="mt-1 block w-full rounded-md border-gray-300">
                        </div>
                    </div>
                    <div class="bg-white border-t border-gray-100 px-6 py-4 flex justify-end gap-3 flex-none shrink-0 z-10 relative">
                        <button type="button" @click="showRestockModal = false" class="inline-flex justify-center rounded-lg border border-gray-300 px-5 py-2.5 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-sm transition-colors">
                            Cancel
                        </button>
                        <button type="submit" class="inline-flex justify-center rounded-lg border border-transparent shadow-sm px-5 py-2.5 bg-green-600 text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                            Restock
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Adjustment Modal -->
    <div x-show="showAdjustModal" class="fixed z-50 inset-0 overflow-y-auto" style="display: none;" @keydown.escape.window="closeAdjustModal()">
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="closeAdjustModal()"></div>
            <div class="relative bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:w-full sm:max-w-lg flex flex-col">
                <form @submit.prevent="saveAdjustment()">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-2">Adjust Stock</h3>
                        <p class="text-sm text-gray-600 mb-4">
                            <span class="font-medium" x-text="adjustItem.product_name || ''"></span>
                            <span class="text-gray-400">•</span>
                            Current: <span class="font-medium" x-text="adjustItem.quantity_in_stock ?? '-'"></span>
                        </p>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Adjustment Type *</label>
                                <select x-model="adjustmentForm.adjustment_type" @change="adjustmentForm.reasonSelect = ''; adjustmentForm.reason = ''" required class="mt-1 block w-full rounded-md border-gray-300">
                                    <option value="increase">Increase (add)</option>
                                    <option value="decrease">Decrease (remove)</option>
                                    <option value="set">Set exact stock</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    Quantity *
                                </label>
                                <input type="number" x-model.number="adjustmentForm.quantity" required min="0" class="mt-1 block w-full rounded-md border-gray-300">
                                <p class="text-xs text-gray-500 mt-1">
                                    For Increase/Decrease, quantity is the amount to change. For Set, quantity is the new stock value.
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Reason *</label>
                                <select x-model="adjustmentForm.reasonSelect" @change="adjustmentForm.reason = (adjustmentForm.reasonSelect === 'Other') ? '' : adjustmentForm.reasonSelect" required class="mt-1 block w-full rounded-md border-gray-300">
                                    <option value="" disabled>Select a reason</option>
                                    <template x-for="r in formattedReasons" :key="r">
                                        <option :value="r" x-text="r"></option>
                                    </template>
                                </select>
                            </div>
                            <div x-show="adjustmentForm.reasonSelect === 'Other'">
                                <label class="block text-sm font-medium text-gray-700 mt-2">Specify Reason *</label>
                                <input type="text" x-model="adjustmentForm.reason" :required="adjustmentForm.reasonSelect === 'Other'" class="mt-1 block w-full rounded-md border-gray-300" placeholder="Please specify...">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Notes</label>
                                <textarea x-model="adjustmentForm.notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300" placeholder="Optional details..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white border-t border-gray-100 px-6 py-4 flex justify-end gap-3 flex-none shrink-0 z-10 relative">
                        <button type="button" @click="closeAdjustModal()" class="inline-flex justify-center rounded-lg border border-gray-300 px-5 py-2.5 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-sm transition-colors">
                            Cancel
                        </button>
                        <button type="submit" class="inline-flex justify-center rounded-lg border border-transparent shadow-sm px-5 py-2.5 bg-amber-600 text-sm font-medium text-white hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition-colors">
                            Save Adjustment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Adjustments History Modal -->
    <div x-show="showAdjustmentsModal" class="fixed z-50 inset-0 overflow-y-auto" style="display: none;" @keydown.escape.window="closeAdjustmentsModal()">
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="closeAdjustmentsModal()"></div>
            <div class="relative bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:w-full sm:max-w-3xl flex flex-col">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Stock Adjustment History</h3>
                            <p class="text-sm text-gray-600" x-text="adjustItem.product_name || ''"></p>
                        </div>
                        <button class="text-gray-500 hover:text-gray-700" @click="closeAdjustmentsModal()">Close</button>
                    </div>

                    <div class="overflow-x-auto border rounded-md">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">When</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">From → To</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="a in adjustments" :key="a.id">
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-700" x-text="formatDateTime(a.created_at)"></td>
                                        <td class="px-4 py-2 text-sm text-gray-700" x-text="a.adjustment_type"></td>
                                        <td class="px-4 py-2 text-sm text-gray-700" x-text="a.quantity"></td>
                                        <td class="px-4 py-2 text-sm text-gray-700" x-text="`${a.old_quantity} → ${a.new_quantity}`"></td>
                                        <td class="px-4 py-2 text-sm text-gray-700">
                                            <div class="font-medium" x-text="a.reason"></div>
                                            <div class="text-xs text-gray-500" x-show="a.notes" x-text="a.notes"></div>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="!adjustmentsLoading && adjustments.length === 0">
                                    <td colspan="5" class="px-4 py-6 text-sm text-gray-500 text-center">No adjustments yet</td>
                                </tr>
                                <tr x-show="adjustmentsLoading">
                                    <td colspan="5" class="px-4 py-6 text-sm text-gray-500 text-center">Loading…</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.inventoryManager = function() {
    return {
        inventory: [],
        categories: @json($categories),
        subcategories: @json($subcategories),
        types: [],
        sizes: [],
        loading: false,
        showAddModal: false,
        showEditModal: false,
        showAdvanced: false, // For progressive disclosure
        showRestockModal: false,
        showAdjustModal: false,
        showAdjustmentsModal: false,
        targetRestockItem: {},
        restockQuantity: 1,
        adjustItem: {},
        adjustmentForm: {
            adjustment_type: 'increase',
            quantity: 1,
            reason: '',
            reasonSelect: '',
            notes: ''
        },
        adjustments: [],
        adjustmentsLoading: false,
        filters: {
            search: '',
            category: '',
            stock_level: ''
        },
        form: {
            id: null,
            category: '',
            subcategory: '',
            type: '',
            size: '',
            size_unit: '',
            code: '',
            unit: 'pcs',
            quantity_in_stock: 0,
            min_stock_level: 10,
            max_stock: 100,
            reorder_threshold: 20,
            expiry_date: '',
            batch_number: '',
            is_rentable: false,
            country_of_manufacture: '',
            packaging_unit: '',
            price: 0,
            selling_price: 0,
            manufacturer: '',
            description: '',
            // Attributes container for JSON
            attributes: {}
        },
        currentAttributes: [],
        reasonsMap: {
            'increase': ['Restock', 'Return from Customer', 'Found Missing Stock', 'Correction', 'Other'],
            'decrease': ['Damaged', 'Expired', 'Lost', 'Stolen', 'Sample/Demo', 'Correction', 'Other'],
            'correction': ['Data Entry Error', 'Audit Adjustment', 'System Error', 'Other']
        },

        init() {
            this.loadInventory(); 
            this.loadTypes(); 
            this.loadSizes();

            this.$watch('form.category', (value) => {
                const category = this.categories.find(c => c.name === value);
                if (category && category.attributes) {
                    this.currentAttributes = category.attributes;
                } else {
                    this.currentAttributes = [];
                }
            });
        },

        get showType() {
            return !['implant', 'sterile'].includes(this.currentArchetype);
        },
        
        get showSize() {
            return !['implant'].includes(this.currentArchetype);
        },

        get showExpiry() {
            return !['asset'].includes(this.currentArchetype);
        },

        get showStock() {
            // Hides Stock Levels (Min/Max/Reorder) and current Stock for Assets (managed differently)
            return !['asset'].includes(this.currentArchetype);
        },

        applyArchetype() {
            // Reset modules
            this.activeModules = { ortho: false, clinical: false, dimensions: false, classification: false, asset: false };
            
            // Activate based on selection
            switch(this.currentArchetype) {
                case 'implant': 
                    this.activeModules.ortho = true; 
                    this.activeModules.dimensions = true; 
                    break;
                case 'sterile': 
                    this.activeModules.clinical = true; 
                    break;
                case 'consumable': 
                    this.activeModules.dimensions = true; 
                    break;
                case 'support': 
                    this.activeModules.classification = true; 
                    this.activeModules.ortho = true; // For Side
                    break;
                case 'asset': 
                    this.activeModules.asset = true; 
                    this.form.is_rentable = true;
                    // Default stock for assets
                    this.form.quantity_in_stock = 1;
                    this.form.min_stock_level = 0;
                    this.form.max_stock = 1;
                    break;
                case 'custom':
                    // User toggles manually
                    break;
            }
        },

        get formattedReasons() {
            return this.reasonsMap[this.adjustmentForm.adjustment_type] || [];
        },

        generateProductName() {
            const parts = [this.form.subcategory, this.form.type];

            // Dynamic Attributes
            this.currentAttributes.forEach(attr => {
                const val = this.form.attributes[attr.slug];
                if (val) {
                    if (attr.unit) {
                        parts.push(val + attr.unit);
                    } else {
                        parts.push(val);
                    }
                }
            });

            // Fallback for standard fields if not in attributes
            if (this.form.size && this.form.size_unit) {
                parts.push(this.form.size + this.form.size_unit);
            } else if (this.form.size) {
                parts.push(this.form.size);
            }

            return parts.filter(Boolean).join(' ') || 'Enter attributes...';
        },

        saveDraft() {
            // Auto-save form data to localStorage
            try {
                localStorage.setItem('inventory_draft', JSON.stringify(this.form));
            } catch (e) {
                console.error('Failed to save draft:', e);
            }
        },

        restoreDraft() {
            // Restore draft from localStorage
            try {
                const draft = localStorage.getItem('inventory_draft');
                if (draft) {
                    const parsed = JSON.parse(draft);
                    // Only restore if it's a new item (no ID)
                    if (!parsed.id || parsed.id === null) {
                        this.form = {...this.form, ...parsed};
                        if (this.form.category) {
                            this.loadSubcategoriesForCategory();
                        }
                    }
                }
            } catch (e) {
                console.error('Failed to restore draft:', e);
            }
        },

        clearDraft() {
            try {
                localStorage.removeItem('inventory_draft');
            } catch (e) {
                console.error('Failed to clear draft:', e);
            }
        },

        async loadSubcategoriesForCategory() {
            if (!this.form.category) {
                this.subcategories = [];
                return;
            }
            try {
                const response = await axios.get(`/categories/${encodeURIComponent(this.form.category)}/subcategories`);
                this.subcategories = response.data;
            } catch (error) {
                console.error('Error loading subcategories:', error);
                this.subcategories = [];
            }
        },

        async loadInventory() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.filters.search) params.append('search', this.filters.search);
                if (this.filters.category) params.append('category', this.filters.category);
                if (this.filters.stock_level === 'expiring') {
                    params.append('expiring_soon', 'true');
                } else if (this.filters.stock_level) {
                    params.append('stock_level', this.filters.stock_level);
                }

                const response = await axios.get(`/inventory?${params}`);
                this.inventory = response.data.data || response.data;
            } catch (error) {
                console.error('Error loading inventory:', error);
                alert('Error loading inventory');
            } finally {
                this.loading = false;
            }
        },

        exportInventory() {
            const params = new URLSearchParams();
            if (this.filters.search) params.append('search', this.filters.search);
            if (this.filters.category) params.append('category', this.filters.category);
            if (this.filters.stock_level) params.append('stock_level', this.filters.stock_level);
            params.append('export', 'true');
            
            window.location.href = `/inventory?${params.toString()}`;
        },

        async loadCategories() {
            try {
                const response = await axios.get('/categories');
                this.categories = response.data;
            } catch (error) {
                console.error('Error loading categories:', error);
            }
        },

        async loadTypes() {
            try {
                const response = await axios.get('/inventory/types');
                this.types = response.data;
            } catch (error) {
                console.error('Error loading types:', error);
            }
        },

        async loadSizes() {
            try {
                const response = await axios.get('/inventory/sizes');
                this.sizes = response.data;
            } catch (error) {
                console.error('Error loading sizes:', error);
            }
        },

        editItem(item) {
            this.form = {...item};
            this.form.is_rentable = Boolean(item.is_rentable);
            
            // Assign attributes directly
            this.form.attributes = item.attributes ? JSON.parse(JSON.stringify(item.attributes)) : {};
            
            // Trigger category change to load currentAttributes (definitions)
            // We need to wait for categories to load if they haven't; init does this.
            // But we can manually ensure currentAttributes is set if categories are loaded
            const category = this.categories.find(c => c.name === item.category);
            if (category && category.attributes) {
                this.currentAttributes = category.attributes;
            } else {
                this.currentAttributes = [];
            }

            this.attributesJson = JSON.stringify(this.form.attributes, null, 2);
            this.showEditModal = true;
            // Load subcategories for the selected category
            if (this.form.category) {
                this.loadSubcategoriesForCategory();
            }
        },


        restockItem(item) {
            console.log('Restock button clicked', item);
            this.targetRestockItem = item;
            this.restockQuantity = 1;
            this.showRestockModal = true;
            console.log('showRestockModal set to:', this.showRestockModal);
            // Debug: Check if modal element exists and its display state
            setTimeout(() => {
                const modal = document.querySelector('[x-show="showRestockModal"]');
                if (modal) {
                    console.log('Modal element found');
                    console.log('Has x-cloak:', modal.hasAttribute('x-cloak'));
                    console.log('Computed display:', window.getComputedStyle(modal).display);
                    console.log('Inline style:', modal.style.display);
                } else {
                    console.log('Modal element NOT found');
                }
            }, 100);
        },

        async confirmRestock() {
            try {
                await axios.post(`/inventory/${this.targetRestockItem.id}/restock`, {
                    quantity: this.restockQuantity
                });
                this.showRestockModal = false;
                // Reset state
                this.targetRestockItem = {};
                this.restockQuantity = 1;
                // Reload inventory to show updated stock
                await this.loadInventory();
                alert('Stock updated successfully');
            } catch (error) {
                alert('Error restocking: ' + (error.response?.data?.message || error.message));
            }
        },

        openAdjustModal(item) {
            console.log('Adjust button clicked', item);
            this.adjustItem = item;
            this.adjustmentForm = {
                adjustment_type: 'increase',
                quantity: 1,
                reason: '',
                reasonSelect: '',
                notes: ''
            };
            this.showAdjustModal = true;
        },

        closeAdjustModal() {
            this.showAdjustModal = false;
            this.adjustItem = {};
        },

        async saveAdjustment() {
            try {
                await axios.post(`/inventory/${this.adjustItem.id}/adjust`, this.adjustmentForm);
                this.showAdjustModal = false;
                await this.loadInventory();
                alert('Stock adjusted successfully');
            } catch (error) {
                const msg = error.response?.data?.message || error.message;
                alert('Error adjusting stock: ' + msg);
            }
        },

        async openAdjustmentsModal(item) {
            console.log('History button clicked', item);
            this.adjustItem = item;
            this.showAdjustmentsModal = true;
            await this.loadAdjustments(item);
        },

        closeAdjustmentsModal() {
            this.showAdjustmentsModal = false;
            this.adjustments = [];
            this.adjustmentsLoading = false;
            this.adjustItem = {};
        },

        async loadAdjustments(item) {
            this.adjustmentsLoading = true;
            try {
                const res = await axios.get(`/inventory/${item.id}/adjustments?per_page=50`);
                this.adjustments = res.data?.data || [];
            } catch (error) {
                alert('Error loading adjustments: ' + (error.response?.data?.message || error.message));
            } finally {
                this.adjustmentsLoading = false;
            }
        },

        async saveItem() {
            try {
                // Attributes are already in this.form.attributes
                // We just need to ensure numeric values are treated as such if needed, but for JSON storage string is fine mostly
                // Depending on backend casting. The form binding (x-model) puts values in this.form.attributes.

                // Ensure we don't send individual legacy fields if they are not in the form attributes
                // The backend store/update expects 'attributes' array.
                // this.form.attributes is the source of truth.

                // Auto-generate name before saving
                this.form.product_name = this.generateProductName();

                if (this.form.id) {
                    await axios.put(`/inventory/${this.form.id}`, this.form);
                } else {
                    await axios.post('/inventory', this.form);
                }
                this.closeModal();
                this.clearDraft(); // Clear draft after successful save
                this.loadInventory();
                this.loadCategories();
                alert('Product saved successfully');
            } catch (error) {
                alert('Error saving product: ' + (error.response?.data?.message || error.message));
            }
        },

        async deleteItem(item) {
            if (!confirm(`Are you sure you want to delete ${item.product_name}?`)) return;
            
            try {
                await axios.delete(`/inventory/${item.id}`);
                this.loadInventory();
                alert('Product deleted successfully');
            } catch (error) {
                alert('Error deleting product: ' + (error.response?.data?.message || error.message));
            }
        },

        closeModal() {
            this.showAddModal = false;
            this.showEditModal = false;
            this.showAdvanced = false;
            this.currentAttributes = [];
            this.subcategories = [];
            
            // Clear the draft from localStorage
            this.clearDraft();
            
            // Reset form to initial state
            this.form = {
                id: null,
                category: '',
                subcategory: '',
                type: '',
                size: '',
                size_unit: '',
                code: '',
                unit: 'pcs',
                quantity_in_stock: 0,
                min_stock_level: 10,
                max_stock: 100,
                reorder_threshold: 20,
                expiry_date: '',
                batch_number: '',
                is_rentable: false,
                country_of_manufacture: '',
                packaging_unit: '',
                price: 0,
                selling_price: 0,
                manufacturer: '',
                description: '',
                attributes: {}
            };
            this.attributesJson = '{}';
        },

        formatCurrency(amount) {
            return 'KSh ' + parseFloat(amount || 0).toLocaleString('en-KE', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },

        formatDateTime(value) {
            if (!value) return '-';
            const d = new Date(value);
            if (isNaN(d.getTime())) return String(value);
            return d.toLocaleString();
        }
    }
}
</script>
@endsection

