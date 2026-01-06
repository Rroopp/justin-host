@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Edit Budget</h1>
                <p class="text-gray-600 mt-1">{{ $budget->reference_number }}</p>
            </div>
            <a href="{{ route('budgets.show', $budget) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-arrow-left mr-2"></i>Cancel
            </a>
        </div>
    </div>

    <form action="{{ route('budgets.update', $budget) }}" method="POST" x-data="budgetForm()">
        @csrf
        @method('PUT')
        
        <div class="space-y-6">
            <!-- Basic Information -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Budget Name *</label>
                        <input type="text" name="name" value="{{ $budget->name }}" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Period Type</label>
                        <input type="text" value="{{ ucfirst($budget->period_type) }}" disabled class="block w-full rounded-md border-gray-300 bg-gray-100 text-gray-500 shadow-sm">
                        <p class="text-xs text-gray-500 mt-1">Period type cannot be changed.</p>
                    </div>
                    
                    <div></div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                        <input type="date" value="{{ $budget->start_date->format('Y-m-d') }}" disabled class="block w-full rounded-md border-gray-300 bg-gray-100 text-gray-500 shadow-sm">
                        <p class="text-xs text-gray-500 mt-1">Start date cannot be changed.</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                        <input type="date" value="{{ $budget->end_date->format('Y-m-d') }}" disabled class="block w-full rounded-md border-gray-300 bg-gray-100 text-gray-500 shadow-sm">
                        <p class="text-xs text-gray-500 mt-1">End date cannot be changed.</p>
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" rows="3" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ $budget->description }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Budget Line Items -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Budget Line Items</h2>
                    <button type="button" @click="addLineItem()" class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                        <i class="fas fa-plus mr-2"></i>Add Line Item
                    </button>
                </div>

                <div class="space-y-4">
                    <template x-for="(item, index) in lineItems" :key="index">
                        <div class="border border-gray-200 rounded-lg p-4">
                            <input type="hidden" :name="'line_items['+index+'][id]'" x-model="item.id">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                                    <select :name="'line_items['+index+'][category]'" x-model="item.category" required class="block w-full rounded-md border-gray-300 text-sm">
                                        <option value="">Select category...</option>
                                        <option value="Inventory">Inventory & Supplies</option>
                                        <option value="Salaries">Staff Salaries</option>
                                        <option value="Rent">Rent & Utilities</option>
                                        <option value="Marketing">Marketing & Advertising</option>
                                        <option value="Equipment">Equipment & Maintenance</option>
                                        <option value="Operations">Operational Expenses</option>
                                        <option value="Other">Other Expenses</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Subcategory</label>
                                    <input type="text" :name="'line_items['+index+'][subcategory]'" x-model="item.subcategory" class="block w-full rounded-md border-gray-300 text-sm">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Allocated Amount *</label>
                                    <input type="number" :name="'line_items['+index+'][allocated_amount]'" x-model="item.allocated_amount" @input="calculateTotal()" required min="0" step="0.01" class="block w-full rounded-md border-gray-300 text-sm">
                                </div>
                                
                                <div class="flex items-end">
                                    <button type="button" @click="removeLineItem(index)" class="w-full px-3 py-2 border border-red-300 text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50">
                                        <i class="fas fa-trash mr-2"></i>Remove
                                    </button>
                                </div>
                                
                                <div class="col-span-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                    <input type="text" :name="'line_items['+index+'][description]'" x-model="item.description" class="block w-full rounded-md border-gray-300 text-sm">
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Total -->
                <div class="mt-6 pt-4 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <span class="text-lg font-semibold text-gray-900">Total Allocated:</span>
                        <span class="text-2xl font-bold text-indigo-600">KES <span x-text="totalAllocated.toLocaleString()"></span></span>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Additional Notes</h2>
                <textarea name="notes" rows="4" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ $budget->notes }}</textarea>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3">
                <a href="{{ route('budgets.show', $budget) }}" class="px-6 py-3 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-3 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                    <i class="fas fa-save mr-2"></i>Update Budget
                </button>
            </div>
        </div>
    </form>
</div>

<script>
function budgetForm() {
    return {
        lineItems: @json($budget->lineItems),
        totalAllocated: {{ $budget->total_allocated }},
        
        init() {
            // Ensure numbers are handled as numbers
            this.lineItems = this.lineItems.map(item => ({
                ...item,
                allocated_amount: parseFloat(item.allocated_amount)
            }));
            this.calculateTotal();
        },
        
        addLineItem() {
            this.lineItems.push({
                id: null,
                category: '',
                subcategory: '',
                allocated_amount: 0,
                description: ''
            });
        },
        
        removeLineItem(index) {
            this.lineItems.splice(index, 1);
            this.calculateTotal();
        },
        
        calculateTotal() {
            this.totalAllocated = this.lineItems.reduce((sum, item) => {
                return sum + parseFloat(item.allocated_amount || 0);
            }, 0);
        }
    }
}
</script>
@endsection
