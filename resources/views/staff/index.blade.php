@extends('layouts.app')

@section('content')
<div x-data="staffManager()" x-init="loadStaff()">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Staff Management</h1>
                <p class="mt-2 text-sm text-gray-600">Manage staff members and roles</p>
            </div>
            <button type="button" @click.prevent="openAddModal()" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                Add Staff
            </button>
        </div>

        <!-- Filters -->
        <div class="bg-white shadow rounded-lg p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <select x-model="filters.role" @change="loadStaff()" class="w-full rounded-md border-gray-300">
                        <option value="">All Roles</option>
                        <option value="admin">Admin</option>
                        <option value="staff">Staff</option>
                        <option value="accountant">Accountant</option>
                    </select>
                </div>
                <div>
                    <select x-model="filters.status" @change="loadStaff()" class="w-full rounded-md border-gray-300">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
                <div>
                    <input type="text" x-model="filters.search" @input.debounce.300ms="loadStaff()" placeholder="Search..." class="w-full rounded-md border-gray-300">
                </div>
            </div>
        </div>

        <!-- Staff Table -->
        <div class="bg-white shadow sm:rounded-md">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="member in staff" :key="member.id">
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="member.full_name"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="member.username"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="member.role"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span 
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    :class="{
                                        'bg-green-100 text-green-800': member.status === 'active',
                                        'bg-gray-100 text-gray-800': member.status === 'inactive',
                                        'bg-red-100 text-red-800': member.status === 'suspended'
                                    }"
                                    x-text="member.status"
                                ></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="member.email || member.phone || '-'"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex flex-col items-end gap-2">
                                    <a :href="`/staff/${member.id}`" class="text-blue-600 hover:text-blue-900">View</a>
                                    <button @click="editStaff(member)" class="text-indigo-600 hover:text-indigo-900">Edit</button>
                                    <button 
                                        x-show="member.status !== 'suspended'" 
                                        @click="suspendStaff(member)" 
                                        class="text-orange-600 hover:text-orange-900"
                                    >
                                        Suspend
                                    </button>
                                    <button 
                                        x-show="member.status === 'suspended'" 
                                        @click="reinstateStaff(member)" 
                                        class="text-green-600 hover:text-green-900"
                                    >
                                        Reinstate
                                    </button>
                                    <button @click="deleteStaff(member)" class="text-red-600 hover:text-red-900">Delete</button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

    <!-- Add/Edit Modal -->
    <div x-show="showAddModal || showEditModal" x-transition.opacity class="fixed z-50 inset-0 overflow-y-auto" @keydown.escape.window="closeModal()">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="closeModal()"></div>
            <div class="relative inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <form @submit.prevent="saveStaff()">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" x-text="showAddModal ? 'Add Staff Member' : 'Edit Staff Member'"></h3>
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Username *</label>
                                    <input type="text" x-model="form.username" required class="mt-1 block w-full rounded-md border-gray-300" x-ref="usernameInput">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Full Name *</label>
                                    <input type="text" x-model="form.full_name" required class="mt-1 block w-full rounded-md border-gray-300">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700" x-text="showAddModal ? 'Password *' : 'New Password (optional)'"></label>
                                <input type="password" x-model="form.password" :required="showAddModal" placeholder="Leave blank to keep current password" class="mt-1 block w-full rounded-md border-gray-300">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Role *</label>
                                    <select x-model="form.role" required class="mt-1 block w-full rounded-md border-gray-300">
                                        <option value="staff">Staff</option>
                                        <option value="admin">Admin</option>
                                        <option value="accountant">Accountant</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Status *</label>
                                    <select x-model="form.status" required class="mt-1 block w-full rounded-md border-gray-300">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="suspended">Suspended</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Email</label>
                                    <input type="email" x-model="form.email" class="mt-1 block w-full rounded-md border-gray-300">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Phone</label>
                                    <input type="text" x-model="form.phone" class="mt-1 block w-full rounded-md border-gray-300">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">ID Number</label>
                                    <input type="text" x-model="form.id_number" class="mt-1 block w-full rounded-md border-gray-300">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Salary</label>
                                    <input type="number" step="0.01" min="0" x-model="form.salary" class="mt-1 block w-full rounded-md border-gray-300">
                                </div>
                            </div>
                            <!-- Job & Bank Details -->
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 border-b pb-2 mb-3">Job & Banking Details</h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Designation / Job Title</label>
                                        <input type="text" x-model="form.designation" placeholder="e.g. Senior Nurse" class="mt-1 block w-full rounded-md border-gray-300">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Bank Name</label>
                                        <input type="text" x-model="form.bank_name" placeholder="e.g. KCB, Equity" class="mt-1 block w-full rounded-md border-gray-300">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Account Number</label>
                                        <input type="text" x-model="form.account_number" class="mt-1 block w-full rounded-md border-gray-300">
                                    </div>
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
window.staffManager = function() {
    return {
        staff: [],
        filters: {
            role: '',
            status: '',
            search: ''
        },
        showAddModal: false,
        showEditModal: false,
        form: {
            id: null,
            username: '',
            password: '',
            full_name: '',
            role: 'staff',
            status: 'active',
            email: '',
            phone: '',
            id_number: '',
            phone: '',
            id_number: '',
            salary: '',
            designation: '',
            bank_name: '',
            account_number: ''
        },

        async loadStaff() {
            try {
                const params = new URLSearchParams();
                if (this.filters.role) params.append('role', this.filters.role);
                if (this.filters.status) params.append('status', this.filters.status);
                if (this.filters.search) params.append('search', this.filters.search);

                const response = await axios.get(`/staff?${params}`);
                this.staff = response.data.data || response.data;
            } catch (error) {
                console.error('Error loading staff:', error);
            }
        },

        openAddModal() {
            this.showAddModal = true;
            this.showEditModal = false;
            this.form = {
                id: null,
                username: '',
                password: '',
                full_name: '',
                role: 'staff',
                status: 'active',
                email: '',
                phone: '',
                id_number: '',
                id_number: '',
                salary: '',
                designation: '',
                bank_name: '',
                account_number: ''
            };
            this.$nextTick(() => {
                this.$refs.usernameInput?.focus();
            });
        },

        editStaff(member) {
            this.form = {
                id: member.id,
                username: member.username,
                password: '',
                full_name: member.full_name,
                role: member.role,
                status: member.status,
                email: member.email || '',
                phone: member.phone || '',
                id_number: member.id_number || '',
                id_number: member.id_number || '',
                salary: member.salary || '',
                designation: member.designation || '',
                bank_name: member.bank_name || '',
                account_number: member.account_number || ''
            };
            this.showEditModal = true;
            this.$nextTick(() => {
                this.$refs.usernameInput?.focus();
            });
        },

        async saveStaff() {
            try {
                const payload = { ...this.form };
                // Avoid failing validation on update when password is blank
                if (!payload.password) {
                    delete payload.password;
                }

                if (this.form.id) {
                    await axios.put(`/staff/${this.form.id}`, payload);
                } else {
                    await axios.post('/staff', payload);
                }
                this.closeModal();
                this.loadStaff();
                alert('Staff member saved successfully');
            } catch (error) {
                alert('Error saving staff: ' + (error.response?.data?.message || error.message));
            }
        },

        async deleteStaff(member) {
            if (!confirm(`Are you sure you want to delete ${member.full_name}?`)) return;
            try {
                await axios.delete(`/staff/${member.id}`);
                this.loadStaff();
                alert('Staff member deleted successfully');
            } catch (error) {
                alert('Error deleting staff: ' + (error.response?.data?.message || error.message));
            }
        },

        async suspendStaff(member) {
            if (!confirm(`Are you sure you want to suspend ${member.full_name}?`)) return;
            try {
                await axios.post(`/staff/${member.id}/suspend`);
                this.loadStaff();
                alert('Staff member suspended successfully');
            } catch (error) {
                alert('Error suspending staff: ' + (error.response?.data?.message || error.message));
            }
        },

        async reinstateStaff(member) {
            if (!confirm(`Are you sure you want to reinstate ${member.full_name}?`)) return;
            try {
                await axios.post(`/staff/${member.id}/reinstate`);
                this.loadStaff();
                alert('Staff member reinstated successfully');
            } catch (error) {
                alert('Error reinstating staff: ' + (error.response?.data?.message || error.message));
            }
        },

        closeModal() {
            this.showAddModal = false;
            this.showEditModal = false;
            this.form = {
                id: null,
                username: '',
                password: '',
                full_name: '',
                role: 'pos_clerk',
                status: 'active',
                email: '',
                phone: '',
                id_number: '',
                id_number: '',
                salary: '',
                designation: '',
                bank_name: '',
                account_number: ''
            };
        }
    }
}
</script>
@endsection

