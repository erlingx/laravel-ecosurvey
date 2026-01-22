<x-filament-panels::page>
    {{-- Welcome Banner --}}
    <div class="mb-6 rounded-lg bg-gradient-to-r from-emerald-500 to-teal-600 p-6 text-white shadow-lg">
        <div class="flex items-center gap-4">
            <div class="rounded-full bg-white/20 p-3">
                <svg class="h-8 w-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" />
                </svg>
            </div>
            <div>
                <h2 class="text-2xl font-bold">Welcome, {{ auth()->user()->name }}!</h2>
                <p class="text-white/80">Platform Overview & Analytics</p>
            </div>
        </div>
    </div>
</x-filament-panels::page>
