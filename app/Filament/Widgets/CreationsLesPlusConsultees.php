<?php

namespace App\Filament\Widgets;

use App\Models\Creation;
use App\Models\Visite;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Quelles créations intéressent vraiment.
 *
 * L'information la plus actionnable du tableau de bord : elle dit quoi mettre
 * en avant, quoi photographier à nouveau, et quelles pièces proposer en
 * priorité dans une soumission.
 */
class CreationsLesPlusConsultees extends TableWidget
{
    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Créations les plus consultées — 30 jours')
            ->query(
                Visite::query()
                    ->selectRaw('MIN(id) as id, entite_id, COUNT(*) as vues, COUNT(DISTINCT empreinte) as visiteurs')
                    ->depuis(30)
                    ->where('entite_type', 'Creation')
                    ->groupBy('entite_id')
                    ->orderByDesc('vues')
            )
            ->columns([
                TextColumn::make('entite_id')
                    ->label('Création')
                    ->formatStateUsing(fn ($state) => Creation::find($state)?->t('titre') ?? '—')
                    ->url(fn ($record) => ($c = Creation::find($record->entite_id))
                        ? route_langue('creations.show', $c->slugPour('fr'), 'fr')
                        : null)
                    ->openUrlInNewTab(),

                TextColumn::make('vues')->label('Vues')->numeric()->alignEnd(),
                TextColumn::make('visiteurs')->label('Visiteurs')->numeric()->alignEnd(),
            ])
            ->paginated([10, 25])
            ->emptyStateHeading('Aucune fiche consultée')
            ->emptyStateDescription('Les créations consultées apparaîtront ici.');
    }
}
