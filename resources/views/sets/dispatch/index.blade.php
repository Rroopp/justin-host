@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between mb-8">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                Dispatch & Reconciliation Dashboard
            </h2>
            <p class="mt-1 text-sm text-gray-500">Manage surgical set dispatching and return processing.</p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
             <!-- Actions could go here -->
        </div>
    </div>

    <!-- Upcoming Cases Table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-8">
        <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Upcoming Surgeries & Dispatch Status
            </h3>
        </div>
        <ul class="divide-y divide-gray-200">
            @forelse($upcomingCases as $case)
            <li>
                <div class="px-4 py-4 sm:px-6 hover:bg-gray-50 transition duration-150 ease-in-out">
                    <div class="flex items-center justify-between">
                        <div class="text-sm font-medium text-indigo-600 truncate">
                            {{ $case->case_number }} - {{ $case->procedure_name ?? 'Procedure' }}
                        </div>
                        <div class="ml-2 flex-shrink-0 flex">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $case->status === 'confirmed' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($case->status) }}
                            </span>
                        </div>
                    </div>
                    <div class="mt-2 sm:flex sm:justify-between">
                        <div class="sm:flex">
                            <div class="mr-6 flex items-center text-sm text-gray-500">
                                <i class="fas fa-user-md mr-1.5 text-gray-400"></i>
                                {{ $case->surgeon_name ?? 'Unknown Surgeon' }}
                            </div>
                            <div class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0 sm:ml-6">
                                <i class="fas fa-calendar-alt mr-1.5 text-gray-400"></i>
                                {{ $case->surgery_date ? $case->surgery_date->format('M d, Y H:i') : 'Date Not Set' }}
                            </div>
                        </div>
                        <div class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0 sm:ml-6">
                             <!-- Set Status Logic -->
                             @php
                                 $dispatchedSet = $case->surgicalSets->where('pivot.status', 'dispatched')->first();
                                 $returnedSet = $case->surgicalSets->where('pivot.status', 'returned')->first();
                             @endphp

                             @if($dispatchedSet)
                                <div class="flex items-center text-green-600">
                                    <i class="fas fa-check-circle mr-1.5"></i> Dispatched: {{ $dispatchedSet->name }}
                                </div>
                             @elseif($returnedSet)
                                <div class="flex items-center text-blue-600">
                                    <i class="fas fa-undo mr-1.5"></i> Returned: {{ $returnedSet->name }}
                                </div>
                             @else
                                <div class="flex items-center text-yellow-600">
                                    <i class="fas fa-exclamation-circle mr-1.5"></i> No Set Dispatched
                                </div>
                             @endif
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="mt-4 flex justify-end space-x-3">
                        @if(!$dispatchedSet && !$returnedSet)
                            <a href="{{ route('dispatch.create', $case->id) }}" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                                Dispatch Set
                            </a>
                        @elseif($dispatchedSet)
                             <!-- Only allow reconcile if time passed (backend checks this too, but UX hint) -->
                             @php
                                $canReconcile = $case->surgery_date && $case->surgery_date->isPast();
                             @endphp
                             
                             @if($canReconcile)
                                <a href="{{ route('reconcile.create', $case->id) }}" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                                    Return & Reconcile
                                </a>
                             @else
                                <span class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded-md text-gray-400 bg-gray-50 cursor-not-allowed" title="Cannot reconcile before surgery time">
                                    Return & Reconcile (Locked)
                                </span>
                             @endif
                        @endif
                        
                        <a href="{{ route('reservations.show', $case->id) }}" class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded-md text-gray-700 bg-white hover:text-gray-500">
                            View Case
                        </a>
                    </div>
                </div>
            </li>
            @empty
            <li class="px-4 py-8 text-center text-gray-500">
                No upcoming surgeries found.
            </li>
            @endforelse
        </ul>
    </div>

    <!-- Available Sets Section -->
    <div class="mt-8">
        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Available Sterile Sets</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($availableSets as $set)
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-400">
                <div class="flex justify-between items-start">
                    <div>
                        <h4 class="text-lg font-bold text-gray-900">{{ $set->name }}</h4>
                        <p class="text-sm text-gray-500">{{ $set->location ? $set->location->name : 'No Location' }}</p>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                        Sterile
                    </span>
                </div>
                <div class="mt-4 text-sm text-gray-600">
                    <i class="fas fa-tools mr-1"></i> {{ $set->instruments->count() }} Instruments
                </div>
            </div>
            @endforeach
            
            @if($availableSets->isEmpty())
            <div class="col-span-full text-center text-gray-500 py-4">
                No sterile sets currently available in inventory.
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
