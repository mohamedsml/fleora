<?php

namespace App\Filament\Resources\Creations;

use App\Filament\Resources\Creations\Pages\CreateCreation;
use App\Filament\Resources\Creations\Pages\EditCreation;
use App\Filament\Resources\Creations\Pages\ListCreations;
use App\Filament\Resources\Creations\Schemas\CreationForm;
use App\Filament\Resources\Creations\Tables\CreationsTable;
use App\Models\Creation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CreationResource extends Resource
{
    protected static ?string $model = Creation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|null|\UnitEnum $navigationGroup = 'Contenu du site';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'création';

    protected static ?string $pluralModelLabel = 'créations';

    protected static ?string $recordTitleAttribute = 'titre_fr';

    public static function form(Schema $schema): Schema
    {
        return CreationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CreationsTable::configure($table);
    }

    /**
     * Les listes chargent leurs relations d'emblée : sans cela, chaque ligne
     * déclencherait ses propres requêtes pour le type et les occasions.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['productType', 'occasions']);
    }

    /**
     * Badge d'attention : le nombre de créations encore non publiées.
     */
    public static function getNavigationBadge(): ?string
    {
        $brouillons = static::getModel()::where('publie', false)->count();

        return $brouillons > 0 ? (string) $brouillons : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Créations non publiées';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCreations::route('/'),
            'create' => CreateCreation::route('/create'),
            'edit' => EditCreation::route('/{record}/edit'),
        ];
    }
}
