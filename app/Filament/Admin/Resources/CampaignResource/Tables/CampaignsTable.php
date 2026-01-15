<?php

namespace App\Filament\Admin\Resources\CampaignResource\Tables;

use App\Models\Campaign;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CampaignsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Campaign $record): string => $record->description ?? ''),

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
                    ->color('info'),

                TextColumn::make('survey_zones_count')
                    ->label('Zones')
                    ->counts('surveyZones')
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('start_date')
                    ->date('M d, Y')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('end_date')
                    ->date('M d, Y')
                    ->sortable()
                    ->toggleable(),

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
                    ->native(false),

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
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
