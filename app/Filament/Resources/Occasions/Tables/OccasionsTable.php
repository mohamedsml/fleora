<?php

namespace App\Filament\Resources\Occasions\Tables;

use App\Models\Occasion;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class OccasionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nom_fr')
                    ->label('Occasion')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug_fr')
                    ->label('URL')
                    ->prefix('/occasions/')
                    ->color('gray'),
                TextColumn::make('creations_count')
                    ->label('Créations')
                    ->counts('creations')
                    ->badge(),
                // Une page pilier sans contenu long ne se référencera pas :
                // le signaler dans la liste évite de l'oublier.
                IconColumn::make('contenu_seo_fr')
                    ->label('Contenu SEO')
                    ->boolean()
                    ->getStateUsing(fn (Occasion $record) => filled($record->contenu_seo_fr))
                    ->tooltip(fn (Occasion $record) => filled($record->contenu_seo_fr)
                        ? 'Contenu rédigé'
                        : 'Aucun contenu long : cette page ne se référencera pas'),
                IconColumn::make('publie')
                    ->label('Publiée')
                    ->boolean(),
            ])
            ->defaultSort('ordre')
            ->filters([
                TernaryFilter::make('publie')->label('Publiée'),
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
            ->emptyStateHeading('Aucune occasion')
            ->emptyStateDescription('Mariage, baby shower, baptême… Chaque occasion devient une page du site.');
    }
}
