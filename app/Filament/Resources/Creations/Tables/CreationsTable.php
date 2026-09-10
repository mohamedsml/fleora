<?php

namespace App\Filament\Resources\Creations\Tables;

use App\Models\Creation;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CreationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // Vignette : sur un catalogue visuel, reconnaître une création
                // à sa photo est plus rapide qu'à son titre.
                ImageColumn::make('apercu')
                    ->label('')
                    ->state(fn (Creation $record) => $record->imagePrincipale()?->urlVariante(400))
                    ->imageHeight(56)
                    ->extraImgAttributes(['class' => 'rounded-lg object-cover'])
                    ->defaultImageUrl(null),
                TextColumn::make('titre_fr')
                    ->label('Création')
                    ->searchable()
                    ->sortable()
                    // Signale ce qui reste à traduire sans ouvrir la fiche
                    ->description(fn (Creation $record) => $record->titre_en ?: '⚠ Traduction anglaise manquante'),
                TextColumn::make('productType.nom_fr')
                    ->label('Type')
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('occasions.nom_fr')
                    ->label('Occasions')
                    ->badge()
                    ->limitList(2)
                    ->expandableLimitedList(),
                TextColumn::make('prix_min')
                    ->label('Prix')
                    ->placeholder('Sur devis')
                    ->formatStateUsing(fn (Creation $record) => $record->fourchettePrix('fr')),
                IconColumn::make('vedette')
                    ->label('Vedette')
                    ->boolean(),
                IconColumn::make('publie')
                    ->label('Publiée')
                    ->boolean(),
                TextColumn::make('ordre')
                    ->label('Ordre')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Modifiée')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('ordre')
            ->filters([
                SelectFilter::make('product_type_id')
                    ->label('Type de produit')
                    ->relationship('productType', 'nom_fr')
                    ->preload(),
                SelectFilter::make('occasions')
                    ->label('Occasion')
                    ->relationship('occasions', 'nom_fr')
                    ->preload(),
                TernaryFilter::make('publie')->label('Publiée'),
                TernaryFilter::make('vedette')->label('Vedette'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('ordre')
            ->emptyStateHeading('Aucune création pour l’instant')
            ->emptyStateDescription('Ajoutez votre première création pour la voir apparaître dans la galerie du site.');
    }
}
