@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="mb-6 border-b pb-4">
        <h1 class="text-2xl font-bold text-gray-900">Return & Reconcile Set</h1>
        <p class="text-gray-600">Processing return for <strong>{{ $set->name }}</strong> from Case #{{ $reservation->id }}</p>
    </div>

    <form action="{{ route('reconcile.store', $reservation->id) }}" method="POST" class="bg-white shadow rounded-lg p-6" onsubmit="document.getElementById('reconcile-btn').disabled = true; document.getElementById('reconcile-btn').innerText = 'Processing...';">
        @csrf
        <input type="hidden" name="surgical_set_id" value="{{ $set->id }}">

        <!-- Instrument Audit -->
        <div class="mb-8">
            <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">1. Instrument Audit</h3>
            <p class="text-sm text-gray-500 mb-4">Please inspect each instrument and mark its condition.</p>
            
            <div class="overflow-hidden border rounded-md">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Instrument</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Serial</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Condition</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach($set->instruments as $inst)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $inst->name }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $inst->serial_number ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex gap-4">
                                        <label class="inline-flex items-center">
                                            <input type="radio" name="instruments[{{ $inst->id }}]" value="good" checked class="text-indigo-600 focus:ring-indigo-500">
                                            <span class="ml-2 text-sm text-gray-700">Good</span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input type="radio" name="instruments[{{ $inst->id }}]" value="damaged" class="text-red-600 focus:ring-red-500">
                                            <span class="ml-2 text-sm text-gray-700">Damaged</span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input type="radio" name="instruments[{{ $inst->id }}]" value="missing" class="text-orange-600 focus:ring-orange-500">
                                            <span class="ml-2 text-sm text-gray-700">Missing</span>
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Consumables Note -->
        <div class="mb-8">
            <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">2. Consumables Used</h3>
            <div class="bg-gray-50 p-4 rounded-md text-sm text-gray-600">
                To bill for used consumables (Imlpants/Screws), please use the standard <strong>Case Usage / Billing</strong> workflow.
                This form only updates the Set's location and instrument status.
            </div>
        </div>

        <!-- Notes -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Inspection Notes</label>
            <textarea name="notes" rows="3" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md" placeholder="Any issues found during inspection..."></textarea>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('reservations.show', $reservation->id) }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50">Cancel</a>
            <button type="submit" id="reconcile-btn" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 shadow-sm">
                Complete Return & Reconcile
            </button>

        </div>
    </form>
</div>
@endsection
