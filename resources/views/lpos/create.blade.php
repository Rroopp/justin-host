@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Upload New LPO</h2>

        <form action="{{ route('lpos.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <!-- Customer Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Customer / Facility <span class="text-red-500">*</span></label>
                <select name="customer_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Select Customer</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">
                            {{ $customer->name }} {{ $customer->facility ? "($customer->facility)" : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- LPO Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">LPO Number <span class="text-red-500">*</span></label>
                    <input type="text" name="lpo_number" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Total Amount <span class="text-red-500">*</span></label>
                    <input type="number" name="amount" min="0" step="0.01" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>

            <!-- Validity Dates -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Valid From</label>
                    <input type="date" name="valid_from" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Valid Until</label>
                    <input type="date" name="valid_until" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>

            <!-- File Upload -->
            <div>
                <label class="block text-sm font-medium text-gray-700">LPO Document (PDF/Image)</label>
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:bg-gray-50 transition-colors">
                    <div class="space-y-1 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="flex text-sm text-gray-600">
                            <label for="file-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                <span>Upload a file</span>
                                <input id="file-upload" name="lpo_document" type="file" class="sr-only" accept=".pdf,.jpg,.jpeg,.png">
                            </label>
                            <p class="pl-1">or drag and drop</p>
                        </div>
                        <p class="text-xs text-gray-500">PNG, JPG, PDF up to 5MB</p>
                    </div>
                </div>
            </div>

            <!-- Description -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Notes / Description</label>
                <textarea name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3 pt-4">
                <a href="{{ route('lpos.index') }}" class="px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-md font-medium hover:bg-gray-50">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md font-medium hover:bg-indigo-700">Save LPO</button>
            </div>
        </form>
    </div>
</div>
@endsection
