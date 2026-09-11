<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Rien à faire pour le mot de passe : le modèle porte `'password' => 'hashed'`,
 * donc le cast s'en charge. Le hacher ici aussi produirait un double hachage,
 * et la connexion échouerait sans message exploitable.
 */
class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
