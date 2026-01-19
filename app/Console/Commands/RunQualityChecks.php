<?php

namespace App\Console\Commands;

use App\Services\QualityCheckService;
use Illuminate\Console\Command;

class RunQualityChecks extends Command
{
    protected $signature = 'ecosurvey:quality-check
                            {--auto-approve : Auto-approve qualified data points}
                            {--flag-suspicious : Flag suspicious readings}';

    protected $description = 'Run automated quality checks on data points';

    public function handle(QualityCheckService $service): int
    {
        $this->info('Running quality checks...');

        if ($this->option('flag-suspicious')) {
            $flagged = $service->flagSuspiciousReadings();
            $this->info("✓ Flagged {$flagged} suspicious readings for review");
        }

        if ($this->option('auto-approve')) {
            $approved = $service->autoApproveQualified();
            $this->info("✓ Auto-approved {$approved} high-quality data points");
        }

        if (! $this->option('flag-suspicious') && ! $this->option('auto-approve')) {
            $this->warn('No action specified. Use --flag-suspicious or --auto-approve');
            $this->info('Run with --help for more information');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Quality check completed successfully!');

        return self::SUCCESS;
    }
}
