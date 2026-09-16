<?php

namespace App\Filament\Widgets;

use App\Models\CustomRequest;
use App\Models\Visite;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

/**
 * Chiffres d'en-tête du rapport de visites.
 *
 * Widget plutôt que balisage écrit à la main : Filament sert un CSS
 * précompilé qui ne contient que les classes de ses propres composants. Des
 * classes Tailwind écrites à la main dans une vue ne sont jamais générées —
 * la page s'affichait donc sans aucun style.
 */
class ResumeFrequentation extends StatsOverviewWidget
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

    protected int|string|array $columnSpan = 'full';

    protected function debut(): Carbon
    {
        return now()->subDays((int) ($this->pageFilters['periode'] ?? 30))->startOfDay();
    }

    protected function getStats(): array
    {
        $debut = $this->debut();
        $jours = (int) ($this->pageFilters['periode'] ?? 30);
        $precedent = now()->subDays($jours * 2)->startOfDay();

        $visites = Visite::query()->entre($debut, now())->count();
        $visitesAvant = Visite::query()->entre($precedent, $debut)->count();

        $profondeur = Visite::profondeur($debut, now());
        $demandes = CustomRequest::whereBetween('created_at', [$debut, now()])->count();

        $conversion = $profondeur['visiteurs'] > 0
            ? round($demandes / $profondeur['visiteurs'] * 100, 1)
            : 0.0;

        return [
            Stat::make('Visites', number_format($visites, 0, ',', ' '))
                ->description($this->evolution($visites, $visitesAvant))
                ->descriptionIcon($visites >= $visitesAvant
                    ? 'heroicon-m-arrow-trending-up'
                    : 'heroicon-m-arrow-trending-down')
                ->color($visitesAvant === 0 ? 'gray' : ($visites >= $visitesAvant ? 'success' : 'danger')),

            Stat::make('Visiteurs', number_format($profondeur['visiteurs'], 0, ',', ' '))
                ->description('Distincts par jour')
                ->descriptionIcon('heroicon-m-users')
                ->color('gray'),

            Stat::make('Pages par visiteur', (string) $profondeur['pages_par_visiteur'])
                // Sous 2, la page d'entrée ne donne pas envie d'aller plus loin.
                ->description($profondeur['pages_par_visiteur'] < 2
                    ? 'Faible — la page d’entrée retient peu'
                    : 'Bon signe d’intérêt')
                ->descriptionIcon('heroicon-m-document-duplicate')
                ->color($profondeur['pages_par_visiteur'] < 2 ? 'warning' : 'success'),

            Stat::make('Une seule page', $profondeur['une_seule_page'].' %')
                ->description('Reparties sans aller plus loin')
                ->descriptionIcon('heroicon-m-arrow-uturn-left')
                ->color($profondeur['une_seule_page'] > 70 ? 'danger' : 'gray'),

            Stat::make('Demandes', (string) $demandes)
                ->description('Formulaires envoyés')
                ->descriptionIcon('heroicon-m-inbox-arrow-down')
                ->color($demandes > 0 ? 'success' : 'gray'),

            Stat::make('Conversion', $conversion.' %')
                ->description('Cible : 2 à 4 %')
                ->descriptionIcon('heroicon-m-cursor-arrow-ripple')
                ->color(match (true) {
                    $conversion >= 2.0 => 'success',
                    $conversion >= 1.5 => 'warning',
                    default => 'danger',
                }),
        ];
    }

    private function evolution(int $actuel, int $precedent): string
    {
        if ($precedent === 0) {
            return $actuel > 0 ? 'Première période mesurée' : 'Aucune donnée';
        }

        $variation = round(($actuel - $precedent) / $precedent * 100);

        return ($variation >= 0 ? '+' : '').$variation.' % vs période précédente';
    }
}
