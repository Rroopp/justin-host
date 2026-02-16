@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Batch & Serial Number Tracking</h1>
        <div class="flex space-x-2">
            <a href="{{ route('batches.traceability') }}" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                🔍 Traceability Search
            </a>
            <a href="{{ route('batches.expiring') }}" class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
                ⚠️ Expiring Soon
            </a>
            <a href="{{ route('batches.recalled') }}" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                🚨 Recalled
            </a>
            <button onclick="openCreateModal()" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                + Add Batch
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET" action="{{ route('batches.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Batch/Serial/Product..." class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Status</label>
                <select name="status" class="w-full border rounded px-3 py-2">
                    <option value="">All Statuses</option>
                    <option value="available" {{ request('status') == 'available' ? 'selected' : '' }}>Available</option>
                    <option value="sold" {{ request('status') == 'sold' ? 'selected' : '' }}>Sold</option>
                    <option value="recalled" {{ request('status') == 'recalled' ? 'selected' : '' }}>Recalled</option>
                    <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                    <option value="damaged" {{ request('status') == 'damaged' ? 'selected' : '' }}>Damaged</option>
                    <option value="returned" {{ request('status') == 'returned' ? 'selected' : '' }}>Returned</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Recall Status</label>
                <select name="recall_status" class="w-full border rounded px-3 py-2">
                    <option value="">All</option>
                    <option value="none" {{ request('recall_status') == 'none' ? 'selected' : '' }}>None</option>
                    <option value="pending" {{ request('recall_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="active" {{ request('recall_status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="resolved" {{ request('recall_status') == 'resolved' ? 'selected' : '' }}>Resolved</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Product</label>
                <select name="inventory_id" class="w-full border rounded px-3 py-2">
                    <option value="">All Products</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ request('inventory_id') == $product->id ? 'selected' : '' }}>
                            {{ $product->product_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Filter</button>
                <a href="{{ route('batches.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Clear</a>
            </div>
        </form>
    </div>

    <!-- Batches Table -->
    <div class="bg-white rounded-lg shadow">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Batch #</th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Serial #</th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expiry</th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Recall</th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($batches as $batch)
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $batch->inventory->product_name ?? 'N/A' }}</div>
                            @if($batch->manufacturer)
                                <div class="text-xs text-gray-500">{{ $batch->manufacturer->name }}</div>
                            @endif
                        </td>
                        <td class="px-3 py-4 whitespace-nowrap text-sm">{{ $batch->batch_number }}</td>
                        <td class="px-3 py-4 whitespace-nowrap text-sm">
                            @if($batch->serial_number)
                                <span class="font-mono bg-gray-100 px-2 py-1 rounded">{{ $batch->serial_number }}</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-3 py-4 whitespace-nowrap text-sm">
                            @if($batch->expiry_date)
                                <span class="{{ $batch->isExpired() ? 'text-red-600 font-semibold' : ($batch->expiry_date <= now()->addDays(90) ? 'text-yellow-600' : 'text-gray-900') }}">
                                    {{ $batch->expiry_date->format('Y-m-d') }}
                                </span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-3 py-4 whitespace-nowrap text-sm">{{ $batch->quantity }}</td>
                        <td class="px-3 py-4 whitespace-nowrap text-sm">{{ $batch->location->name ?? '-' }}</td>
                        <td class="px-3 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded-full bg-{{ $batch->status_color }}-100 text-{{ $batch->status_color }}-800">
                                {{ ucfirst($batch->status) }}
                            </span>
                        </td>
                        <td class="px-3 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded-full bg-{{ $batch->recall_color }}-100 text-{{ $batch->recall_color }}-800">
                                {{ ucfirst($batch->recall_status) }}
                            </span>
                        </td>
                        <td class="px-3 py-4 whitespace-nowrap text-sm space-x-2">
                            <button onclick="editBatch({{ $batch->id }})" class="text-blue-600 hover:text-blue-900">Edit</button>
                            @if($batch->recall_status === 'none')
                                <button onclick="recallBatch({{ $batch->id }})" class="text-red-600 hover:text-red-900">Recall</button>
                            @elseif($batch->recall_status === 'active')
                                <button onclick="resolveRecall({{ $batch->id }})" class="text-green-600 hover:text-green-900">Resolve</button>
                            @endif
                            <button onclick="deleteBatch({{ $batch->id }})" class="text-red-600 hover:text-red-900">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-3 py-4 text-center text-gray-500">No batches found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $batches->links() }}
    </div>
</div>

<!-- Create/Edit Modal -->
<div id="batchModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 id="modalTitle" class="text-lg font-semibold">Add Batch</h3>
            <button onclick="closeModal()" class="text-gray-600 hover:text-gray-900">&times;</button>
        </div>
        <form id="batchForm" method="POST">
            @csrf
            <input type="hidden" id="batchId" name="_method" value="POST">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Product *</label>
                    <select name="inventory_id" id="inventory_id" required class="w-full border rounded px-3 py-2">
                        <option value="">Select Product</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->product_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Manufacturer</label>
                    <select name="manufacturer_id" id="manufacturer_id" class="w-full border rounded px-3 py-2">
                        <option value="">Select Manufacturer</option>
                        @foreach($manufacturers as $manufacturer)
                            <option value="{{ $manufacturer->id }}">{{ $manufacturer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Batch Number *</label>
                    <input type="text" name="batch_number" id="batch_number" required class="w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Serial Number</label>
                    <input type="text" name="serial_number" id="serial_number" class="w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Expiry Date</label>
                    <input type="date" name="expiry_date" id="expiry_date" class="w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Quantity *</label>
                    <input type="number" name="quantity" id="quantity" required min="1" class="w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Cost Price *</label>
                    <input type="number" name="cost_price" id="cost_price" required step="0.01" min="0" class="w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Selling Price</label>
                    <input type="number" name="selling_price" id="selling_price" step="0.01" min="0" class="w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Location</label>
                    <select name="location_id" id="location_id" class="w-full border rounded px-3 py-2">
                        <option value="">Select Location</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" name="is_serialized" id="is_serialized" value="1" class="mr-2">
                    <label for="is_serialized" class="text-sm font-medium">Requires Serial Tracking</label>
                </div>
            </div>

            <div class="flex justify-end space-x-2 mt-6">
                <button type="button" onclick="closeModal()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Cancel</button>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Recall Modal -->
<div id="recallModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-red-600">⚠️ Mark Batch as Recalled</h3>
            <button onclick="closeRecallModal()" class="text-gray-600 hover:text-gray-900">&times;</button>
        </div>
        <form id="recallForm" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Recall Reason *</label>
                    <textarea name="recall_reason" id="recall_reason" required rows="4" class="w-full border rounded px-3 py-2"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Recall Date</label>
                    <input type="date" name="recall_date" id="recall_date" value="{{ date('Y-m-d') }}" class="w-full border rounded px-3 py-2">
                </div>
            </div>
            <div class="flex justify-end space-x-2 mt-6">
                <button type="button" onclick="closeRecallModal()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Cancel</button>
                <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Mark as Recalled</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Add Batch';
    document.getElementById('batchForm').action = '{{ route("batches.store") }}';
    document.getElementById('batchForm').reset();
    document.getElementById('batchId').value = 'POST';
    document.getElementById('batchModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('batchModal').classList.add('hidden');
}

function editBatch(id) {
    // Fetch batch data and populate form
    fetch(`/batches/${id}`)
        .then(r => r.json())
        .then(batch => {
            document.getElementById('modalTitle').textContent = 'Edit Batch';
            document.getElementById('batchForm').action = `/batches/${id}`;
            document.getElementById('batchId').value = 'PUT';
            
            // Populate form fields
            document.getElementById('inventory_id').value = batch.inventory_id;
            document.getElementById('manufacturer_id').value = batch.manufacturer_id || '';
            document.getElementById('batch_number').value = batch.batch_number;
            document.getElementById('serial_number').value = batch.serial_number || '';
            document.getElementById('expiry_date').value = batch.expiry_date || '';
            document.getElementById('quantity').value = batch.quantity;
            document.getElementById('cost_price').value = batch.cost_price;
            document.getElementById('selling_price').value = batch.selling_price || '';
            document.getElementById('location_id').value = batch.location_id || '';
            document.getElementById('is_serialized').checked = batch.is_serialized;
            
            document.getElementById('batchModal').classList.remove('hidden');
        });
}

function deleteBatch(id) {
    if (confirm('Are you sure you want to delete this batch?')) {
        fetch(`/batches/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        }).then(() => location.reload());
    }
}

function recallBatch(id) {
    document.getElementById('recallForm').action = `/batches/${id}/recall`;
    document.getElementById('recallModal').classList.remove('hidden');
}

function closeRecallModal() {
    document.getElementById('recallModal').classList.add('hidden');
}

function resolveRecall(id) {
    const notes = prompt('Resolution notes (optional):');
    if (notes !== null) {
        fetch(`/batches/${id}/resolve-recall`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ resolution_notes: notes })
        }).then(() => location.reload());
    }
}
</script>
@endsection
