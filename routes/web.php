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

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    // Data Collection - use regular Livewire with app layout
    Volt::route('data-points/submit', 'data-collection.reading-form')->name('data-points.submit');
    Volt::route('data-points/{dataPoint}/edit', 'data-collection.reading-form')->name('data-points.edit');

    // Maps - use regular Livewire with app layout
    Volt::route('maps/survey', 'maps.survey-map-viewer')->name('maps.survey');
    Volt::route('maps/satellite', 'maps.satellite-viewer')->name('maps.satellite');

    // Analytics - use regular Livewire with app layout
    Volt::route('analytics/heatmap', 'analytics.heatmap-generator')->name('analytics.heatmap');
    Volt::route('analytics/trends', 'analytics.trend-chart')->name('analytics.trends');

    // Campaigns - user-facing campaign list
    Volt::route('campaigns', 'campaigns.my-campaigns')->name('campaigns.index');

    // Exports
    Route::get('campaigns/{campaign}/export/json', [App\Http\Controllers\ExportController::class, 'exportJSON'])
        ->name('campaigns.export.json');
    Route::get('campaigns/{campaign}/export/csv', [App\Http\Controllers\ExportController::class, 'exportCSV'])
        ->name('campaigns.export.csv');
    Route::get('campaigns/{campaign}/export/pdf', [App\Http\Controllers\ExportController::class, 'exportPDF'])
        ->name('campaigns.export.pdf');

    // Survey Zone Management
    Volt::route('campaigns/{campaignId}/zones/manage', 'campaigns.zone-manager')->name('campaigns.zones.manage');

    // Billing & Subscriptions
    Volt::route('billing/plans', 'billing.subscription-plans')->name('billing.plans');
    Volt::route('billing/checkout/{plan}', 'billing.checkout')->name('billing.checkout');
    Volt::route('billing/success', 'billing.success')->name('billing.success');
    Volt::route('billing/cancel', 'billing.cancel')->name('billing.cancel');
    Volt::route('billing/manage', 'billing.manage')->name('billing.manage');

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
