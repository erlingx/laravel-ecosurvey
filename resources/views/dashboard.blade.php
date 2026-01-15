<x-layouts.app :title="__('EcoSurvey Dashboard')">
    @php
        $userId = Auth::id();
        $stats = [
            'campaigns' => [
                'total' => \App\Models\Campaign::where('user_id', $userId)->count(),
                'active' => \App\Models\Campaign::where('user_id', $userId)->where('status', 'active')->count(),
            ],
            'data_points' => [
                'total' => \App\Models\DataPoint::whereHas('campaign', fn($q) => $q->where('user_id', $userId))->count(),
                'approved' => \App\Models\DataPoint::whereHas('campaign', fn($q) => $q->where('user_id', $userId))->where('status', 'approved')->count(),
            ],
            'recent_campaigns' => \App\Models\Campaign::where('user_id', $userId)
                ->withCount('dataPoints')
                ->latest()
                ->take(3)
                ->get(),
        ];
    @endphp

    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <!-- Stats Grid -->
        <div class="grid gap-4 md:grid-cols-3">
            <!-- Total Campaigns -->
            <x-card>
                <div class="flex items-center justify-between">
                    <div>
                        <flux:subheading>{{ __('Total Campaigns') }}</flux:subheading>
                        <flux:heading size="xl">{{ $stats['campaigns']['total'] }}</flux:heading>
                    </div>
                    <svg class="size-12 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                    </svg>
                </div>
                @if($stats['campaigns']['active'] > 0)
                    <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                        {{ $stats['campaigns']['active'] }} active
                    </div>
                @endif
            </x-card>

            <!-- Total Data Points -->
            <x-card>
                <div class="flex items-center justify-between">
                    <div>
                        <flux:subheading>{{ __('Data Points') }}</flux:subheading>
                        <flux:heading size="xl">{{ $stats['data_points']['total'] }}</flux:heading>
                    </div>
                    <svg class="size-12 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                @if($stats['data_points']['approved'] > 0)
                    <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                        {{ $stats['data_points']['approved'] }} approved
                    </div>
                @endif
            </x-card>

            <!-- Quick Actions -->
            <x-card>
                <flux:subheading class="mb-3">{{ __('Quick Actions') }}</flux:subheading>
                <div class="space-y-2">
                    <flux:button href="{{ route('data-points.submit') }}" variant="ghost" icon="plus" class="w-full justify-start">
                        {{ __('Submit Reading') }}
                    </flux:button>
                    <flux:button :href="route('campaigns.index')" variant="ghost" icon="folder" class="w-full justify-start">
                        {{ __('View Campaigns') }}
                    </flux:button>
                    <flux:button :href="route('maps.satellite')" variant="ghost" icon="globe-alt" class="w-full justify-start">
                        {{ __('View Satellite Data') }}
                    </flux:button>
                </div>
            </x-card>
        </div>

        <!-- Recent Campaigns -->
        @if($stats['recent_campaigns']->isNotEmpty())
            <div>
                <div class="flex items-center justify-between mb-4">
                    <flux:heading size="lg">{{ __('Recent Campaigns') }}</flux:heading>
                    <flux:button :href="route('campaigns.index')" variant="ghost" size="sm">
                        {{ __('View All') }}
                    </flux:button>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    @foreach($stats['recent_campaigns'] as $campaign)
                        <x-card>
                            <div class="space-y-2">
                                <div class="flex items-start justify-between">
                                    <flux:heading size="lg" class="flex-1">{{ $campaign->name }}</flux:heading>
                                    <flux:badge :color="match($campaign->status) {
                                        'active' => 'green',
                                        'completed' => 'yellow',
                                        'archived' => 'red',
                                        default => 'zinc'
                                    }" size="sm">
                                        {{ ucfirst($campaign->status) }}
                                    </flux:badge>
                                </div>

                                <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $campaign->data_points_count }} data points
                                </div>

                                <div class="flex gap-2 pt-2 border-t border-zinc-200 dark:border-zinc-700">
                                    <flux:button :href="route('campaigns.index')" size="sm" variant="ghost">
                                        {{ __('View All') }}
                                    </flux:button>
                                    <flux:button :href="route('campaigns.zones.manage', $campaign)" size="sm" variant="ghost">
                                        {{ __('Zones') }}
                                    </flux:button>
                                </div>
                            </div>
                        </x-card>
                    @endforeach
                </div>
            </div>
        @else
            <x-card class="text-center">
                <div class="flex flex-col items-center gap-4">
                    <svg class="size-16 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                    </svg>
                    <div>
                        <flux:heading size="lg">{{ __('No campaigns yet') }}</flux:heading>
                        <flux:subheading>{{ __('Create your first campaign to start collecting environmental data') }}</flux:subheading>
                    </div>
                    <flux:button :href="route('campaigns.index')" variant="primary" icon="plus">
                        {{ __('Go to Campaigns') }}
                    </flux:button>
                </div>
            </x-card>
        @endif
    </div>
</x-layouts.app>

