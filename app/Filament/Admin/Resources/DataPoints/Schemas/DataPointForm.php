<?php

namespace App\Filament\Admin\Resources\DataPoints\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
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

                        DateTimePicker::make('calibration_at')
                            ->label('Calibration Date')
                            ->native(false)
                            ->seconds(false)
                            ->helperText('When was the sensor last calibrated?'),
                    ])
                    ->columns(2),

                Section::make('Review Information')
                    ->schema([
                        Textarea::make('review_notes')
                            ->label('Review Notes')
                            ->rows(3)
                            ->maxLength(1000)
                            ->helperText('Notes from reviewer (approve/reject decision)')
                            ->columnSpanFull(),

                        Placeholder::make('reviewed_info')
                            ->label('Review Details')
                            ->content(function ($record) {
                                if (! $record || ! $record->reviewed_at) {
                                    return 'Not yet reviewed';
                                }

                                $reviewer = $record->reviewedBy?->name ?? 'Unknown';
                                $date = $record->reviewed_at->format('M d, Y H:i');

                                return "Reviewed by {$reviewer} on {$date}";
                            })
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(false),

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

                Section::make('Quality Assurance')
                    ->schema([
                        Placeholder::make('qa_flags_display')
                            ->label('QA Flags')
                            ->content(function ($record) {
                                if (! $record || empty($record->qa_flags)) {
                                    return '✅ No quality issues detected';
                                }

                                // Map flag types to labels with icons (same as selector)
                                $flagLabels = [
                                    'high_gps_error' => '📍 High GPS Error (>50m)',
                                    'statistical_outlier' => '📊 Statistical Outlier',
                                    'outside_zone' => '🗺️ Outside Survey Zone',
                                    'unexpected_range' => '⚠️ Unexpected Range',
                                    'outlier' => '📊 Statistical Outlier (Manual)',
                                    'suspicious_value' => '⚠️ Suspicious Value',
                                    'location_uncertainty' => '📍 Location Uncertainty',
                                    'calibration_overdue' => '⚙️ Calibration Issue',
                                    'manual_review' => '👁️ Manual Review Required',
                                    'data_quality' => '🔍 Data Quality Concern',
                                ];

                                $flags = collect($record->qa_flags)
                                    ->map(function ($flag) use ($flagLabels) {
                                        $type = $flag['type'] ?? 'unknown';
                                        $label = $flagLabels[$type] ?? '⚠️ Unknown Flag';
                                        $reason = $flag['reason'] ?? 'No reason provided';

                                        return "{$label}\n  → {$reason}";
                                    })
                                    ->join("\n\n");

                                return $flags;
                            })
                            ->helperText('Quality issues detected by automated checks. Use "Clear QA Flags" bulk action to remove.')
                            ->columnSpanFull(),

                        Repeater::make('qa_flags')
                            ->label('Edit QA Flags')
                            ->schema([
                                Select::make('type')
                                    ->label('Flag Type')
                                    ->options([
                                        // Automated QA flags
                                        'high_gps_error' => '📍 High GPS Error (>50m)',
                                        'statistical_outlier' => '📊 Statistical Outlier',
                                        'outside_zone' => '🗺️ Outside Survey Zone',
                                        'unexpected_range' => '⚠️ Unexpected Range',
                                        // Manual QA flags
                                        'outlier' => '📊 Statistical Outlier (Manual)',
                                        'suspicious_value' => '⚠️ Suspicious Value',
                                        'location_uncertainty' => '📍 Location Uncertainty',
                                        'calibration_overdue' => '⚙️ Calibration Issue',
                                        'manual_review' => '👁️ Manual Review Required',
                                        'data_quality' => '🔍 Data Quality Concern',
                                    ])
                                    ->required()
                                    ->searchable()
                                    ->native(false),

                                TextInput::make('reason')
                                    ->label('Reason')
                                    ->required()
                                    ->placeholder('e.g., GPS accuracy exceeds threshold'),
                            ])
                            ->columns(2)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['type'] ?? 'New Flag')
                            ->addActionLabel('Add QA Flag')
                            ->helperText('View, edit, or remove existing QA flags. Add new flags as needed.')
                            ->columnSpanFull()
                            ->cloneable()
                            ->reorderable(),
                    ])
                    ->collapsible()
                    ->collapsed(fn ($record) => empty($record?->qa_flags)),
            ]);
    }
}
