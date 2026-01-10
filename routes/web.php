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

    // Data Collection
    Volt::route('data-points/submit', 'data-collection.reading-form')->name('data-points.submit');
    Volt::route('data-points/{dataPoint}/edit', 'data-collection.reading-form')->name('data-points.edit');

    // Maps
    Volt::route('maps/survey', 'maps.survey-map-viewer')->name('maps.survey');
    Volt::route('maps/satellite', 'maps.satellite-viewer')->name('maps.satellite');

    // Analytics
    Volt::route('analytics/heatmap', 'analytics.heatmap-generator')->name('analytics.heatmap');
    Volt::route('analytics/trends', 'analytics.trend-chart')->name('analytics.trends');

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
