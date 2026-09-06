<?php

namespace App\Filament\Resources\ProductTypes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nom_fr')
                    ->label('Type')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('creations_count')
                    ->label('Créations')
                    ->counts('creations')
                    ->badge(),
                IconColumn::make('publie')
                    ->label('Publié')
                    ->boolean(),
            ])
            ->defaultSort('ordre')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('ordre')
            ->emptyStateHeading('Aucun type de produit')
            ->emptyStateDescription('Boîte à fleurs, coffret prénom, lot d’invités…');
    }
}
