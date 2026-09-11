<?php

namespace App\Filament\Resources\Occasions\Pages;

use App\Filament\Concerns\GereLesPhotos;
use App\Filament\Resources\Occasions\OccasionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOccasion extends CreateRecord
{
    use GereLesPhotos;

    protected static string $resource = OccasionResource::class;

    /**
     * `photos` n'est pas une colonne : on l'écarte avant l'insertion.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->extrairePhotos($data);
    }

    /**
     * Le média a besoin de l'id du parent : on le traite après création.
     */
    protected function afterCreate(): void
    {
        $this->traiterPhotos($this->record);
    }
}
