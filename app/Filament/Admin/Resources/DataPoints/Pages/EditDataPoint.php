<?php

namespace App\Filament\Admin\Resources\DataPoints\Pages;

use App\Filament\Admin\Resources\DataPoints\DataPointResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditDataPoint extends EditRecord
{
    protected static string $resource = DataPointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Extract latitude and longitude from PostGIS location
        if ($this->record->id) {
            $coords = DB::selectOne(
                'SELECT ST_Y(location::geometry) as latitude, ST_X(location::geometry) as longitude FROM data_points WHERE id = ?',
                [$this->record->id]
            );

            if ($coords) {
                $data['latitude'] = $coords->latitude;
                $data['longitude'] = $coords->longitude;
            }
        }

        // Ensure photo_path is included for FileUpload preview
        if ($this->record->photo_path) {
            $data['photo_path'] = $this->record->photo_path;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Track status changes - set reviewed_at and reviewed_by
        $originalStatus = $this->record->status ?? null;
        $newStatus = $data['status'] ?? $originalStatus;

        // If status changed to approved or rejected, record the review
        if ($originalStatus !== $newStatus && in_array($newStatus, ['approved', 'rejected'])) {
            $data['reviewed_at'] = now();
            $data['reviewed_by'] = auth()->id();
        }

        // If status changed back to pending/draft, clear review data
        if ($originalStatus !== $newStatus && in_array($newStatus, ['pending', 'draft'])) {
            $data['reviewed_at'] = null;
            $data['reviewed_by'] = null;
        }

        // Convert latitude/longitude to PostGIS geometry point
        if (isset($data['latitude']) && isset($data['longitude'])) {
            $lat = $data['latitude'];
            $lon = $data['longitude'];

            // Store as geometry (not geography) to match database column type
            $data['location'] = DB::raw("ST_SetSRID(ST_MakePoint({$lon}, {$lat}), 4326)");

            // Remove lat/lon from data as they're not database columns
            unset($data['latitude']);
            unset($data['longitude']);
        }

        // Handle photo_path - Filament FileUpload returns array with file path
        if (isset($data['photo_path']) && is_array($data['photo_path'])) {
            // If it's an array, take the first element (new upload)
            $data['photo_path'] = $data['photo_path'][0] ?? null;
        } elseif (isset($data['photo_path']) && empty($data['photo_path'])) {
            // If explicitly cleared, set to null
            $data['photo_path'] = null;
        }
        // If it's already a string (existing path), leave it as is

        return $data;
    }

    protected function afterSave(): void
    {
        // Send success notification as centered modal overlay
        Notification::make()
            ->success()
            ->title('Reading updated successfully!')
            ->body('The data point has been updated.')
            ->duration(3000)
            ->send();
    }

    protected function getRedirectUrl(): ?string
    {
        // Redirect back to the list page after saving
        return $this->getResource()::getUrl('index');
    }
}
