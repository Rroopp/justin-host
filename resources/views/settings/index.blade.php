@extends('layouts.app')

@section('content')
<div x-data="settingsManager()" x-init="init()">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Settings</h1>
        <p class="mt-2 text-sm text-gray-600">System, company, module toggles, preferences, and audit log</p>
    </div>

    <div class="bg-white shadow rounded-lg">
        <div class="p-6">
                @if(auth()->user()->hasRole('admin'))
                <button type="button" @click="tab='system'" class="px-3 py-2 rounded-md text-sm font-medium"
                    :class="tab==='system' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                    System
                </button>
                <button type="button" @click="tab='inventory'" class="px-3 py-2 rounded-md text-sm font-medium"
                    :class="tab==='inventory' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                    Inventory
                </button>
                <button type="button" @click="tab='security'" class="px-3 py-2 rounded-md text-sm font-medium"
                    :class="tab==='security' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                    Security
                </button>
                <button type="button" @click="tab='permissions'" class="px-3 py-2 rounded-md text-sm font-medium"
                    :class="tab==='permissions' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                    Permissions
                </button>
                <button type="button" @click="tab='company'" class="px-3 py-2 rounded-md text-sm font-medium"
                    :class="tab==='company' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                    Company
                </button>
                <button type="button" @click="tab='modules'" class="px-3 py-2 rounded-md text-sm font-medium"
                    :class="tab==='modules' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                    Modules
                </button>
                @endif
                <button type="button" @click="tab='preferences'" class="px-3 py-2 rounded-md text-sm font-medium"
                    :class="tab==='preferences' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                    Preferences
                </button>
                @if(auth()->user()->hasRole('admin'))
                <button type="button" @click="tab='audit'" class="px-3 py-2 rounded-md text-sm font-medium"
                    :class="tab==='audit' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                    Audit Log
                </button>
                @endif

            <!-- System -->
            <div class="space-y-6" x-show="tab==='system'">
                <div>
                    <h2 class="text-lg font-medium text-gray-900 mb-4">System Settings</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Currency Code</label>
                            <input type="text" x-model="settings.system.currency_code" class="mt-1 block w-full rounded-md border-gray-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Currency Symbol</label>
                            <input type="text" x-model="settings.system.currency_symbol" class="mt-1 block w-full rounded-md border-gray-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Default Tax Rate (%)</label>
                            <input type="number" step="0.01" x-model="settings.system.default_tax_rate" class="mt-1 block w-full rounded-md border-gray-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Invoice Prefix</label>
                            <input type="text" x-model="settings.system.invoice_prefix" class="mt-1 block w-full rounded-md border-gray-300">
                        </div>
                    </div>
                </div>
                <div class="border-t pt-6">
                    <button type="button" @click="saveSystemInventorySecurity()" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                        Save System Settings
                    </button>
                </div>
            </div>

            <!-- Inventory -->
            <div class="space-y-6" x-show="tab==='inventory'">
                <div>
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Inventory Settings</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Low Stock Threshold</label>
                            <input type="number" x-model="settings.inventory.low_stock_threshold" class="mt-1 block w-full rounded-md border-gray-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Auto Restock Suggestions</label>
                            <select x-model="settings.inventory.auto_restock_suggestions" class="mt-1 block w-full rounded-md border-gray-300">
                                <option value="1">Enabled</option>
                                <option value="0">Disabled</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="border-t pt-6">
                    <button type="button" @click="saveSystemInventorySecurity()" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                        Save Inventory Settings
                    </button>
                </div>
            </div>

            <!-- Security -->
            <div class="space-y-6" x-show="tab==='security'">
                <div>
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Security Settings</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Session Timeout (minutes)</label>
                            <input type="number" x-model="settings.security.session_timeout_minutes" class="mt-1 block w-full rounded-md border-gray-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Minimum Password Length</label>
                            <input type="number" x-model="settings.security.password_min_length" class="mt-1 block w-full rounded-md border-gray-300">
                        </div>
                    </div>
                </div>
                <div class="border-t pt-6">
                    <button type="button" @click="saveSystemInventorySecurity()" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                        Save Security Settings
                    </button>
                </div>
            </div>

            @include('settings.permissions_tab')

            <!-- Company -->
            <div class="space-y-6" x-show="tab==='company'">
                <div>
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Company Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Company Name</label>
                            <input type="text" x-model="company.company_name" class="mt-1 block w-full rounded-md border-gray-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Company Phone</label>
                            <input type="text" x-model="company.company_phone" class="mt-1 block w-full rounded-md border-gray-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Company Email</label>
                            <input type="email" x-model="company.company_email" class="mt-1 block w-full rounded-md border-gray-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Registration</label>
                            <input type="text" x-model="company.company_registration" class="mt-1 block w-full rounded-md border-gray-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tax Number</label>
                            <input type="text" x-model="company.tax_number" class="mt-1 block w-full rounded-md border-gray-300">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Company Address</label>
                            <textarea rows="2" x-model="company.company_address" class="mt-1 block w-full rounded-md border-gray-300"></textarea>
                        </div>
                    </div>
                </div>

                <div class="border-t pt-6 flex gap-2">
                    <button type="button" @click="saveCompany()" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                        Save Company Info
                    </button>
                </div>
            </div>

            <!-- Modules -->
            <div class="space-y-6" x-show="tab==='modules'">
                <div>
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Module Toggles</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" class="rounded border-gray-300" x-model="modules.module_pos_enabled">
                            POS Enabled
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" class="rounded border-gray-300" x-model="modules.module_inventory_enabled">
                            Inventory Enabled
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" class="rounded border-gray-300" x-model="modules.module_orders_enabled">
                            Orders Enabled
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" class="rounded border-gray-300" x-model="modules.module_staff_enabled">
                            Staff Enabled
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" class="rounded border-gray-300" x-model="modules.module_accounting_enabled">
                            Accounting Enabled
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" class="rounded border-gray-300" x-model="modules.module_rentals_enabled">
                            Rentals Enabled
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" class="rounded border-gray-300" x-model="modules.module_payroll_enabled">
                            Payroll Enabled
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" class="rounded border-gray-300" x-model="modules.module_reports_enabled">
                            Reports Enabled
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" class="rounded border-gray-300" x-model="modules.module_suppliers_enabled">
                            Suppliers Enabled
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" class="rounded border-gray-300" x-model="modules.module_customers_enabled">
                            Customers Enabled
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" class="rounded border-gray-300" x-model="modules.module_consignments_enabled">
                            Surgery Consignments Enabled
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" class="rounded border-gray-300" x-model="modules.module_commissions_enabled">
                            Commissions Enabled
                        </label>
                    </div>
                </div>
                <div class="border-t pt-6 flex gap-2">
                    <button type="button" @click="saveModules()" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                        Save Module Settings
                    </button>
                    <button type="button" @click="runBackup()" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-50">
                        Backup Settings
                    </button>
                </div>
            </div>

            <!-- Preferences -->
            <div class="space-y-6" x-show="tab==='preferences'">
                <div>
                    <h2 class="text-lg font-medium text-gray-900 mb-4">User Preferences</h2>
                    <p class="text-sm text-gray-600 mb-6">Customize your personal experience. These settings are specific to your account.</p>
                    
                    <!-- Appearance Section -->
                    <div class="mb-6 border-b pb-6">
                        <h3 class="text-md font-semibold text-gray-800 mb-4 flex items-center">
                            <svg class="h-5 w-5 mr-2 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                            </svg>
                            Appearance
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Theme</label>
                                <select x-model="preferences.theme" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="light">Light</option>
                                    <option value="dark">Dark</option>
                                    <option value="auto">Auto (System)</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Choose your preferred color scheme</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Font Size</label>
                                <select x-model="preferences.font_size" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="small">Small</option>
                                    <option value="medium">Medium</option>
                                    <option value="large">Large</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Adjust text size for better readability</p>
                            </div>
                            <div>
                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                    <input type="checkbox" x-model="preferences.compact_mode" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="font-medium">Compact Mode</span>
                                </label>
                                <p class="mt-1 text-xs text-gray-500 ml-6">Reduce spacing for a denser layout</p>
                            </div>
                            <div>
                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                    <input type="checkbox" x-model="preferences.sidebar_collapsed" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="font-medium">Collapse Sidebar by Default</span>
                                </label>
                                <p class="mt-1 text-xs text-gray-500 ml-6">Start with sidebar minimized</p>
                            </div>
                        </div>
                    </div>

                    <!-- Dashboard Section -->
                    <div class="mb-6 border-b pb-6">
                        <h3 class="text-md font-semibold text-gray-800 mb-4 flex items-center">
                            <svg class="h-5 w-5 mr-2 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            Dashboard
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Default View</label>
                                <select x-model="preferences.dashboard_default_view" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="overview">Overview</option>
                                    <option value="sales">Sales Focus</option>
                                    <option value="inventory">Inventory Focus</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Choose what you see first on dashboard</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Refresh Interval</label>
                                <select x-model="preferences.dashboard_refresh_interval" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="30">30 seconds</option>
                                    <option value="60">1 minute</option>
                                    <option value="300">5 minutes</option>
                                    <option value="0">Manual only</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Auto-refresh dashboard data</p>
                            </div>
                            <div>
                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                    <input type="checkbox" x-model="preferences.dashboard_show_quick_stats" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="font-medium">Show Quick Stats</span>
                                </label>
                                <p class="mt-1 text-xs text-gray-500 ml-6">Display summary cards at top</p>
                            </div>
                        </div>
                    </div>

                    <!-- POS Section -->
                    <div class="mb-6 border-b pb-6">
                        <h3 class="text-md font-semibold text-gray-800 mb-4 flex items-center">
                            <svg class="h-5 w-5 mr-2 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            POS Preferences
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Default Payment Method</label>
                                <select x-model="preferences.pos_default_payment" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="cash">Cash</option>
                                    <option value="card">Card</option>
                                    <option value="mobile">Mobile Money</option>
                                    <option value="credit">Credit</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Pre-select payment method in POS</p>
                            </div>
                            <div class="col-span-1 md:col-span-2">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                            <input type="checkbox" x-model="preferences.pos_auto_print" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                            <span class="font-medium">Auto-Print Receipts</span>
                                        </label>
                                        <p class="mt-1 text-xs text-gray-500 ml-6">Print after each sale</p>
                                    </div>
                                    <div>
                                        <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                            <input type="checkbox" x-model="preferences.pos_sound_enabled" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                            <span class="font-medium">Transaction Sounds</span>
                                        </label>
                                        <p class="mt-1 text-xs text-gray-500 ml-6">Play sound on completion</p>
                                    </div>
                                    <div>
                                        <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                            <input type="checkbox" x-model="preferences.pos_show_images" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                            <span class="font-medium">Show Product Images</span>
                                        </label>
                                        <p class="mt-1 text-xs text-gray-500 ml-6">Display thumbnails in POS</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notifications Section -->
                    <div class="mb-6 border-b pb-6">
                        <h3 class="text-md font-semibold text-gray-800 mb-4 flex items-center">
                            <svg class="h-5 w-5 mr-2 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            Notifications
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                    <input type="checkbox" x-model="preferences.notify_low_stock" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="font-medium">Low Stock Alerts</span>
                                </label>
                                <p class="mt-1 text-xs text-gray-500 ml-6">Get notified when items are low</p>
                            </div>
                            <div>
                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                    <input type="checkbox" x-model="preferences.notify_daily_summary" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="font-medium">Daily Sales Summary</span>
                                </label>
                                <p class="mt-1 text-xs text-gray-500 ml-6">Receive end-of-day reports</p>
                            </div>
                            <div>
                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                    <input type="checkbox" x-model="preferences.notify_email" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="font-medium">Email Notifications</span>
                                </label>
                                <p class="mt-1 text-xs text-gray-500 ml-6">Send notifications via email</p>
                            </div>
                        </div>
                    </div>

                    <!-- Data Display Section -->
                    <div class="mb-6 border-b pb-6">
                        <h3 class="text-md font-semibold text-gray-800 mb-4 flex items-center">
                            <svg class="h-5 w-5 mr-2 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                            </svg>
                            Data Display
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Items Per Page</label>
                                <select x-model="preferences.items_per_page" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Default pagination size for tables</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Date Format</label>
                                <select x-model="preferences.date_format" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="DD/MM/YYYY">DD/MM/YYYY</option>
                                    <option value="MM/DD/YYYY">MM/DD/YYYY</option>
                                    <option value="YYYY-MM-DD">YYYY-MM-DD</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">How dates are displayed</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Time Format</label>
                                <select x-model="preferences.time_format" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="12h">12-hour (AM/PM)</option>
                                    <option value="24h">24-hour</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Clock format preference</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Number Format</label>
                                <select x-model="preferences.number_format" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="1,234.56">1,234.56 (Comma separator)</option>
                                    <option value="1.234,56">1.234,56 (Dot separator)</option>
                                    <option value="1 234.56">1 234.56 (Space separator)</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">How numbers are formatted</p>
                            </div>
                        </div>
                    </div>

                    <!-- Localization Section -->
                    <div class="mb-6">
                        <h3 class="text-md font-semibold text-gray-800 mb-4 flex items-center">
                            <svg class="h-5 w-5 mr-2 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                            </svg>
                            Localization
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Language</label>
                                <select x-model="preferences.language" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="en">English</option>
                                    <option value="sw">Swahili</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Interface language</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Timezone</label>
                                <select x-model="preferences.timezone" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="Africa/Nairobi">East Africa Time (EAT)</option>
                                    <option value="UTC">UTC</option>
                                    <option value="Africa/Lagos">West Africa Time (WAT)</option>
                                    <option value="Africa/Johannesburg">South Africa Time (SAST)</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Your local timezone</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="border-t pt-6 flex gap-2">
                    <button type="button" @click="savePreferences()" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Save Preferences
                    </button>
                    <button type="button" @click="resetPreferences()" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Reset to Defaults
                    </button>
                </div>
            </div>


            <!-- Audit --@include('settings._audit_tab')
        </div>
    </div>
