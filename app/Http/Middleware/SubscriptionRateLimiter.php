<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionRateLimiter
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            // Guest users get the lowest limit
            return $this->handleRateLimit($request, $next, 'guest', 30);
        }

        $tier = $user->subscriptionTier();
        $limit = $this->getLimitForTier($tier);

        return $this->handleRateLimit($request, $next, "user:{$user->id}", $limit);
    }

    /**
     * Get rate limit for subscription tier
     */
    protected function getLimitForTier(string $tier): int
    {
        return match ($tier) {
            'free' => 60,           // 60 requests per hour
            'pro' => 300,           // 300 requests per hour
            'enterprise' => 1000,   // 1000 requests per hour
            default => 60,
        };
    }

    /**
     * Handle the rate limiting logic
     */
    protected function handleRateLimit(Request $request, Closure $next, string $key, int $maxAttempts): Response
    {
        $executed = RateLimiter::attempt(
            $key,
            $maxAttempts,
            function () use ($next, $request) {
                return $next($request);
            },
            60 // Decay rate in seconds (1 hour)
        );

        if (! $executed) {
            return response()->json([
                'message' => 'Too many requests. Please slow down.',
                'retry_after' => RateLimiter::availableIn($key),
            ], 429);
        }

        return $executed;
    }
}
