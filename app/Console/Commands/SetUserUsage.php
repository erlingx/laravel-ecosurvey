<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\UsageTrackingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SetUserUsage extends Command
{
    protected $signature = 'usage:set {user_id} {resource=data_points} {count=50}';

    protected $description = 'Set usage count for a user (for testing limits)';

    public function handle(UsageTrackingService $service): int
    {
        $userId = (int) $this->argument('user_id');
        $resource = $this->argument('resource');
        $count = (int) $this->argument('count');

        $user = User::find($userId);

        if (! $user) {
            $this->error("User with ID {$userId} not found.");

            return self::FAILURE;
        }

        $validResources = ['data_points', 'satellite_analyses', 'report_exports'];
        if (! in_array($resource, $validResources)) {
            $this->error('Invalid resource. Must be one of: '.implode(', ', $validResources));

            return self::FAILURE;
        }

        $cycleStart = $service->getBillingCycleStart($user);
        $cycleEnd = $service->getBillingCycleEnd($user);

        DB::table('usage_meters')->updateOrInsert(
            [
                'user_id' => $user->id,
                'resource' => $resource,
                'billing_cycle_start' => $cycleStart->toDateString(),
            ],
            [
                'billing_cycle_end' => $cycleEnd->toDateString(),
                'count' => $count,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->info("✅ Usage set to {$count} {$resource} for user: {$user->email}");
        $this->info("   Billing cycle: {$cycleStart->format('M d, Y')} - {$cycleEnd->format('M d, Y')}");

        return self::SUCCESS;
    }
}
