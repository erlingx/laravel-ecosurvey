<?php

namespace App\Filament\Admin\Resources\DataPoints;

use App\Filament\Admin\Resources\DataPoints\Pages\CreateDataPoint;
use App\Filament\Admin\Resources\DataPoints\Pages\EditDataPoint;
use App\Filament\Admin\Resources\DataPoints\Pages\ListDataPoints;
use App\Filament\Admin\Resources\DataPoints\Schemas\DataPointForm;
use App\Filament\Admin\Resources\DataPoints\Tables\DataPointsTable;
use App\Models\DataPoint;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class DataPointResource extends Resource
{
    protected static ?string $model = DataPoint::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Data Quality';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Review Data Points';

    protected static ?string $label = 'Data Point';

    public static function form(Schema $schema): Schema
    {
        return DataPointForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DataPointsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDataPoints::route('/'),
            'create' => CreateDataPoint::route('/create'),
            'edit' => EditDataPoint::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
