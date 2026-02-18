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
                            {{ $sets->where('status', 'available')->count() }}
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
                        <dt class="text-sm font-medium text-gray-500 truncate">Not Available / In Use</dt>
                        <dd class="text-2xl font-semibold text-gray-900">
                            {{ $sets->where('status', '!=', 'available')->count() }}
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
                        @php
                            $statusColors = [
                                'available' => 'bg-green-100 text-green-800',
                                'dispatched' => 'bg-blue-100 text-blue-800',
                                'in_surgery' => 'bg-indigo-100 text-indigo-800',
                                'in_transit' => 'bg-yellow-100 text-yellow-800',
                                'dirty' => 'bg-red-100 text-red-800',
                                'sterilizing' => 'bg-orange-100 text-orange-800',
                                'maintenance' => 'bg-gray-100 text-gray-800',
                                'incomplete' => 'bg-red-50 text-red-600',
                            ];
                            $color = $statusColors[$set->status] ?? 'bg-gray-100 text-gray-800';
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $color }}">
                            {{ ucfirst(str_replace('_', ' ', $set->status)) }}
                        </span>
                        @if($set->sterilization_status == 'sterile')
                             <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-50 text-green-700 border border-green-200">
                                <i class="fas fa-sparkles mr-1"></i> Sterile
                            </span>
                        @endif
                    </div>
                    <div class="text-gray-400">
                         <!-- Simple Status Update Request -->
                         <form action="{{ route('sets.status.update', $set->id) }}" method="POST" class="inline-block">
                            @csrf
                            @method('PUT')
                            <select name="status" onchange="this.form.submit()" class="text-xs py-0 pl-2 pr-6 border-gray-200 rounded text-gray-600 focus:ring-indigo-500 focus:border-indigo-500 bg-transparent hover:bg-gray-50 cursor-pointer" title="Quick Update Status">
                                <option value="" disabled>Change Status...</option>
                                <option value="available" {{ $set->status == 'available' ? 'selected' : '' }}>Available</option>
                                <option value="dirty" {{ $set->status == 'dirty' ? 'selected' : '' }}>Dirty</option>
                                <option value="sterilizing" {{ $set->status == 'sterilizing' ? 'selected' : '' }}>Sterilizing</option>
                                <option value="maintenance" {{ $set->status == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                            </select>
                        </form>
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
