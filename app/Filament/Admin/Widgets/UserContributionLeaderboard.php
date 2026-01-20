<?php

namespace App\Filament\Admin\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class UserContributionLeaderboard extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $thirtyDaysAgo = now()->subDays(30);

        $topContributors = User::query()
            ->select([
                'users.id',
                'users.name',
                DB::raw('COUNT(data_points.id) as total_submissions'),
                DB::raw('SUM(CASE WHEN data_points.status = \'approved\' THEN 1 ELSE 0 END) as approved_count'),
                DB::raw('AVG(data_points.accuracy) as avg_accuracy'),
            ])
            ->join('data_points', 'users.id', '=', 'data_points.user_id')
            ->where('data_points.created_at', '>=', $thirtyDaysAgo)
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_submissions')
            ->limit(5)
            ->get();

        if ($topContributors->isEmpty()) {
            return [
                Stat::make('No Data', 'No user contributions in the last 30 days')
                    ->description('Start collecting data to see leaderboard')
                    ->descriptionIcon('heroicon-o-information-circle')
                    ->color('gray'),
            ];
        }

        $medals = ['🥇', '🥈', '🥉'];
        $colors = ['warning', 'gray', 'danger', 'primary', 'primary'];

        return $topContributors->map(function ($user, $index) use ($medals, $colors) {
            $approvalRate = $user->total_submissions > 0
                ? round(($user->approved_count / $user->total_submissions) * 100, 1)
                : 0;

            $avgAccuracy = $user->avg_accuracy ? round($user->avg_accuracy, 2) : 0;

            $rank = $index < 3 ? $medals[$index] : '#'.($index + 1);

            return Stat::make(
                "{$rank} {$user->name}",
                "{$user->total_submissions} submissions"
            )
                ->description("{$approvalRate}% approved | {$avgAccuracy}m avg accuracy")
                ->descriptionIcon('heroicon-o-check-badge')
                ->color($colors[$index]);
        })->toArray();
    }
}
