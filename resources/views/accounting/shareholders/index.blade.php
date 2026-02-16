@extends('layouts.app')

@section('content')
<div x-data="shareholderManager()" x-init="loadStaff()">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Shareholders</h1>
            <p class="mt-2 text-sm text-gray-600">Manage investors and equity distribution</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('accounting.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300">
                &larr; Back to Accounts
            </a>
            <button type="button" @click="openDistributionModal()" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                Distribute Profits
            </button>
            <button type="button" @click="openAddModal()" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 ml-2">
                Add Shareholder
            </button>
            <button type="button" @click="openCapitalModal()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 ml-2">
                Record Capital
            </button>
        </div>
    </div>

    <!-- Shareholders Table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Staff Member</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ownership %</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Capital Balance</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($shareholders as $sh)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $sh->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            @if($sh->staff)
                                {{ $sh->staff->full_name }} ({{ $sh->staff->username }})
                            @else
                                <span class="text-gray-400">Not linked</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ number_format($sh->ownership_percentage, 2) }}%</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium">{{ number_format($sh->capital_balance, 2) }} ({{ $sh->capitalAccount ? $sh->capitalAccount->code : 'N/A' }})</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button type="button" class="text-indigo-600 hover:text-indigo-900 mr-3" @click='openEditModal(@json($sh))'>Edit</button>
                            <form action="{{ route('accounting.shareholders.destroy', $sh->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">
                            No owners/investors found. Add one to start tracking equity.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Add/Edit Shareholder Modal -->
    <div x-show="showModal" class="fixed z-50 inset-0 overflow-y-auto" style="display: none;" x-cloak>
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="closeModals()"></div>
            <div class="relative inline-block align-middle bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form :action="form.id ? `/accounting/shareholders/${form.id}` : '/accounting/shareholders'" method="POST">
                    @csrf
                    <template x-if="form.id"><input type="hidden" name="_method" value="PUT"></template>
                    
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" x-text="form.id ? 'Edit Shareholder' : 'Add Shareholder'"></h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Link to Staff Member (Optional)</label>
                                <select name="staff_id" x-model="form.staff_id" class="mt-1 block w-full rounded-md border-gray-300">
                                    <option value="">Select a staff member...</option>
                                    <template x-for="staff in staffList" :key="staff.id">
                                        <option :value="staff.id" x-text="`${staff.full_name} (${staff.username})`"></option>
                                    </template>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Link this shareholder to an existing staff member</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Name *</label>
                                <input type="text" name="name" x-model="form.name" required class="mt-1 block w-full rounded-md border-gray-300">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Ownership Percentage (%) *</label>
                                <input type="number" step="0.01" min="0" max="100" name="ownership_percentage" x-model="form.ownership_percentage" required class="mt-1 block w-full rounded-md border-gray-300">
                                <p class="text-xs text-gray-500 mt-1">Total across all shareholders should equal 100%.</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm">
                            Save
                        </button>
                        <button type="button" @click="closeModals()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Dividend Distribution Modal -->
    <div x-show="showDistributionModal" class="fixed z-50 inset-0 overflow-y-auto" style="display: none;" x-cloak>
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="closeModals()"></div>
            <div class="relative inline-block align-middle bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Profit Distribution (Preview)</h3>
                    <div x-show="loadingDistribution" class="text-center py-4">
                        <p>Calculating...</p>
                    </div>
                    <div x-show="!loadingDistribution">
                        <p class="text-sm text-gray-600 mb-4">Based on Net Income (Total Income - Total Expenses) to date.</p>
                        
                        <div class="bg-gray-50 p-4 rounded-md mb-4 flex justify-between">
                            <span class="font-medium text-gray-700">Total Net Income Available:</span>
                            <span class="font-bold text-green-700" x-text="formatCurrency(distributionData.total_net_income)"></span>
                        </div>

                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="text-left text-xs font-medium text-gray-500 uppercase">Shareholder</th>
                                    <th class="text-right text-xs font-medium text-gray-500 uppercase">Share %</th>
                                    <th class="text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="item in distributionData.distribution" :key="item.name">
                                    <tr>
                                        <td class="py-2 text-sm text-gray-900" x-text="item.name"></td>
                                        <td class="py-2 text-sm text-gray-500 text-right" x-text="Number(item.percentage).toFixed(2) + '%'"></td>
                                        <td class="py-2 text-sm font-medium text-green-600 text-right" x-text="formatCurrency(item.amount)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    
                        <div class="mt-4 bg-yellow-50 p-3 rounded text-xs text-yellow-800">
                            <strong>Note:</strong> This checks available profit. It does not automatically create withdrawal transactions. If you wish to pay out, use "Journal Entries" or "Expenses" to record the payment to each shareholder using their Drawings or Current account.
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" @click="closeModals()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Capital Investment Modal -->
    <div x-show="showCapitalModal" class="fixed z-50 inset-0 overflow-y-auto" style="display: none;" x-cloak>
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="closeModals()"></div>
            <div class="relative inline-block align-middle bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form action="{{ route('accounting.capital-investment') }}" method="POST">
                    @csrf
                    
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Record Capital Investment</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Shareholder</label>
                                <select name="shareholder_id" required class="mt-1 block w-full rounded-md border-gray-300">
                                    <option value="">Select Shareholder</option>
                                    @foreach($shareholders as $sh)
                                        <option value="{{ $sh->id }}">{{ $sh->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Amount Invested</label>
                                <input type="number" step="0.01" min="0" name="amount" required class="mt-1 block w-full rounded-md border-gray-300">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Deposit To (Asset Account)</label>
                                <select name="account_id" required class="mt-1 block w-full rounded-md border-gray-300">
                                    @foreach($assetAccounts as $acc)
                                        <option value="{{ $acc->id }}" {{ Str::contains($acc->name, 'Bank') ? 'selected' : '' }}>
                                            {{ $acc->code }} - {{ $acc->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Date</label>
                                <input type="date" name="date" value="{{ date('Y-m-d') }}" required class="mt-1 block w-full rounded-md border-gray-300">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Description</label>
                                <input type="text" name="description" value="Capital Injection" required class="mt-1 block w-full rounded-md border-gray-300">
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 sm:ml-3 sm:w-auto sm:text-sm">
                            Record Investment
                        </button>
                        <button type="button" @click="closeModals()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
window.shareholderManager = function() {
    return {
        showModal: false,
        showDistributionModal: false,
        showCapitalModal: false,
        loadingDistribution: false,
        distributionData: { total_net_income: 0, distribution: [] },
        staffList: @json($staffList ?? []),

        async loadStaff() {
            // No longer needed, passed from controller
        },

        openAddModal() {
            this.form = { id: null, name: '', ownership_percentage: '', staff_id: '' };
            this.showModal = true;
            this.showDistributionModal = false;
        },

        openEditModal(data) {
            this.form = {
                id: data.id,
                name: data.name,
                ownership_percentage: data.ownership_percentage,
                staff_id: data.staff_id || ''
            };
            this.showModal = true;
            this.showDistributionModal = false;
        },

        async openDistributionModal() {
            this.showDistributionModal = true;
            this.showModal = false;
            this.loadingDistribution = true;
            try {
                const response = await axios.get('/accounting/dividends/preview');
                this.distributionData = response.data;
            } catch (error) {
                alert('Failed to load distribution data');
            } finally {
                this.loadingDistribution = false;
            }
        },

        openCapitalModal() {
            this.showCapitalModal = true;
            this.showModal = false;
            this.showDistributionModal = false;
        },

        closeModals() {
            this.showModal = false;
            this.showDistributionModal = false;
            this.showCapitalModal = false;
        },

        formatCurrency(amount) {
            return 'KSh ' + parseFloat(amount || 0).toLocaleString('en-KE', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
    }
}
</script>
@endsection
