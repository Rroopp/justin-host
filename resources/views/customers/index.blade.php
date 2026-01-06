@extends('layouts.app')

@section('content')
<div x-data="customerManager()" x-init="loadCustomers()">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Customers</h1>
                <p class="mt-2 text-sm text-gray-600">Manage customers and patients</p>
            </div>
            <div class="flex gap-2">
                <button @click="exportCustomers()" class="bg-indigo-100 text-indigo-700 px-4 py-2 rounded-md hover:bg-indigo-200">
                    Export CSV
                </button>
                <button type="button" @click.prevent="showAddModal = true" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                    Add Customer
                </button>
            </div>
        </div>

        <!-- Search -->
        <div class="bg-white shadow rounded-lg p-4 mb-6">
            <input 
                type="text" 
                x-model="search" 
                @input.debounce.300ms="loadCustomers()"
                placeholder="Search customers..." 
                class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
            >
        </div>

        <!-- Customers Table -->
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <div x-show="loading" class="p-8 text-center">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
            </div>

            <table x-show="!loading" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient Info</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="customer in customers" :key="customer.id">
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900" x-text="customer.name"></div>
                                <div class="text-sm text-gray-500" x-text="customer.facility || ''"></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900" x-text="customer.phone || '-'"></div>
                                <div class="text-sm text-gray-500" x-text="customer.email || ''"></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900" x-text="customer.patient_name || '-'"></div>
                                <div class="text-sm text-gray-500" x-text="customer.patient_type || ''"></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button @click="editCustomer(customer)" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</button>
                                <button @click="deleteCustomer(customer)" class="text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

    <!-- Add/Edit Modal -->
    <div x-show="showAddModal || showEditModal" class="fixed z-50 inset-0 overflow-y-auto" style="display: none;" x-cloak>
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="closeModal()"></div>
            <div class="relative inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <form @submit.prevent="saveCustomer()">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" x-text="showAddModal ? 'Add Customer' : 'Edit Customer'"></h3>
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4" x-show="form.customer_code">
                                <div class="col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Customer Code</label>
                                    <input type="text" x-model="form.customer_code" disabled class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Name *</label>
                                    <input type="text" x-model="form.name" required class="mt-1 block w-full rounded-md border-gray-300">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Customer Type</label>
                                    <select x-model="form.customer_type" class="mt-1 block w-full rounded-md border-gray-300">
                                        <option value="individual">Individual</option>
                                        <option value="corporate">Corporate</option>
                                        <option value="hospital">Hospital</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Facility</label>
                                    <input type="text" x-model="form.facility" class="mt-1 block w-full rounded-md border-gray-300">
                                </div>
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
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">City</label>
                                    <input type="text" x-model="form.city" class="mt-1 block w-full rounded-md border-gray-300">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Postal Code</label>
                                    <input type="text" x-model="form.postal_code" class="mt-1 block w-full rounded-md border-gray-300">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Country</label>
                                    <input type="text" x-model="form.country" class="mt-1 block w-full rounded-md border-gray-300">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Tax Number</label>
                                    <input type="text" x-model="form.tax_number" class="mt-1 block w-full rounded-md border-gray-300">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Credit Limit</label>
                                    <input type="number" step="0.01" x-model="form.credit_limit" min="0" class="mt-1 block w-full rounded-md border-gray-300">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Current Balance</label>
                                    <input type="number" step="0.01" x-model="form.current_balance" class="mt-1 block w-full rounded-md border-gray-300">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Payment Terms</label>
                                <textarea x-model="form.payment_terms" rows="2" class="mt-1 block w-full rounded-md border-gray-300"></textarea>
                            </div>
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" x-model="form.is_active" class="rounded border-gray-300 text-indigo-600">
                                    <span class="ml-2 text-sm text-gray-700">Active</span>
                                </label>
                            </div>
                            <div class="border-t pt-4">
                                <h4 class="text-sm font-medium text-gray-700 mb-2">Patient Information</h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Patient Name</label>
                                        <input type="text" x-model="form.patient_name" class="mt-1 block w-full rounded-md border-gray-300">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Patient Number</label>
                                        <input type="text" x-model="form.patient_number" class="mt-1 block w-full rounded-md border-gray-300">
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700">Patient Type</label>
                                    <select x-model="form.patient_type" class="mt-1 block w-full rounded-md border-gray-300">
                                        <option value="">Select Type</option>
                                        <option value="Inpatient">Inpatient</option>
                                        <option value="Outpatient">Outpatient</option>
                                    </select>
                                </div>
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
window.customerManager = function() {
    return {
        customers: [],
        loading: false,
        search: '',
        showAddModal: false,
        showEditModal: false,
        form: {
            id: null,
            customer_code: '',
            customer_type: 'individual',
            name: '',
            facility: '',
            contact_person: '',
            phone: '',
            email: '',
            address: '',
            city: '',
            postal_code: '',
            country: 'Kenya',
            tax_number: '',
            payment_terms: '',
            credit_limit: 0,
            current_balance: 0,
            is_active: true,
            patient_name: '',
            patient_number: '',
            patient_type: ''
        },

        async loadCustomers() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.search) params.append('search', this.search);

                const response = await axios.get(`/customers?${params}`);
                this.customers = response.data.data || response.data;
            } catch (error) {
                console.error('Error loading customers:', error);
            } finally {
                this.loading = false;
            }
        },

        exportCustomers() {
            const params = new URLSearchParams();
            if (this.search) params.append('search', this.search);
            params.append('export', 'true');
            window.location.href = `/customers?${params.toString()}`;
        },

        editCustomer(customer) {
            this.form = {...customer};
            if (this.form.is_active === undefined || this.form.is_active === null) this.form.is_active = true;
            if (!this.form.country) this.form.country = 'Kenya';
            if (!this.form.customer_type) this.form.customer_type = 'individual';
            this.showEditModal = true;
        },

        async saveCustomer() {
            try {
                if (this.form.id) {
                    await axios.put(`/customers/${this.form.id}`, this.form);
                } else {
                    await axios.post('/customers', this.form);
                }
                this.closeModal();
                this.loadCustomers();
                alert('Customer saved successfully');
            } catch (error) {
                alert('Error saving customer: ' + (error.response?.data?.message || error.message));
            }
        },

        async deleteCustomer(customer) {
            if (!confirm(`Are you sure you want to delete ${customer.name}?`)) return;
            
            try {
                await axios.delete(`/customers/${customer.id}`);
                this.loadCustomers();
                alert('Customer deleted successfully');
            } catch (error) {
                alert('Error deleting customer: ' + (error.response?.data?.message || error.message));
            }
        },

        closeModal() {
            this.showAddModal = false;
            this.showEditModal = false;
            this.form = {
                id: null,
                customer_code: '',
                customer_type: 'individual',
                name: '',
                facility: '',
                contact_person: '',
                phone: '',
                email: '',
                address: '',
                city: '',
                postal_code: '',
                country: 'Kenya',
                tax_number: '',
                payment_terms: '',
                credit_limit: 0,
                current_balance: 0,
                is_active: true,
                patient_name: '',
                patient_number: '',
                patient_type: ''
            };
        }
    }
}
</script>
@endsection

