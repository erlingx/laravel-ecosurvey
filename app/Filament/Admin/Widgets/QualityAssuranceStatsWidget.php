<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Campaign;
use App\Models\DataPoint;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class QualityAssuranceStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalPoints = DataPoint::count();
        $pendingPoints = DataPoint::where('status', 'pending')->count();
        $approvedPoints = DataPoint::where('status', 'approved')->count();
        $rejectedPoints = DataPoint::where('status', 'rejected')->count();

        $approvalRate = $totalPoints > 0
            ? round(($approvedPoints / $totalPoints) * 100, 1)
            : 0;

        $activeCampaigns = Campaign::where('status', 'active')->count();
        $totalUsers = User::count();

        return [
            Stat::make('Pending Review', $pendingPoints)
                ->description('Data points awaiting approval')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning')
                ->chart($this->getPendingTrend()),

            Stat::make('Approved', $approvedPoints)
                ->description("{$approvalRate}% approval rate")
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Rejected', $rejectedPoints)
                ->description('Quality control rejections')
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('danger'),

            Stat::make('Active Campaigns', $activeCampaigns)
                ->description('Currently collecting data')
                ->descriptionIcon('heroicon-o-map')
                ->color('info'),

            Stat::make('Total Data Points', $totalPoints)
                ->description('All submitted measurements')
                ->descriptionIcon('heroicon-o-clipboard-document-check')
                ->color('primary'),

            Stat::make('Active Users', $totalUsers)
                ->description('Registered contributors')
                ->descriptionIcon('heroicon-o-users')
                ->color('success'),
        ];
    }

    protected function getPendingTrend(): array
    {
        // Last 7 days of pending queue size (snapshot at end of each day)
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->endOfDay();

            // Count data points that were pending at this point in time:
            // 1. Created before or on this date
            // 2. Either still pending, OR were reviewed AFTER this date
            $count = DataPoint::where(function ($query) use ($date) {
                $query->where('status', 'pending')
                    ->where('created_at', '<=', $date);
            })
                ->orWhere(function ($query) use ($date) {
                    // Items that are now approved/rejected but weren't reviewed yet at this date
                    $query->whereIn('status', ['approved', 'rejected'])
                        ->where('created_at', '<=', $date)
                        ->where(function ($q) use ($date) {
                            $q->whereNull('reviewed_at')
                                ->orWhere('reviewed_at', '>', $date);
                        });
                })
                ->count();

            $data[] = $count;
        }

        return $data;
    }
}
