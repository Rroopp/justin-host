<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Hospital POS') }}</title>
    
    <!-- Early theme application to prevent flash -->
    <script>
        // Apply theme from localStorage immediately to prevent flash
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else if (theme === 'auto') {
                if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.documentElement.classList.add('dark');
                }
            }
        })();
    </script>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* Smooth Sidebar Transitions */
        aside {
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .nav-text {
            transition: opacity 0.2s ease-in-out, width 0.2s ease-in-out, margin-left 0.2s ease-in-out;
            opacity: 1;
            width: auto;
            overflow: hidden;
        }

        body.sidebar-collapsed .nav-text {
            opacity: 0;
            width: 0;
            margin-left: 0;
        }

        /* Center icons when collapsed */
        body.sidebar-collapsed aside a {
            justify-content: center;
            padding-left: 0;
            padding-right: 0;
        }
        
        body.sidebar-collapsed aside a svg {
            margin-right: 0;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div id="app" class="h-screen flex overflow-hidden">
        @php
            $user = auth()->user() ?? request()->user();
            $isLoginPage = request()->routeIs('login');
        @endphp
        @unless($isLoginPage)
            <!-- Sidebar -->
            <aside class="flex flex-shrink-0 w-64 bg-indigo-600">
                <div class="flex flex-col w-full">
                    <div class="flex flex-col flex-grow pt-3 overflow-y-auto">
                        <div class="flex items-center justify-center flex-shrink-0 mb-3 pt-1">
                            <img src="{{ asset('images/logo.jpg') }}" alt="Justine POS" class="w-full h-auto object-contain">
                        </div>
                        <div class="mt-2 flex-1 flex flex-col">
                            <nav class="flex-1 px-2 space-y-0.5">
                                <!-- Dashboard -->
                                <a href="{{ route('dashboard') }}" class="group flex items-center px-2 py-1.5 text-xs font-medium rounded-md {{ request()->routeIs('dashboard') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                    <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                    </svg>
                                    <span class="ml-3 nav-text">Dashboard</span>
                                </a>

                                <!-- POS -->
                                @if(settings('module_pos_enabled', true) && auth()->user()->hasRole(['admin', 'staff']))
                                <a href="{{ route('pos.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('pos.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                    <svg class="mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <span class="ml-3 nav-text">POS</span>
                                </a>
                                @endif

                                <!-- Inventory -->
                                @if(settings('module_inventory_enabled', true))
                                    @if(auth()->user()->hasRole(['admin', 'staff']))
                                    <a href="{{ route('inventory.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('inventory.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                        <svg class="mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                        <span class="ml-3 nav-text">Inventory</span>
                                    </a>
                                    <a href="{{ route('inventory.categories') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md ml-6 {{ request()->routeIs('inventory.categories') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                        <svg class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                        </svg>
                                        <span class="ml-3 nav-text">Categories</span>
                                    </a>
                                    @if(auth()->user()->hasRole('admin'))
                                    <a href="{{ route('inventory.health') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md ml-6 {{ request()->routeIs('inventory.health') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                        <svg class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span class="ml-3 nav-text">Health Dashboard</span>
                                    </a>
                                    @endif
                                    @if(auth()->user()->hasRole(['admin', 'accountant']))
                                    <a href="{{ route('stock-takes.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md ml-6 {{ request()->routeIs('stock-takes.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                        <svg class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                        </svg>
                                        <span class="ml-3 nav-text">Stock Takes</span>
                                    </a>
                                    <a href="{{ route('packages.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md ml-6 {{ request()->routeIs('packages.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                        <svg class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                        <span class="ml-3 nav-text">Packages</span>
                                    </a>
                                    @endif
                                    @endif
                                @endif

                                <!-- Sales -->
                                @if(auth()->user()->hasRole(['admin', 'accountant', 'staff']))
                                <a href="{{ route('sales.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('sales.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                    <svg class="mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                    <span class="ml-3 nav-text">Sales</span>
                                </a>
                                @if(auth()->user()->hasRole(['admin', 'accountant']))
                                <a href="{{ route('sales.invoices.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md ml-6 {{ request()->routeIs('sales.invoices.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                    <svg class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l2-2 4 4m0 0l2-2m-2 2V6a2 2 0 00-2-2H7a2 2 0 00-2 2v12a2 2 0 002 2h3" />
                                    </svg>
                                    <span class="ml-3 nav-text">Credit Invoices</span>
                                </a>
                                @endif
                                @endif

                                <!-- Surgery Consignments -->
                                @if(auth()->user()->hasRole(['admin', 'staff', 'accountant']))
                                <a href="{{ route('sales.consignments.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md ml-6 {{ request()->routeIs('sales.consignments.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                    <svg class="mr-3 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                    <span class="ml-3 nav-text">Surgery Consignments</span>
                                </a>
                                @endif

                                <!-- Customers -->
                                @if(settings('module_customers_enabled', true) && auth()->user()->hasRole(['admin', 'staff', 'accountant']))
                                <a href="{{ route('customers.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('customers.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                    <svg class="mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <span class="ml-3 nav-text">Customers</span>
                                </a>
                                @endif

                                <!-- Refunds (Admin & Accountant) -->
                                @if(auth()->user()->hasRole(['admin', 'accountant']))
                                <a href="{{ route('refunds.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('refunds.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                    <svg class="mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z" />
                                    </svg>
                                    <span class="ml-3 nav-text">Refunds</span>
                                </a>
                                @endif

                                <!-- Orders & LPOs -->
                                @if(settings('module_orders_enabled', true) && auth()->user()->hasRole(['admin', 'staff']))
                                <a href="{{ route('orders.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('orders.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                    <svg class="mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    <span class="ml-3 nav-text">Orders</span>
                                </a>
                                <a href="{{ route('orders.suggestions.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md ml-6 {{ request()->routeIs('orders.suggestions.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                    <svg class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                    </svg>
                                    <span class="ml-3 nav-text">Suggestions</span>
                                </a>
                                <a href="{{ route('lpos.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md ml-6 {{ request()->routeIs('lpos.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                    <svg class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span class="ml-3 nav-text">LPOs</span>
                                </a>
                                @endif

                                <!-- Suppliers -->
                                @if(settings('module_suppliers_enabled', true) && auth()->user()->hasRole(['admin', 'staff']))
                                <a href="{{ route('suppliers.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('suppliers.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                    <svg class="mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    <span class="ml-3 nav-text">Suppliers</span>
                                </a>
                                @endif

                                <!-- Commissions -->
                                @if(auth()->user()->hasRole(['admin', 'accountant', 'staff']))
                                <a href="{{ route('commissions.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('commissions.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                    <svg class="mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="ml-3 nav-text">Commissions</span>
                                </a>
                                @endif

                                <!-- Accounting & Expenses -->
                                @if(settings('module_accounting_enabled', true))
                                    @if(auth()->user()->hasRole(['admin', 'accountant']))
                                    <a href="{{ route('accounting.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('accounting.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                        <svg class="mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span class="ml-3 nav-text">Accounting</span>
                                    </a>
                                    <a href="{{ route('expenses.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('expenses.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                        <svg class="mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        <span class="ml-3 nav-text">Expenses</span>
                                    </a>
                                    <a href="{{ route('budgets.dashboard') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('budgets.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                        <svg class="mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                        </svg>
                                        <span class="ml-3 nav-text">Budgets</span>
                                    </a>
                                    @endif
                                @endif

                                <!-- Reports -->
                                @if(settings('module_reports_enabled', true) && auth()->user()->hasRole(['admin', 'accountant']))
                                <a href="{{ route('reports.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('reports.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                    <svg class="mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                    <span class="ml-3 nav-text">Reports & AI</span>
                                </a>
                                @endif

                                <!-- Payroll -->
                                @if(settings('module_payroll_enabled', true) && auth()->user()->hasRole('admin'))
                                <a href="{{ route('payroll.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('payroll.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                    <svg class="mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <span class="ml-3 nav-text">Payroll</span>
                                </a>
                                @endif

                                <!-- Assets -->
                                @if(settings('module_rentals_enabled', true) && auth()->user()->hasRole('admin'))
                                <a href="{{ route('assets.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('assets.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                    <svg class="mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    <span class="ml-3 nav-text">Assets</span>
                                </a>
                                @endif

                                <!-- Staff -->
                                @if(settings('module_staff_enabled', true) && auth()->user()->hasRole('admin'))
                                <a href="{{ route('staff.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('staff.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                    <svg class="mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    <span class="ml-3 nav-text">Staff</span>
                                </a>
                                @endif





                                <!-- Settings -->
                                @if(auth()->user()->hasRole(['admin', 'staff', 'accountant']))
                                <a href="{{ route('settings.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('settings.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                    <svg class="mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span class="ml-3 nav-text">Settings</span>
                                </a>
                                @endif
                            </nav>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main Content -->
            <div class="flex flex-col w-0 flex-1 overflow-hidden">
                <!-- Top Header -->
                <div class="relative z-10 flex-shrink-0 flex h-16 bg-white shadow">
                    <button type="button" class="px-4 border-r border-gray-200 text-gray-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500 md:hidden">
                        <span class="sr-only">Open sidebar</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                        </svg>
                    </button>
                    <div class="flex-1 px-4 flex justify-between">
                        <div class="flex-1 flex">
                            <!-- Optional Search Bar placeholder -->
                        </div>
                        <div class="ml-4 flex items-center md:ml-6 space-x-4">
                            <!-- Notifications Dropdown -->
                            <div class="relative" x-data="{ open: false, count: 0, notifications: [] }" x-init="
                                axios.get('/notifications').then(res => { count = res.data.count; notifications = res.data.notifications; });
                                setInterval(() => { axios.get('/notifications').then(res => { count = res.data.count; notifications = res.data.notifications; }); }, 60000);
                            ">
                                <button @click="open = !open" class="bg-white p-1 rounded-full text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 relative">
                                    <span class="sr-only">View notifications</span>
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                    </svg>
                                    <span x-show="count > 0" class="absolute top-0 right-0 block h-2.5 w-2.5 rounded-full ring-2 ring-white bg-red-500"></span>
                                </button>

                                <div x-show="open" @click.away="open = false" class="origin-top-right absolute right-0 mt-2 w-80 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-50 p-2" x-cloak>
                                    <h3 class="text-sm font-medium text-gray-900 border-b pb-2 mb-2 px-2">Notifications</h3>
                                    <template x-if="count === 0">
                                        <p class="text-sm text-gray-500 px-2">No new notifications</p>
                                    </template>
                                    <template x-for="note in notifications" :key="note.id">
                                        <a :href="note.link" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md">
                                            <div class="flex items-start">
                                                <div class="flex-shrink-0">
                                                    <template x-if="note.type === 'warning'">
                                                        <span class="text-yellow-400"><i class="fas fa-exclamation-triangle"></i></span>
                                                    </template>
                                                    <template x-if="note.type === 'info'">
                                                        <span class="text-blue-400"><i class="fas fa-info-circle"></i></span>
                                                    </template>
                                                </div>
                                                <div class="ml-3 w-0 flex-1">
                                                    <p class="text-sm font-medium text-gray-900" x-text="note.title"></p>
                                                    <p class="text-xs text-gray-500" x-text="note.message"></p>
                                                    <p class="text-xs text-gray-400 mt-1" x-text="note.time"></p>
                                                </div>
                                            </div>
                                        </a>
                                    </template>
                                </div>
                            </div>

                            <!-- Profile Dropdown -->
                            <div class="ml-3 relative" x-data="{ open: false }">
                                <div>
                                    <button @click="open = !open" class="max-w-xs bg-white flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" id="user-menu" aria-haspopup="true">
                                        <span class="sr-only">Open user menu</span>
                                        @php
                                            $user = auth()->user() ?? request()->user();
                                            $initial = $user ? strtoupper(substr($user->full_name ?? $user->username ?? 'U', 0, 1)) : 'U';
                                        @endphp
                                        <div class="h-8 w-8 rounded-full bg-indigo-600 flex items-center justify-center text-white font-medium">
                                            {{ $initial }}
                                        </div>
                                    </button>
                                </div>
                                <div x-show="open" @click.away="open = false" class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-50">
                                    <div class="px-4 py-2 border-b">
                                        <p class="text-sm font-medium text-gray-900">{{ $user->full_name ?? $user->username ?? 'User' }}</p>
                                        <p class="text-xs text-gray-500 truncate">{{ $user->role ?? 'Staff' }}</p>
                                    </div>
                                    <a href="{{ route('settings.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            Sign out
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Page content -->
                <main class="flex-1 relative overflow-y-auto focus:outline-none">
                    <div class="{{ request()->routeIs('pos.*') ? 'h-full' : 'py-6' }}">
                        <div class="{{ request()->routeIs('pos.*') ? 'h-full px-2' : 'max-w-7xl mx-auto px-4 sm:px-6 md:px-8' }}">
                            @yield('content')
                        </div>
                    </div>
                </main>
            </div>
        @else
            <!-- Login page - no sidebar -->
            <div class="w-full h-screen overflow-y-auto">
                @yield('content')
            </div>
        @endunless
    </div>
    
    <script>
        // Initialize preferences on page load
        document.addEventListener('DOMContentLoaded', function() {
            if (window.preferencesManager) {
                // Initialize and apply preferences
                window.preferencesManager.init().then(() => {
                    console.log('Preferences loaded and applied');
                }).catch(error => {
                    console.error('Failed to initialize preferences:', error);
                });
            }
        });
    </script>
</body>
</html>
