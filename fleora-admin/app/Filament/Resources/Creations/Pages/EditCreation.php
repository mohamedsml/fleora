<?php

namespace App\Filament\Resources\Creations\Pages;

use App\Filament\Resources\Creations\CreationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCreation extends EditRecord
{
    protected static string $resource = CreationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
