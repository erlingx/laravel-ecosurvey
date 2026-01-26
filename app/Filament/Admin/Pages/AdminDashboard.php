<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\ApiUsageTracker;
use App\Filament\Admin\Widgets\QualityAssuranceStatsWidget;
use App\Filament\Admin\Widgets\RateLimitStatusWidget;
use App\Filament\Admin\Widgets\UsageStatsWidget;
use App\Filament\Admin\Widgets\UserContributionLeaderboard;
use Filament\Pages\Dashboard;

class AdminDashboard extends Dashboard
{
    protected static string $routePath = '';

    protected static ?string $title = 'Admin Dashboard';

    protected static ?string $navigationLabel = 'Admin Dashboard';

    protected static ?int $navigationSort = -2;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-chart-bar';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Overview';
    }

    public function getWidgets(): array
    {
        return [
            RateLimitStatusWidget::class,
            UsageStatsWidget::class,
            QualityAssuranceStatsWidget::class,
            ApiUsageTracker::class,
            UserContributionLeaderboard::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }

    public function getHeaderWidgets(): array
    {
        return [];
    }
}
