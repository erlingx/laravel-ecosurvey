<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\ApiUsageTracker;
use App\Filament\Admin\Widgets\QualityAssuranceStatsWidget;
use App\Filament\Admin\Widgets\UserContributionLeaderboard;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class QualityDashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected string $view = 'filament.admin.pages.quality-dashboard';

    protected static string|UnitEnum|null $navigationGroup = 'Data Quality';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Quality Dashboard';

    protected static ?string $navigationLabel = 'Quality Dashboard';

    public function getHeading(): string
    {
        return 'Quality Assurance Dashboard';
    }

    public function getSubheading(): ?string
    {
        return 'Monitor data quality metrics, user contributions, and API usage in real-time.';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            QualityAssuranceStatsWidget::class,
            UserContributionLeaderboard::class,
            ApiUsageTracker::class,
        ];
    }
}
