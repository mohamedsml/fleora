<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable()
                    // Repérer son propre compte d'un coup d'œil évite de
                    // modifier le mauvais.
                    ->description(fn (User $record) => $record->is(auth()->user()) ? 'Vous' : null),

                TextColumn::make('email')
                    ->label('Courriel')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                IconColumn::make('actif')
                    ->label('Actif')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                TernaryFilter::make('actif')
                    ->label('Compte actif')
                    ->placeholder('Tous')
                    ->trueLabel('Actifs')
                    ->falseLabel('Désactivés'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    // Deux suppressions à rendre impossibles : la sienne, qui
                    // déconnecte immédiatement, et celle du dernier compte
                    // actif, qui fermerait l'administration pour de bon.
                    ->hidden(fn (User $record) => $record->is(auth()->user()) || static::estLeDernierActif($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        // Une sélection groupée contournerait les protections
                        // individuelles ci-dessus : cocher « tout » fermerait
                        // l'administration. On retire les comptes protégés du
                        // lot plutôt que de refuser l'action entière — l'autre
                        // choix obligerait à deviner quelle ligne pose
                        // problème.
                        ->before(function (Collection $records) {
                            $proteges = $records->filter(
                                fn (User $record) => $record->is(auth()->user())
                                    || static::estLeDernierActif($record)
                            );

                            if ($proteges->isEmpty()) {
                                return;
                            }

                            foreach ($proteges->keys() as $cle) {
                                $records->forget($cle);
                            }

                            Notification::make()
                                ->warning()
                                ->title('Certains comptes ont été conservés')
                                ->body('Votre propre compte et le dernier compte actif ne peuvent pas être supprimés.')
                                ->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('Aucun utilisateur')
            ->emptyStateDescription('Les comptes créés ici peuvent se connecter à l’administration.');
    }

    /**
     * Vrai si supprimer ou désactiver ce compte ne laisserait aucun accès.
     */
    public static function estLeDernierActif(User $utilisateur): bool
    {
        if (! $utilisateur->actif) {
            return false;
        }

        return User::where('actif', true)->whereKeyNot($utilisateur->getKey())->doesntExist();
    }
}
