<div class="space-y-4">
    <div class="flex items-center justify-between">
        <flux:heading size="lg">{{ __('My Campaigns') }}</flux:heading>
        <flux:button href="/admin/campaigns/create" icon="plus">
            {{ __('Create Campaign') }}
        </flux:button>
    </div>

    @if($this->campaigns->isEmpty())
        <x-card class="text-center">
            <div class="flex flex-col items-center gap-4">
                <svg class="size-16 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                </svg>
                <div>
                    <flux:heading size="lg">{{ __('No campaigns yet') }}</flux:heading>
                    <flux:subheading>{{ __('Create your first campaign to start collecting data') }}</flux:subheading>
                </div>
                <flux:button href="/admin/campaigns/create" variant="primary" icon="plus">
                    {{ __('Create Campaign') }}
                </flux:button>
            </div>
        </x-card>
    @else
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach($this->campaigns as $campaign)
                <x-card>
                    <div class="space-y-3">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <flux:heading size="lg">{{ $campaign->name }}</flux:heading>
                                @if($campaign->description)
                                    <flux:subheading class="line-clamp-2">{{ $campaign->description }}</flux:subheading>
                                @endif
                            </div>
                            <flux:badge :color="match($campaign->status) {
                                'active' => 'green',
                                'completed' => 'yellow',
                                'archived' => 'red',
                                default => 'zinc'
                            }">
                                {{ ucfirst($campaign->status) }}
                            </flux:badge>
                        </div>

                        <div class="flex gap-4 text-sm text-zinc-600 dark:text-zinc-400">
                            <div class="flex items-center gap-1">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span>{{ $campaign->data_points_count }} points</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                </svg>
                                <span>{{ $campaign->survey_zones_count }} zones</span>
                            </div>
                        </div>

                        @if($campaign->start_date || $campaign->end_date)
                            <div class="text-xs text-zinc-500">
                                @if($campaign->start_date && $campaign->end_date)
                                    {{ $campaign->start_date->format('M d, Y') }} - {{ $campaign->end_date->format('M d, Y') }}
                                @elseif($campaign->start_date)
                                    Started {{ $campaign->start_date->format('M d, Y') }}
                                @elseif($campaign->end_date)
                                    Ends {{ $campaign->end_date->format('M d, Y') }}
                                @endif
                            </div>
                        @endif

                        <div class="flex gap-2 pt-2 border-t border-zinc-200 dark:border-zinc-700">
                            <flux:button href="/admin/campaigns/{{ $campaign->id }}/edit" size="sm" variant="ghost" icon="pencil">
                                {{ __('Edit') }}
                            </flux:button>
                            <flux:button href="{{ route('campaigns.zones.manage', $campaign) }}" size="sm" variant="ghost" icon="map">
                                {{ __('Zones') }}
                            </flux:button>
                        </div>
                    </div>
                </x-card>
            @endforeach
        </div>
    @endif
</div>

