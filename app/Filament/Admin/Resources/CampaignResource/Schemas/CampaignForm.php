<?php

namespace App\Filament\Admin\Resources\CampaignResource\Schemas;

use App\Models\Campaign;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull()
                    ->placeholder('e.g., Urban Air Quality Study 2026'),

                Textarea::make('description')
                    ->rows(3)
                    ->maxLength(1000)
                    ->columnSpanFull()
                    ->placeholder('Describe the campaign objectives and scope'),

                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'archived' => 'Archived',
                    ])
                    ->default('draft')
                    ->required()
                    ->native(false),

                DatePicker::make('start_date')
                    ->native(false)
                    ->displayFormat('Y-m-d')
                    ->placeholder('Select start date'),

                DatePicker::make('end_date')
                    ->native(false)
                    ->displayFormat('Y-m-d')
                    ->placeholder('Select end date')
                    ->after('start_date'),

                Placeholder::make('owner')
                    ->label('Campaign Owner')
                    ->content(fn (?Campaign $record): string => $record?->user?->name ?? auth()->user()->name ?? 'Unknown')
                    ->columnSpanFull(),

                Placeholder::make('data_points_count')
                    ->label('Total Data Points')
                    ->content(fn (?Campaign $record): string => $record ? number_format($record->dataPoints()->count()) : '0')
                    ->hidden(fn (?Campaign $record) => $record === null),

                Placeholder::make('approved_count')
                    ->label('Approved Data Points')
                    ->content(fn (?Campaign $record): string => $record ? number_format($record->dataPoints()->where('status', 'approved')->count()) : '0')
                    ->hidden(fn (?Campaign $record) => $record === null),

                Placeholder::make('survey_zones_count')
                    ->label('Survey Zones')
                    ->content(fn (?Campaign $record): string => $record ? number_format($record->surveyZones()->count()) : '0')
                    ->hidden(fn (?Campaign $record) => $record === null),
            ]);
    }
}
