<?php

namespace App\Filament\Admin\Resources\DataPoints\Pages;

use App\Filament\Admin\Resources\DataPoints\DataPointResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDataPoint extends CreateRecord
{
    protected static string $resource = DataPointResource::class;
}
