@extends('layouts.app')

@section('content')
<div x-data="stockTakeManager()" x-init="init()" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Stock Take: {{ $stockTake->reference_number }}</h1>
            <p class="mt-2 text-sm text-gray-600">
                Date: {{ $stockTake->date->format('M d, Y') }} | 
                Status: <span class="font-semibold">{{ ucfirst(str_replace('_', ' ', $stockTake->status)) }}</span>
            </p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('stock-takes.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                Back to List
            </a>
            <a href="{{ route('stock-takes.sheet', $stockTake) }}" target="_blank" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-print mr-2"></i>Print Sheet
            </a>
            @if($stockTake->isEditable())
                <button @click="saveCounts()" :disabled="saving" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50">
                    <span x-show="!saving">Save Counts</span>
                    <span x-show="saving">Saving...</span>
                </button>
            @endif
            @if($stockTake->status === 'in_progress')
                <form action="{{ route('stock-takes.complete', $stockTake) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                        Mark as Complete
                    </button>
                </form>
            @endif
            @if($stockTake->canReconcile())
                <form action="{{ route('stock-takes.reconcile', $stockTake) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure? This will update all inventory quantities based on physical counts.')">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700">
                        Reconcile Inventory
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Progress Summary -->
    <div class="bg-white shadow rounded-lg p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <p class="text-sm text-gray-500">Total Items</p>
                <p class="text-2xl font-bold text-gray-900">{{ $stockTake->items->count() }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Items Counted</p>
                <p class="text-2xl font-bold text-blue-600" x-text="countedItems"></p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Items with Variances</p>
                <p class="text-2xl font-bold text-orange-600">{{ $stockTake->variance_count }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Progress</p>
                <p class="text-2xl font-bold text-green-600" x-text="progressPercentage + '%'"></p>
            </div>
        </div>
    </div>

    <!-- Items by Category -->
    @if($itemsByCategory && $itemsByCategory->count() > 0)
    @foreach($itemsByCategory as $category => $items)
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg font-medium text-gray-900">{{ $category }} ({{ $items->count() }} items)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">System Qty</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Physical Count</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Variance</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($items as $item)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $item->inventory->code ?? 'N/A' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $item->inventory->product_name ?? 'Unknown Item' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ number_format($item->system_quantity, 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($stockTake->isEditable())
                                        <input type="number" 
                                               step="0.01" 
                                               min="0"
                                               x-model="counts[{{ $item->id }}].physical_quantity"
                                               @input="updateVariance({{ $item->id }}, {{ $item->system_quantity }})"
                                               class="w-24 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                    @else
                                        <span class="text-sm text-gray-900">{{ $item->physical_quantity !== null ? number_format($item->physical_quantity, 2) : '-' }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span x-text="formatVariance({{ $item->id }})" 
                                          :class="getVarianceClass({{ $item->id }})"
                                          class="text-sm font-medium"></span>
                                </td>
                                <td class="px-6 py-4">
                                    @if($stockTake->isEditable())
                                        <input type="text" 
                                               x-model="counts[{{ $item->id }}].notes"
                                               placeholder="Optional notes"
                                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                    @else
                                        <span class="text-sm text-gray-500">{{ $item->notes ?? '-' }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
    @else
        <div class="bg-white shadow rounded-lg p-8 text-center">
            <p class="text-gray-500 text-lg">No items found for this stock take.</p>
            <p class="text-gray-400 text-sm mt-2">This stock take may not have any items, or there was an error loading them.</p>
            <p class="text-gray-400 text-sm">Total items in stock take: {{ $stockTake->items->count() }}</p>
        </div>
    @endif

    @if($stockTake->status === 'completed')
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        This stock take is completed and ready for reconciliation. 
                        <form action="{{ route('stock-takes.reconcile', $stockTake) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure? This will update all inventory quantities.')">
                            @csrf
                            <button type="submit" class="font-medium underline hover:text-yellow-800">Click here to reconcile</button>
                        </form>
                    </p>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
function stockTakeManager() {
    return {
        counts: @json($countsData),
        saving: false,

        init() {
            this.updateProgress();
        },

        get countedItems() {
            return Object.values(this.counts).filter(c => c.physical_quantity !== null && c.physical_quantity !== '').length;
        },

        get progressPercentage() {
            const total = Object.keys(this.counts).length;
            return total > 0 ? Math.round((this.countedItems / total) * 100) : 0;
        },

        updateVariance(itemId, systemQty) {
            const physicalQty = parseFloat(this.counts[itemId].physical_quantity) || 0;
            this.counts[itemId].variance = physicalQty - systemQty;
        },

        formatVariance(itemId) {
            const variance = this.counts[itemId].variance;
            if (variance === null || variance === undefined) return '-';
            const sign = variance > 0 ? '+' : '';
            return sign + parseFloat(variance).toFixed(2);
        },

        getVarianceClass(itemId) {
            const variance = this.counts[itemId].variance;
            if (variance === null || variance === 0) return 'text-gray-500';
            return variance > 0 ? 'text-green-600' : 'text-red-600';
        },

        updateProgress() {
            // Trigger reactivity
            this.$nextTick(() => {});
        },

        async saveCounts() {
            this.saving = true;
            try {
                const countsArray = Object.values(this.counts);
                const response = await axios.post('{{ route('stock-takes.update-counts', $stockTake) }}', {
                    counts: countsArray
                });
                alert('Counts saved successfully');
                window.location.reload();
            } catch (error) {
                alert('Error saving counts: ' + (error.response?.data?.message || error.message));
            } finally {
                this.saving = false;
            }
        }
    }
}
</script>
@endsection
