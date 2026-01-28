<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

// Serve favicon.svg as favicon.ico for browser compatibility
Route::get('favicon.ico', function () {
    return response()->file(public_path('favicon.svg'), [
        'Content-Type' => 'image/svg+xml',
    ]);
});

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'subscription.rate_limit'])->group(function () {
    // Data Collection - rate limited per tier
    Volt::route('data-points/submit', 'data-collection.reading-form')->name('data-points.submit');
    Volt::route('data-points/{dataPoint}/edit', 'data-collection.reading-form')->name('data-points.edit');

    // Maps - rate limited per tier
    Volt::route('maps/survey', 'maps.survey-map-viewer')->name('maps.survey');
    Volt::route('maps/satellite', 'maps.satellite-viewer')->name('maps.satellite');

    // Analytics - rate limited per tier
    Volt::route('analytics/heatmap', 'analytics.heatmap-generator')->name('analytics.heatmap');
    Volt::route('analytics/trends', 'analytics.trend-chart')->name('analytics.trends');

    // Exports - rate limited per tier
    Route::get('campaigns/{campaign}/export/json', [App\Http\Controllers\ExportController::class, 'exportJSON'])
        ->name('campaigns.export.json')
        ->where('campaign', '[0-9]+');
    Route::get('campaigns/{campaign}/export/csv', [App\Http\Controllers\ExportController::class, 'exportCSV'])
        ->name('campaigns.export.csv')
        ->where('campaign', '[0-9]+');
    Route::get('campaigns/{campaign}/export/pdf', [App\Http\Controllers\ExportController::class, 'exportPDF'])
        ->name('campaigns.export.pdf')
        ->where('campaign', '[0-9]+');
});

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    // Campaigns - user-facing campaign list (not rate limited)
    Volt::route('campaigns', 'campaigns.my-campaigns')->name('campaigns.index');

    // Survey Zone Management (not rate limited)
    Volt::route('campaigns/{campaignId}/zones/manage', 'campaigns.zone-manager')->name('campaigns.zones.manage');

    // Billing & Subscriptions
    Volt::route('billing/plans', 'billing.subscription-plans')->name('billing.plans');
    Volt::route('billing/checkout/{plan}', 'billing.checkout')->name('billing.checkout');
    Volt::route('billing/success', 'billing.success')->name('billing.success');
    Volt::route('billing/cancel', 'billing.cancel')->name('billing.cancel');
    Volt::route('billing/manage', 'billing.manage')->name('billing.manage');
    Volt::route('billing/usage', 'billing.usage-dashboard')->name('billing.usage');
    Route::get('billing/invoices/{invoiceId}', function (string $invoiceId) {
        return auth()->user()->downloadInvoice($invoiceId, [
            'vendor' => config('app.name'),
            'product' => 'Subscription',
        ]);
    })->name('billing.invoice.download');
    Route::get('billing/portal', function () {
        $user = auth()->user();

        if (! $user->subscribed('default')) {
            return redirect()->route('billing.manage')->with('error', 'No active subscription found.');
        }

        return $user->redirectToBillingPortal(route('billing.manage'));
    })->name('billing.portal');

    // Settings
    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('user-password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});
