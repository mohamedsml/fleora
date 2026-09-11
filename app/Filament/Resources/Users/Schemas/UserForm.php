<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nom')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label('Courriel')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->helperText('Sert à se connecter à l’administration.'),

                TextInput::make('password')
                    ->label('Mot de passe')
                    ->password()
                    ->revealable()
                    ->rule(Password::min(12))
                    // Obligatoire à la création seulement : en modification, un
                    // champ laissé vide garde le mot de passe actuel.
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->helperText(fn (string $operation) => $operation === 'create'
                        ? 'Douze caractères minimum. Ce compte donne accès aux demandes clientes.'
                        : 'Laisser vide pour conserver le mot de passe actuel.')
                    ->autocomplete('new-password')
                    ->maxLength(255),

                Toggle::make('actif')
                    ->label('Compte actif')
                    ->default(true)
                    ->helperText('Désactiver coupe l’accès à l’administration sans supprimer le compte '
                        .'— ni son historique.')
                    // Volontairement NON grisé pour son propre compte : un
                    // champ `disabled` n'est pas soumis du tout, ce qui prive
                    // la validation serveur de toute valeur à contrôler — la
                    // protection ne tiendrait alors qu'au navigateur. Le refus
                    // vit dans EditUser, où la donnée est écrite.
                    ->hintColor('warning')
                    ->hint(fn (?User $record) => $record?->is(auth()->user())
                        ? 'Vous ne pouvez pas désactiver votre propre compte.'
                        : null),
            ]);
    }
}
