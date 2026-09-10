<?php

namespace App\Filament\Resources\CustomRequests\Pages;

use App\Enums\StatutDemande;
use App\Filament\Resources\CustomRequests\CustomRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomRequest extends EditRecord
{
    protected static string $resource = CustomRequestResource::class;

    public function getTitle(): string
    {
        return $this->record->numero_suivi;
    }

    protected function getHeaderActions(): array
    {
        return [
            // Suppression douce : les demandes contiennent des renseignements
            // personnels, mais une suppression accidentelle ne doit pas être
            // définitive. La purge Loi 25 efface réellement à échéance.
            DeleteAction::make(),
        ];
    }

    /**
     * Horodate la réponse dès qu'une demande quitte l'état « nouvelle ».
     *
     * Sans automatisme, ce champ resterait vide : personne ne pense à
     * renseigner une date en traitant une demande. Or c'est lui qui alimente
     * le tri de la liste et le compteur de la navigation.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Le champ arrive tantôt en chaîne (formulaire), tantôt en enum
        // (cast du modèle) : on normalise avant de comparer, sinon la
        // comparaison échoue toujours et toute sauvegarde horodate la réponse.
        $statut = $data['statut'] ?? null;
        $statut = $statut instanceof StatutDemande ? $statut->value : $statut;

        $quitteNouvelle = $statut !== null && $statut !== StatutDemande::Nouvelle->value;

        if ($quitteNouvelle && blank($data['repondu_le'] ?? null)) {
            $data['repondu_le'] = now();
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
