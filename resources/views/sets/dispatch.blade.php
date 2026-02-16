@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="mb-6 border-b pb-4">
        <h1 class="text-2xl font-bold text-gray-900">Dispatch Surgical Set</h1>
        <p class="text-gray-600">Assign a set to Case #{{ $reservation->id }} for {{ $reservation->surgery_date->format('M d, Y') }}</p>
    </div>

    <form action="{{ route('dispatch.store', $reservation->id) }}" method="POST" class="bg-white shadow rounded-lg p-6">
        @csrf
        
        <!-- Case Details -->
        <div class="mb-6 bg-blue-50 p-4 rounded-md border border-blue-100">
            <h3 class="text-sm font-bold text-blue-800 uppercase mb-2">Case Details</h3>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div><span class="text-gray-500">Surgeon:</span> {{ $reservation->surgeon_name }}</div>
                <div><span class="text-gray-500">Patient:</span> {{ $reservation->patient_name ?? 'N/A' }}</div>
                <div><span class="text-gray-500">Hospital:</span> {{ $reservation->hospital_name ?? 'N/A' }}</div>
                <div><span class="text-gray-500">Procedure:</span> {{ $reservation->procedure_name ?? 'N/A' }}</div>
            </div>
        </div>

        <!-- Select Set -->
        <div class="mb-6">
            
            @if($reservation->surgicalSets->count() > 0)
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sets Already Assigned:</label>
                    <div class="space-y-2">
                        @foreach($reservation->surgicalSets as $attachedSet)
                        <div class="p-3 bg-gray-50 border border-gray-200 rounded text-gray-700 flex justify-between items-center">
                            <span><strong>{{ $attachedSet->name }}</strong></span>
                            <span class="badge bg-green-100 text-green-800 px-2 py-1 rounded text-xs uppercase">{{ $attachedSet->pivot->status }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <label class="block text-sm font-medium text-gray-700 mb-2">Select Available Set to Dispatch *</label>
            <select name="surgical_set_id" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">-- Choose a Set --</option>
                @foreach($availableSets as $set)
                    <option value="{{ $set->id }}">{{ $set->name }} (Asset: {{ $set->asset->name ?? '-' }})</option>
                @endforeach
            </select>
            @if($availableSets->isEmpty())
                <div class="mt-2 p-3 bg-red-50 border border-red-200 rounded-md">
                    <p class="text-red-700 text-sm font-bold">No available sets found!</p>
                    <p class="text-red-600 text-xs mt-1">
                        Please ensure you have created <strong>Surgical Sets</strong> in the system 
                        <a href="{{ route('sets.create') }}" class="underline font-bold">here</a> and that they are marked 'Available'.
                    </p>
                </div>
            @endif
        </div>

        <!-- Checklist Warning -->
        <div class="mb-6 p-4 border border-yellow-200 bg-yellow-50 rounded-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">Pre-Dispatch Verification</h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p>By confirming dispatch, you verify that:</p>
                        <ul class="list-disc pl-5 space-y-1">
                            <li>All instruments are present and sterilized.</li>
                            <li>Consumable par levels have been checked.</li>
                            <li>Asset tags match the casing.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('reservations.show', $reservation->id) }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 shadow-sm" 
                {{ $availableSets->isEmpty() ? 'disabled' : '' }}>
                Confirm Dispatch
            </button>
        </div>
    </form>
</div>
@endsection
