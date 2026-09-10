<?php

namespace App\Filament\Resources\CustomRequests\Tables;

use App\Enums\StatutDemande;
use App\Models\CustomRequest;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('numero_suivi')
                    ->label('Nº')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Numéro copié')
                    ->fontFamily('mono')
                    ->size('xs'),

                TextColumn::make('nom')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable()
                    ->description(fn (CustomRequest $record) => $record->courriel),

                TextColumn::make('occasion.nom_fr')
                    ->label('Occasion')
                    ->badge()
                    // occasionLibelle() couvre le cas « Autre », où l'occasion
                    // est saisie en texte libre et non liée.
                    ->state(fn (CustomRequest $record) => $record->occasionLibelle())
                    ->placeholder('—'),

                TextColumn::make('date_evenement')
                    ->label('Événement')
                    ->date('j M Y')
                    ->sortable()
                    ->placeholder('—')
                    // Compte à rebours : « dans 6 jours » se lit plus vite
                    // qu'une date quand on trie des demandes urgentes.
                    ->description(fn (CustomRequest $record) => $record->date_evenement
                        ? $record->date_evenement->diffForHumans(['short' => true])
                        : null)
                    ->color(fn (CustomRequest $record) => $record->estUrgente() ? 'danger' : null),

                TextColumn::make('budget')
                    ->label('Budget')
                    ->formatStateUsing(fn (?string $state) => $state ? __('demande.budgets.'.$state) : null)
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('quantite')
                    ->label('Qté')
                    ->formatStateUsing(fn (?string $state) => $state ? __('demande.quantites.'.$state) : null)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('statut')
                    ->label('Statut')
                    ->badge()
                    ->sortable(),

                IconColumn::make('repondu_le')
                    ->label('Répondu')
                    ->boolean()
                    ->getStateUsing(fn (CustomRequest $record) => $record->repondu_le !== null)
                    ->tooltip(fn (CustomRequest $record) => $record->repondu_le?->diffForHumans()),

                TextColumn::make('created_at')
                    ->label('Reçue')
                    ->since()
                    ->sortable()
                    ->tooltip(fn (CustomRequest $record) => $record->created_at->translatedFormat('j F Y à H:i')),
            ])
            // Les plus récentes d'abord : c'est l'ordre dans lequel on traite.
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('statut')
                    ->label('Statut')
                    ->options(StatutDemande::class)
                    ->multiple(),

                SelectFilter::make('occasion')
                    ->label('Occasion')
                    ->relationship('occasion', 'nom_fr')
                    ->preload(),

                TernaryFilter::make('repondu_le')
                    ->label('Répondue')
                    ->nullable()
                    ->trueLabel('Oui')
                    ->falseLabel('Pas encore'),

                Filter::make('urgentes')
                    ->label('Urgentes (événement < 14 j, sans réponse)')
                    ->query(fn (Builder $query) => $query
                        ->whereNull('repondu_le')
                        ->whereNotNull('date_evenement')
                        ->whereDate('date_evenement', '<=', now()->addDays(14)))
                    ->toggle(),

                Filter::make('avec_images')
                    ->label('Avec images d’inspiration')
                    ->query(fn (Builder $query) => $query->has('attachments'))
                    ->toggle(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Suppression douce : une demande effacée par erreur reste
                    // récupérable, et la purge Loi 25 s'en charge à terme.
                    DeleteBulkAction::make(),
                ]),
            ])
            // 30 s : le back-office reste ouvert dans un onglet, une nouvelle
            // demande doit apparaître sans qu'on pense à rafraîchir.
            ->poll('30s')
            ->emptyStateHeading('Aucune demande pour l’instant')
            ->emptyStateDescription('Les demandes envoyées depuis le site apparaissent ici.');
    }
}
