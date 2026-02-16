@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="caseManager()">
    <!-- Header -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('reservations.index') }}" class="hover:text-gray-900">Cases</a>
                <i class="fas fa-chevron-right text-xs"></i>
                <span>{{ $reservation->case_number }}</span>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-3">
                {{ $reservation->patient_name }}
                @php
                    $statusClass = match($reservation->status) {
                        'draft' => 'bg-gray-100 text-gray-800',
                        'confirmed' => 'bg-yellow-100 text-yellow-800',
                        'completed' => 'bg-green-100 text-green-800',
                        'cancelled' => 'bg-red-100 text-red-800',
                        default => 'bg-gray-100 text-gray-800',
                    };
                @endphp
                <span class="px-3 py-1 text-sm font-semibold rounded-full {{ $statusClass }}">
                    {{ ucfirst($reservation->status) }}
                </span>
            </h1>
            <p class="text-gray-600 mt-1">
                {{ $reservation->procedure_name }} • {{ $reservation->surgeon_name }} • {{ $reservation->surgery_date->format('M d, Y @ h:i A') }}
            </p>
        </div>
        
        <div class="flex gap-3">
            @if($reservation->status === 'draft')
                <button @click="openAddModal()" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                    <i class="fas fa-plus mr-2"></i> Add Item
                </button>
                <form action="{{ route('reservations.confirm', $reservation->id) }}" method="POST" onsubmit="return confirm('Confirming will DEDUCT stock from inventory. Continue?');">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700" {{ $reservation->items->isEmpty() ? 'disabled opacity-50' : '' }}>
                        <i class="fas fa-check mr-2"></i> Confirm & Reserve
                    </button>
                </form>
            @elseif($reservation->status === 'confirmed')
                <button @click="openCompleteModal()" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    <i class="fas fa-check-double mr-2"></i> Reconcile & Invoice
                </button>
                <form action="{{ route('reservations.cancel', $reservation->id) }}" method="POST" onsubmit="return confirm('Cancel case and RETURN stock?');">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <i class="fas fa-ban mr-2"></i> Cancel Case
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left Column: Items List -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Surgical Sets Section -->
            <div class="bg-white shadow rounded-lg mb-6 border-l-4 border-blue-500">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-blue-50">
                    <h3 class="text-lg font-medium text-blue-900 flex items-center gap-2">
                        <i class="fas fa-medkit"></i> Surgical Sets (Assets)
                    </h3>
                    <a href="{{ route('dispatch.create', $reservation->id) }}" class="text-sm bg-blue-600 text-white px-3 py-1.5 rounded hover:bg-blue-700 shadow-sm">
                        <i class="fas fa-exchange-alt mr-1"></i> Dispatch / Assign Set
                    </a>
                </div>
                <div class="p-0">
                    @if($reservation->surgicalSets->isEmpty())
                        <div class="p-6 text-center text-gray-500">
                            <p>No surgical sets assigned to this case.</p>
                        </div>
                    @else
                        <ul class="divide-y divide-gray-200">
                            @foreach($reservation->surgicalSets as $set)
                                <li class="p-4 hover:bg-gray-50 flex items-center justify-between">
                                    <div>
                                        <div class="font-bold text-gray-900">{{ $set->name }}</div>
                                        <div class="text-xs text-gray-500">
                                            Asset: {{ $set->asset->name ?? 'N/A' }} • Location: {{ $set->location->name ?? 'N/A' }}
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                            {{ $set->pivot->status === 'dispatched' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ strtoupper($set->pivot->status) }}
                                        </span>
                                        
                                        @if($set->pivot->status === 'dispatched')
                                            <a href="{{ route('reconcile.create', $reservation->id) }}" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                                Return & Reconcile
                                            </a>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                    <h3 class="text-lg font-medium text-gray-900">Reserved Inventory</h3>
                    <span class="text-sm text-gray-500">{{ $reservation->items->count() }} items</span>
                </div>
                
                @if($reservation->items->isEmpty())
                    <div class="p-8 text-center text-gray-500">
                        <i class="fas fa-box-open text-4xl mb-3 text-gray-300"></i>
                        <p>No items added yet.</p>
                        @if($reservation->status === 'draft')
                            <button @click="openAddModal()" class="mt-2 text-indigo-600 hover:underline">Add items now</button>
                        @endif
                    </div>
                @else
                    <ul class="divide-y divide-gray-200">
                        @foreach($reservation->items as $item)
                            <li class="p-6 hover:bg-gray-50 transition-colors">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-start gap-3">
                                        <div class="p-2 bg-indigo-50 rounded text-indigo-600">
                                            <i class="fas fa-cube"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900">{{ $item->inventory->product_name }}</h4>
                                            <div class="text-xs text-gray-500 mt-1 space-x-2">
                                                <span>Code: {{ $item->inventory->code }}</span>
                                                <span>•</span>
                                                <span class="font-mono bg-gray-100 px-1 rounded">Batch: {{ $item->batch->batch_number ?? 'Any' }}</span>
                                            </div>
                                            @if($item->notes)
                                                <div class="text-xs text-indigo-700 mt-1 bg-indigo-50 px-2 py-0.5 rounded inline-block border border-indigo-100">
                                                    {{ $item->notes }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-6">
                                        <div class="text-right">
                                            <div class="text-sm font-bold text-gray-900">{{ $item->quantity_reserved }} {{ $item->inventory->unit }}</div>
                                            <div class="text-xs text-{{ $item->status_color }}-600 font-medium uppercase">{{ ucfirst($item->status) }}</div>
                                        </div>
                                        @if($reservation->status === 'draft')
                                            <form action="{{ route('reservations.items.remove', [$reservation->id, $item->id]) }}" method="POST">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-red-400 hover:text-red-600" title="Remove Item">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <!-- Right Column: Details Card -->
        <div class="space-y-6">
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <div class="flex justify-between items-center">
                        <h3 class="text-base font-medium text-gray-900">Case Details</h3>
                        @if($reservation->status === 'draft')
                            <button @click="openEditModal()" class="text-indigo-600 hover:text-indigo-900 text-xs font-medium">Edit</button>
                        @endif
                    </div>
                </div>
                <div class="p-6 space-y-4 text-sm">
                    <div>
                        <span class="block text-gray-500 text-xs uppercase tracking-wide">Patient</span>
                        <div class="font-medium text-gray-900">{{ $reservation->patient_name }}</div>
                        <div class="text-gray-500 text-xs">{{ $reservation->patient_id ?? 'No ID' }}</div>
                    </div>
                    <div>
                        <span class="block text-gray-500 text-xs uppercase tracking-wide">Bill To</span>
                        <div class="font-medium text-gray-900">{{ $reservation->customer->name ?? 'Patient (Self Pay)' }}</div>
                    </div>
                    <div>
                        <span class="block text-gray-500 text-xs uppercase tracking-wide">Surgeon</span>
                        <div class="font-medium text-gray-900">{{ $reservation->surgeon_name }}</div>
                    </div>
                    <div>
                        <span class="block text-gray-500 text-xs uppercase tracking-wide">Location</span>
                        <div class="font-medium text-gray-900">{{ $reservation->location->name ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <span class="block text-gray-500 text-xs uppercase tracking-wide">Schedule</span>
                        <div class="font-medium text-gray-900">{{ $reservation->surgery_date->format('M d, Y') }}</div>
                        <div class="text-gray-500">{{ $reservation->surgery_date->format('h:i A') }}</div>
                    </div>
                    @if($reservation->notes)
                        <div class="pt-4 border-t border-gray-100">
                            <span class="block text-gray-500 text-xs uppercase tracking-wide mb-1">Notes</span>
                            <div class="text-gray-700 bg-yellow-50 p-2 rounded">{{ $reservation->notes }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Add Item Modal -->
    <div x-show="showAddModal" class="fixed z-50 inset-0 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="showAddModal = false"></div>
            <div class="relative bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:w-full sm:max-w-2xl flex flex-col h-[600px]">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Add Inventory to Case</h3>
                    <button @click="showAddModal = false" class="text-gray-400 hover:text-gray-500"><i class="fas fa-times"></i></button>
                </div>
                
                <div class="p-6 flex-1 overflow-y-auto">
                    <!-- Step 1: Search -->
                    <div x-show="!selectedProduct" x-init="searchInventory()">
                        <!-- Filters -->
                        <div class="flex flex-col sm:flex-row gap-2 mb-3">
                            <select x-model="filterCategory" @change="searchInventory()" class="block w-full sm:w-1/3 rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">All Categories</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->name }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                            <div class="flex rounded-md shadow-sm">
                                <button type="button" @click="filterType = ''; searchInventory()" :class="{'bg-indigo-600 text-white': filterType === '', 'bg-white text-gray-700': filterType !== ''}" class="relative inline-flex items-center px-4 py-2 rounded-l-md border border-gray-300 text-sm font-medium hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">All</button>
                                <button type="button" @click="filterType = 'product'; searchInventory()" :class="{'bg-indigo-600 text-white': filterType === 'product', 'bg-white text-gray-700': filterType !== 'product'}" class="relative -ml-px inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">Items</button>
                                <button type="button" @click="filterType = 'set'; searchInventory()" :class="{'bg-indigo-600 text-white': filterType === 'set', 'bg-white text-gray-700': filterType !== 'set'}" class="relative -ml-px inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">Sets</button>
                                <button type="button" @click="filterType = 'package'; searchInventory()" :class="{'bg-indigo-600 text-white': filterType === 'package', 'bg-white text-gray-700': filterType !== 'package'}" class="relative -ml-px inline-flex items-center px-4 py-2 rounded-r-md border border-gray-300 text-sm font-medium hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">Packages</button>
                            </div>
                        </div>

                        <div class="relative">
                            <input type="text" x-model="searchQuery" @input.debounce.500ms="searchInventory()" placeholder="Search product name, code..." class="w-full rounded-md border-gray-300 pl-10 focus:border-indigo-500 focus:ring-indigo-500">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                        
                        <div class="mt-4 space-y-2">
                            <template x-if="loading">
                                <div class="text-center py-4 text-gray-500">Searching...</div>
                            </template>
                            
                            <template x-for="product in searchResults" :key="product.id">
                                <div class="p-3 border border-gray-200 rounded-md hover:bg-gray-50 flex justify-between items-center">
                                    <template x-if="product.type === 'product'">
                                        <div @click="selectProduct(product)" class="flex-1 cursor-pointer flex justify-between items-center">
                                            <div>
                                                <div class="font-medium text-gray-900" x-text="product.name"></div>
                                                <div class="text-xs text-gray-500">Code: <span x-text="product.code"></span></div>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-sm font-bold text-green-600" x-text="product.available_stock"></div>
                                                <div class="text-xs text-gray-400">Available</div>
                                            </div>
                                        </div>
                                    </template>
                                    
                                    <template x-if="product.type === 'set'">
                                        <div class="flex-1 flex justify-between items-center bg-indigo-50 p-2 rounded" x-data="{ isRental: false, rentalPrice: product.rental_price || 0 }">
                                            <div>
                                                <div class="font-bold text-indigo-900" x-text="product.display_name"></div>
                                                <div class="text-xs text-indigo-500">Surgical Set • One-Click Add</div>
                                                
                                                <!-- Rental Options -->
                                                <div class="mt-2">
                                                    <label class="inline-flex items-center">
                                                        <input type="checkbox" name="is_rental" value="1" x-model="isRental" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                                        <span class="ml-2 text-sm text-gray-600">Bill Rental Fee</span>
                                                    </label>
                                                    <div x-show="isRental" class="mt-1 flex items-center space-x-1" style="display: none;">
                                                        <span class="text-xs text-gray-500">Price:</span>
                                                        <input type="number" step="0.01" name="rental_price" x-model="rentalPrice" class="w-24 text-xs border-gray-300 rounded shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                    </div>
                                                </div>
                                            </div>
                                            <form action="{{ route('reservations.add_set', $reservation->id) }}" method="POST" class="flex flex-col items-end">
                                                @csrf
                                                <input type="hidden" name="set_id" :value="product.id">
                                                <input type="hidden" name="is_rental" :value="isRental ? '1' : '0'">
                                                <input type="hidden" name="rental_price" :value="rentalPrice">
                                                
                                                <button type="submit" class="text-sm bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded shadow-sm mt-2">
                                                    Add Set
                                                </button>
                                            </form>
                                        </div>
                                    </template>

                                    <template x-if="product.type === 'package'">
                                        <div class="flex-1 flex justify-between items-center">
                                            <div>
                                                <div class="font-bold text-green-900" x-text="product.display_name"></div>
                                                <div class="text-xs text-green-500">Package • One-Click Add</div>
                                            </div>
                                            <form action="{{ route('reservations.add_package', $reservation->id) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="package_id" :value="product.id">
                                                <button type="submit" class="text-sm bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded shadow-sm">
                                                    Add Package
                                                </button>
                                            </form>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Step 2: Configure & Batch -->
                    <div x-show="selectedProduct" class="space-y-6">
                        <div class="flex items-center justify-between bg-gray-50 p-3 rounded-md">
                            <div>
                                <span class="block text-xs text-gray-500 uppercase">Selected Product</span>
                                <span class="font-bold text-gray-900" x-text="selectedProduct?.name"></span>
                            </div>
                            <button @click="selectedProduct = null; selectedBatch = null" class="text-indigo-600 text-sm hover:underline">Change</button>
                        </div>

                        <form action="{{ route('reservations.items.add', $reservation->id) }}" method="POST" id="addItemForm">
                            @csrf
                            <input type="hidden" name="inventory_id" :value="selectedProduct?.id">
                            
                            <!-- Batch Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Select Batch *</label>
                                <div class="space-y-2 max-h-48 overflow-y-auto border rounded-md p-2">
                                    <template x-for="batch in selectedProduct?.batches" :key="batch.id">
                                        <label class="flex items-center p-2 rounded hover:bg-gray-50 cursor-pointer border border-transparent" :class="{'border-indigo-500 bg-indigo-50': selectedBatch === batch.id, 'opacity-50 cursor-not-allowed': batch.quantity <= 0}">
                                            <input type="radio" name="batch_id" :value="batch.id" x-model="selectedBatch" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300" required :disabled="batch.quantity <= 0">
                                            <div class="ml-3 flex-1 flex justify-between">
                                                <span class="text-sm font-medium text-gray-900" x-text="batch.batch_number"></span>
                                                <span class="text-sm text-gray-500">
                                                    Avail: <span x-text="batch.quantity" class="font-bold text-gray-900"></span>
                                                    <span class="text-xs ml-1" x-text="'exp: ' + (batch.expiry_date || 'N/A')"></span>
                                                </span>
                                            </div>
                                        </label>
                                    </template>
                                    <div x-show="!selectedProduct?.batches?.length" class="text-sm text-gray-500 p-2 bg-yellow-50 rounded">
                                        <p class="mb-2">No specific batches found.</p>
                                        <label class="flex items-center space-x-2 cursor-pointer">
                                            <input type="radio" name="batch_id" value="" checked class="text-indigo-600">
                                            <span class="font-medium text-gray-700">Reserve Generic Stock</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Quantity -->
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Quantity to Reserve *</label>
                                <input type="number" name="quantity" min="1" value="1" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-lg">
                            </div>

                            <div class="mt-8 flex justify-end space-x-3">
                                <button type="button" @click="submitItem(true)" :disabled="submitting" class="inline-flex justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                    <span x-show="!submitting">Add & Add Another</span>
                                    <span x-show="submitting">Adding...</span>
                                </button>
                                <button type="button" @click="submitItem(false)" :disabled="submitting" class="inline-flex justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                    <span x-show="!submitting">Add & Close</span>
                                    <span x-show="submitting">Adding...</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Complete/Reconcile Modal -->
    <div x-show="showCompleteModal" class="fixed z-50 inset-0 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="showCompleteModal = false"></div>
            <div class="relative bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:w-full sm:max-w-4xl flex flex-col">
                <form action="{{ route('reservations.complete', $reservation->id) }}" method="POST">
                    @csrf
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-100 flex justify-between items-center">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Reconcile & Generate Invoice</h3>
                        <button type="button" @click="showCompleteModal = false" class="text-gray-400 hover:text-gray-500"><i class="fas fa-times"></i></button>
                    </div>
                    
                    <div class="p-6">
                        <p class="text-sm text-gray-500 mb-4">Confirm actual usage. Unused items will be returned to inventory. An invoice will be generated for used items.</p>
                        
                        <table class="min-w-full divide-y divide-gray-200 border">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">Reserved</th>
                                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">Used</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Notes</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($reservation->items as $item)
                                    <tr>
                                        <td class="px-3 py-2 text-sm text-gray-900">
                                            {{ $item->inventory->product_name }}
                                            <div class="text-xs text-gray-500">{{ $item->batch->batch_number ?? 'No Batch' }}</div>
                                        </td>
                                        <td class="px-3 py-2 text-center text-sm text-gray-500">
                                            {{ $item->quantity_reserved }}
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <input type="number" name="usage[{{ $item->id }}]" min="0" max="{{ $item->quantity_reserved }}" value="{{ $item->quantity_reserved }}" class="w-20 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-center text-sm">
                                        </td>
                                        <td class="px-3 py-2">
                                           <input type="text" name="notes[{{ $item->id }}]" value="{{ $item->notes }}" placeholder="Usage notes..." class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"> 
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="mt-4 flex items-center bg-yellow-50 p-3 rounded text-sm text-yellow-800">
                            <i class="fas fa-file-invoice-dollar mr-2"></i>
                            <span>A Credit Invoice will be generated for <strong>{{ $reservation->patient_name }}</strong>.</span>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Generate Invoice & Complete
                        </button>
                        <button type="button" @click="showCompleteModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

    <!-- Edit Case Modal -->
    <div x-show="showEditModal" class="fixed z-50 inset-0 overflow-y-auto" style="display: none;" x-cloak>
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="showEditModal = false"></div>
            <div class="relative bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:w-full sm:max-w-lg">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Edit Case Details</h3>
                    <button @click="showEditModal = false" class="text-gray-400 hover:text-gray-500"><i class="fas fa-times"></i></button>
                </div>
                <div class="p-6">
                    <form action="{{ route('reservations.update', $reservation->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="space-y-4">
                            <div>
                                <input type="text" name="patient_name" value="{{ $reservation->patient_name }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Bill To (Facility)</label>
                                <select name="customer_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">-- Patient (Self Pay) --</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{ $reservation->customer_id == $customer->id ? 'selected' : '' }}>
                                            {{ $customer->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Surgeon Name</label>
                                <input type="text" name="surgeon_name" value="{{ $reservation->surgeon_name }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Procedure</label>
                                <input type="text" name="procedure_name" value="{{ $reservation->procedure_name }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Surgery Date & Time</label>
                                <input type="datetime-local" name="surgery_date" value="{{ $reservation->surgery_date->format('Y-m-d\TH:i') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Notes</label>
                                <textarea name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ $reservation->notes }}</textarea>
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end gap-3">
                            <button type="button" @click="showEditModal = false" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none">Cancel</button>
                            <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<script>
    function caseManager() {
        return {
            showAddModal: false,
            showEditModal: false,
            searchQuery: '',
            searchResults: [],
            loading: false,
            submitting: false,
            filterCategory: '',
            filterType: '',
            selectedProduct: null,
            selectedBatch: null,
            showCompleteModal: false,
            
            openAddModal() {
            this.showAddModal = true;
            this.searchQuery = '';
            // Don't reset filters? Or reset them? 
            // Resetting for fresh start
            // this.filterCategory = '';
            // this.filterType = '';
            // Reload default list if empty
            if (this.searchResults.length === 0) {
                this.searchInventory();
            }
            this.selectedProduct = null;
            this.selectedBatch = null;
        },

        openEditModal() {
            this.showEditModal = true;
        },

        openCompleteModal() {
            this.showCompleteModal = true;
        },

        async searchInventory() { // ... (Rest of JS)
            // if (this.searchQuery.length < 2) return; // Allow empty for browse
            this.loading = true;
            try {
                // Determine if we are scanning a barcode or typing text. 
                // Using the unified search endpoint for Products AND Sets
                let url = '{{ route("reservations.search_items") }}?query=' + encodeURIComponent(this.searchQuery) + '&category=' + encodeURIComponent(this.filterCategory) + '&type=' + encodeURIComponent(this.filterType);
                
                const response = await fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    // Handle pagination wrapper if present (Laravel paginate returns data inside 'data' key)
                    this.searchResults = data.data ? data.data : data; 
                }
            } catch (error) {
                console.error('Search failed', error);
            } finally {
                this.loading = false;
            }
        },

        async submitItem(continueAdding) {
            const form = document.getElementById('addItemForm');
            const formData = new FormData(form);
            const url = form.action;

            this.submitting = true;
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: formData
                });
                
                const data = await response.json();

                if (response.ok) {
                    // Success
                    // Ideally use a toast, for now alert is safe fallback or verify if toast library exists
                    // Assuming no toast lib, we just log/alert slightly nicer if possible?
                    // We'll reset logic.
                    if (continueAdding) {
                        this.selectedProduct = null;
                        this.selectedBatch = null;
                        this.searchQuery = '';
                        this.searchResults = [];
                        // Optional: Show a temporary success message in the modal?
                        // For now, silent success + reset is standard "Speed Mode".
                        alert("Item Added! Ready for next."); 
                    } else {
                        window.location.reload();
                    }
                } else {
                    alert(data.error || 'Failed to add item.');
                }
            } catch (error) {
                console.error('Submission failed', error);
                alert('An error occurred. Please try again.');
            } finally {
                this.submitting = false;
            }
        },

        selectProduct(product) {
            this.selectedProduct = product;
            // Ensure batches are loaded. 
            // If they are not in the search result, we might need to fetch them.
            // But InventoryController::index includes 'batches', so we should be good.
        }
    }
}
</script>
@endsection
