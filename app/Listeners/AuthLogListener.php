<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use App\Services\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class AuthLogListener
{
    protected $auditService;

    /**
     * Create the event listener.
     */
    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Handle the event.
     */
    public function handle($event): void
    {
        if ($event instanceof Login) {
            $this->auditService->log(
                $event->user,
                'login',
                'auth',
                $event->user->id,
                'User logged in',
                get_class($event->user)
            );
        } elseif ($event instanceof Logout) {
             $this->auditService->log(
                $event->user,
                'logout',
                'auth',
                $event->user->id,
                'User logged out',
                get_class($event->user)
            );
        }
    }
}
