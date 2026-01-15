<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

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

    // Campaigns - redirect to Filament admin
    Route::redirect('campaigns', '/admin/campaigns')->name('campaigns.index');

    // Exports
    Route::get('campaigns/{campaign}/export/json', [App\Http\Controllers\ExportController::class, 'exportJSON'])
        ->name('campaigns.export.json');
    Route::get('campaigns/{campaign}/export/csv', [App\Http\Controllers\ExportController::class, 'exportCSV'])
        ->name('campaigns.export.csv');

    // Survey Zone Management
    Volt::route('campaigns/{campaignId}/zones/manage', 'campaigns.zone-manager')->name('campaigns.zones.manage');

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
