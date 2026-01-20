<?php

namespace App\Filament\Admin\Widgets;

use App\Models\SatelliteApiCall;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ApiUsageTracker extends StatsOverviewWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $today = today();
        $startOfMonth = now()->startOfMonth();

        // Satellite API calls today (ALL calls including overlays)
        $callsToday = SatelliteApiCall::whereDate('created_at', $today)->count();
        $callsThisMonth = SatelliteApiCall::where('created_at', '>=', $startOfMonth)->count();

        // 7-day trend for chart
        $sevenDayTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $sevenDayTrend[] = SatelliteApiCall::whereDate('created_at', $date)->count();
        }

        // Cache hit rate - count cached vs non-cached calls today
        $cachedCallsToday = SatelliteApiCall::whereDate('created_at', $today)->where('cached', true)->count();
        $nonCachedCallsToday = SatelliteApiCall::whereDate('created_at', $today)->where('cached', false)->count();
        $totalCallsToday = $cachedCallsToday + $nonCachedCallsToday;

        $cacheHitRate = $totalCallsToday > 0
            ? round(($cachedCallsToday / $totalCallsToday) * 100, 1)
            : 0;

        // Total cost (credits) this month
        $totalCostThisMonth = SatelliteApiCall::where('created_at', '>=', $startOfMonth)
            ->sum('cost_credits');

        $totalCostThisMonth = number_format($totalCostThisMonth, 2, '.', '');

        // Breakdown by call type today
        $callTypeBreakdown = SatelliteApiCall::whereDate('created_at', $today)
            ->select('call_type', DB::raw('count(*) as count'))
            ->groupBy('call_type')
            ->pluck('count', 'call_type')
            ->toArray();

        $enrichmentCalls = $callTypeBreakdown['enrichment'] ?? 0;
        $overlayCalls = $callTypeBreakdown['overlay'] ?? 0;
        $analysisCalls = $callTypeBreakdown['analysis'] ?? 0;

        return [
            Stat::make('Satellite API Calls (Today)', $callsToday)
                ->description("{$callsThisMonth} this month ({$totalCostThisMonth} credits)")
                ->descriptionIcon('heroicon-o-globe-alt')
                ->color('success')
                ->chart($sevenDayTrend),

            Stat::make('Cache Hit Rate', "{$cacheHitRate}%")
                ->description("{$cachedCallsToday} cached / {$nonCachedCallsToday} fresh today")
                ->descriptionIcon('heroicon-o-server-stack')
                ->color($cacheHitRate > 80 ? 'success' : 'warning'),

            Stat::make('Call Type Breakdown', $callsToday)
                ->description("Enrichment: {$enrichmentCalls} | Overlay: {$overlayCalls} | Analysis: {$analysisCalls}")
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color('info'),
        ];
    }
}