</div>

<script>
window.settingsManager = function() {
    window.settingsManager = settingsManager;
    return {
        tab: '{{ auth()->user()->hasRole("admin") ? "system" : "preferences" }}',
        settings: {
            system: { currency_code: 'KSH', currency_symbol: 'KSh', default_tax_rate: '16', invoice_prefix: 'INV-' },
            inventory: { low_stock_threshold: '10', auto_restock_suggestions: '1' },
            security: { session_timeout_minutes: '60', password_min_length: '6' }
        },
        company: {
            company_name: '',
            company_address: '',
            company_phone: '',
            company_email: '',
            company_registration: '',
            tax_number: '',
            company_logo_url: ''
        },
        modules: {
            module_orders_enabled: true,
            module_staff_enabled: true,
            module_accounting_enabled: true,
            module_inventory_enabled: true,
            module_pos_enabled: true,
            module_rentals_enabled: true,
            module_payroll_enabled: true,
            module_reports_enabled: true,
            module_suppliers_enabled: true,
            module_suppliers_enabled: true,
            module_customers_enabled: true,
            module_consignments_enabled: true,
            module_commissions_enabled: true
        },
        preferences: {
            // Appearance
            theme: 'light',
            font_size: 'medium',
            compact_mode: false,
            sidebar_collapsed: false,
            
            // Dashboard
            dashboard_default_view: 'overview',
            dashboard_refresh_interval: '60',
            dashboard_show_quick_stats: true,
            
            // POS
            pos_default_payment: 'cash',
            pos_auto_print: false,
            pos_sound_enabled: true,
            pos_show_images: true,
            
            // Notifications
            notify_low_stock: true,
            notify_daily_summary: false,
            notify_email: false,
            
            // Data Display
            items_per_page: '25',
            date_format: 'DD/MM/YYYY',
            time_format: '24h',
            number_format: '1,234.56',
            
            // Localization
            language: 'en',
            timezone: 'Africa/Nairobi'
        },
        audit: [],
        staffMembers: [],
        selectedStaff: null,
        selectedStaffPermissions: [],
        initialPermissions: [],

        get permissionsChanged() {
            if (!this.selectedStaff) return false;
            // Compare arrays
            const a = this.selectedStaffPermissions.sort();
            const b = this.initialPermissions.sort();
            return JSON.stringify(a) !== JSON.stringify(b);
        },

        async init() {
            await this.loadSettings();
            await this.loadCompany();
            await this.loadModules();
            await this.loadPreferences();
            await this.loadAudit();
            // Load staff if admin
            if (this.tab === 'system' || this.tab === 'permissions') {
                await this.loadPermissions();
            }
        },

        async loadSettings() {
            try {
                const response = await axios.get('/settings');
                if (response.data.system) this.settings.system = { ...this.settings.system, ...response.data.system };
                if (response.data.inventory) this.settings.inventory = { ...this.settings.inventory, ...response.data.inventory };
                if (response.data.security) this.settings.security = { ...this.settings.security, ...response.data.security };
                // Also load permissions if we are admin
                if (this.tab !== 'preferences') {
                     await this.loadPermissions();
                }
            } catch (error) {
                console.error('Error loading settings:', error);
            }
        },

        async saveSystemInventorySecurity() {
            try {
                const settingsArray = [];
                Object.keys(this.settings.system).forEach(key => settingsArray.push({ key, value: this.settings.system[key], category: 'system' }));
                Object.keys(this.settings.inventory).forEach(key => settingsArray.push({ key, value: this.settings.inventory[key], category: 'inventory' }));
                Object.keys(this.settings.security).forEach(key => settingsArray.push({ key, value: this.settings.security[key], category: 'security' }));
                await axios.put('/settings', { settings: settingsArray });
                alert('Settings saved successfully');
                await this.loadAudit();
            } catch (error) {
                alert('Error saving settings: ' + (error.response?.data?.error || error.message));
            }
        },

        async loadCompany() {
            try {
                const res = await axios.get('/settings/company');
                this.company = { ...this.company, ...res.data };
            } catch (error) {
                console.error('Error loading company info:', error);
            }
        },

        async saveCompany() {
            try {
                await axios.put('/settings/company', { company: this.company });
                alert('Company information saved successfully');
                await this.loadAudit();
            } catch (error) {
                alert('Error saving company: ' + (error.response?.data?.error || error.message));
            }
        },



        async loadModules() {
            try {
                const res = await axios.get('/settings/modules');
                const toBool = (v) => v === true || v === 1 || v === '1';
                this.modules = {
                    module_orders_enabled: toBool(res.data.module_orders_enabled),
                    module_staff_enabled: toBool(res.data.module_staff_enabled),
                    module_accounting_enabled: toBool(res.data.module_accounting_enabled),
                    module_inventory_enabled: toBool(res.data.module_inventory_enabled),
                    module_pos_enabled: toBool(res.data.module_pos_enabled),
                    module_rentals_enabled: toBool(res.data.module_rentals_enabled),
                    module_payroll_enabled: toBool(res.data.module_payroll_enabled),
                    module_reports_enabled: toBool(res.data.module_reports_enabled),
                    module_suppliers_enabled: toBool(res.data.module_suppliers_enabled),
                    module_suppliers_enabled: toBool(res.data.module_suppliers_enabled),
                    module_customers_enabled: toBool(res.data.module_customers_enabled),
                    module_consignments_enabled: toBool(res.data.module_consignments_enabled),
                    module_commissions_enabled: toBool(res.data.module_commissions_enabled),
                };
            } catch (error) {
                console.error('Error loading modules:', error);
            }
        },

        async saveModules() {
            try {
                await axios.put('/settings/modules', {
                    modules: {
                        module_orders_enabled: this.modules.module_orders_enabled ? '1' : '0',
                        module_staff_enabled: this.modules.module_staff_enabled ? '1' : '0',
                        module_accounting_enabled: this.modules.module_accounting_enabled ? '1' : '0',
                        module_inventory_enabled: this.modules.module_inventory_enabled ? '1' : '0',
                        module_pos_enabled: this.modules.module_pos_enabled ? '1' : '0',
                        module_rentals_enabled: this.modules.module_rentals_enabled ? '1' : '0',
                        module_payroll_enabled: this.modules.module_payroll_enabled ? '1' : '0',
                        module_reports_enabled: this.modules.module_reports_enabled ? '1' : '0',
                        module_suppliers_enabled: this.modules.module_suppliers_enabled ? '1' : '0',
                        module_suppliers_enabled: this.modules.module_suppliers_enabled ? '1' : '0',
                        module_customers_enabled: this.modules.module_customers_enabled ? '1' : '0',
                        module_consignments_enabled: this.modules.module_consignments_enabled ? '1' : '0',
                        module_commissions_enabled: this.modules.module_commissions_enabled ? '1' : '0',
                    }
                });
                alert('Module settings saved successfully');
                await this.loadAudit();
            } catch (error) {
                alert('Error saving modules: ' + (error.response?.data?.error || error.message));
            }
        },

        async loadPreferences() {
            try {
                const res = await axios.get('/settings/user/preferences');
                // Merge loaded preferences with defaults to handle missing values
                this.preferences = { ...this.preferences, ...res.data };
            } catch (error) {
                console.error('Error loading preferences:', error);
            }
        },

        async savePreferences() {
            try {
                // Use the global manager if available to ensure sync with LocalStorage
                if (window.preferencesManager) {
                    await window.preferencesManager.save(this.preferences);
                    // Re-apply to ensure UI on this page reflects it
                    window.preferencesManager.applyAll();
                } else {
                    // Fallback
                    await axios.put('/settings/user/preferences', { preferences: this.preferences });
                }
                
                // Also update local Alpine state just in case
                this.applyTheme();
                
                alert('Preferences saved successfully!');
            } catch (error) {
                alert('Error saving preferences: ' + (error.response?.data?.error || error.message));
            }
        },

        async resetPreferences() {
            if (!confirm('Are you sure you want to reset all preferences to default values?')) {
                return;
            }
            
            // Reset to default values
            this.preferences = {
                // Appearance
                theme: 'light',
                font_size: 'medium',
                compact_mode: false,
                sidebar_collapsed: false,
                
                // Dashboard
                dashboard_default_view: 'overview',
                dashboard_refresh_interval: '60',
                dashboard_show_quick_stats: true,
                
                // POS
                pos_default_payment: 'cash',
                pos_auto_print: false,
                pos_sound_enabled: true,
                pos_show_images: true,
                
                // Notifications
                notify_low_stock: true,
                notify_daily_summary: false,
                notify_email: false,
                
                // Data Display
                items_per_page: '25',
                date_format: 'DD/MM/YYYY',
                time_format: '24h',
                number_format: '1,234.56',
                
                // Localization
                language: 'en',
                timezone: 'Africa/Nairobi'
            };
            
            // Save the reset preferences
            await this.savePreferences();
        },

        applyTheme() {
            // Apply theme to document
            const theme = this.preferences.theme;
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            } else if (theme === 'light') {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else if (theme === 'auto') {
                // Auto theme based on system preference
                if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
                localStorage.setItem('theme', 'auto');
            }
        },

        async loadAudit() {
            try {
                const res = await axios.get('/settings/audit-log?limit=100');
                this.audit = res.data || [];
            } catch (error) {
                console.error('Error loading audit log:', error);
            }
        },

        async runBackup() {
            try {
                const res = await axios.post('/settings/backup', {});
                alert('Backup created: ' + (res.data.path || 'ok'));
                await this.loadAudit();
            } catch (error) {
                alert('Error creating backup: ' + (error.response?.data?.error || error.message));
            }
        },

        async loadPermissions() {
            try {
                const res = await axios.get('/settings/permissions');
                this.staffMembers = res.data.staff || [];
                this.permissionDefaults = res.data.defaults || {};
                this.adminLevelPermissions = res.data.admin_level || [];
            } catch (error) {
                // If 403, just ignore (not admin)
                if (error.response && error.response.status !== 403) {
                     console.error('Error loading staff permissions:', error);
                }
            }
        },

        selectStaffForPermissions(staff) {
            this.selectedStaff = staff;
            // Ensure permissions is array
            this.selectedStaffPermissions = Array.isArray(staff.permissions) ? [...staff.permissions] : [];
            this.initialPermissions = [...this.selectedStaffPermissions];
        },

        // Check if a permission is a default for the selected staff's role
        isDefaultPermission(permission) {
            if (!this.selectedStaff) return false;
            const defaults = this.permissionDefaults?.[this.selectedStaff.role] || [];
            return defaults.includes(permission);
        },

        // Check if a permission is admin-level
        isAdminLevelPermission(permission) {
            return this.adminLevelPermissions?.includes(permission) ?? false;
        },

        // Reset permissions to role defaults
        resetToDefaults() {
            if (!this.selectedStaff) return;
            const defaults = this.permissionDefaults?.[this.selectedStaff.role] || [];
            this.selectedStaffPermissions = [...defaults];
        },

        // Grant all permissions
        grantAllPermissions() {
            const allPermissions = [
                'inventory.view', 'inventory.edit', 'inventory.adjust', 'inventory.categories', 'inventory.batches',
                'pos.access', 'pos.discount', 'sales.view', 'sales.refund', 'sales.invoices',
                'reports.view', 'finance.view', 'reports.analytics',
                'accounting.view', 'accounting.manage', 'accounting.reports', 'accounting.journal', 'accounting.periods',
                'payroll.view', 'payroll.process', 'payroll.deductions', 'payroll.reimbursements',
                'banking.view', 'banking.manage', 'banking.reconcile',
                'suppliers.view', 'suppliers.manage', 'customers.view', 'customers.manage',
                'orders.create', 'orders.approve', 'rentals.view', 'rentals.manage',
                'budgets.view', 'budgets.manage', 'budgets.approve',
                'consignments.view', 'consignments.manage', 'consignments.settle',
                'stock.view', 'stock.create', 'stock.transfers.view', 'stock.transfers.manage',
                'expenses.view', 'expenses.manage',
                'commissions.view', 'commissions.manage',
                'assets.view', 'assets.manage',
                'surgery.view', 'surgery.manage',
                'packages.view', 'packages.manage',
                'documents.view', 'documents.manage',
                'audit.view', 'audit.manage',
                'settings.view', 'settings.manage', 'settings.company', 'settings.users'
            ];
            this.selectedStaffPermissions = [...allPermissions];
        },

        // Clear all permissions
        clearAllPermissions() {
            this.selectedStaffPermissions = [];
        },

        async savePermissions() {
            if (!this.selectedStaff) return;

            try {
                const res = await axios.put(`/settings/permissions/${this.selectedStaff.id}`, {
                    permissions: this.selectedStaffPermissions
                });

                // Update local state
                this.selectedStaff.permissions = res.data.permissions;
                this.initialPermissions = [...res.data.permissions];

                alert(`Permissions updated for ${this.selectedStaff.full_name}`);
                await this.loadAudit();
            } catch (error) {
                alert('Error saving permissions: ' + (error.response?.data?.error || error.message));
            }
        }
    }
}

