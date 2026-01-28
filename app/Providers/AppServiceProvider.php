<?php

namespace App\Providers;

use App\Models\Campaign;
use App\Models\DataPoint;
use App\Observers\DataPointObserver;
use App\Policies\CampaignPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
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
        // Prevent lazy loading in non-production (detect N+1 queries)
        Model::preventLazyLoading(! app()->isProduction());

        // Register policies
        Gate::policy(Campaign::class, CampaignPolicy::class);

        DataPoint::observe(DataPointObserver::class);

        // Register Stripe webhook listener
        \Event::listen(
            \Laravel\Cashier\Events\WebhookReceived::class,
            \App\Listeners\StripeWebhookListener::class
        );
    }
}
