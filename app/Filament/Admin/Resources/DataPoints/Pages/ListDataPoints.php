<?php

namespace App\Filament\Admin\Resources\DataPoints\Pages;

use App\Filament\Admin\Resources\DataPoints\DataPointResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDataPoints extends ListRecords
{
    protected static string $resource = DataPointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
