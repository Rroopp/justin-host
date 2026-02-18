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
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
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

        @media print {
            @page { margin: 1cm; size: auto; }
            body { background: white; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            #app, main, .overflow-y-auto { overflow: visible !important; height: auto !important; }
            aside, header, .print\:hidden { display: none !important; }
            .max-w-7xl { max-width: none !important; padding: 0 !important; margin: 0 !important; }
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
            <aside class="flex flex-shrink-0 w-64 bg-indigo-600 print:hidden">
                <div class="flex flex-col w-full">
                    <!-- Fixed Logo Header -->
                    <div class="flex items-center justify-center flex-shrink-0 px-4 py-4 bg-indigo-600 z-10">
                        <img src="{{ asset('images/logo.jpg') }}" alt="Justine POS" class="w-full h-auto object-contain rounded bg-white p-1">
                    </div>

                    <!-- Scrollable Navigation -->
                    <div class="flex flex-col flex-grow overflow-y-auto">
                        <div class="flex-1 flex flex-col">
                            <nav class="flex-1 px-2 space-y-0.5">
                                <!-- Dashboard -->
                                <a href="{{ route('dashboard') }}" class="group flex items-center px-2 py-1.5 text-xs font-medium rounded-md {{ request()->routeIs('dashboard') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                    <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                    </svg>
                                    <span class="ml-3 nav-text">Dashboard</span>
                                </a>

                                <!-- POS -->
                                @if(settings('module_pos_enabled', true) && auth()->user()->hasPermission('pos.access'))
                                <a href="{{ route('pos.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('pos.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                    <svg class="mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <span class="ml-3 nav-text">POS</span>
                                </a>
                                <!-- Surgery Cases (Under POS) -->
                                @if(auth()->user()->hasPermission('surgery.view') || auth()->user()->hasPermission('surgery.manage'))
                                <a href="{{ route('reservations.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md ml-6 {{ request()->routeIs('reservations.*') || request()->routeIs('dispatch.*') || request()->routeIs('reconcile.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                    <svg class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                    </svg>
                                    <span class="ml-3 nav-text">Surgery Cases</span>
                                </a>
                                @endif
                                @endif

                                <!-- Inventory -->
                                @if(settings('module_inventory_enabled', true))
                                    @if(auth()->user()->hasPermission('inventory.view') || auth()->user()->hasPermission('inventory.edit') || auth()->user()->hasPermission('inventory.adjust'))
                                    <a href="{{ route('inventory.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('inventory.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                        <svg class="mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                        <span class="ml-3 nav-text">Inventory</span>
                                    </a>
                                    @if(auth()->user()->hasPermission('inventory.categories'))
                                    <a href="{{ route('inventory.categories') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md ml-6 {{ request()->routeIs('inventory.categories') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                        <svg class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                        </svg>
                                        <span class="ml-3 nav-text">Categories</span>
                                    </a>
                                    @endif
                                    <!-- Stock Takes (Financial Audit) -->
                                    @if(auth()->user()->hasPermission('stock.view') || auth()->user()->hasPermission('stock.create'))
                                    <a href="{{ route('stock-takes.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md ml-6 {{ request()->routeIs('stock-takes.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                        <svg class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                        </svg>
                                        <span class="ml-3 nav-text">Stock Takes</span>
                                    </a>
                                    @endif
                                    
                                    <!-- Stock Transfers -->
                                    @if(auth()->user()->hasPermission('stock.transfers.view') || auth()->user()->hasPermission('stock.transfers.manage'))
                                    <a href="{{ route('stock-transfers.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md ml-6 {{ request()->routeIs('stock-transfers.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                        <svg class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                        </svg>
                                        <span class="ml-3 nav-text">Stock Transfers</span>
                                    </a>
                                    @endif
                                    
                                    <!-- Surgical Sets -->
                                    @if(auth()->user()->hasPermission('inventory.view'))
                                    <a href="{{ route('sets.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md ml-6 {{ request()->routeIs('sets.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                        <svg class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                        </svg>
                                        <span class="ml-3 nav-text">Surgical Sets</span>
                                    </a>
                                    @endif
                                    
                                    <!-- Packages -->
                                    @if(auth()->user()->hasPermission('packages.view') || auth()->user()->hasPermission('packages.manage'))
                                    <a href="{{ route('packages.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md ml-6 {{ request()->routeIs('packages.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                        <svg class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                        <span class="ml-3 nav-text">Packages</span>
                                    </a>
                                    @endif
                                    @endif
                                @endif

                                <!-- Batch & Serial Tracking -->
                                @if(auth()->user()->hasPermission('inventory.batches'))
                                    <a href="{{ route('batches.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('batches.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                        <svg class="mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <span class="ml-3 nav-text">Batch Tracking</span>
                                    </a>
                                @endif

                                <!-- Consignment Management -->
                                @if(auth()->user()->hasPermission('consignments.view') || auth()->user()->hasPermission('consignments.manage'))
                                    <a href="{{ route('consignment.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('consignment.*') || request()->routeIs('sales.consignments.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                        <svg class="mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                        <span class="ml-3 nav-text">Consignment</span>
                                    </a>
                                    <!-- Consignment Sub-items -->
                                    @if(auth()->user()->hasPermission('consignments.view') || auth()->user()->hasPermission('consignments.manage'))
                                    <a href="{{ route('consignment.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md ml-6 {{ request()->routeIs('consignment.*') && !request()->routeIs('sales.consignments.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                        <svg class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                        <span class="ml-3 nav-text">Stock Tracking</span>
                                    </a>
                                    @endif
                                    @if(settings('module_consignments_enabled', true) && (auth()->user()->hasPermission('consignments.view') || auth()->user()->hasPermission('consignments.manage')))
                                    <a href="{{ route('sales.consignments.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md ml-6 {{ request()->routeIs('sales.consignments.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                        <svg class="mr-3 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                        </svg>
                                        <span class="ml-3 nav-text">Surgery Sales</span>
                                    </a>
                                    @endif
                                @endif

                                <!-- Sales -->
                                @if(auth()->user()->hasPermission('sales.view') || auth()->user()->hasPermission('sales.refund') || auth()->user()->hasPermission('sales.invoices'))
                                <a href="{{ route('sales.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('sales.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                    <svg class="mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                    <span class="ml-3 nav-text">Sales</span>
                                </a>
                                @if(auth()->user()->hasPermission('sales.invoices'))
                                <a href="{{ route('sales.invoices.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md ml-6 {{ request()->routeIs('sales.invoices.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                    <svg class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l2-2 4 4m0 0l2-2m-2 2V6a2 2 0 00-2-2H7a2 2 0 00-2 2v12a2 2 0 002 2h3" />
                                    </svg>
                                    <span class="ml-3 nav-text">Credit Invoices</span>
                                </a>
                                @endif
                                @endif

                                <!-- Customers -->
                                @if(settings('module_customers_enabled', true) && (auth()->user()->hasPermission('customers.view') || auth()->user()->hasPermission('customers.manage')))
                                <a href="{{ route('customers.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('customers.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                    <svg class="mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <span class="ml-3 nav-text">Customers</span>
                                </a>
                                @endif

                                <!-- Refunds -->
                                @if(auth()->user()->hasPermission('refunds.view') || auth()->user()->hasPermission('refunds.process') || auth()->user()->hasPermission('sales.refund'))
                                <a href="{{ route('refunds.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('refunds.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                    <svg class="mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z" />
                                    </svg>
                                    <span class="ml-3 nav-text">Refunds</span>
                                </a>
                                @endif

                                <!-- Orders & LPOs -->
                                @if(settings('module_orders_enabled', true) && (auth()->user()->hasPermission('orders.create') || auth()->user()->hasPermission('orders.approve')))
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
                                @if(settings('module_suppliers_enabled', true) && (auth()->user()->hasPermission('suppliers.view') || auth()->user()->hasPermission('suppliers.manage')))
                                <a href="{{ route('suppliers.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('suppliers.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                    <svg class="mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    <span class="ml-3 nav-text">Suppliers</span>
                                </a>
                                @endif

                                <!-- Commissions -->
                                @if(settings('module_commissions_enabled', true) && (auth()->user()->hasPermission('commissions.view') || auth()->user()->hasPermission('commissions.manage')))
                                <a href="{{ route('commissions.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('commissions.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                    <svg class="mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="ml-3 nav-text">Commissions</span>
                                </a>
                                @endif

                                <!-- My Reimbursements (All Staff) -->
                                <a href="{{ route('reimbursements.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('reimbursements.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                    <svg class="mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z" />
                                    </svg>
                                    <span class="ml-3 nav-text">My Reimbursements</span>
                                    @php
                                        $pendingCount = auth()->user()->pendingReimbursements()->count();
                                    @endphp
                                    @if($pendingCount > 0)
                                        <span class="ml-auto inline-block py-0.5 px-2 text-xs font-semibold rounded-full bg-yellow-400 text-gray-900">{{ $pendingCount }}</span>
                                    @endif
                                </a>


                                <!-- Accounting & Expenses -->
                                @if(settings('module_accounting_enabled', true))
                                    @php
                                        $hasAccountingAccess = auth()->user()->hasPermission('accounting.view') 
                                            || auth()->user()->hasPermission('accounting.manage') 
                                            || auth()->user()->hasPermission('accounting.reports')
                                            || auth()->user()->hasPermission('accounting.journal')
                                            || auth()->user()->hasPermission('accounting.periods')
                                            || auth()->user()->hasPermission('payroll.view')
                                            || auth()->user()->hasPermission('payroll.process')
                                            || auth()->user()->hasPermission('payroll.deductions')
                                            || auth()->user()->hasPermission('payroll.reimbursements')
                                            || auth()->user()->hasPermission('banking.view')
                                            || auth()->user()->hasPermission('banking.manage')
                                            || auth()->user()->hasPermission('banking.reconcile')
                                            || auth()->user()->hasPermission('expenses.view')
                                            || auth()->user()->hasPermission('expenses.manage');
                                    @endphp
                                    @if($hasAccountingAccess)
                                    <a href="{{ route('accounting.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('accounting.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                        <svg class="mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span class="ml-3 nav-text">Accounting</span>
                                    </a>
                                    @endif
                                    @if(auth()->user()->hasPermission('expenses.view') || auth()->user()->hasPermission('expenses.manage'))
                                    <a href="{{ route('expenses.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md ml-6 {{ request()->routeIs('expenses.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                        <svg class="mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        <span class="ml-3 nav-text">Expenses</span>
                                    </a>
                                    @endif

                                    
                                    <!-- Admin: Approve Reimbursements (Sub-item) -->
                                    @if(auth()->user()->hasPermission('payroll.reimbursements'))
                                    <a href="{{ route('reimbursements.index', ['status' => 'pending']) }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md ml-6 {{ request()->routeIs('reimbursements.*') && request()->get('status') === 'pending' ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                        <svg class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span class="ml-3 nav-text">Approve Reimbursements</span>
                                        @php
                                            $adminPendingCount = \App\Models\StaffReimbursement::where('status', 'pending')->count();
                                        @endphp
                                        @if($adminPendingCount > 0)
                                            <span class="ml-auto inline-block py-0.5 px-2 text-xs font-semibold rounded-full bg-red-400 text-white">{{ $adminPendingCount }}</span>
                                        @endif
                                    </a>
                                    @endif
                                @endif

                                <!-- Reports -->
                                @if(settings('module_reports_enabled', true) && (auth()->user()->hasPermission('reports.view') || auth()->user()->hasPermission('finance.view') || auth()->user()->hasPermission('reports.analytics')))
                                <a href="{{ route('reports.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('reports.index') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                    <svg class="mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                    <span class="ml-3 nav-text">Reports & AI</span>
                                </a>
                                @endif



                                <!-- Assets -->
                                @if(settings('module_rentals_enabled', true) && (auth()->user()->hasPermission('assets.view') || auth()->user()->hasPermission('assets.manage')))
                                <a href="{{ route('assets.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('assets.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                    <svg class="mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    <span class="ml-3 nav-text">Assets</span>
                                </a>
                                @endif

                                <!-- Rentals -->
                                @if(settings('module_rentals_enabled', true) && (auth()->user()->hasPermission('rentals.view') || auth()->user()->hasPermission('rentals.manage')))
                                <a href="{{ route('rentals.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('rentals.*') ? 'bg-indigo-700 text-white' : 'text-indigo-100 hover:bg-indigo-700 hover:text-white' }}">
                                    <svg class="mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                                    </svg>
                                    <span class="ml-3 nav-text">Rentals</span>
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







                                <!-- Settings (All authenticated users can access for password change and preferences) -->
                                @if(auth()->user()->hasPermission('settings.view') || auth()->user()->hasPermission('settings.manage') || auth()->user()->hasPermission('settings.company') || auth()->user()->hasPermission('settings.users') || auth()->user()->hasRole(['staff', 'accountant']))
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
                <div class="relative z-10 flex-shrink-0 flex h-16 bg-white shadow print:hidden">
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
                            @if(session('success'))
                                <div class="mb-4 rounded-md bg-green-50 p-4 border-l-4 border-green-400">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-check-circle text-green-400"></i>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if(session('error'))
                                <div class="mb-4 rounded-md bg-red-50 p-4 border-l-4 border-red-400">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-exclamation-circle text-red-400"></i>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if(session('warning'))
                                <div class="mb-4 rounded-md bg-yellow-50 p-4 border-l-4 border-yellow-400">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-yellow-800">{{ session('warning') }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endif

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
