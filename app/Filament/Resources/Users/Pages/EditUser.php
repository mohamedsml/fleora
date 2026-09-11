<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\Tables\UsersTable;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn (User $record) => $record->is(auth()->user())
                    || UsersTable::estLeDernierActif($record)),
        ];
    }

    /**
     * Dernière barrière avant l'écriture.
     *
     * Le formulaire grise déjà le champ pour son propre compte, mais un champ
     * désactivé côté navigateur ne protège rien : la vérification doit vivre
     * là où la donnée est écrite.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->getRecord();

        // Le champ absent du tableau n'est pas une autorisation : quand il est
        // grisé, Filament ne le soumet pas. On compare donc à l'état réel du
        // compte plutôt qu'à ce que le formulaire a bien voulu envoyer.
        $resteActif = (bool) ($data['actif'] ?? $record->actif);

        if ($record->actif && ! $resteActif) {
            if ($record->is(auth()->user())) {
                $this->refuser('Vous ne pouvez pas désactiver votre propre compte.');
            }

            if (UsersTable::estLeDernierActif($record)) {
                $this->refuser(
                    'C’est le dernier compte actif : le désactiver fermerait '
                    .'définitivement l’administration.'
                );
            }
        }

        return $data;
    }

    private function refuser(string $message): never
    {
        Notification::make()
            ->danger()
            ->title('Modification refusée')
            ->body($message)
            ->send();

        // Clé préfixée : Filament expose les champs sous `data.`, et une
        // erreur non rattachée ne s'afficherait pas sous le champ.
        throw ValidationException::withMessages(['data.actif' => $message]);
    }
}
