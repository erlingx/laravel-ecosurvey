<?php

namespace App\Filament\Admin\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class UsageStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        // Get subscription counts
        $totalUsers = User::count();
        $freeUsers = User::whereDoesntHave('subscriptions')->count();
        $proUsers = User::whereHas('subscriptions', function ($query) {
            $query->where('stripe_price', config('subscriptions.plans.pro.stripe_price_id'))
                ->where('stripe_status', 'active');
        })->count();
        $enterpriseUsers = User::whereHas('subscriptions', function ($query) {
            $query->where('stripe_price', config('subscriptions.plans.enterprise.stripe_price_id'))
                ->where('stripe_status', 'active');
        })->count();

        // Calculate MRR (Monthly Recurring Revenue)
        $proMRR = $proUsers * config('subscriptions.plans.pro.price', 29);
        $enterpriseMRR = $enterpriseUsers * config('subscriptions.plans.enterprise.price', 99);
        $totalMRR = $proMRR + $enterpriseMRR;

        // Get usage stats for current month
        $currentMonth = now()->format('Y-m');
        $usageStats = DB::table('usage_meters')
            ->where('billing_cycle_start', '>=', now()->startOfMonth())
            ->select('resource', DB::raw('SUM(count) as total'))
            ->groupBy('resource')
            ->get()
            ->pluck('total', 'resource');

        $totalDataPoints = $usageStats['data_points'] ?? 0;
        $totalSatelliteAnalyses = $usageStats['satellite_analyses'] ?? 0;
        $totalReportExports = $usageStats['report_exports'] ?? 0;

        // Get previous month usage for trend calculation
        $previousMonth = now()->subMonth()->format('Y-m');
        $previousUsageStats = DB::table('usage_meters')
            ->where('billing_cycle_start', '>=', now()->subMonth()->startOfMonth())
            ->where('billing_cycle_start', '<', now()->startOfMonth())
            ->select('resource', DB::raw('SUM(count) as total'))
            ->groupBy('resource')
            ->get()
            ->pluck('total', 'resource');

        $previousDataPoints = $previousUsageStats['data_points'] ?? 1;
        $previousSatelliteAnalyses = $previousUsageStats['satellite_analyses'] ?? 1;
        $previousExports = $previousUsageStats['report_exports'] ?? 1;

        // Calculate percentage changes
        $dataPointsChange = $previousDataPoints > 0
            ? round((($totalDataPoints - $previousDataPoints) / $previousDataPoints) * 100, 1)
            : 0;
        $satelliteChange = $previousSatelliteAnalyses > 0
            ? round((($totalSatelliteAnalyses - $previousSatelliteAnalyses) / $previousSatelliteAnalyses) * 100, 1)
            : 0;
        $exportsChange = $previousExports > 0
            ? round((($totalReportExports - $previousExports) / $previousExports) * 100, 1)
            : 0;

        return [
            // Revenue Stats
            Stat::make('Monthly Recurring Revenue', '$'.number_format($totalMRR, 0))
                ->description($proUsers.' Pro + '.$enterpriseUsers.' Enterprise subscribers')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success')
                ->chart([
                    $proMRR * 0.7,
                    $proMRR * 0.85,
                    $proMRR * 0.9,
                    $totalMRR * 0.95,
                    $totalMRR,
                ]),

            // User Stats
            Stat::make('Total Users', number_format($totalUsers))
                ->description($freeUsers.' Free • '.$proUsers.' Pro • '.$enterpriseUsers.' Enterprise')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary')
                ->chart([
                    $totalUsers * 0.6,
                    $totalUsers * 0.75,
                    $totalUsers * 0.85,
                    $totalUsers * 0.95,
                    $totalUsers,
                ]),

            // Data Points Usage
            Stat::make('Data Points (This Month)', number_format($totalDataPoints))
                ->description($dataPointsChange >= 0 ? '+'.$dataPointsChange.'% from last month' : $dataPointsChange.'% from last month')
                ->descriptionIcon($dataPointsChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($dataPointsChange >= 0 ? 'success' : 'danger')
                ->chart([
                    $previousDataPoints * 0.7,
                    $previousDataPoints * 0.85,
                    $previousDataPoints,
                    $totalDataPoints * 0.9,
                    $totalDataPoints,
                ]),

            // Satellite Analyses Usage
            Stat::make('Satellite Analyses', number_format($totalSatelliteAnalyses))
                ->description($satelliteChange >= 0 ? '+'.$satelliteChange.'% from last month' : $satelliteChange.'% from last month')
                ->descriptionIcon($satelliteChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($satelliteChange >= 0 ? 'success' : 'danger')
                ->chart([
                    $previousSatelliteAnalyses * 0.7,
                    $previousSatelliteAnalyses * 0.85,
                    $previousSatelliteAnalyses,
                    $totalSatelliteAnalyses * 0.9,
                    $totalSatelliteAnalyses,
                ]),

            // Report Exports
            Stat::make('Report Exports', number_format($totalReportExports))
                ->description($exportsChange >= 0 ? '+'.$exportsChange.'% from last month' : $exportsChange.'% from last month')
                ->descriptionIcon($exportsChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($exportsChange >= 0 ? 'success' : 'danger')
                ->chart([
                    $previousExports * 0.7,
                    $previousExports * 0.85,
                    $previousExports,
                    $totalReportExports * 0.9,
                    $totalReportExports,
                ]),

            // Average Usage Per User
            Stat::make('Avg Data Points/User', $totalUsers > 0 ? number_format($totalDataPoints / $totalUsers, 1) : '0')
                ->description('Average monthly usage per user')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),
        ];
    }
}
