<?php

namespace App\Filament\Resources\CustomRequests;

use App\Filament\Resources\CustomRequests\Pages\EditCustomRequest;
use App\Filament\Resources\CustomRequests\Pages\ListCustomRequests;
use App\Filament\Resources\CustomRequests\Pages\ViewCustomRequest;
use App\Filament\Resources\CustomRequests\Schemas\CustomRequestForm;
use App\Filament\Resources\CustomRequests\Tables\CustomRequestsTable;
use App\Models\CustomRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Demandes de soumission reçues depuis le site.
 *
 * Aucune page de création : une demande vient toujours du formulaire public.
 * En saisir une à la main produirait un consentement Loi 25 non horodaté et
 * une source de données incohérente.
 */
class CustomRequestResource extends Resource
{
    protected static ?string $model = CustomRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static string|null|\UnitEnum $navigationGroup = 'Commercial';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'demande';

    protected static ?string $pluralModelLabel = 'demandes';

    protected static ?string $recordTitleAttribute = 'numero_suivi';

    /**
     * Compteur des demandes sans réponse : c'est l'information qu'on veut voir
     * en ouvrant le back-office, sans avoir à cliquer.
     */
    public static function getNavigationBadge(): ?string
    {
        $enAttente = static::getModel()::query()->nonRepondues()->count();

        return $enAttente > 0 ? (string) $enAttente : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        // Rouge dès qu'une demande dont l'événement approche attend une
        // réponse : le délai est la première cause de perte d'une cliente.
        $urgentes = static::getModel()::query()
            ->nonRepondues()
            ->whereNotNull('date_evenement')
            ->whereDate('date_evenement', '<=', now()->addDays(14))
            ->exists();

        return $urgentes ? 'danger' : 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return CustomRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomRequestsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomRequests::route('/'),
            'view' => ViewCustomRequest::route('/{record}'),
            'edit' => EditCustomRequest::route('/{record}/edit'),
        ];
    }

    /**
     * Les demandes supprimées par erreur restent consultables : `softDeletes`
     * sur le modèle ne sert à rien si l'interface les cache définitivement.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }
}
