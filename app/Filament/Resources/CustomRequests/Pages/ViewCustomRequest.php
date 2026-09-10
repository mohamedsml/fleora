<?php

namespace App\Filament\Resources\CustomRequests\Pages;

use App\Filament\Resources\CustomRequests\CustomRequestResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomRequest extends ViewRecord
{
    protected static string $resource = CustomRequestResource::class;

    public function getTitle(): string
    {
        return $this->record->numero_suivi;
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
