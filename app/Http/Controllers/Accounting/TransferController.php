<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Services\AccountingService;
use Illuminate\Http\Request;

class TransferController extends Controller
{
    protected $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    public function create()
    {
        // Fetch Asset accounts (Cash, Bank) for transfer
        // Typically transfers are between Liquid Assets
        $accounts = ChartOfAccount::where('account_type', 'Asset')
            ->where(function($q) {
                // Filter specifically for Cash/Bank type logic if possible, 
                // but strictly speaking, our subtype logic wasn't fully enforced.
                // We'll rely on "Asset" and maybe naming checks or flexible allowing any asset.
                // Better: Just show all Assets, or filter by code prefixes 1000-1199?
                // Let's verify commonly used subtypes. 'Cash', 'Bank'.
                $q->where('sub_type', 'Cash')
                  ->orWhere('sub_type', 'Bank')
                  ->orWhere('code', 'like', '10%'); // Heuristic
            })
            ->orderBy('code')
            ->get();

        return view('accounting.transfers.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'from_account_id' => 'required|exists:chart_of_accounts,id|different:to_account_id',
            'to_account_id' => 'required|exists:chart_of_accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'transfer_date' => 'required|date',
            'description' => 'required|string|max:255',
        ]);

        $entry = $this->accountingService->recordTransfer(
            $validated['from_account_id'],
            $validated['to_account_id'],
            $validated['amount'],
            $validated['transfer_date'],
            $validated['description'],
            $request->user()
        );

        if ($entry) {
            return redirect()->route('accounting.dashboard')->with('success', 'Transfer recorded successfully (Entry #' . $entry->entry_number . ')');
        } else {
            return redirect()->back()->with('error', 'Failed to record transfer. Check logs.');
        }
    }
}
