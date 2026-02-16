@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Submit Reimbursement Request</h1>
        <p class="mt-2 text-sm text-gray-600">Request reimbursement for work-related expenses</p>
    </div>

    <form action="{{ route('reimbursements.store') }}" method="POST" enctype="multipart/form-data" class="bg-white shadow rounded-lg p-6">
        @csrf
        
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Description *</label>
                <textarea name="description" rows="3" required class="mt-1 block w-full rounded-md border-gray-300" placeholder="Describe the expense...">{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Category</label>
                <select name="category" class="mt-1 block w-full rounded-md border-gray-300">
                    <option value="">Select category...</option>
                    <option value="Travel" {{ old('category') === 'Travel' ? 'selected' : '' }}>Travel</option>
                    <option value="Meals" {{ old('category') === 'Meals' ? 'selected' : '' }}>Meals</option>
                    <option value="Supplies" {{ old('category') === 'Supplies' ? 'selected' : '' }}>Supplies</option>
                    <option value="Fuel" {{ old('category') === 'Fuel' ? 'selected' : '' }}>Fuel</option>
                    <option value="Other" {{ old('category') === 'Other' ? 'selected' : '' }}>Other</option>
                </select>
                @error('category')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Amount (KES) *</label>
                    <input type="number" name="amount" step="0.01" min="0.01" required value="{{ old('amount') }}" class="mt-1 block w-full rounded-md border-gray-300" placeholder="0.00">
                    @error('amount')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Expense Date *</label>
                    <input type="date" name="expense_date" required value="{{ old('expense_date', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}" class="mt-1 block w-full rounded-md border-gray-300">
                    @error('expense_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Receipt/Invoice</label>
                <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                <p class="mt-1 text-xs text-gray-500">Accepted formats: JPG, PNG, PDF (max 5MB)</p>
                @error('receipt')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('reimbursements.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50">
                Cancel
            </a>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                Submit Request
            </button>
        </div>
    </form>
</div>
@endsection
