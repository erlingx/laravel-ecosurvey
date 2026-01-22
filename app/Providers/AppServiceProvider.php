<?php

namespace App\Providers;

use App\Models\DataPoint;
use App\Observers\DataPointObserver;
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
        DataPoint::observe(DataPointObserver::class);

        // Register Stripe webhook listener
        \Event::listen(
            \Laravel\Cashier\Events\WebhookReceived::class,
            \App\Listeners\StripeWebhookListener::class
        );
    }
}
