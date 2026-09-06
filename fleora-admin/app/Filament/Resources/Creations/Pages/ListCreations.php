<?php

namespace App\Filament\Resources\Creations\Pages;

use App\Filament\Resources\Creations\CreationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCreations extends ListRecords
{
    protected static string $resource = CreationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
