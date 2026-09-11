<?php

namespace App\Filament\Widgets;

use App\Models\CustomRequest;
use App\Models\Visite;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Les quatre chiffres qui comptent, sur 30 jours.
 *
 * Chacun est comparé aux 30 jours précédents : un nombre seul ne dit rien,
 * c'est la tendance qui indique si ce qu'on fait fonctionne.
 */
class StatistiquesVisites extends StatsOverviewWidget
{
    protected ?string $heading = 'Fréquentation — 30 derniers jours';

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $debut = now()->subDays(30)->startOfDay();
        $debutPrecedent = now()->subDays(60)->startOfDay();

        $visites = Visite::entre($debut, now())->count();
        $visitesPrecedentes = Visite::entre($debutPrecedent, $debut)->count();

        $visiteurs = Visite::entre($debut, now())->distinct('empreinte')->count('empreinte');

        $demandes = CustomRequest::whereBetween('created_at', [$debut, now()])->count();
        $demandesPrecedentes = CustomRequest::whereBetween('created_at', [$debutPrecedent, $debut])->count();

        // Le seul chiffre qui décide vraiment. Sous 1,5 %, le problème est
        // presque toujours la qualité des photos ou une friction dans le
        // formulaire — pas le volume de trafic.
        $conversion = $visiteurs > 0 ? round($demandes / $visiteurs * 100, 1) : 0.0;

        return [
            Stat::make('Visites', number_format($visites, 0, ',', ' '))
                ->description($this->evolution($visites, $visitesPrecedentes))
                ->descriptionIcon($this->icone($visites, $visitesPrecedentes))
                ->color($this->couleur($visites, $visitesPrecedentes)),

            Stat::make('Visiteurs', number_format($visiteurs, 0, ',', ' '))
                ->description('Distincts par jour')
                ->color('gray'),

            Stat::make('Demandes reçues', $demandes)
                ->description($this->evolution($demandes, $demandesPrecedentes))
                ->descriptionIcon($this->icone($demandes, $demandesPrecedentes))
                ->color($this->couleur($demandes, $demandesPrecedentes)),

            Stat::make('Taux de conversion', $conversion.' %')
                ->description($this->lectureConversion($conversion))
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

        return ($variation >= 0 ? '+' : '').$variation.' % sur 30 jours';
    }

    private function icone(int $actuel, int $precedent): ?string
    {
        if ($precedent === 0) {
            return null;
        }

        return $actuel >= $precedent
            ? 'heroicon-m-arrow-trending-up'
            : 'heroicon-m-arrow-trending-down';
    }

    private function couleur(int $actuel, int $precedent): string
    {
        if ($precedent === 0) {
            return 'gray';
        }

        return $actuel >= $precedent ? 'success' : 'danger';
    }

    private function lectureConversion(float $taux): string
    {
        return match (true) {
            $taux === 0.0 => 'Pas encore de demande',
            $taux < 1.5 => 'Sous la cible — vérifier les photos et le formulaire',
            $taux < 2.0 => 'Correct, cible 2 à 4 %',
            $taux <= 4.0 => 'Dans la cible',
            default => 'Excellent',
        };
    }
}
