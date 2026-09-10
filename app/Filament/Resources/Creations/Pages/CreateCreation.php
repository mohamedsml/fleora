<?php

namespace App\Filament\Resources\Creations\Pages;

use App\Filament\Concerns\GereLesPhotos;
use App\Filament\Resources\Creations\CreationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCreation extends CreateRecord
{
    use GereLesPhotos;

    protected static string $resource = CreationResource::class;

    /**
     * `photos` n'est pas une colonne : on l'écarte avant l'insertion.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->extrairePhotos($data);
    }

    /**
     * Les médias ont besoin de l'id du parent : on les traite après création.
     */
    protected function afterCreate(): void
    {
        $this->traiterPhotos($this->record);
    }
}
