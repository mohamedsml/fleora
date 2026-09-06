<?php

namespace App\Filament\Resources\ProductTypes;

use App\Filament\Resources\ProductTypes\Pages\CreateProductType;
use App\Filament\Resources\ProductTypes\Pages\EditProductType;
use App\Filament\Resources\ProductTypes\Pages\ListProductTypes;
use App\Filament\Resources\ProductTypes\Schemas\ProductTypeForm;
use App\Filament\Resources\ProductTypes\Tables\ProductTypesTable;
use App\Models\ProductType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProductTypeResource extends Resource
{
    protected static ?string $model = ProductType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|null|\UnitEnum $navigationGroup = 'Contenu du site';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'type de produit';

    protected static ?string $pluralModelLabel = 'types de produit';

    protected static ?string $recordTitleAttribute = 'nom_fr';

    public static function form(Schema $schema): Schema
    {
        return ProductTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductTypesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductTypes::route('/'),
            'create' => CreateProductType::route('/create'),
            'edit' => EditProductType::route('/{record}/edit'),
        ];
    }
}
