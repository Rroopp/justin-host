<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Event listener is auto-discovered
        // \Illuminate\Support\Facades\Event::listen(
        //     \App\Events\SaleCompleted::class,
        //     \App\Listeners\AccountingListener::class,
        // );
    }
}
