@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Create Budget</h1>
                <p class="text-gray-600 mt-1">Set up a new budget for your organization</p>
            </div>
            <a href="{{ route('budgets.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-arrow-left mr-2"></i>Back to Budgets
            </a>
        </div>
    </div>

    <form action="{{ route('budgets.store') }}" method="POST" x-data="budgetForm()">
        @csrf
        
        <div class="space-y-6">
            <!-- Basic Information -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Budget Name *</label>
                        <input type="text" name="name" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="e.g., Annual Budget 2025">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Period Type *</label>
                        <select name="period_type" x-model="periodType" @change="updateDatesForPeriod()" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="annual">Annual</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="monthly">Monthly</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>
                    
                    <div></div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Date *</label>
                        <input type="date" name="start_date" x-model="startDate" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">End Date *</label>
                        <input type="date" name="end_date" x-model="endDate" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" rows="3" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Brief description of this budget..."></textarea>
                    </div>
                </div>
            </div>

            <!-- AI Forecasting -->
            <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-lg shadow p-6 border border-indigo-100">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 flex items-center">
                            <i class="fas fa-robot text-indigo-600 mr-2"></i> AI Budget Assistant
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">Let Gemini AI analyze your historical sales and generate a budget for you.</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Available Capital (Optional)</label>
                            <input type="number" x-model="capital" class="block w-40 rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="e.g. 500000">
                        </div>
                        <button type="button" @click="generateAiBudget()" :disabled="isLoadingAi" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 transition-all">
                            <i class="fas fa-magic mr-2" :class="{'fa-spin': isLoadingAi}"></i>
                            <span x-text="isLoadingAi ? 'Analyzing Data...' : 'Auto-Generate Budget'"></span>
                        </button>
                    </div>
                </div>
                <div x-show="aiError" class="mt-3 text-sm text-red-600">
                    <i class="fas fa-exclamation-circle mr-1"></i> <span x-text="aiError"></span>
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
                        <div class="border border-gray-200 rounded-lg p-4 transition-all hover:shadow-md">
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
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Description / AI Explanation</label>
                                    <input type="text" :name="'line_items['+index+'][description]'" x-model="item.description" class="block w-full rounded-md border-gray-300 text-sm" placeholder="Optional description...">
                                    <p x-show="item.explanation" x-text="item.explanation" class="text-xs text-indigo-600 mt-1 italic"></p>
                                </div>
                            </div>
                        </div>
                    </template>

                    <div x-show="lineItems.length === 0" class="text-center py-12 text-gray-500">
                        <i class="fas fa-list text-4xl mb-4 text-gray-300"></i>
                        <p>No line items added yet</p>
                        <button type="button" @click="addLineItem()" class="text-indigo-600 hover:text-indigo-900 mt-2">Add your first line item</button>
                    </div>
                </div>

                <!-- Total -->
                <div class="mt-6 pt-4 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <span class="text-lg font-semibold text-gray-900">Total Allocated:</span>
                        <div class="text-right">
                            <span class="text-2xl font-bold text-indigo-600">KES <span x-text="totalAllocated.toLocaleString()"></span></span>
                            <div x-show="capital > 0" class="text-sm mt-1" :class="totalAllocated > capital ? 'text-red-500' : 'text-green-500'">
                                <span x-text="totalAllocated > capital ? 'Over Capital: ' : 'Remaining Capital: '"></span>
                                KES <span x-text="Math.abs(capital - totalAllocated).toLocaleString()"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Additional Notes</h2>
                <textarea name="notes" rows="4" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Any additional notes or comments about this budget..."></textarea>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3">
                <a href="{{ route('budgets.index') }}" class="px-6 py-3 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-3 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                    <i class="fas fa-save mr-2"></i>Create Budget
                </button>
            </div>
        </div>
    </form>
</div>

<script>
function budgetForm() {
    return {
        periodType: 'annual',
        startDate: '',
        endDate: '',
        capital: '',
        lineItems: [],
        totalAllocated: 0,
        isLoadingAi: false,
        aiError: null,
        
        init() {
            // Set default start date to today
            const today = new Date();
            this.startDate = today.toISOString().split('T')[0];
            this.updateDatesForPeriod();
            
            // Watch for start date changes to update end date if period is not custom
            this.$watch('startDate', (value) => {
                if (value && this.periodType !== 'custom') {
                    this.updateDatesForPeriod();
                }
            });
        },
        
        updateDatesForPeriod() {
            if (!this.startDate) return;
            
            const start = new Date(this.startDate);
            const end = new Date(start);
            
            switch(this.periodType) {
                case 'annual':
                    end.setFullYear(start.getFullYear() + 1);
                    end.setDate(end.getDate() - 1);
                    this.endDate = end.toISOString().split('T')[0];
                    break;
                    
                case 'quarterly':
                    end.setMonth(start.getMonth() + 3);
                    end.setDate(end.getDate() - 1);
                    this.endDate = end.toISOString().split('T')[0];
                    break;
                    
                case 'monthly':
                    end.setMonth(start.getMonth() + 1);
                    end.setDate(end.getDate() - 1);
                    this.endDate = end.toISOString().split('T')[0];
                    break;
                    
                case 'custom':
                    // Don't auto-fill for custom
                    break;
            }
        },
        
        async generateAiBudget() {
            this.isLoadingAi = true;
            this.aiError = null;
            
            try {
                const response = await fetch('{{ route("budgets.generate-ai") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        period_type: this.periodType,
                        start_date: this.startDate,
                        end_date: this.endDate,
                        capital: this.capital
                    })
                });
                
                if (!response.ok) throw new Error('Failed to generate budget');
                
                const data = await response.json();
                
                if (data.line_items) {
                    this.lineItems = data.line_items.map(item => ({
                        category: item.category,
                        subcategory: item.subcategory,
                        allocated_amount: item.allocated_amount,
                        description: item.explanation,
                        explanation: item.explanation
                    }));
                    this.calculateTotal();
                }
                
            } catch (error) {
                console.error(error);
                this.aiError = 'Failed to generate budget. Please try again or check your settings.';
            } finally {
                this.isLoadingAi = false;
            }
        },
        
        addLineItem() {
            this.lineItems.push({
                category: '',
                subcategory: '',
                allocated_amount: 0,
                description: '',
                forecast_basis: 'manual'
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
