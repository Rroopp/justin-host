
            <!-- Staff Permissions -->
            <div class="space-y-6" x-show="tab==='permissions'">
                <div>
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Staff Permissions</h2>
                    <p class="text-sm text-gray-600 mb-2">Assign specific permissions to staff members. Admins have full access by default.</p>
                    <p class="text-xs text-gray-500 mb-6">
                        <span class="inline-flex items-center gap-1"><span class="w-2 h-2 bg-indigo-500 rounded-full"></span> Blue dot = Default permission for this role</span>
                        <span class="inline-flex items-center gap-1 ml-3"><span class="w-2 h-2 bg-amber-500 rounded-full"></span> Amber dot = Admin-level permission</span>
                    </p>

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
                                            <div class="text-xs text-gray-500 capitalize" x-text="staff.role + (staff.designation ? ' - ' + staff.designation : '')"></div>
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
                                    <div class="flex items-center justify-between mb-4">
                                        <h3 class="text-lg font-medium text-gray-900 flex items-center gap-2">
                                            <span x-text="selectedStaff.full_name"></span>'s Permissions
                                            <span class="text-xs px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full" x-show="permissionsChanged">Unsaved Changes</span>
                                        </h3>
                                        <div class="flex gap-2">
                                            <button type="button" @click="resetToDefaults()" class="text-xs px-3 py-1 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">
                                                Reset to Defaults
                                            </button>
                                            <button type="button" @click="grantAllPermissions()" class="text-xs px-3 py-1 bg-indigo-100 text-indigo-700 rounded hover:bg-indigo-200">
                                                Grant All
                                            </button>
                                            <button type="button" @click="clearAllPermissions()" class="text-xs px-3 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200">
                                                Clear All
                                            </button>
                                        </div>
                                    </div>

                                    <div class="space-y-6 max-h-[600px] overflow-y-auto pr-2">
                                        <!-- Inventory -->
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900 border-b pb-1 mb-2">Inventory</h4>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="inventory.view" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">View Inventory</span>
                                                    <span x-show="isDefaultPermission('inventory.view')" class="w-2 h-2 bg-indigo-500 rounded-full" title="Default for this role"></span>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="inventory.edit" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">Edit/Add/Delete (Manage)</span>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="inventory.adjust" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">Adjust Stock</span>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="inventory.categories" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">Manage Categories</span>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="inventory.batches" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">Manage Batches</span>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- POS & Sales -->
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900 border-b pb-1 mb-2">POS & Sales</h4>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="pos.access" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">Access POS</span>
                                                    <span x-show="isDefaultPermission('pos.access')" class="w-2 h-2 bg-indigo-500 rounded-full" title="Default for this role"></span>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="pos.discount" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">Give Discounts</span>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="sales.view" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">View Sales History (All)</span>
                                                    <span x-show="isDefaultPermission('sales.view')" class="w-2 h-2 bg-indigo-500 rounded-full" title="Default for this role"></span>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="sales.refund" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">Process Refunds</span>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="sales.invoices" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">View/Manage Invoices</span>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Reports -->
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900 border-b pb-1 mb-2">Reports & Analytics</h4>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="reports.view" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">View Reports</span>
                                                    <span x-show="isDefaultPermission('reports.view')" class="w-2 h-2 bg-indigo-500 rounded-full" title="Default for this role"></span>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="finance.view" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">View Financials</span>
                                                    <span x-show="isDefaultPermission('finance.view')" class="w-2 h-2 bg-indigo-500 rounded-full" title="Default for this role"></span>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="reports.analytics" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">View Analytics Dashboard</span>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Accounting -->
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900 border-b pb-1 mb-2">Accounting</h4>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="accounting.view" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">View Accounting Dashboard</span>
                                                    <span x-show="isDefaultPermission('accounting.view')" class="w-2 h-2 bg-indigo-500 rounded-full" title="Default for this role"></span>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="accounting.manage" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">Manage Accounts & Entries</span>
                                                    <span x-show="isAdminLevelPermission('accounting.manage')" class="w-2 h-2 bg-amber-500 rounded-full" title="Admin-level permission"></span>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="accounting.reports" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">View Financial Reports</span>
                                                    <span x-show="isDefaultPermission('accounting.reports')" class="w-2 h-2 bg-indigo-500 rounded-full" title="Default for this role"></span>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="accounting.journal" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">Manage Journal Entries</span>
                                                    <span x-show="isAdminLevelPermission('accounting.journal')" class="w-2 h-2 bg-amber-500 rounded-full" title="Admin-level permission"></span>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="accounting.periods" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">Manage Accounting Periods</span>
                                                    <span x-show="isAdminLevelPermission('accounting.periods')" class="w-2 h-2 bg-amber-500 rounded-full" title="Admin-level permission"></span>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Payroll -->
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900 border-b pb-1 mb-2">Payroll</h4>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="payroll.view" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">View Payroll</span>
                                                    <span x-show="isDefaultPermission('payroll.view')" class="w-2 h-2 bg-indigo-500 rounded-full" title="Default for this role"></span>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="payroll.process" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">Process Payroll & Payslips</span>
                                                    <span x-show="isAdminLevelPermission('payroll.process')" class="w-2 h-2 bg-amber-500 rounded-full" title="Admin-level permission"></span>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="payroll.deductions" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">Manage Deduction Types</span>
                                                    <span x-show="isAdminLevelPermission('payroll.deductions')" class="w-2 h-2 bg-amber-500 rounded-full" title="Admin-level permission"></span>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="payroll.reimbursements" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">Approve Reimbursements</span>
                                                    <span x-show="isAdminLevelPermission('payroll.reimbursements')" class="w-2 h-2 bg-amber-500 rounded-full" title="Admin-level permission"></span>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Banking -->
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900 border-b pb-1 mb-2">Banking</h4>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="banking.view" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">View Banking</span>
                                                    <span x-show="isDefaultPermission('banking.view')" class="w-2 h-2 bg-indigo-500 rounded-full" title="Default for this role"></span>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="banking.manage" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">Manage Bank Accounts</span>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="banking.reconcile" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">Reconcile Bank Statements</span>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Suppliers & CRM -->
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900 border-b pb-1 mb-2">Suppliers & Customers</h4>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="suppliers.view" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">View Suppliers</span>
                                                    <span x-show="isDefaultPermission('suppliers.view')" class="w-2 h-2 bg-indigo-500 rounded-full" title="Default for this role"></span>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="suppliers.manage" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">Manage Suppliers</span>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="customers.view" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">View Customers</span>
                                                    <span x-show="isDefaultPermission('customers.view')" class="w-2 h-2 bg-indigo-500 rounded-full" title="Default for this role"></span>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="customers.manage" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">Manage Customers</span>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Orders & Rentals -->
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900 border-b pb-1 mb-2">Orders & Rentals</h4>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="orders.create" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">Create Orders (LPO)</span>
                                                    <span x-show="isDefaultPermission('orders.create')" class="w-2 h-2 bg-indigo-500 rounded-full" title="Default for this role"></span>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="orders.approve" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">Approve Orders</span>
                                                    <span x-show="isAdminLevelPermission('orders.approve')" class="w-2 h-2 bg-amber-500 rounded-full" title="Admin-level permission"></span>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="rentals.view" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">View Rentals/Assets</span>
                                                    <span x-show="isDefaultPermission('rentals.view')" class="w-2 h-2 bg-indigo-500 rounded-full" title="Default for this role"></span>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="rentals.manage" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">Manage Rentals</span>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Budgets -->
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900 border-b pb-1 mb-2">Budgets</h4>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="budgets.view" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">View Budgets</span>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="budgets.manage" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">Manage Budgets</span>
                                                    <span x-show="isAdminLevelPermission('budgets.manage')" class="w-2 h-2 bg-amber-500 rounded-full" title="Admin-level permission"></span>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="budgets.approve" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">Approve Budgets</span>
                                                    <span x-show="isAdminLevelPermission('budgets.approve')" class="w-2 h-2 bg-amber-500 rounded-full" title="Admin-level permission"></span>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Consignments -->
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900 border-b pb-1 mb-2">Consignments</h4>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="consignments.view" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">View Consignments</span>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="consignments.manage" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">Manage Consignments</span>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="consignments.settle" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">Settle Consignments</span>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Stock Management -->
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900 border-b pb-1 mb-2">Stock Management</h4>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="stock.view" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">View Stock Takes</span>
                                                    <span x-show="isDefaultPermission('stock.view')" class="w-2 h-2 bg-indigo-500 rounded-full" title="Default for this role"></span>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="stock.create" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">Create Stock Takes</span>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="stock.transfers.view" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">View Stock Transfers</span>
                                                    <span x-show="isDefaultPermission('stock.transfers.view')" class="w-2 h-2 bg-indigo-500 rounded-full" title="Default for this role"></span>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="stock.transfers.manage" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">Manage Stock Transfers</span>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Expenses -->
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900 border-b pb-1 mb-2">Expenses</h4>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="expenses.view" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">View Expenses</span>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="expenses.manage" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">Manage Expenses & Bills</span>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Commissions -->
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900 border-b pb-1 mb-2">Commissions</h4>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="commissions.view" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">View Commissions</span>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="commissions.manage" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">Manage Commissions</span>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Assets -->
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900 border-b pb-1 mb-2">Fixed Assets</h4>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="assets.view" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">View Assets</span>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="assets.manage" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">Manage Assets</span>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Surgery Usage -->
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900 border-b pb-1 mb-2">Surgery Usage</h4>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="surgery.view" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">View Surgery Usage</span>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="surgery.manage" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">Manage Surgery Usage</span>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Packages -->
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900 border-b pb-1 mb-2">Packages</h4>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="packages.view" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">View Packages</span>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="packages.manage" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">Manage Packages</span>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Documents -->
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900 border-b pb-1 mb-2">Document Templates</h4>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="documents.view" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">View Document Templates</span>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="documents.manage" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">Manage Document Templates</span>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Audit -->
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900 border-b pb-1 mb-2">Audit</h4>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="audit.view" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">View Audit Logs</span>
                                                    <span x-show="isAdminLevelPermission('audit.view')" class="w-2 h-2 bg-amber-500 rounded-full" title="Admin-level permission"></span>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="audit.manage" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">Manage Audit Settings</span>
                                                    <span x-show="isAdminLevelPermission('audit.manage')" class="w-2 h-2 bg-amber-500 rounded-full" title="Admin-level permission"></span>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Settings -->
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900 border-b pb-1 mb-2">Settings</h4>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="settings.view" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">View Settings</span>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="settings.manage" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">Manage Settings</span>
                                                    <span x-show="isAdminLevelPermission('settings.manage')" class="w-2 h-2 bg-amber-500 rounded-full" title="Admin-level permission"></span>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="settings.company" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">Manage Company Settings</span>
                                                    <span x-show="isAdminLevelPermission('settings.company')" class="w-2 h-2 bg-amber-500 rounded-full" title="Admin-level permission"></span>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input type="checkbox" value="settings.users" x-model="selectedStaffPermissions" class="rounded border-gray-300">
                                                    <span class="flex-1">Manage Users & Permissions</span>
                                                    <span x-show="isAdminLevelPermission('settings.users')" class="w-2 h-2 bg-amber-500 rounded-full" title="Admin-level permission"></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-6 border-t pt-4 flex justify-end gap-2">
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
