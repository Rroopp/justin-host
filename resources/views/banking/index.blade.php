@extends('layouts.app')

@section('title', 'Banking & Cash')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6" x-data="{ showDepositModal: false, showTransferModal: false, showExpenseModal: false, selectedAccount: null }">
    
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Banking & Cash Management</h1>
            <p class="text-sm text-gray-500">Manage your bank accounts, cash registers, and mobile money.</p>
        </div>
        <div class="flex space-x-3">
             <button @click="showExpenseModal = true" class="inline-flex items-center px-4 py-2 border border-red-300 shadow-sm text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none">
                <i class="fas fa-receipt mr-2"></i> Record Expense
            </button>
             <button @click="showTransferModal = true" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none">
                <i class="fas fa-exchange-alt mr-2"></i> Transfer Funds
            </button>
            <button @click="showDepositModal = true" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none">
                <i class="fas fa-plus mr-2"></i> Deposit / Top Up
            </button>
        </div>
    </div>

    <!-- Accounts Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        @foreach($bankAccounts as $account)
        <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 {{ $account->balance >= 0 ? 'border-green-500' : 'border-red-500' }}">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                         @if(str_contains(strtolower($account->name), 'mpesa'))
                            <span class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center text-green-600">
                                <i class="fas fa-mobile-alt text-xl"></i>
                            </span>
                        @elseif(str_contains(strtolower($account->name), 'cash'))
                            <span class="h-12 w-12 rounded-full bg-yellow-100 flex items-center justify-center text-yellow-600">
                                <i class="fas fa-coins text-xl"></i>
                            </span>
                        @else
                            <span class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                <i class="fas fa-university text-xl"></i>
                            </span>
                        @endif
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                {{ $account->name }}
                            </dt>
                            <dd>
                                <div class="text-lg font-bold text-gray-900">
                                    {{ number_format($account->balance, 2) }}
                                </div>
                                <div class="text-xs text-gray-400 mt-1">
                                    Code: {{ $account->code }}
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <a href="{{ route('banking.show', $account->id) }}" class="font-medium text-indigo-600 hover:text-indigo-900">
                        View Statement <span aria-hidden="true">&rarr;</span>
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Modals -->
    @include('banking.modals.deposit')
    @include('banking.modals.transfer')
    @include('banking.modals.expense')

</div>
@endsection
