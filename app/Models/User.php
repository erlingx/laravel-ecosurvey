<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Cashier\Billable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use Billable, HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Determine if the user can access the Filament panel
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return true; // All users can access admin panel
    }

    /**
     * Get the data points submitted by this user
     */
    public function dataPoints(): HasMany
    {
        return $this->hasMany(DataPoint::class);
    }

    /**
     * Get the campaigns created by this user
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    /**
     * Get the data points reviewed by this user
     */
    public function reviewedDataPoints(): HasMany
    {
        return $this->hasMany(DataPoint::class, 'reviewed_by');
    }

    /**
     * Get the user's current subscription tier
     */
    public function subscriptionTier(): string
    {
        if ($this->subscribed('default')) {
            $subscription = $this->subscription('default');

            // Get the price ID from subscription items
            $item = $subscription->items()->first();
            if ($item) {
                $priceId = $item->stripe_price;

                if ($priceId === config('subscriptions.plans.enterprise.stripe_price_id')) {
                    return 'enterprise';
                }

                if ($priceId === config('subscriptions.plans.pro.stripe_price_id')) {
                    return 'pro';
                }
            }
        }

        return 'free';
    }

    /**
     * Check if user has an active plan of the given tier
     */
    public function hasActivePlan(string $tier): bool
    {
        return $this->subscriptionTier() === $tier;
    }

    /**
     * Get the usage limit for a specific resource
     */
    public function getUsageLimit(string $resource): int
    {
        $tier = $this->subscriptionTier();
        $limits = config("subscriptions.plans.{$tier}.limits");

        return $limits[$resource] ?? 0;
    }

    /**
     * Check if user can create a data point based on current usage
     */
    public function canCreateDataPoint(): bool
    {
        $limit = $this->getUsageLimit('data_points');

        if ($limit === PHP_INT_MAX) {
            return true; // Unlimited
        }

        // TODO: Implement actual usage counting in Task 2.1
        // For now, always allow (will be restricted when usage tracking is implemented)
        return true;
    }

    /**
     * Check if user can run a satellite analysis based on current usage
     */
    public function canRunSatelliteAnalysis(): bool
    {
        $limit = $this->getUsageLimit('satellite_analyses');

        if ($limit === PHP_INT_MAX) {
            return true; // Unlimited
        }

        // TODO: Implement actual usage counting in Task 2.1
        // For now, always allow (will be restricted when usage tracking is implemented)
        return true;
    }
}
