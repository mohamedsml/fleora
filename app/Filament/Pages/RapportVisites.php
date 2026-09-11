<?php

namespace App\Filament\Pages;

use App\Models\CustomRequest;
use App\Models\Visite;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Rapport de fréquentation détaillé.
 *
 * Le tableau de bord garde la synthèse — quatre chiffres et les demandes en
 * cours. Cette page répond aux questions qui demandent de creuser : par où
 * les visiteuses arrivent, ce qu'elles consultent ensuite, et quelles pages
 * les laissent repartir.
 *
 * Les calculs vivent dans le modèle Visite : une page Filament qui porte ses
 * propres requêtes devient vite illisible, et les mêmes chiffres seraient
 * recalculés différemment ailleurs.
 */
class RapportVisites extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|null|\UnitEnum $navigationGroup = 'Système';

    protected static ?int $navigationSort = 80;

    protected static ?string $title = 'Rapport de visites';

    protected static ?string $navigationLabel = 'Rapport de visites';

    protected string $view = 'filament.pages.rapport-visites';

    /** Nombre de jours analysés. Les valeurs proposées couvrent les usages réels. */
    public int $periode = 30;

    /** @var array<int, string> */
    public array $periodes = [
        7 => '7 derniers jours',
        30 => '30 derniers jours',
        90 => '3 derniers mois',
        365 => '12 derniers mois',
    ];

    public function debut(): Carbon
    {
        return now()->subDays($this->periode)->startOfDay();
    }

    /**
     * Chiffres d'en-tête, comparés à la période précédente de même durée.
     *
     * @return array<string, array{valeur: string, evolution: ?float, aide: string}>
     */
    public function resume(): array
    {
        $debut = $this->debut();
        $precedent = now()->subDays($this->periode * 2)->startOfDay();

        $visites = Visite::query()->entre($debut, now())->count();
        $visitesAvant = Visite::query()->entre($precedent, $debut)->count();

        $profondeur = Visite::profondeur($debut, now());
        $demandes = CustomRequest::whereBetween('created_at', [$debut, now()])->count();

        return [
            'Visites' => [
                'valeur' => number_format($visites, 0, ',', ' '),
                'evolution' => $this->evolution($visites, $visitesAvant),
                'aide' => 'Pages vues sur la période',
            ],
            'Visiteurs' => [
                'valeur' => number_format($profondeur['visiteurs'], 0, ',', ' '),
                'evolution' => null,
                'aide' => 'Distincts par jour',
            ],
            'Pages par visiteur' => [
                'valeur' => (string) $profondeur['pages_par_visiteur'],
                'evolution' => null,
                // Sous 2, les visiteuses repartent après la première page.
                'aide' => $profondeur['pages_par_visiteur'] < 2
                    ? 'Faible — la page d’entrée retient peu'
                    : 'Bon signe d’intérêt',
            ],
            'Une seule page' => [
                'valeur' => $profondeur['une_seule_page'].' %',
                'evolution' => null,
                'aide' => 'Reparties sans aller plus loin',
            ],
            'Demandes' => [
                'valeur' => (string) $demandes,
                'evolution' => null,
                'aide' => 'Formulaires envoyés',
            ],
            'Conversion' => [
                'valeur' => $profondeur['visiteurs'] > 0
                    ? round($demandes / $profondeur['visiteurs'] * 100, 1).' %'
                    : '—',
                'evolution' => null,
                'aide' => 'Cible : 2 à 4 %',
            ],
        ];
    }

    /**
     * Courbe des visites, un point par jour.
     *
     * Les jours sans visite valent zéro plutôt que d'être absents : une courbe
     * qui saute les creux ment sur la régularité du trafic.
     *
     * @return array<string, int>
     */
    public function evolutionQuotidienne(): array
    {
        $brut = Visite::parJour($this->debut(), now());
        $serie = [];

        for ($jour = $this->debut()->copy(); $jour <= now(); $jour->addDay()) {
            $cle = $jour->format('Y-m-d');
            $serie[$cle] = $brut[$cle] ?? 0;
        }

        return $serie;
    }

    /** @return array<string, int> */
    public function pagesEntree(): array
    {
        return Visite::pagesEntree($this->debut(), now());
    }

    /** @return array<string, int> */
    public function pagesVues(): array
    {
        return Visite::repartition('chemin', $this->debut(), now(), 15);
    }

    /** @return array<string, int> */
    public function sources(): array
    {
        return Visite::repartition('source', $this->debut(), now(), 10);
    }

    /** @return array<string, int> */
    public function appareils(): array
    {
        return Visite::repartition('appareil', $this->debut(), now());
    }

    /** @return array<string, int> */
    public function langues(): array
    {
        return Visite::repartition('langue', $this->debut(), now());
    }

    /**
     * Export CSV de la période affichée.
     *
     * En flux plutôt qu'en mémoire : douze mois de visites peuvent représenter
     * des dizaines de milliers de lignes, et l'hébergement mutualisé limite la
     * mémoire par processus.
     */
    public function exporter(): StreamedResponse
    {
        $debut = $this->debut();
        $nom = 'visites-'.$debut->format('Y-m-d').'-au-'.now()->format('Y-m-d').'.csv';

        return Response::streamDownload(function () use ($debut) {
            $sortie = fopen('php://output', 'w');

            // BOM UTF-8 : sans lui, Excel affiche « Ã© » à la place des accents.
            fwrite($sortie, "\xEF\xBB\xBF");

            fputcsv($sortie, ['Date', 'Heure', 'Page', 'Langue', 'Source', 'Appareil', 'Type', 'Élément']);

            Visite::query()
                ->entre($debut, now())
                ->orderBy('created_at')
                ->chunk(1000, function ($lot) use ($sortie) {
                    foreach ($lot as $visite) {
                        fputcsv($sortie, [
                            $visite->created_at?->format('Y-m-d'),
                            $visite->created_at?->format('H:i'),
                            $visite->chemin,
                            $visite->langue,
                            $visite->source ?? 'direct',
                            $visite->appareil,
                            $visite->entite_type,
                            $visite->entite_id,
                        ]);
                    }
                });

            fclose($sortie);
        }, $nom, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function evolution(int $actuel, int $precedent): ?float
    {
        if ($precedent === 0) {
            return null;
        }

        return round(($actuel - $precedent) / $precedent * 100);
    }
}
