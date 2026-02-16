
            <!-- Staff Permissions -->
            <div class="space-y-6" x-show="tab==='permissions'">
                <div>
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Staff Permissions</h2>
                    <p class="text-sm text-gray-600 mb-6">Assign specific permissions to staff members. Admins have full access by default.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Staff List -->
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <h3 class="text-sm font-semibold text-gray-700 mb-3">Select Staff Member</h3>
                            <div class="space-y-2 max-h-96 overflow-y-auto">
                                <template x-for="staff in staffMembers" :key="staff.id">
                                    <button type="button" 
                                        @click="selectStaffForPermissions(staff)"
                                        class="w-full text-left px-3 py-2 rounded-md transition-colors flex justify-between items-center"
                                        :class="selectedStaff?.id === staff.id ? 'bg-indigo-100 text-indigo-800 border-indigo-200 border' : 'bg-white hover:bg-gray-100 text-gray-700 border border-transparent'">
                                        <div>
                                            <div class="font-medium" x-text="staff.full_name"></div>
                                            <div class="text-xs text-gray-500" x-text="staff.designation || 'Staff'"></div>
                                        </div>
                                        <svg x-show="selectedStaff?.id === staff.id" class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                </template>
                                <div x-show="staffMembers.length === 0" class="text-sm text-gray-500 italic p-2">
                                    No non-admin staff found.
                                </div>
                            </div>
                        </div>

                        <!-- Permission Toggles -->
                        <div class="md:col-span-2 bg-white border border-gray-200 rounded-lg p-6">
                            <template x-if="selectedStaff">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center gap-2">
                                        <span x-text="selectedStaff.full_name"></span>'s Permissions
                                        <span class="text-xs px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full" x-show="permissionsChanged">Unsaved Changes</span>
                                    </h3>

                                    <div class="space-y-6">
                                        <!-- Inventory -->
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900 border-b pb-1 mb-2">Inventory</h4>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                                    <input type="checkbox" value="inventory.view" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    View Inventory
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                                    <input type="checkbox" value="inventory.edit" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    Edit/Add/Delete (Manage)
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                                    <input type="checkbox" value="inventory.adjust" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    Adjust Stock
                                                </label>
                                            </div>
                                        </div>

                                        <!-- POS & Sales -->
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900 border-b pb-1 mb-2">POS & Sales</h4>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                                    <input type="checkbox" value="pos.access" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    Access POS
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                                    <input type="checkbox" value="pos.discount" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    Give Discounts
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                                    <input type="checkbox" value="sales.view" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    View Sales History (All)
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                                    <input type="checkbox" value="sales.refund" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    Process Refunds
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Reports -->
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900 border-b pb-1 mb-2">Reports & Analytics</h4>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                                    <input type="checkbox" value="reports.view" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    View Reports
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                                    <input type="checkbox" value="finance.view" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    View Financials
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Accounting -->
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900 border-b pb-1 mb-2">Accounting</h4>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                                    <input type="checkbox" value="accounting.view" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    View Accounting Dashboard
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                                    <input type="checkbox" value="accounting.manage" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    Manage Accounts & Entries
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                                    <input type="checkbox" value="accounting.reports" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    View Financial Reports
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Payroll -->
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900 border-b pb-1 mb-2">Payroll</h4>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                                    <input type="checkbox" value="payroll.view" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    View Payroll
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                                    <input type="checkbox" value="payroll.process" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    Process Payroll & Payslips
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Suppliers & CRM -->
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900 border-b pb-1 mb-2">Suppliers & Customers</h4>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                                    <input type="checkbox" value="suppliers.view" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    View Suppliers
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                                    <input type="checkbox" value="suppliers.manage" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    Manage Suppliers
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                                    <input type="checkbox" value="customers.view" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    View Customers
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                                    <input type="checkbox" value="customers.manage" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    Manage Customers
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Orders & Rentals -->
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900 border-b pb-1 mb-2">Orders & Rentals</h4>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                                    <input type="checkbox" value="orders.create" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    Create Orders (LPO)
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                                    <input type="checkbox" value="orders.approve" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    Approve Orders
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                                    <input type="checkbox" value="rentals.view" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    View Rentals/Assets
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                                    <input type="checkbox" value="rentals.manage" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    Manage Rentals
                                                </label>
                                            </div>
                                        </div>

                                    <div class="mt-6 border-t pt-4 flex justify-end">
                                        <button type="button" @click="savePermissions()" 
                                            class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 disabled:opacity-50"
                                            :disabled="!permissionsChanged">
                                            Save Permissions
                                        </button>
                                    </div>
                                </div>
                            </template>
                            <div x-show="!selectedStaff" class="text-center py-12 text-gray-500">
                                <svg class="h-12 w-12 mx-auto text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                Select a staff member to manage permissions
                            </div>
                        </div>
                    </div>
                </div>
            </div>
