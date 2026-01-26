<?php

namespace App\Filament\Admin\Resources\CampaignResource\Pages;

use App\Filament\Admin\Resources\CampaignResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;

class ListCampaigns extends ListRecords
{
    protected static string $resource = CampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        if ($this->isRateLimited()) {
            $retryAfter = $this->getRetryAfter();
            $minutes = floor($retryAfter / 60);

            return view('filament.pages.campaigns.rate-limit-warning', [
                'minutes' => $minutes,
                'retryAfter' => $retryAfter,
            ]);
        }

        return parent::getHeader();
    }

    protected function isRateLimited(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        $tier = $user->subscriptionTier();
        $maxAttempts = match ($tier) {
            'free' => 60,
            'pro' => 300,
            'enterprise' => 1000,
            default => 60,
        };

        $key = "user:{$user->id}";

        return RateLimiter::remaining($key, $maxAttempts) === 0;
    }

    protected function getRetryAfter(): int
    {
        $user = auth()->user();
        if (! $user) {
            return 0;
        }

        $key = "user:{$user->id}";

        return RateLimiter::availableIn($key);
    }
}
