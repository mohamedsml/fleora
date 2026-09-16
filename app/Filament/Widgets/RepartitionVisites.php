<?php

namespace App\Filament\Widgets;

use App\Models\Visite;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

/**
 * Répartitions du rapport : pages d'entrée, pages vues, sources, appareils,
 * langues.
 *
 * Un seul widget porte les cinq listes : elles partagent la même forme —
 * libellé, nombre, proportion — et cinq classes quasi identiques auraient
 * divergé à la première correction.
 */
class RepartitionVisites extends Widget
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

    protected string $view = 'filament.widgets.repartition-visites';

    protected int|string|array $columnSpan = 'full';

    protected function debut(): Carbon
    {
        return now()->subDays((int) ($this->pageFilters['periode'] ?? 30))->startOfDay();
    }

    /**
     * Les cinq blocs, dans l'ordre où ils se lisent : d'où viennent les
     * visiteuses, ce qu'elles consultent, puis avec quoi.
     *
     * @return array<int, array{titre: string, aide: string, donnees: array<string, int>}>
     */
    public function blocs(): array
    {
        $debut = $this->debut();

        return [
            [
                'titre' => 'Pages d’entrée',
                'aide' => 'La première page vue — ce que Google et Instagram envoient réellement.',
                'donnees' => Visite::pagesEntree($debut, now()),
            ],
            [
                'titre' => 'Pages les plus vues',
                'aide' => 'Toutes visites confondues.',
                'donnees' => Visite::repartition('chemin', $debut, now(), 10),
            ],
            [
                'titre' => 'Sources',
                'aide' => 'Site d’où vient la visiteuse. « Direct » n’apparaît pas : aucun référent.',
                'donnees' => Visite::repartition('source', $debut, now(), 8),
            ],
            [
                'titre' => 'Appareils',
                'aide' => 'Décide où porter l’effort de mise en page.',
                'donnees' => Visite::repartition('appareil', $debut, now()),
            ],
            [
                'titre' => 'Langues',
                'aide' => 'Part du trafic anglophone.',
                'donnees' => Visite::repartition('langue', $debut, now()),
            ],
        ];
    }
}
