@extends('layouts.app')

@section('content')
<div x-data="stockTransfers()">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Stock Transfers</h1>
            <p class="text-sm text-gray-600">Manage movement of stock between locations</p>
        </div>
        <a href="{{ route('stock-transfers.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors">
            New Transfer
        </a>
    </div>

    <!-- Filters placeholder (optional) -->
    
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">From</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">To</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <template x-for="transfer in transfers" :key="transfer.id">
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="formatDate(transfer.created_at)"></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="transfer.from_location ? transfer.from_location.name : 'Main Store'"></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium" x-text="transfer.to_location ? transfer.to_location.name : 'Main Store'"></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                :class="{
                                    'bg-green-100 text-green-800': transfer.status === 'completed',
                                    'bg-yellow-100 text-yellow-800': transfer.status === 'pending',
                                    'bg-red-100 text-red-800': transfer.status === 'cancelled'
                                }" x-text="transfer.status">
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <span x-text="transfer.items.length + ' items'"></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button x-show="transfer.status === 'pending'" @click="completeTransfer(transfer)" class="text-indigo-600 hover:text-indigo-900 mr-2">Complete</button>
                            <!-- <a :href="`/stock-transfers/${transfer.id}`" class="text-gray-600 hover:text-gray-900">View</a> -->
                        </td>
                    </tr>
                </template>
                <tr x-show="transfers.length === 0">
                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">No transfers found.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
function stockTransfers() {
    return {
        transfers: [],
        init() {
            this.loadTransfers();
        },
        async loadTransfers() {
            try {
                // Using the API endpoint we created
                const response = await axios.get('/stock-transfers/data');
                this.transfers = response.data.data;
            } catch (error) {
                console.error('Error loading transfers:', error);
                alert('Failed to load transfers');
            }
        },
        formatDate(dateString) {
            return new Date(dateString).toLocaleDateString();
        },
        async completeTransfer(transfer) {
            if (!confirm('Are you sure you want to complete this transfer? This will move stock between locations.')) return;
            try {
                await axios.post(`/stock-transfers/${transfer.id}/complete`);
                alert('Transfer completed successfully!');
                this.loadTransfers();
            } catch (error) {
                console.error(error);
                alert('Error completing transfer: ' + (error.response?.data?.error || error.message));
            }
        }
    }
}
</script>
@endsection
