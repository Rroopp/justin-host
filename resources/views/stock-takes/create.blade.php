@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">New Stock Take</h1>
        <p class="mt-2 text-sm text-gray-600">Create a new physical inventory count session</p>
    </div>

    <div class="bg-white shadow rounded-lg p-6">
        <form action="{{ route('stock-takes.store') }}" method="POST">
            @csrf

            <div class="space-y-6">
                <!-- Date -->
                <div>
                    <label for="date" class="block text-sm font-medium text-gray-700">Stock Take Date *</label>
                    <input type="date" 
                           name="date" 
                           id="date" 
                           value="{{ old('date', date('Y-m-d')) }}" 
                           required 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Category Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Categories to Include</label>
                    <p class="text-xs text-gray-500 mb-3">Leave empty to include all categories</p>
                    
                    <div class="grid grid-cols-2 gap-3 max-h-64 overflow-y-auto border border-gray-200 rounded-md p-3">
                        @foreach($categories as $category)
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="category_filter[]" 
                                       value="{{ $category }}" 
                                       {{ in_array($category, old('category_filter', [])) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">{{ $category }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('category_filter')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Notes -->
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                    <textarea name="notes" 
                              id="notes" 
                              rows="3" 
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                              placeholder="Optional notes about this stock take...">{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Info Box -->
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle text-blue-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                <strong>What happens next:</strong><br>
                                1. System will create a stock take sheet with all selected items<br>
                                2. Print the sheet and physically count your inventory<br>
                                3. Enter the physical counts into the system<br>
                                4. Review variances and reconcile to update inventory
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('stock-takes.index') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" 
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                    Create Stock Take
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
