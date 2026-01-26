<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\RateLimiter;

class RateLimitStatusWidget extends Widget
{
    protected static ?string $heading = 'Rate Limit Status';

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.admin.widgets.rate-limit-status';

    public function getViewData(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [
                'tier' => 'guest',
                'maxAttempts' => 30,
                'remaining' => 30,
                'used' => 0,
                'isRateLimited' => false,
                'retryAfter' => 0,
                'percentageUsed' => 0,
            ];
        }

        $tier = $user->subscriptionTier();
        $maxAttempts = match ($tier) {
            'free' => 60,
            'pro' => 300,
            'enterprise' => 1000,
            default => 60,
        };

        $rateLimitKey = "user:{$user->id}";
        $remaining = RateLimiter::remaining($rateLimitKey, $maxAttempts);
        $used = $maxAttempts - $remaining;
        $isRateLimited = $remaining === 0;
        $retryAfter = $isRateLimited ? RateLimiter::availableIn($rateLimitKey) : 0;
        $percentageUsed = ($used / $maxAttempts) * 100;

        return [
            'tier' => $tier,
            'maxAttempts' => $maxAttempts,
            'remaining' => $remaining,
            'used' => $used,
            'isRateLimited' => $isRateLimited,
            'retryAfter' => $retryAfter,
            'percentageUsed' => $percentageUsed,
        ];
    }
}
