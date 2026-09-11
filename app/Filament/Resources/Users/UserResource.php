<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Comptes ayant accès à l'administration.
 *
 * Remplace la création de comptes par `tinker` en production : le mot de passe
 * n'a plus à transiter par une ligne de commande, où il resterait dans
 * l'historique du shell.
 *
 * Ces comptes ouvrent sur les demandes clientes — donc sur des renseignements
 * personnels au sens de la Loi 25. D'où deux partis pris, appliqués dans
 * UsersTable et EditUser plutôt que laissés à la vigilance :
 *
 * - on ne peut ni se désactiver, ni se supprimer soi-même ;
 * - le dernier compte actif ne peut pas être désactivé.
 *
 * Sans ces garde-fous, un clic suffirait à rendre l'administration
 * définitivement inaccessible, et il faudrait un accès SSH pour la rouvrir.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|null|\UnitEnum $navigationGroup = 'Système';

    protected static ?int $navigationSort = 90;

    protected static ?string $modelLabel = 'utilisateur';

    protected static ?string $pluralModelLabel = 'utilisateurs';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
