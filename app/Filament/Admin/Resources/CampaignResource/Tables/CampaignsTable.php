<?php

namespace App\Filament\Admin\Resources\CampaignResource\Tables;

use App\Models\Campaign;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\RateLimiter;

class CampaignsTable
{
    /**
     * Check if current user is rate limited
     */
    protected static function isRateLimited(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        $tier = $user->subscriptionTier();
        $maxAttempts = match ($tier) {
            'free' => 60,
            'pro' => 300,
            'enterprise' => 1000,
            default => 60,
        };

        $key = "user:{$user->id}";

        return RateLimiter::remaining($key, $maxAttempts) === 0;
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Campaign $record): string => str($record->description ?? '')->limit(50))
                    ->wrap(),

                BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'success' => 'active',
                        'warning' => 'completed',
                        'danger' => 'archived',
                    ])
                    ->sortable(),

                TextColumn::make('data_points_count')
                    ->label('Data Points')
                    ->counts('dataPoints')
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->toggleable(),

                TextColumn::make('survey_zones_count')
                    ->label('Zones')
                    ->counts('surveyZones')
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->toggleable(),

                TextColumn::make('start_date')
                    ->date('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('end_date')
                    ->date('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'archived' => 'Archived',
                    ])
                    ->native(false)
                    ->searchable()
                    ->multiple()
                    ->indicator('Status'),

                Filter::make('has_data')
                    ->label('Has Data Points')
                    ->query(fn ($query) => $query->has('dataPoints')),

                Filter::make('has_zones')
                    ->label('Has Survey Zones')
                    ->query(fn ($query) => $query->has('surveyZones')),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('manage_zones')
                    ->label('Manage Zones')
                    ->icon(Heroicon::OutlinedMapPin)
                    ->url(fn (Campaign $record): string => route('campaigns.zones.manage', $record))
                    ->openUrlInNewTab(),
                ActionGroup::make([
                    Action::make('export_pdf')
                        ->label('Export as PDF')
                        ->icon(Heroicon::OutlinedDocumentText)
                        ->url(fn (Campaign $record): string => route('campaigns.export.pdf', $record))
                        ->openUrlInNewTab()
                        ->color('success')
                        ->disabled(fn (): bool => static::isRateLimited())
                        ->tooltip(fn (): ?string => static::isRateLimited() ? 'Rate limit exceeded. Please wait before exporting.' : null),
                    Action::make('export_json')
                        ->label('Export as JSON')
                        ->icon(Heroicon::OutlinedCodeBracket)
                        ->url(fn (Campaign $record): string => route('campaigns.export.json', $record))
                        ->openUrlInNewTab()
                        ->color('info')
                        ->disabled(fn (): bool => static::isRateLimited())
                        ->tooltip(fn (): ?string => static::isRateLimited() ? 'Rate limit exceeded. Please wait before exporting.' : null),
                    Action::make('export_csv')
                        ->label('Export as CSV')
                        ->icon(Heroicon::OutlinedTableCells)
                        ->url(fn (Campaign $record): string => route('campaigns.export.csv', $record))
                        ->openUrlInNewTab()
                        ->color('warning')
                        ->disabled(fn (): bool => static::isRateLimited())
                        ->tooltip(fn (): ?string => static::isRateLimited() ? 'Rate limit exceeded. Please wait before exporting.' : null),
                ])
                    ->label('Export')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color(fn (): string => static::isRateLimited() ? 'gray' : 'primary')
                    ->button(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->filtersTriggerAction(
                fn (Action $action) => $action
                    ->modalCloseButton()
            )
            ->columnManagerTriggerAction(
                fn (Action $action) => $action
                    ->modalCloseButton()
            )
            ->defaultSort('created_at', 'desc');
    }
}
