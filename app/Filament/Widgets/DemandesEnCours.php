<?php

namespace App\Filament\Widgets;

use App\Enums\StatutDemande;
use App\Models\CustomRequest;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * État des demandes clientes.
 *
 * C'est ce qu'on vient voir en ouvrant l'administration : ce qui attend une
 * réponse, et depuis combien de temps. Les statistiques de fréquentation
 * répondent à « comment va le site » ; ce widget répond à « qu'est-ce que je
 * dois faire aujourd'hui ».
 */
class DemandesEnCours extends StatsOverviewWidget
{
    protected ?string $heading = 'Demandes clientes';

    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        $nouvelles = CustomRequest::where('statut', StatutDemande::Nouvelle)->count();
        $actives = CustomRequest::whereIn('statut', StatutDemande::actives())->count();

        // Une demande sans réponse depuis plus de 24 h contredit la promesse
        // affichée sur le site — c'est l'alerte la plus utile du tableau.
        $enRetard = CustomRequest::where('statut', StatutDemande::Nouvelle)
            ->where('created_at', '<', now()->subDay())
            ->count();

        $semaine = CustomRequest::where('created_at', '>=', now()->subDays(7))->count();

        return [
            Stat::make('Nouvelles', $nouvelles)
                ->description($nouvelles > 0 ? 'À traiter' : 'Rien en attente')
                ->descriptionIcon($nouvelles > 0 ? 'heroicon-m-inbox-arrow-down' : 'heroicon-m-check-circle')
                ->color($nouvelles > 0 ? 'warning' : 'success')
                ->url(route('filament.admin.resources.custom-requests.index')),

            Stat::make('Sans réponse depuis 24 h', $enRetard)
                ->description($enRetard > 0
                    ? 'La promesse du site est de 24 h'
                    : 'Aucun retard')
                ->descriptionIcon($enRetard > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($enRetard > 0 ? 'danger' : 'success'),

            Stat::make('En cours', $actives)
                ->description('Toutes étapes confondues')
                ->color('gray'),

            Stat::make('Reçues cette semaine', $semaine)
                ->description('7 derniers jours')
                ->color('gray'),
        ];
    }
}
