@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8" x-data="recordSurgery()">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Record Surgery Usage</h1>
        <p class="text-sm text-gray-600">Track items used during surgical procedures</p>
    </div>

    <form @submit.prevent="submitForm()" class="bg-white shadow rounded-lg p-6">
        <!-- Surgery Info -->
        <div class="mb-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Surgery Information</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Surgery Date *</label>
                    <input type="date" x-model="form.surgery_date" required class="mt-1 block w-full rounded-md border-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Set Used</label>
                    <select x-model="form.set_location_id" @change="loadSetContents()" class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="">No Set / Main Store</option>
                        <template x-for="set in sets" :key="set.id">
                            <option :value="set.id" x-text="set.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Patient Name</label>
                    <input type="text" x-model="form.patient_name" class="mt-1 block w-full rounded-md border-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Patient Number</label>
                    <input type="text" x-model="form.patient_number" class="mt-1 block w-full rounded-md border-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Surgeon Name</label>
                    <input type="text" x-model="form.surgeon_name" class="mt-1 block w-full rounded-md border-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Facility</label>
                    <input type="text" x-model="form.facility_name" class="mt-1 block w-full rounded-md border-gray-300">
                </div>
            </div>
        </div>

        <!-- Items Used -->
        <div class="mb-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Items Used</h3>
            <div class="border rounded-md overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Batch</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Source</th>
                            <th class="px-4 py-2 w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <template x-for="(item, index) in form.items" :key="index">
                            <tr>
                                <td class="px-4 py-2">
                                    <select x-model="item.inventory_id" @change="loadBatchesForItem(item)" required class="block w-full text-sm border-gray-300 rounded-md">
                                        <option value="">Select Product...</option>
                                        <template x-for="prod in availableProducts" :key="prod.id">
                                            <option :value="prod.id" x-text="prod.product_name"></option>
                                        </template>
                                    </select>
                                </td>
                                <td class="px-4 py-2">
                                    <select x-model="item.batch_id" required class="block w-full text-sm border-gray-300 rounded-md">
                                        <option value="">Select Batch...</option>
                                        <template x-for="batch in (item.batches || [])" :key="batch.id">
                                            <option :value="batch.id" x-text="`${batch.batch_number} (Qty: ${batch.quantity})`"></option>
                                        </template>
                                    </select>
                                </td>
                                <td class="px-4 py-2">
                                    <input type="number" x-model="item.quantity" min="1" required class="block w-full text-sm border-gray-300 rounded-md">
                                </td>
                                <td class="px-4 py-2">
                                    <select x-model="item.from_set" class="block w-full text-sm border-gray-300 rounded-md">
                                        <option :value="true">From Set</option>
                                        <option :value="false">Main Store</option>
                                    </select>
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <button type="button" @click="removeItem(index)" class="text-red-500 hover:text-red-700">&times;</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <button type="button" @click="addItem()" class="mt-2 text-sm text-indigo-600 hover:text-indigo-800 font-medium">+ Add Item</button>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('surgery-usage.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Record Usage</button>
        </div>
    </form>
</div>

<script>
function recordSurgery() {
    return {
        sets: @json($sets),
        availableProducts: [],
        form: {
            surgery_date: new Date().toISOString().split('T')[0],
            set_location_id: '',
            patient_name: '',
            patient_number: '',
            surgeon_name: '',
            facility_name: '',
            items: [
                { inventory_id: '', batch_id: '', quantity: 1, from_set: true, batches: [] }
            ]
        },
        async init() {
            await this.loadProducts();
        },
        async loadProducts() {
            try {
                const response = await axios.get('/inventory');
                this.availableProducts = response.data.data;
            } catch (error) {
                console.error('Error loading products:', error);
            }
        },
        async loadBatchesForItem(item) {
            if (!item.inventory_id) return;
            try {
                // Fetch batches for this product at the selected location
                const locationId = this.form.set_location_id || null;
                const response = await axios.get(`/api/v1/batches?inventory_id=${item.inventory_id}&location_id=${locationId || ''}`);
                item.batches = response.data;
            } catch (error) {
                console.error('Error loading batches:', error);
                item.batches = [];
            }
        },
        addItem() {
            this.form.items.push({ inventory_id: '', batch_id: '', quantity: 1, from_set: true, batches: [] });
        },
        removeItem(index) {
            this.form.items.splice(index, 1);
        },
        async submitForm() {
            try {
                await axios.post('{{ route("surgery-usage.store") }}', this.form);
                window.location.href = '{{ route("surgery-usage.index") }}';
            } catch (error) {
                alert('Error recording usage: ' + (error.response?.data?.message || error.message));
            }
        }
    }
}
</script>
@endsection
