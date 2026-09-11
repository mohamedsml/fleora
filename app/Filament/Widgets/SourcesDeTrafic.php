<?php

namespace App\Filament\Widgets;

use App\Models\Visite;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * D'où viennent les visiteurs.
 *
 * Répond à la seule question qui oriente le budget et le temps : faut-il
 * publier davantage sur Instagram, ou travailler le référencement ?
 */
class SourcesDeTrafic extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Sources de trafic — 30 jours')
            ->query(
                Visite::query()
                    ->selectRaw('MIN(id) as id, COALESCE(source, "direct") as source, COUNT(*) as vues')
                    ->depuis(30)
                    ->groupBy('source')
                    ->orderByDesc('vues')
            )
            ->columns([
                TextColumn::make('source')
                    ->label('Provenance')
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'direct' => 'Accès direct ou lien privé',
                        'google.com', 'google.ca' => 'Recherche Google',
                        'instagram.com' => 'Instagram',
                        'facebook.com' => 'Facebook',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'instagram.com', 'facebook.com' => 'warning',
                        'google.com', 'google.ca' => 'success',
                        'direct' => 'gray',
                        default => 'info',
                    }),

                TextColumn::make('vues')
                    ->label('Visites')
                    ->numeric()
                    ->alignEnd(),
            ])
            ->paginated([10])
            ->emptyStateHeading('Aucune source enregistrée');
    }
}
