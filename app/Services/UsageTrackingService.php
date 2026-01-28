<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UsageTrackingService
{
    public function recordDataPointCreation(User $user): bool
    {
        return $this->recordUsage($user, 'data_points');
    }

    public function recordSatelliteAnalysis(User $user, string $index): bool
    {
        return $this->recordUsage($user, 'satellite_analyses');
    }

    public function recordReportExport(User $user, string $format): bool
    {
        return $this->recordUsage($user, 'report_exports');
    }

    public function getCurrentUsage(User $user): array
    {
        $cycleStart = $this->getBillingCycleStart($user);
        $cycleEnd = $this->getBillingCycleEnd($user);

        return [
            'data_points' => $this->getResourceUsage($user, 'data_points', $cycleStart, $cycleEnd),
            'satellite_analyses' => $this->getResourceUsage($user, 'satellite_analyses', $cycleStart, $cycleEnd),
            'report_exports' => $this->getResourceUsage($user, 'report_exports', $cycleStart, $cycleEnd),
            'cycle_start' => $cycleStart,
            'cycle_end' => $cycleEnd,
        ];
    }

    public function getRemainingQuota(User $user, string $resource): int
    {
        $limit = $user->getUsageLimit($resource);
        $used = $this->getResourceUsage($user, $resource);
        if ($limit === PHP_INT_MAX) {
            return PHP_INT_MAX;
        }

        return max(0, $limit - $used);
    }

    public function canPerformAction(User $user, string $resource): bool
    {
        $limit = $user->getUsageLimit($resource);
        if ($limit === PHP_INT_MAX) {
            return true;
        }
        $used = $this->getResourceUsage($user, $resource);

        return $used < $limit;
    }

    public function getBillingCycleStart(User $user): Carbon
    {
        if ($user->subscribed('default')) {
            $subscription = $user->subscription('default');
            $subStart = Carbon::parse($subscription->created_at);
            $now = Carbon::now();
            $cycleDay = $subStart->day;
            if ($now->day >= $cycleDay) {
                return Carbon::create($now->year, $now->month, $cycleDay)->startOfDay();
            } else {
                return Carbon::create($now->year, $now->month, $cycleDay)->subMonth()->startOfDay();
            }
        }

        return Carbon::now()->startOfMonth();
    }

    public function getBillingCycleEnd(User $user): Carbon
    {
        if ($user->subscribed('default')) {
            $cycleStart = $this->getBillingCycleStart($user);

            return $cycleStart->copy()->addMonth()->subSecond();
        }

        return Carbon::now()->endOfMonth();
    }

    protected function recordUsage(User $user, string $resource): bool
    {
        $cycleStart = $this->getBillingCycleStart($user);
        $cycleEnd = $this->getBillingCycleEnd($user);

        DB::transaction(function () use ($user, $resource, $cycleStart, $cycleEnd) {
            $existing = DB::table('usage_meters')
                ->where('user_id', $user->id)
                ->where('resource', $resource)
                ->where('billing_cycle_start', $cycleStart->toDateString())
                ->first();

            if ($existing) {
                DB::table('usage_meters')
                    ->where('id', $existing->id)
                    ->update([
                        'count' => DB::raw('count + 1'),
                        'billing_cycle_end' => $cycleEnd->toDateString(),
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('usage_meters')->insert([
                    'user_id' => $user->id,
                    'resource' => $resource,
                    'billing_cycle_start' => $cycleStart->toDateString(),
                    'billing_cycle_end' => $cycleEnd->toDateString(),
                    'count' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        $this->clearUsageCache($user, $resource, $cycleStart);

        return true;
    }

    protected function getResourceUsage(
        User $user,
        string $resource,
        ?Carbon $cycleStart = null,
        ?Carbon $cycleEnd = null
    ): int {
        $cycleStart = $cycleStart ?? $this->getBillingCycleStart($user);
        $cycleEnd = $cycleEnd ?? $this->getBillingCycleEnd($user);
        $cacheKey = $this->getUsageCacheKey($user, $resource, $cycleStart);

        return Cache::remember($cacheKey, now()->addHour(), function () use ($user, $resource, $cycleStart) {
            $record = DB::table('usage_meters')
                ->where('user_id', $user->id)
                ->where('resource', $resource)
                ->where('billing_cycle_start', $cycleStart->toDateString())
                ->first();

            return $record ? $record->count : 0;
        });
    }

    protected function getUsageCacheKey(User $user, string $resource, Carbon $cycleStart): string
    {
        return "usage:{$user->id}:{$resource}:{$cycleStart->toDateString()}";
    }

    protected function clearUsageCache(User $user, string $resource, Carbon $cycleStart): void
    {
        $cacheKey = $this->getUsageCacheKey($user, $resource, $cycleStart);
        Cache::forget($cacheKey);
    }

    public function resetUsage(User $user, ?string $resource = null): void
    {
        $cycleStart = $this->getBillingCycleStart($user);
        $query = DB::table('usage_meters')
            ->where('user_id', $user->id)
            ->where('billing_cycle_start', $cycleStart->toDateString());
        if ($resource) {
            $query->where('resource', $resource);
        }
        $query->delete();
        $resources = $resource ? [$resource] : ['data_points', 'satellite_analyses', 'report_exports'];
        foreach ($resources as $res) {
            $this->clearUsageCache($user, $res, $cycleStart);
        }
    }
}
