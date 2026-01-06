            <!-- Audit Log Tab - Comprehensive Forensic Trail -->
            <div class="space-y-6" x-show="tab==='audit'" x-data="auditLogManager()" x-init="loadFilters(); loadAuditLogs()">
                <div>
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h2 class="text-lg font-medium text-gray-900">Comprehensive Audit Log</h2>
                            <p class="text-sm text-gray-600 mt-1">Complete forensic trail of every action performed in the system - inventory changes, sales, payments, refunds, and more.</p>
                        </div>
                        <button @click="exportAuditLogs()" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Export CSV
                        </button>
                    </div>

                    <!-- Filters -->
                    <div class="bg-gray-50 border rounded-lg p-4 mb-4">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                                <input type="text" 
                                       x-model="filters.search" 
                                       @input.debounce.500ms="loadAuditLogs()"
                                       placeholder="Description, user, ID..." 
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">User</label>
                                <select x-model="filters.user_id" @change="loadAuditLogs()" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                    <option value="">All Users</option>
                                    <template x-for="user in users" :key="user.id">
                                        <option :value="user.id" x-text="user.full_name"></option>
                                    </template>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Module</label>
                                <select x-model="filters.module" @change="loadAuditLogs()" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                    <option value="">All Modules</option>
                                    <template x-for="module in modules" :key="module">
                                        <option :value="module" x-text="module"></option>
                                    </template>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Action</label>
                                <select x-model="filters.action" @change="loadAuditLogs()" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                    <option value="">All Actions</option>
                                    <template x-for="action in actions" :key="action">
                                        <option :value="action" x-text="action"></option>
                                    </template>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                                <input type="date" 
                                       x-model="filters.date_from" 
                                       @change="loadAuditLogs()"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                                <input type="date" 
                                       x-model="filters.date_to" 
                                       @change="loadAuditLogs()"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            </div>

                            <div class="flex items-end gap-2 md:col-span-2">
                                <button @click="clearFilters()" class="flex-1 bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 text-sm">
                                    Clear Filters
                                </button>
                                <button @click="loadAuditLogs()" class="flex-1 bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 text-sm">
                                    Refresh
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Loading State -->
                    <div x-show="loading" class="flex justify-center py-12">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                    </div>

                    <!-- Audit Logs Table -->
                    <div x-show="!loading" class="border rounded-lg overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date/Time</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Module</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template x-for="log in logs" :key="log.id">
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900" x-text="formatDate(log.created_at)"></td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700" x-text="log.user_name || 'System'"></td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                                      :class="{
                                                          'bg-green-100 text-green-800': ['create', 'sale_completed'].includes(log.action),
                                                          'bg-blue-100 text-blue-800': log.action === 'update',
                                                          'bg-red-100 text-red-800': log.action === 'delete',
                                                          'bg-yellow-100 text-yellow-800': ['refund_requested', 'refund_approved'].includes(log.action),
                                                          'bg-gray-100 text-gray-800': !['create', 'update', 'delete', 'sale_completed', 'refund_requested', 'refund_approved'].includes(log.action)
                                                      }"
                                                      x-text="log.action">
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700" x-text="log.module"></td>
                                            <td class="px-4 py-3 text-sm text-gray-900 max-w-md truncate" x-text="log.description"></td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500" x-text="log.ip_address || '-'"></td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm">
                                                <button @click="viewDetails(log)" class="text-indigo-600 hover:text-indigo-900">
                                                    View
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr x-show="logs.length === 0">
                                        <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">
                                            No audit logs found
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div x-show="pagination.total > 0" class="bg-gray-50 px-4 py-3 border-t border-gray-200 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="text-sm text-gray-700">
                                    Showing <span class="font-medium" x-text="pagination.from"></span> to <span class="font-medium" x-text="pagination.to"></span> of <span class="font-medium" x-text="pagination.total"></span> results
                                </div>
                                <div class="flex gap-2">
                                    <button @click="changePage(pagination.current_page - 1)" 
                                            :disabled="pagination.current_page === 1"
                                            :class="pagination.current_page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-200'"
                                            class="px-3 py-1 border rounded-md text-sm">
                                        Previous
                                    </button>
                                    <span class="px-3 py-1 text-sm text-gray-700">
                                        Page <span x-text="pagination.current_page"></span> of <span x-text="pagination.last_page"></span>
                                    </span>
                                    <button @click="changePage(pagination.current_page + 1)" 
                                            :disabled="pagination.current_page === pagination.last_page"
                                            :class="pagination.current_page === pagination.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-200'"
                                            class="px-3 py-1 border rounded-md text-sm">
                                        Next
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detail Modal -->
                    <div x-show="showDetailModal" 
                         x-cloak
                         class="fixed inset-0 z-50 overflow-y-auto" 
                         style="display: none;">
                        <div class="flex items-center justify-center min-h-screen px-4">
                            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showDetailModal = false"></div>
                            
                            <div class="relative bg-white rounded-lg max-w-3xl w-full p-6 shadow-xl">
                                <div class="flex justify-between items-start mb-4">
                                    <h3 class="text-lg font-medium text-gray-900">Audit Log Details</h3>
                                    <button @click="showDetailModal = false" class="text-gray-400 hover:text-gray-500">
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>

                                <template x-if="selectedLog">
                                    <div class="space-y-4">
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500">Date/Time</dt>
                                                <dd class="mt-1 text-sm text-gray-900" x-text="formatDate(selectedLog.created_at)"></dd>
                                            </div>
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500">User</dt>
                                                <dd class="mt-1 text-sm text-gray-900" x-text="selectedLog.user_name || 'System'"></dd>
                                            </div>
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500">Action</dt>
                                                <dd class="mt-1 text-sm text-gray-900" x-text="selectedLog.action"></dd>
                                            </div>
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500">Module</dt>
                                                <dd class="mt-1 text-sm text-gray-900" x-text="selectedLog.module"></dd>
                                            </div>
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500">Target Type</dt>
                                                <dd class="mt-1 text-sm text-gray-900" x-text="selectedLog.target_type || '-'"></dd>
                                            </div>
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500">Target ID</dt>
                                                <dd class="mt-1 text-sm text-gray-900" x-text="selectedLog.target_id || '-'"></dd>
                                            </div>
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500">IP Address</dt>
                                                <dd class="mt-1 text-sm text-gray-900" x-text="selectedLog.ip_address || '-'"></dd>
                                            </div>
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500">User Agent</dt>
                                                <dd class="mt-1 text-sm text-gray-900 truncate" x-text="selectedLog.user_agent || '-'"></dd>
                                            </div>
                                        </div>

                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Description</dt>
                                            <dd class="mt-1 text-sm text-gray-900" x-text="selectedLog.description"></dd>
                                        </div>

                                        <div x-show="selectedLog.old_values" class="border-t pt-4">
                                            <dt class="text-sm font-medium text-gray-500 mb-2">Old Values (Before)</dt>
                                            <dd class="mt-1 text-xs text-gray-900 bg-gray-50 p-3 rounded border overflow-x-auto">
                                                <pre x-text="JSON.stringify(selectedLog.old_values, null, 2)"></pre>
                                            </dd>
                                        </div>

                                        <div x-show="selectedLog.new_values" class="border-t pt-4">
                                            <dt class="text-sm font-medium text-gray-500 mb-2">New Values (After)</dt>
                                            <dd class="mt-1 text-xs text-gray-900 bg-gray-50 p-3 rounded border overflow-x-auto">
                                                <pre x-text="JSON.stringify(selectedLog.new_values, null, 2)"></pre>
                                            </dd>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
