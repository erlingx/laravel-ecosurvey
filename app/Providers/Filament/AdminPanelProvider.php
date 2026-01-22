<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Widgets\QualityAssuranceStatsWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn (): string => Blade::render('@vite("resources/css/app.css")'),
        );

        // Inject dark mode initialization script
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_START,
            fn (): string => Blade::render(<<<'HTML'
                <script>
                    (function() {
                        const savedTheme = localStorage.getItem('theme');
                        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                        const isDark = savedTheme === 'dark' || (!savedTheme && prefersDark);
                        if (isDark) {
                            document.documentElement.classList.add('dark');
                        }
                    })();
                </script>
            HTML),
        );

        // Add custom dark mode CSS for Filament sidebar
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn (): string => Blade::render(<<<'HTML'
                <style>
                    /* Force Filament sidebar to respect dark mode */
                    .dark aside[class*="fi-sidebar"],
                    .dark .fi-sidebar {
                        --sidebar-bg: rgb(24 24 27) !important;
                        background-color: rgb(24 24 27) !important;
                        border-color: rgb(63 63 70) !important;
                    }

                    .dark nav[class*="fi-sidebar"],
                    .dark .fi-sidebar nav {
                        background-color: rgb(24 24 27) !important;
                    }

                    .dark .fi-sidebar-item,
                    .dark a[class*="fi-sidebar-item"],
                    .dark button[class*="fi-sidebar-item"] {
                        color: rgb(228 228 231) !important;
                    }

                    .dark .fi-sidebar-item-button,
                    .dark .fi-sidebar-group-label,
                    .dark .fi-sidebar-item-label {
                        color: rgb(228 228 231) !important;
                    }

                    .dark .fi-sidebar-item-button:hover,
                    .dark a[class*="fi-sidebar-item"]:hover,
                    .dark button[class*="fi-sidebar-item"]:hover {
                        background-color: rgba(255, 255, 255, 0.05) !important;
                    }

                    .dark .fi-sidebar-header,
                    .dark div[class*="fi-sidebar-header"] {
                        border-color: rgb(63 63 70) !important;
                        background-color: rgb(24 24 27) !important;
                    }

                    .dark .fi-topbar {
                        background-color: rgb(24 24 27) !important;
                        border-color: rgb(63 63 70) !important;
                    }

                    /* Filament sidebar group labels */
                    .dark .fi-sidebar-group-label,
                    .dark div[class*="fi-sidebar-group"] > div {
                        color: rgb(161 161 170) !important;
                    }

                    /* Filament sidebar icons */
                    .dark .fi-sidebar-item-icon,
                    .dark svg[class*="fi-sidebar-item-icon"] {
                        color: rgb(228 228 231) !important;
                    }

                    /* Active sidebar items */
                    .dark .fi-active .fi-sidebar-item-button,
                    .dark [class*="fi-active"] button,
                    .dark [class*="fi-active"] a {
                        background-color: rgba(16, 185, 129, 0.1) !important;
                        color: rgb(52 211 153) !important;
                    }

                    /* Center Filament notifications */
                    .fi-no {
                        position: fixed !important;
                        top: 50% !important;
                        left: 50% !important;
                        right: auto !important;
                        transform: translate(-50%, -50%) !important;
                        inset-inline-end: auto !important;
                        width: auto !important;
                        max-width: 500px !important;
                    }

                    /* Enhance notification shadow */
                    .fi-no-notification {
                        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2) !important;
                    }
                </style>
            HTML),
        );

        // Add dark mode toggle button to sidebar
        FilamentView::registerRenderHook(
            PanelsRenderHook::SIDEBAR_NAV_END,
            fn (): string => Blade::render(<<<'HTML'
                <div class="fi-sidebar-item-button" style="margin-top: 1rem;">
                    <button
                        type="button"
                        id="filament-dark-mode-toggle"
                        class="fi-sidebar-item-button flex items-center gap-x-3 rounded-lg px-2 py-2 text-sm font-medium outline-none transition duration-75 hover:bg-gray-100 dark:hover:bg-white/5 w-full"
                        onclick="toggleFilamentDarkMode()"
                    >
                        <svg id="filament-dark-mode-icon" class="fi-sidebar-item-icon h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                        </svg>
                        <span id="filament-dark-mode-label" class="fi-sidebar-item-label flex-1 text-gray-700 dark:text-gray-200">
                            Light Mode
                        </span>
                    </button>
                </div>
            HTML),
        );

        // Sync Filament dark mode toggle with our custom localStorage key
        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn (): string => Blade::render(<<<'HTML'
                <script>
                    function toggleFilamentDarkMode() {
                        const isDark = document.documentElement.classList.toggle('dark');
                        localStorage.setItem('theme', isDark ? 'dark' : 'light');
                        updateFilamentDarkModeButton(isDark);
                    }

                    function updateFilamentDarkModeButton(isDark) {
                        const label = document.getElementById('filament-dark-mode-label');
                        const icon = document.getElementById('filament-dark-mode-icon');

                        if (label) {
                            label.textContent = isDark ? 'Dark Mode' : 'Light Mode';
                        }

                        if (icon) {
                            // Sun icon for light mode, Moon icon for dark mode
                            if (isDark) {
                                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />';
                            } else {
                                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />';
                            }
                        }
                    }

                    document.addEventListener('DOMContentLoaded', function() {
                        // Initialize button state on load
                        const isDark = document.documentElement.classList.contains('dark');
                        updateFilamentDarkModeButton(isDark);

                        // Sync our theme key with Filament's dark mode state (for built-in toggle compatibility)
                        const observer = new MutationObserver(function(mutations) {
                            const isDark = document.documentElement.classList.contains('dark');
                            const savedTheme = localStorage.getItem('theme');

                            // Sync our theme key with Filament's dark mode state
                            if (isDark && savedTheme !== 'dark') {
                                localStorage.setItem('theme', 'dark');
                                updateFilamentDarkModeButton(true);
                            } else if (!isDark && savedTheme !== 'light') {
                                localStorage.setItem('theme', 'light');
                                updateFilamentDarkModeButton(false);
                            }
                        });

                        observer.observe(document.documentElement, {
                            attributes: true,
                            attributeFilter: ['class']
                        });
                    });
                </script>
            HTML),
        );
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Emerald,
            ])
            ->darkMode()
            ->brandName('EcoSurvey')
            ->brandLogo(fn () => view('filament.admin.brand'))
            ->brandLogoHeight('2rem')
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                'Overview',
                'Campaigns',
                'Data Collection',
                'Satellite & Analysis',
                'Data Quality',
                'Administration',
            ])
            ->spa()
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->pages([
                \App\Filament\Admin\Pages\AdminDashboard::class,
            ])
            ->navigationItems([
                NavigationItem::make('User Dashboard')
                    ->url('/dashboard')
                    ->icon('heroicon-o-home')
                    ->group('Overview')
                    ->sort(0),
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\Filament\\Admin\\Widgets')
            ->widgets([
                QualityAssuranceStatsWidget::class,
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