// Audit Log Manager Component
function auditLogManager() {
    return {
        logs: [],
        modules: [],
        actions: [],
        users: [],
        loading: false,
        showDetailModal: false,
        selectedLog: null,
        filters: {
            search: '',
            user_id: '',
            module: '',
            action: '',
            date_from: '',
            date_to: ''
        },
        pagination: {
            current_page: 1,
            last_page: 1,
            from: 0,
            to: 0,
            total: 0
        },

        async loadFilters() {
            try {
                const [modulesRes, actionsRes, usersRes] = await Promise.all([
                    axios.get('/api/audit-logs/modules'),
                    axios.get('/api/audit-logs/actions'),
                    axios.get('/api/audit-logs/users')
                ]);
                
                this.modules = modulesRes.data;
                this.actions = actionsRes.data;
                this.users = usersRes.data;
            } catch (error) {
                console.error('Error loading filters:', error);
            }
        },

        async loadAuditLogs(page = 1) {
            this.loading = true;
            try {
                const params = {
                    page,
                    ...this.filters
                };
                
                const res = await axios.get('/api/audit-logs', { params });
                
                this.logs = res.data.data;
                this.pagination = {
                    current_page: res.data.current_page,
                    last_page: res.data.last_page,
                    from: res.data.from,
                    to: res.data.to,
                    total: res.data.total
                };
            } catch (error) {
                console.error('Error loading audit logs:', error);
                alert('Error loading audit logs');
            } finally {
                this.loading = false;
            }
        },

        changePage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.loadAuditLogs(page);
            }
        },

        clearFilters() {
            this.filters = {
                search: '',
                user_id: '',
                module: '',
                action: '',
                date_from: '',
                date_to: ''
            };
            this.loadAuditLogs();
        },

        viewDetails(log) {
            this.selectedLog = log;
            this.showDetailModal = true;
        },

        formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString('en-GB', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        },

        async exportAuditLogs() {
            try {
                const params = new URLSearchParams(this.filters).toString();
                window.location.href = `/api/audit-logs/export?${params}`;
            } catch (error) {
                console.error('Error exporting audit logs:', error);
                alert('Error exporting audit logs');
            }
        }
    };
}
</script>
@endsection

