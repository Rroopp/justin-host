@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto py-6 sm:px-6 lg:px-8" x-data="createSet()">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Create New Surgical Set</h1>
        <p class="text-sm text-gray-600">Define a new surgical kit, its reusable instruments, and standard consumable levels.</p>
    </div>

    <form @submit.prevent="submitForm()" class="space-y-6">
        
        <!-- 1. Set Information -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">1. Set Information (Asset Details)</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Set Name *</label>
                    <input type="text" x-model="form.name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="e.g., Large Fragment Set #1">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Asset Cost *</label>
                    <input type="number" x-model="form.purchase_price" required step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Asset Tag / Serial *</label>
                    <input type="text" x-model="form.asset_name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="e.g., LFS-001">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Purchase Date *</label>
                    <input type="date" x-model="form.purchase_date" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>
        </div>

        <!-- 2. Instruments (Reusable) -->
        <div class="bg-white shadow rounded-lg p-6">
            <div class="flex justify-between items-center mb-4 border-b pb-2">
                <h3 class="text-lg font-medium text-gray-900">2. Instruments (Reusable Assets)</h3>
                <button type="button" @click="addInstrument()" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">+ Add Instrument</button>
            </div>
            
            <div class="space-y-2">
                <template x-if="form.instruments.length === 0">
                    <p class="text-sm text-gray-500 italic">No instruments defined yet.</p>
                </template>

                <template x-for="(inst, index) in form.instruments" :key="'inst-'+index">
                    <div class="flex gap-2 items-start bg-gray-50 p-3 rounded border border-gray-200">
                        <div class="flex-1">
                            <label class="block text-xs font-medium text-gray-500">Instrument Name *</label>
                            <input type="text" x-model="inst.name" required class="mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm" placeholder="e.g. Drill Guide">
                        </div>
                        <div class="w-1/4">
                            <label class="block text-xs font-medium text-gray-500">Serial No. (Opt)</label>
                            <input type="text" x-model="inst.serial_number" class="mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div class="w-20">
                            <label class="block text-xs font-medium text-gray-500">Qty *</label>
                            <input type="number" x-model="inst.quantity" min="1" required class="mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm">
                        </div>
                        <button type="button" @click="removeInstrument(index)" class="mt-6 text-red-500 hover:text-red-700 p-1">&times;</button>
                    </div>
                </template>
            </div>
        </div>

        <!-- 3. Standard Consumables (Par Levels) -->
        <div class="bg-white shadow rounded-lg p-6">
             <div class="flex justify-between items-center mb-4 border-b pb-2">
                <h3 class="text-lg font-medium text-gray-900">3. Consumables Template (Par Levels)</h3>
                <button type="button" @click="addContent()" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">+ Add Consumable</button>
            </div>

            <div class="border rounded-md overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-24">Par Level</th>
                            <th class="px-4 py-2 w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <template x-for="(item, index) in form.contents" :key="'cont-'+index">
                            <tr>
                                <td class="px-4 py-2">
                                    <select x-model="item.inventory_id" required class="block w-full text-sm border-gray-300 rounded-md">
                                        <option value="">Select Product...</option>
                                        <template x-for="prod in products" :key="prod.id">
                                            <option :value="prod.id" x-text="prod.product_name"></option>
                                        </template>
                                    </select>
                                </td>
                                <td class="px-4 py-2">
                                    <input type="number" x-model="item.quantity" min="1" required class="block w-full text-sm border-gray-300 rounded-md">
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <button type="button" @click="removeContent(index)" class="text-red-500 hover:text-red-700">&times;</button>
                                </td>
                            </tr>
                        </template>
                        <template x-if="form.contents.length === 0">
                            <tr>
                                <td colspan="3" class="px-4 py-4 text-center text-sm text-gray-500 italic">No consumables defined.</td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex justify-end gap-3 sticky bottom-0 bg-gray-50 p-4 border-t border-gray-200">
            <a href="{{ route('sets.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 shadow-sm">Save Complete Set</button>
        </div>
    </form>
</div>

<script>
function createSet() {
    return {
        products: @json($products),
        form: {
            name: '',
            asset_name: '',
            purchase_price: '',
            purchase_date: new Date().toISOString().split('T')[0],
            instruments: [
                { name: '', quantity: 1, serial_number: '', inventory_id: null }
            ],
            contents: [
                { inventory_id: '', quantity: 1 }
            ]
        },
        addInstrument() {
            this.form.instruments.push({ name: '', quantity: 1, serial_number: '', inventory_id: null });
        },
        removeInstrument(index) {
            this.form.instruments.splice(index, 1);
        },
        addContent() {
            this.form.contents.push({ inventory_id: '', quantity: 1 });
        },
        removeContent(index) {
            this.form.contents.splice(index, 1);
        },
        async submitForm() {
            try {
                // Prepare payload matching controller validation
                const payload = {
                    ...this.form,
                    // Filter out empty rows
                    instruments: this.form.instruments.filter(i => i.name && i.name.trim() !== ''),
                    contents: this.form.contents.filter(c => c.inventory_id)
                };

                const response = await axios.post('{{ route("sets.store") }}', payload);
                
                // Handle redirect from JSON response or reload
                if (response.data.redirect) {
                    window.location.href = response.data.redirect;
                } else {
                    window.location.href = '{{ route("sets.index") }}';
                }
            } catch (error) {
                console.error(error);
                let msg = 'Error creating set';
                if (error.response && error.response.data && error.response.data.message) {
                    msg = error.response.data.message;
                } else if (error.message) {
                    msg = error.message;
                }
                alert(msg);
            }
        }
    }
}
</script>
@endsection
