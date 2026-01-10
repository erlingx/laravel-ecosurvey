<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>EcoSurvey - Environmental Data Collection Platform</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600|inter:400,500,600,700|dm-serif-display:400" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased bg-linear-to-br from-green-50 via-white to-blue-50 dark:from-zinc-900 dark:via-zinc-950 dark:to-emerald-950 min-h-screen">
        <!-- Navigation -->
        <header class="fixed top-0 left-0 right-0 z-50 bg-white/80 dark:bg-zinc-900/80 backdrop-blur-sm border-b border-green-100 dark:border-zinc-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center gap-3">
                        <x-app-logo-icon class="size-10 text-green-600 dark:text-green-500" />
                        <span class="text-xl font-bold text-gray-900 dark:text-white">EcoSurvey</span>
                    </div>

                    @if (Route::has('login'))
                        <nav class="flex items-center gap-4">
                            @auth
                                <a href="{{ url('/dashboard') }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:text-green-600 dark:hover:text-green-400 transition-colors">
                                    Dashboard
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:text-green-600 dark:hover:text-green-400 transition-colors">
                                    Log in
                                </a>

                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 rounded-lg transition-colors shadow-sm">
                                        Get Started
                                    </a>
                                @endif
                            @endauth
                        </nav>
                    @endif
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <main class="pt-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col items-center justify-center py-6 md:py-8 lg:py-10">
                    <!-- Logo -->
                    <div class="mb-3 md:mb-4 animate-fade-in">
                        <div class="relative">
                            <div class="absolute inset-0 bg-green-500/20 dark:bg-green-400/10 blur-3xl rounded-full"></div>
                            <x-app-logo-icon class="relative size-14 md:size-16 lg:size-20 text-green-600 dark:text-green-500" />
                        </div>
                    </div>

                    <!-- Title & Byline -->
                    <div class="text-center mb-4 md:mb-6 max-w-4xl animate-fade-in-up">
                        <h1 class="text-3xl sm:text-4xl md:text-5xl font-bold text-gray-900 dark:text-white mb-2 md:mb-3 tracking-tight">
                            EcoSurvey
                        </h1>
                        <p class="text-base sm:text-lg md:text-xl text-gray-600 dark:text-gray-300 mb-2 font-medium">
                            Environmental Data Collection Platform
                        </p>
                        <p class="text-sm sm:text-base text-gray-500 dark:text-gray-400 max-w-2xl mx-auto">
                            Monitor, analyze, and protect our environment with satellite-powered insights and field data collection
                        </p>
                    </div>

                    <!-- CTA Buttons -->
                    <div class="flex flex-col sm:flex-row gap-3 mb-6 md:mb-8 animate-fade-in-up-delay">
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="px-5 py-2 md:px-6 md:py-2.5 text-sm md:text-base font-semibold text-white bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 rounded-lg transition-all shadow-lg hover:shadow-xl hover:scale-105">
                                Start Surveying
                            </a>
                        @endif
                        <a href="#features" class="px-5 py-2 md:px-6 md:py-2.5 text-sm md:text-base font-semibold text-green-600 dark:text-green-400 border-2 border-green-600 dark:border-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-all">
                            Learn More
                        </a>
                    </div>

                    <!-- Features Grid -->
                    <div id="features" class="grid grid-cols-1 md:grid-cols-3 gap-3 md:gap-4 lg:gap-6 w-full max-w-5xl animate-fade-in-up-delay-2">
                        <div class="bg-white dark:bg-zinc-800/50 p-4 md:p-5 lg:p-6 rounded-xl shadow-sm border border-green-100 dark:border-zinc-700 hover:shadow-md transition-shadow">
                            <div class="flex items-center justify-center w-9 h-9 md:w-10 md:h-10 bg-green-100 dark:bg-green-900/30 rounded-lg mb-2 md:mb-3">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-base md:text-lg font-semibold text-gray-900 dark:text-white mb-1.5 md:mb-2">Satellite Data</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Integrate NASA and Copernicus satellite imagery for comprehensive environmental monitoring
                            </p>
                        </div>

                        <div class="bg-white dark:bg-zinc-800/50 p-4 md:p-5 lg:p-6 rounded-xl shadow-sm border border-green-100 dark:border-zinc-700 hover:shadow-md transition-shadow">
                            <div class="flex items-center justify-center w-9 h-9 md:w-10 md:h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg mb-2 md:mb-3">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <h3 class="text-base md:text-lg font-semibold text-gray-900 dark:text-white mb-1.5 md:mb-2">Field Surveys</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Collect and manage ground-truth data with customizable forms and GPS tracking
                            </p>
                        </div>

                        <div class="bg-white dark:bg-zinc-800/50 p-4 md:p-5 lg:p-6 rounded-xl shadow-sm border border-green-100 dark:border-zinc-700 hover:shadow-md transition-shadow">
                            <div class="flex items-center justify-center w-9 h-9 md:w-10 md:h-10 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg mb-2 md:mb-3">
                                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                                </svg>
                            </div>
                            <h3 class="text-base md:text-lg font-semibold text-gray-900 dark:text-white mb-1.5 md:mb-2">Analytics</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Visualize trends and generate insights with powerful analytics and reporting tools
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t border-green-100 dark:border-zinc-800 py-4 md:py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col md:flex-row justify-between items-center gap-3">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        © {{ date('Y') }} EcoSurvey & Erik Lautrup-Larsen
                    </p>
                    <div class="flex items-center gap-6">
                        <a href="https://laravel.com/docs" target="_blank" class="text-sm text-gray-500 dark:text-gray-400 hover:text-green-600 dark:hover:text-green-400 transition-colors">
                            Documentation
                        </a>
                        <a href="https://github.com" target="_blank" class="text-sm text-gray-500 dark:text-gray-400 hover:text-green-600 dark:hover:text-green-400 transition-colors">
                            GitHub
                        </a>
                    </div>
                </div>
            </div>
        </footer>
    </body>
</html>

