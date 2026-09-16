<?php

namespace App\Filament\Widgets;

use App\Models\Visite;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;

/**
 * Courbe des visites, un point par jour.
 *
 * ChartWidget plutôt qu'un SVG écrit à la main : Filament embarque déjà
 * Chart.js et son CSS, là où un graphique maison dépendait de classes
 * Tailwind absentes du CSS précompilé du panneau.
 */
class CourbeVisites extends ChartWidget
{
    use InteractsWithPageFilters;

    /**
     * Retiré du tableau de bord : ce widget appartient à la page « Rapport de
     * visites », dont il lit le filtre de période. Posé ailleurs, il
     * afficherait toujours trente jours.
     */
    public static function isDiscovered(): bool
    {
        return false;
    }

    protected ?string $heading = 'Évolution des visites';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '260px';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $jours = (int) ($this->pageFilters['periode'] ?? 30);
        $debut = now()->subDays($jours)->startOfDay();

        $brut = Visite::parJour($debut, now());

        $valeurs = [];
        $etiquettes = [];

        // Les jours sans visite valent zéro plutôt que d'être absents : une
        // courbe qui saute les creux ment sur la régularité du trafic.
        for ($jour = $debut->copy(); $jour <= now(); $jour->addDay()) {
            $cle = $jour->format('Y-m-d');
            $valeurs[] = $brut[$cle] ?? 0;

            // Au-delà d'un mois, une étiquette par jour devient illisible :
            // on n'en garde qu'une sur sept.
            $etiquettes[] = $jours > 31 && $jour->dayOfWeek !== Carbon::MONDAY
                ? ''
                : $jour->translatedFormat('j M');
        }

        return [
            'datasets' => [[
                'label' => 'Visites',
                'data' => $valeurs,
                'borderColor' => '#d97706',
                'backgroundColor' => 'rgba(217, 119, 6, 0.08)',
                'fill' => true,
                'tension' => 0.3,
                'pointRadius' => count($valeurs) > 40 ? 0 : 3,
            ]],
            'labels' => $etiquettes,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => [
                // Un axe qui ne part pas de zéro exagère les variations.
                'y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]],
            ],
        ];
    }
}
