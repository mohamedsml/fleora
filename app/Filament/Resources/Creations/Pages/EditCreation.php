<?php

namespace App\Filament\Resources\Creations\Pages;

use App\Filament\Concerns\GereLesPhotos;
use App\Filament\Resources\Creations\CreationResource;
use App\Services\ImageService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCreation extends EditRecord
{
    use GereLesPhotos;

    protected static string $resource = CreationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                // Supprimer la création doit emporter ses fichiers : la
                // cascade en base retire les lignes `media`, pas les images
                // sur le disque.
                ->before(function () {
                    $service = ImageService::make();

                    foreach ($this->record->media as $media) {
                        $service->supprimerFichiers($media);
                    }
                }),
        ];
    }

    /**
     * Alimente le champ `photos` avec les images déjà en base.
     *
     * afterFill, et non mutateFormDataBeforeFill : le composant FileUpload
     * initialise son propre état après le remplissage et écraserait la valeur
     * injectée en amont.
     */
    protected function afterFill(): void
    {
        $this->data['photos'] = $this->photosExistantes($this->record);
    }

    /**
     * `photos` n'est pas une colonne : on l'écarte avant la mise à jour.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->extrairePhotos($data);
    }

    protected function afterSave(): void
    {
        $this->traiterPhotos($this->record);
    }
}
