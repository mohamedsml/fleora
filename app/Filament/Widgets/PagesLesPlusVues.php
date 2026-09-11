<?php

namespace App\Filament\Widgets;

use App\Models\Visite;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Les pages qui attirent réellement.
 *
 * Dit où le contenu fonctionne — donc où investir le prochain effort de
 * rédaction ou de photo.
 */
class PagesLesPlusVues extends TableWidget
{
    /**
     * Retiré du tableau de bord.
     *
     * Son contenu vit sur la page « Rapport de visites », où il se filtre par
     * période. Le garder ici doublonnerait, et allongerait un écran qui doit
     * répondre d'abord à « qu'est-ce que je dois faire aujourd'hui ».
     *
     * La classe reste enregistrable à la main si besoin.
     */
    public static function isDiscovered(): bool
    {
        return false;
    }

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Pages les plus vues — 30 jours')
            ->query(
                Visite::query()
                    ->selectRaw('MIN(id) as id, chemin, COUNT(*) as vues, COUNT(DISTINCT empreinte) as visiteurs')
                    ->depuis(30)
                    ->groupBy('chemin')
                    ->orderByDesc('vues')
            )
            ->columns([
                TextColumn::make('chemin')
                    ->label('Page')
                    ->url(fn ($record) => url($record->chemin))
                    ->openUrlInNewTab()
                    ->limit(40),

                TextColumn::make('vues')
                    ->label('Vues')
                    ->numeric()
                    ->alignEnd(),

                TextColumn::make('visiteurs')
                    ->label('Visiteurs')
                    ->numeric()
                    ->alignEnd()
                    ->toggleable(),
            ])
            ->paginated([10, 25])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Aucune visite enregistrée')
            ->emptyStateDescription('Les statistiques apparaissent dès les premières visites du site.');
    }

    protected function getTableQuery(): ?Builder
    {
        return null;
    }
}
