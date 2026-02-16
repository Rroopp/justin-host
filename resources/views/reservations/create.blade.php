@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <a href="{{ route('reservations.index') }}" class="text-indigo-600 hover:text-indigo-900 font-medium flex items-center mb-2">
            <i class="fas fa-arrow-left mr-2"></i> Back to Cases
        </a>
        <h1 class="text-3xl font-bold text-gray-900">Book New Surgery Case</h1>
        <p class="text-gray-600 mt-1">Create a case to begin reserving inventory</p>
    </div>

    <div class="bg-white shadow rounded-lg">
        <form action="{{ route('reservations.store') }}" method="POST">
            @csrf
            <div class="p-6 space-y-6">
                <!-- Patient Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Patient Name *</label>
                        <input type="text" name="patient_name" value="{{ old('patient_name') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('patient_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Patient ID / MRN</label>
                        <input type="text" name="patient_id" value="{{ old('patient_id') }}" placeholder="Optional" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>
                
                <!-- Billing Info -->
                <div>
                     <label class="block text-sm font-medium text-gray-700">Hospital / Facility (Bill To) <span class="text-gray-400 text-xs font-normal">(Optional - defaults to Patient if empty)</span></label>
                     <select name="customer_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                         <option value="">-- Patient (Self Pay) --</option>
                         @foreach($customers as $customer)
                             <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                 {{ $customer->name }} {{ $customer->customer_code ? "({$customer->customer_code})" : '' }}
                             </option>
                         @endforeach
                     </select>
                </div>

                <!-- Surgeon & Procedure -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Surgeon Name *</label>
                        <input type="text" name="surgeon_name" value="{{ old('surgeon_name') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('surgeon_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Procedure Name</label>
                        <input type="text" name="procedure_name" value="{{ old('procedure_name') }}" placeholder="e.g. ACL Reconstruction" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>

                <!-- Logistics -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Surgery Date & Time *</label>
                        <input type="datetime-local" name="surgery_date" value="{{ old('surgery_date', now()->addDay()->setHour(8)->setMinute(0)->format('Y-m-d\TH:i')) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('surgery_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Location *</label>
                        <select name="location_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" {{ old('location_id', $loop->first ? $location->id : '') == $location->id ? 'selected' : '' }}>{{ $location->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Stock will be reserved from this location.</p>
                    </div>
                </div>

                <!-- Notes -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Notes</label>
                    <textarea name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="px-6 py-4 bg-gray-50 text-right">
                <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Create Draft Case
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
