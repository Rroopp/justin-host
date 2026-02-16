@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Consignment Stock Management</h1>
        <div class="flex space-x-2">
            <button onclick="openPlaceStockModal()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                📦 Place Stock
            </button>
            <a href="{{ route('consignment.unbilled') }}" class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
                💰 Unbilled ({{ number_format($stats['unbilled_amount'], 2) }})
            </a>
            <a href="{{ route('consignment.aging') }}" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                ⏰ Aging ({{ $stats['aging_items'] }})
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <a href="{{ route('sales.consignments.index') }}" class="bg-indigo-50 border border-indigo-200 rounded-lg shadow-sm p-6 hover:shadow-md transition">
            <div class="text-indigo-600 text-sm font-semibold uppercase tracking-wide">Pending Surgery Sales</div>
            <div class="text-3xl font-bold text-indigo-900 mt-2">{{ $stats['pending_surgery_sales'] }}</div>
            <div class="text-xs text-indigo-500 mt-1">Click to Reconcile</div>
        </a>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-500 text-sm">Consignment Locations</div>
            <div class="text-3xl font-bold text-blue-600">{{ $stats['total_locations'] }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-500 text-sm">Total Stock Value</div>
            <div class="text-3xl font-bold text-green-600">{{ number_format($stats['total_stock_value'], 2) }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-500 text-sm">Unbilled Amount</div>
            <div class="text-3xl font-bold text-yellow-600">{{ number_format($stats['unbilled_amount'], 2) }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-500 text-sm">Aging Items (>90 days)</div>
            <div class="text-3xl font-bold text-red-600">{{ $stats['aging_items'] }}</div>
        </div>
    </div>

    <!-- Consignment Locations -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h2 class="text-lg font-semibold">Consignment Locations</h2>
        </div>
        <div class="p-6">
            @if($consignmentLocations->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($consignmentLocations as $location)
                        <div class="border rounded-lg p-4 hover:shadow-md transition">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <h3 class="font-semibold text-lg">{{ $location->name }}</h3>
                                    @if($location->contact_person)
                                        <div class="text-sm text-gray-600">{{ $location->contact_person }}</div>
                                    @endif
                                    @if($location->contact_phone)
                                        <div class="text-sm text-gray-600">{{ $location->contact_phone }}</div>
                                    @endif
                                </div>
                                <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                    {{ ucfirst($location->type) }}
                                </span>
                            </div>
                            
                            @if($location->address)
                                <div class="text-sm text-gray-600 mb-3">{{ $location->address }}</div>
                            @endif

                            <div class="grid grid-cols-2 gap-2 mb-3 text-sm">
                                <div>
                                    <span class="text-gray-500">Stock Value:</span>
                                    <div class="font-semibold text-green-600">{{ number_format($location->total_consignment_value, 2) }}</div>
                                </div>
                                <div>
                                    <span class="text-gray-500">Unbilled:</span>
                                    <div class="font-semibold text-yellow-600">{{ number_format($location->unbilled_amount, 2) }}</div>
                                </div>
                            </div>

                            <div class="flex space-x-2">
                                <a href="{{ route('consignment.location-stock', $location) }}" class="flex-1 bg-blue-500 text-white text-center px-3 py-2 rounded text-sm hover:bg-blue-600">
                                    View Stock
                                </a>
                                <a href="{{ route('consignment.ledger', $location) }}" class="flex-1 bg-gray-500 text-white text-center px-3 py-2 rounded text-sm hover:bg-gray-600">
                                    Ledger
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center text-gray-500 py-8">
                    No consignment locations found. Create a location with type "consignment" first.
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Place Stock Modal -->
<div id="placeStockModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" aria-hidden="true" onclick="closePlaceStockModal()"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <!-- Modal Panel -->
        <div class="relative inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">📦 Place Stock at Consignment Location</h3>
                    <button onclick="closePlaceStockModal()" class="text-gray-400 hover:text-gray-500">
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <form action="{{ route('consignment.place-stock') }}" method="POST" id="placeStockForm">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Consignment Location *</label>
                            <select name="location_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Select Location</option>
                                @foreach($consignmentLocations as $location)
                                    <option value="{{ $location->id }}">{{ $location->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Product *</label>
                            <select name="inventory_id" id="inventory_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" onchange="loadBatches()">
                                <option value="">Select Product</option>
                                @php
                                    $products = \App\Models\Inventory::orderBy('product_name')->get();
                                @endphp
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->product_name }} ({{ $product->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Batch (Optional)</label>
                            <select name="batch_id" id="batch_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">No specific batch</option>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Select a product first to see available batches</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
                            <input type="number" name="quantity" required min="1" value="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Transaction Date *</label>
                            <input type="date" name="transaction_date" value="{{ date('Y-m-d') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                            <textarea name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Optional notes..."></textarea>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:col-start-2 sm:text-sm">
                            Place Stock
                        </button>
                        <button type="button" onclick="closePlaceStockModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:col-start-1 sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openPlaceStockModal() {
    document.getElementById('placeStockModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden'; 
    // Load products if needed, but they are already server-rendered here
}

function closePlaceStockModal() {
    document.getElementById('placeStockModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
    // Reset form
    document.getElementById('placeStockForm').reset();
    document.getElementById('batch_id').innerHTML = '<option value="">No specific batch</option>';
}

function loadBatches() {
    const inventoryId = document.getElementById('inventory_id').value;
    const batchSelect = document.getElementById('batch_id');
    
    if (!inventoryId) {
        batchSelect.innerHTML = '<option value="">No specific batch</option>';
        return;
    }
    
    // Show loading state
    batchSelect.innerHTML = '<option value="">Loading batches...</option>';
    batchSelect.disabled = true;
    
    // Fetch batches for selected product
    fetch(`/batches?inventory_id=${inventoryId}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        batchSelect.innerHTML = '<option value="">No specific batch</option>';
        
        if (data.data && data.data.length > 0) {
            data.data.forEach(batch => {
                const option = document.createElement('option');
                option.value = batch.id;
                option.textContent = `${batch.batch_number} (Qty: ${batch.quantity}${batch.expiry_date ? ', Exp: ' + batch.expiry_date : ''})`;
                batchSelect.appendChild(option);
            });
        } else {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'No batches available for this product';
            option.disabled = true;
            batchSelect.appendChild(option);
        }
        
        batchSelect.disabled = false;
    })
    .catch(error => {
        console.error('Error loading batches:', error);
        batchSelect.innerHTML = '<option value="">Error loading batches</option>';
        batchSelect.disabled = false;
    });
}

// Close modal on ESC key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closePlaceStockModal();
    }
});
</script>
@endsection
