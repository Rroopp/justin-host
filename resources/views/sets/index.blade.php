@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between mb-8">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                Surgical Sets Dashboard
            </h2>
            <p class="mt-1 text-sm text-gray-500">Manage kit composition, replenishment, and readiness.</p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <a href="{{ route('sets.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <i class="fas fa-plus mr-2"></i> Define New Set
            </a>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3 mb-8">
        <!-- Total Sets -->
        <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-indigo-500">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-indigo-50 rounded-md p-3">
                        <i class="fas fa-suitcase-medical text-indigo-600 text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dt class="text-sm font-medium text-gray-500 truncate">Total Sets Defined</dt>
                        <dd class="text-2xl font-semibold text-gray-900">{{ $sets->count() }}</dd>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ready for Surgery -->
        <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-green-500">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-50 rounded-md p-3">
                        <i class="fas fa-check-circle text-green-600 text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dt class="text-sm font-medium text-gray-500 truncate">Ready for Surgery</dt>
                        <dd class="text-2xl font-semibold text-gray-900">
                            {{ $sets->where('status', 'Complete')->count() }}
                        </dd>
                    </div>
                </div>
            </div>
        </div>

        <!-- Needs Attention -->
        <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-red-500">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-red-50 rounded-md p-3">
                        <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dt class="text-sm font-medium text-gray-500 truncate">Incomplete / Restock</dt>
                        <dd class="text-2xl font-semibold text-gray-900">
                            {{ $sets->where('status', '!=', 'Complete')->count() }}
                        </dd>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sets Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($sets as $set)
        <div class="bg-white rounded-lg shadow hover:shadow-md transition-all duration-200 border border-gray-100 flex flex-col h-full">
            <div class="p-6 flex-1">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $set->status === 'Complete' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $set->status === 'Complete' ? 'Ready' : 'Incomplete' }}
                        </span>
                    </div>
                    <div class="text-gray-400">
                        <i class="fas fa-barcode"></i>
                    </div>
                </div>
                
                <h3 class="text-lg font-bold text-gray-900 mb-1 truncate" title="{{ $set->name }}">
                    {{ $set->name }}
                </h3>
                <p class="text-sm text-gray-500 mb-4 truncate">
                    Asset: {{ $set->asset ? $set->asset->name : 'N/A' }}
                </p>

                <!-- Progress Bar -->
                <div class="mb-2">
                    <div class="flex justify-between text-xs font-medium text-gray-500 mb-1">
                        <span>Completeness</span>
                        <span>{{ $set->completeness_percent }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="bg-indigo-600 h-2.5 rounded-full transition-all duration-500" style="width: {{ $set->completeness_percent }}%"></div>
                    </div>
                </div>
                
                <div class="flex justify-between text-xs text-gray-500 mt-2">
                    <span>
                        <i class="fas fa-cubes mr-1"></i> {{ $set->current_stock_qty }} / {{ $set->total_items_qty }} items
                    </span>
                    @if($set->missing_count > 0)
                    <span class="text-red-600 font-medium">
                        {{ $set->missing_count }} Types Missing
                    </span>
                    @endif
                </div>
            </div>
            
            <div class="bg-gray-50 px-6 py-3 border-t border-gray-100 flex items-center justify-between">
                <a href="{{ route('sets.show', $set->id) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">
                    View Contents <i class="fas fa-arrow-right ml-1"></i>
                </a>
                @if($set->missing_count > 0)
                <a href="{{ route('stock-transfers.create') }}?set_id={{ $set->id }}" class="text-xs font-medium text-red-600 hover:text-red-800 bg-red-50 px-2 py-1 rounded">
                    Replenish
                </a>
                @endif
            </div>
        </div>
        @endforeach

        <!-- Create New Card (Empty State) -->
        @if($sets->isEmpty())
        <div class="col-span-full text-center py-12 bg-white rounded-lg border-2 border-dashed border-gray-300">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No sets defined</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by creating a new surgical set.</p>
            <div class="mt-6">
                <a href="{{ route('sets.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <i class="fas fa-plus mr-2"></i> Define New Set
                </a>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
