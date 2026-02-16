@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto" x-data="{ showApproveModal: false, showRejectModal: false, showPaymentModal: false }">
    <div class="mb-6">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Reimbursement Details</h1>
                <p class="mt-1 text-sm text-gray-600">{{ $reimbursement->reference_number }}</p>
            </div>
            <div>
                @if($reimbursement->status === 'pending')
                    <span class="px-3 py-1 text-sm font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                @elseif($reimbursement->status === 'approved')
                    <span class="px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800">Approved</span>
                @elseif($reimbursement->status === 'rejected')
                    <span class="px-3 py-1 text-sm font-semibold rounded-full bg-red-100 text-red-800">Rejected</span>
                @else
                    <span class="px-3 py-1 text-sm font-semibold rounded-full bg-blue-100 text-blue-800">Paid</span>
                @endif
            </div>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Expense Information</h2>
        
        <div class="grid grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-500">Staff Member</label>
                <p class="mt-1 text-sm text-gray-900">{{ $reimbursement->staff->full_name }}</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-500">Category</label>
                <p class="mt-1 text-sm text-gray-900">{{ $reimbursement->category ?? 'Not specified' }}</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-500">Amount</label>
                <p class="mt-1 text-lg font-semibold text-gray-900">KES {{ number_format($reimbursement->amount, 2) }}</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-500">Expense Date</label>
                <p class="mt-1 text-sm text-gray-900">{{ $reimbursement->expense_date->format('d M Y') }}</p>
            </div>
            
            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-500">Description</label>
                <p class="mt-1 text-sm text-gray-900">{{ $reimbursement->description }}</p>
            </div>
            
            @if($reimbursement->receipt_file_path)
            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-500 mb-2">Receipt/Invoice</label>
                @if(Str::endsWith($reimbursement->receipt_file_path, '.pdf'))
                    <a href="{{ Storage::url($reimbursement->receipt_file_path) }}" target="_blank" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <i class="fas fa-file-pdf text-red-600 mr-2"></i> View PDF
                    </a>
                @else
                    <img src="{{ Storage::url($reimbursement->receipt_file_path) }}" alt="Receipt" class="max-w-md rounded-lg border">
                @endif
            </div>
            @endif
        </div>
    </div>

    @if($reimbursement->status !== 'pending')
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
            @if($reimbursement->status === 'rejected') Rejection @else Approval @endif Details
        </h2>
        
        <div class="grid grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-500">
                    @if($reimbursement->status === 'rejected') Rejected @else Approved @endif By
                </label>
                <p class="mt-1 text-sm text-gray-900">{{ $reimbursement->approvedBy->full_name ?? 'N/A' }}</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-500">Date</label>
                <p class="mt-1 text-sm text-gray-900">{{ $reimbursement->approved_at?->format('d M Y H:i') ?? 'N/A' }}</p>
            </div>
            
            @if($reimbursement->status === 'rejected' && $reimbursement->rejection_reason)
            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-500">Rejection Reason</label>
                <p class="mt-1 text-sm text-gray-900">{{ $reimbursement->rejection_reason }}</p>
            </div>
            @elseif($reimbursement->approval_notes)
            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-500">Approval Notes</label>
                <p class="mt-1 text-sm text-gray-900">{{ $reimbursement->approval_notes }}</p>
            </div>
            @endif
        </div>
    </div>
    @endif

    @if($reimbursement->status === 'paid')
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Payment Details</h2>
        
        <div class="grid grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-500">Payment Method</label>
                <p class="mt-1 text-sm text-gray-900">{{ ucfirst(str_replace('_', ' ', $reimbursement->payment_method ?? 'N/A')) }}</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-500">Paid By</label>
                <p class="mt-1 text-sm text-gray-900">{{ $reimbursement->paidBy->full_name ?? 'N/A' }}</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-500">Payment Date</label>
                <p class="mt-1 text-sm text-gray-900">{{ $reimbursement->paid_at?->format('d M Y H:i') ?? 'N/A' }}</p>
            </div>
            
            @if($reimbursement->payroll_run_id)
            <div>
                <label class="block text-sm font-medium text-gray-500">Payroll Run</label>
                <p class="mt-1 text-sm text-gray-900">{{ $reimbursement->payrollRun->reference_number ?? 'N/A' }}</p>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Actions --}}
    <div class="flex justify-between items-center">
        <a href="{{ route('reimbursements.index') }}" class="text-indigo-600 hover:text-indigo-900">
            <i class="fas fa-arrow-left mr-2"></i>Back to List
        </a>
        
        <div class="flex gap-3">
            @if(auth()->user()->hasRole('admin'))
                @if($reimbursement->status === 'pending')
                    <button @click="showApproveModal = true" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                        <i class="fas fa-check mr-2"></i>Approve
                    </button>
                    <button @click="showRejectModal = true" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                        <i class="fas fa-times mr-2"></i>Reject
                    </button>
                @elseif($reimbursement->status === 'approved')
                    <button @click="showPaymentModal = true" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        <i class="fas fa-money-bill mr-2"></i>Mark as Paid
                    </button>
                @endif
            @endif
        </div>
    </div>

    {{-- Approve Modal --}}
    <div x-show="showApproveModal" x-cloak class="fixed z-50 inset-0 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showApproveModal = false"></div>
            <div class="relative bg-white rounded-lg max-w-md w-full p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Approve Reimbursement</h3>
                <form action="{{ route('reimbursements.approve', $reimbursement) }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Approval Notes (Optional)</label>
                        <textarea name="approval_notes" rows="3" class="w-full rounded-md border-gray-300" placeholder="Add any notes..."></textarea>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="showApproveModal = false" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                            Approve
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Reject Modal --}}
    <div x-show="showRejectModal" x-cloak class="fixed z-50 inset-0 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showRejectModal = false"></div>
            <div class="relative bg-white rounded-lg max-w-md w-full p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Reject Reimbursement</h3>
                <form action="{{ route('reimbursements.reject', $reimbursement) }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Rejection Reason *</label>
                        <textarea name="rejection_reason" rows="3" required class="w-full rounded-md border-gray-300" placeholder="Explain why this request is being rejected..."></textarea>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="showRejectModal = false" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                            Reject
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Payment Modal --}}
    <div x-show="showPaymentModal" x-cloak class="fixed z-50 inset-0 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showPaymentModal = false"></div>
            <div class="relative bg-white rounded-lg max-w-md w-full p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Mark as Paid</h3>
                <form action="{{ route('reimbursements.mark-paid', $reimbursement) }}" method="POST">
                    @csrf
                    <div class="space-y-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method *</label>
                            <select name="payment_method" required class="w-full rounded-md border-gray-300">
                                <option value="">Select method...</option>
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Payment Account *</label>
                            <select name="payment_account_id" required class="w-full rounded-md border-gray-300">
                                <option value="">Select account...</option>
                                @foreach($paymentAccounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="showPaymentModal = false" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Mark as Paid
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
