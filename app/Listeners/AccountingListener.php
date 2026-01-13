<?php

namespace App\Listeners;

use App\Events\SaleCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class AccountingListener
{
    /**
     * Create the event listener.
     */
    protected $accountingService;

    /**
     * Create the event listener.
     */
    public function __construct(\App\Services\AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    /**
     * Handle the event.
     */
    public function handle(SaleCompleted $event): void
    {
        // Delegate to AccountingService for Journal creation
        $this->accountingService->postSaleJournal($event->sale);
    }
}
