@extends('layouts.app')

@section('content')
<div x-data="supplierManager()" x-init="loadSuppliers()">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Suppliers</h1>
                <p class="mt-2 text-sm text-gray-600">Manage suppliers and vendors</p>
            </div>
            <button type="button" @click.prevent="showAddModal = true" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                Add Supplier
            </button>
        </div>

        <!-- Search -->
        <div class="bg-white shadow rounded-lg p-4 mb-6">
            <input type="text" x-model="search" @input.debounce.300ms="loadSuppliers()" placeholder="Search suppliers..." class="w-full rounded-md border-gray-300">
        </div>

        <!-- Suppliers Table -->
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <div x-show="loading" class="flex justify-center py-12">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
            </div>

            <table x-show="!loading" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Address</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="supplier in suppliers" :key="supplier.id">
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900" x-text="supplier.name"></div>
                                <div class="text-sm text-gray-500" x-text="supplier.contact_person || ''"></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900" x-text="supplier.phone || '-'"></div>
                                <div class="text-sm text-gray-500" x-text="supplier.email || ''"></div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500" x-text="supplier.address || '-'"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button @click="editSupplier(supplier)" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</button>
                                <button @click="deleteSupplier(supplier)" class="text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

    <!-- Add/Edit Modal -->
    <div x-show="showAddModal || showEditModal" class="fixed z-50 inset-0 overflow-y-auto" style="display: none;" x-cloak>
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="closeModal()"></div>
            <div class="relative inline-block align-middle bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form @submit.prevent="saveSupplier()">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" x-text="showAddModal ? 'Add Supplier' : 'Edit Supplier'"></h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Name *</label>
                                <input type="text" x-model="form.name" required class="mt-1 block w-full rounded-md border-gray-300">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Contact Person</label>
                                    <input type="text" x-model="form.contact_person" class="mt-1 block w-full rounded-md border-gray-300">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Phone</label>
                                    <input type="text" x-model="form.phone" class="mt-1 block w-full rounded-md border-gray-300">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Email</label>
                                <input type="email" x-model="form.email" class="mt-1 block w-full rounded-md border-gray-300">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Address</label>
                                <textarea x-model="form.address" rows="2" class="mt-1 block w-full rounded-md border-gray-300"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Payment Terms</label>
                                <input type="text" x-model="form.payment_terms" class="mt-1 block w-full rounded-md border-gray-300">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tax ID</label>
                                <input type="text" x-model="form.tax_id" class="mt-1 block w-full rounded-md border-gray-300">
                            </div>
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" x-model="form.is_active" class="rounded border-gray-300 text-indigo-600">
                                    <span class="ml-2 text-sm text-gray-700">Active</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm">
                            Save
                        </button>
                        <button type="button" @click="closeModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
window.supplierManager = function() {
    window.supplierManager = supplierManager;
    return {
        suppliers: [],
        search: '',
        loading: false,
        showAddModal: false,
        showEditModal: false,
        form: {
            id: null,
            name: '',
            contact_person: '',
            email: '',
            phone: '',
            address: '',
            payment_terms: '',
            tax_id: '',
            is_active: true
        },

        async loadSuppliers() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.search) params.append('search', this.search);
                const response = await axios.get(`/suppliers?${params}`);
                this.suppliers = response.data.data || response.data;
            } catch (error) {
                console.error('Error loading suppliers:', error);
            } finally {
                this.loading = false;
            }
        },

        editSupplier(supplier) {
            this.form = {...supplier};
            if (this.form.is_active === undefined || this.form.is_active === null) this.form.is_active = true;
            this.showEditModal = true;
        },

        async saveSupplier() {
            try {
                if (this.form.id) {
                    await axios.put(`/suppliers/${this.form.id}`, this.form);
                } else {
                    await axios.post('/suppliers', this.form);
                }
                this.closeModal();
                this.loadSuppliers();
                alert('Supplier saved successfully');
            } catch (error) {
                alert('Error saving supplier: ' + (error.response?.data?.message || error.message));
            }
        },

        async deleteSupplier(supplier) {
            if (!confirm(`Are you sure you want to delete ${supplier.name}?`)) return;
            try {
                await axios.delete(`/suppliers/${supplier.id}`);
                this.loadSuppliers();
                alert('Supplier deleted successfully');
            } catch (error) {
                alert('Error deleting supplier: ' + (error.response?.data?.message || error.message));
            }
        },

        closeModal() {
            this.showAddModal = false;
            this.showEditModal = false;
            this.form = {
                id: null,
                name: '',
                contact_person: '',
                email: '',
                phone: '',
                address: '',
                payment_terms: '',
                tax_id: '',
                is_active: true
            };
        }
    }
}
</script>
@endsection

