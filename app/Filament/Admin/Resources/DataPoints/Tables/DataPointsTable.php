<?php

namespace App\Filament\Admin\Resources\DataPoints\Tables;

use App\Models\DataPoint;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class DataPointsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->sortable()
                    ->searchable(),

                TextColumn::make('campaign.name')
                    ->label('Campaign')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->weight('medium')
                    ->limit(25),

                TextColumn::make('environmentalMetric.name')
                    ->label('Metric')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->limit(20),

                TextColumn::make('value')
                    ->label('Value')
                    ->sortable()
                    ->formatStateUsing(fn (DataPoint $record): string => number_format($record->value, 2).' '.$record->environmentalMetric->unit
                    ),

                TextColumn::make('coordinates')
                    ->label('GPS')
                    ->getStateUsing(function (DataPoint $record): string {
                        $coords = DB::selectOne(
                            'SELECT ST_Y(location::geometry) as lat, ST_X(location::geometry) as lon FROM data_points WHERE id = ?',
                            [$record->id]
                        );

                        return $coords ? number_format($coords->lat, 4).', '.number_format($coords->lon, 4) : 'N/A';
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('accuracy')
                    ->label('GPS Accuracy')
                    ->sortable()
                    ->formatStateUsing(fn (?float $state): string => $state ? number_format($state, 1).'m' : 'N/A')
                    ->badge()
                    ->color(fn (?float $state): string => match (true) {
                        $state === null => 'secondary',
                        $state < 10 => 'success',
                        $state < 20 => 'warning',
                        default => 'danger',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                ImageColumn::make('photo_path')
                    ->label('Photo')
                    ->disk('uploads')
                    ->height(40)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('user.name')
                    ->label('Submitted By')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(20),

                TextColumn::make('qa_flags')
                    ->label('QA Flags')
                    ->badge()
                    ->formatStateUsing(function ($state, DataPoint $record): string {
                        // Get the properly cast value from the model
                        $flags = $record->qa_flags;

                        if (empty($flags)) {
                            return 'Clean';
                        }

                        $count = count($flags);

                        return $count === 1 ? '1 issue' : "{$count} issues";
                    })
                    ->color(function ($state, DataPoint $record): string {
                        $flags = $record->qa_flags;

                        return empty($flags) ? 'success' : 'warning';
                    })
                    ->tooltip(function ($state, DataPoint $record): ?string {
                        $flags = $record->qa_flags;

                        if (empty($flags)) {
                            return null;
                        }

                        return implode("\n", array_map(
                            fn ($flag) => $flag['reason'] ?? 'Unknown issue',
                            $flags
                        ));
                    })
                    ->toggleable(),

                TextColumn::make('collected_at')
                    ->label('Collected')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'pending' => 'Pending Review',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->native(false)
                    ->multiple()
                    ->indicator('Status'),

                SelectFilter::make('campaign')
                    ->relationship('campaign', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->indicator('Campaign'),

                SelectFilter::make('metric')
                    ->relationship('environmentalMetric', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->indicator('Metric'),

                SelectFilter::make('accuracy')
                    ->label('GPS Accuracy')
                    ->options([
                        'excellent' => 'Excellent (<10m)',
                        'good' => 'Good (10-20m)',
                        'poor' => 'Poor (>20m)',
                    ])
                    ->query(function ($query, $data) {
                        if (! $data['value']) {
                            return $query;
                        }

                        return match ($data['value']) {
                            'excellent' => $query->where('accuracy', '<', 10),
                            'good' => $query->whereBetween('accuracy', [10, 20]),
                            'poor' => $query->where('accuracy', '>', 20),
                            default => $query,
                        };
                    })
                    ->native(false)
                    ->indicator('Accuracy'),

                SelectFilter::make('flagged')
                    ->label('QA Status')
                    ->options([
                        'clean' => 'Clean (No Issues)',
                        'flagged' => 'Flagged (Has Issues)',
                    ])
                    ->query(function ($query, $data) {
                        if (! $data['value']) {
                            return $query;
                        }

                        return match ($data['value']) {
                            'clean' => $query->whereNull('qa_flags'),
                            'flagged' => $query->whereNotNull('qa_flags'),
                            default => $query,
                        };
                    })
                    ->native(false)
                    ->indicator('QA Status'),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('approve')
                        ->label('Approve')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn (DataPoint $record) => $record->update(['status' => 'approved']))
                        ->visible(fn (DataPoint $record): bool => $record->status !== 'approved'),

                    Action::make('reject')
                        ->label('Reject')
                        ->icon(Heroicon::OutlinedXCircle)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn (DataPoint $record) => $record->update(['status' => 'rejected']))
                        ->visible(fn (DataPoint $record): bool => $record->status !== 'rejected'),

                    EditAction::make(),
                ])
                    ->label('Actions')
                    ->icon(Heroicon::OutlinedEllipsisVertical)
                    ->color('primary')
                    ->button(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    Action::make('bulk_approve')
                        ->label('Approve Selected')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Approve Data Points')
                        ->modalDescription('Are you sure you want to approve the selected data points?')
                        ->modalSubmitActionLabel('Yes, approve them')
                        ->accessSelectedRecords()
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each(fn (DataPoint $record) => $record->update([
                                'status' => 'approved',
                                'reviewed_at' => now(),
                                'reviewed_by' => auth()->id(),
                            ]));
                        })
                        ->successNotificationTitle(fn ($records) => 'Success!')
                        ->successNotification(function ($records) {
                            $count = $records->count();

                            return Notification::make()
                                ->success()
                                ->title('Data points approved!')
                                ->body("{$count} data point".($count !== 1 ? 's have' : ' has').' been approved.');
                        }),

                    Action::make('bulk_reject')
                        ->label('Reject Selected')
                        ->icon(Heroicon::OutlinedXCircle)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Reject Data Points')
                        ->modalDescription('Are you sure you want to reject the selected data points?')
                        ->modalSubmitActionLabel('Yes, reject them')
                        ->accessSelectedRecords()
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each(fn (DataPoint $record) => $record->update([
                                'status' => 'rejected',
                                'reviewed_at' => now(),
                                'reviewed_by' => auth()->id(),
                            ]));
                        })
                        ->successNotificationTitle(fn ($records) => 'Data points rejected!')
                        ->successNotification(function ($records) {
                            $count = $records->count();

                            return Notification::make()
                                ->danger()
                                ->title('Data points rejected!')
                                ->body("{$count} data point".($count !== 1 ? 's have' : ' has').' been rejected.');
                        }),

                    Action::make('bulk_clear_flags')
                        ->label('Clear QA Flags')
                        ->icon(Heroicon::OutlinedShieldCheck)
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Clear Quality Flags')
                        ->modalDescription('Are you sure you want to clear QA flags from the selected data points?')
                        ->modalSubmitActionLabel('Yes, clear flags')
                        ->accessSelectedRecords()
                        ->action(function ($records) {
                            $records->each(fn (DataPoint $record) => $record->update(['qa_flags' => null]));
                        })
                        ->successNotification(function ($records) {
                            $count = $records->count();

                            return Notification::make()
                                ->success()
                                ->title('QA flags cleared!')
                                ->body("Cleared flags from {$count} data point".($count !== 1 ? 's' : '').'.');
                        }),

                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->filtersTriggerAction(
                fn (Action $action) => $action->modalCloseButton()
            )
            ->columnManagerTriggerAction(
                fn (Action $action) => $action->modalCloseButton()
            )
            ->defaultSort('created_at', 'desc');
    }
}
