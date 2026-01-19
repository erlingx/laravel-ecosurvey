<?php

namespace App\Filament\Admin\Resources\DataPoints\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DataPointForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Point Information')
                    ->schema([
                        Select::make('campaign_id')
                            ->label('Campaign')
                            ->relationship('campaign', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('environmental_metric_id')
                            ->label('Environmental Metric')
                            ->relationship('environmentalMetric', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('value')
                            ->label('Measurement Value')
                            ->numeric()
                            ->required()
                            ->step(0.01),

                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'draft' => 'Draft',
                                'pending' => 'Pending Review',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                            ])
                            ->default('pending')
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Location Information')
                    ->schema([
                        TextInput::make('latitude')
                            ->label('Latitude')
                            ->numeric()
                            ->step(0.000001)
                            ->minValue(-90)
                            ->maxValue(90)
                            ->required()
                            ->helperText('WGS84 decimal degrees (-90 to +90)'),

                        TextInput::make('longitude')
                            ->label('Longitude')
                            ->numeric()
                            ->step(0.000001)
                            ->minValue(-180)
                            ->maxValue(180)
                            ->required()
                            ->helperText('WGS84 decimal degrees (-180 to +180)'),

                        TextInput::make('accuracy')
                            ->label('GPS Accuracy (meters)')
                            ->numeric()
                            ->step(0.1)
                            ->minValue(0)
                            ->helperText('GPS accuracy in meters'),
                    ])
                    ->columns(3),

                Section::make('Collection Details')
                    ->schema([
                        DateTimePicker::make('collected_at')
                            ->label('Collection Date & Time')
                            ->required()
                            ->native(false)
                            ->seconds(false),

                        Select::make('user_id')
                            ->label('Submitted By')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('device_model')
                            ->label('Device Model')
                            ->maxLength(255)
                            ->helperText('e.g., iPhone 14, Samsung Galaxy S23'),

                        TextInput::make('sensor_type')
                            ->label('Sensor Type')
                            ->maxLength(255)
                            ->helperText('e.g., built-in, external'),
                    ])
                    ->columns(2),

                Section::make('Additional Information')
                    ->schema([
                        FileUpload::make('photo_path')
                            ->label('Photo')
                            ->image()
                            ->disk('uploads')
                            ->directory('data-points')
                            ->visibility('public')
                            ->maxSize(5120)
                            ->imagePreviewHeight('200')
                            ->downloadable()
                            ->openable()
                            ->imageEditor()
                            ->helperText('Max 5MB, JPG/PNG. Stored in public/files/data-points/'),

                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ]);
    }
}
