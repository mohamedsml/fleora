<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CourbeVisites;
use App\Filament\Widgets\RepartitionVisites;
use App\Filament\Widgets\ResumeFrequentation;
use App\Models\Visite;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Rapport de fréquentation détaillé.
 *
 * Le tableau de bord garde la synthèse — demandes en cours et quatre chiffres.
 * Cette page répond aux questions qui demandent de creuser : par où les
 * visiteuses arrivent, ce qu'elles consultent, où elles s'arrêtent.
 *
 * Tout passe par des widgets Filament plutôt que par du balisage écrit à la
 * main. Le panneau sert un CSS précompilé qui ne contient que les classes de
 * ses propres composants : des classes Tailwind ajoutées dans une vue ne sont
 * jamais générées, et la page s'affichait sans aucun style.
 */
class RapportVisites extends Page
{
    use HasFiltersForm;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|null|\UnitEnum $navigationGroup = 'Système';

    protected static ?int $navigationSort = 80;

    protected static ?string $title = 'Rapport de visites';

    protected static ?string $navigationLabel = 'Rapport de visites';

    protected string $view = 'filament.pages.rapport-visites';

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('periode')
                ->label('Période analysée')
                ->options([
                    7 => '7 derniers jours',
                    30 => '30 derniers jours',
                    90 => '3 derniers mois',
                    365 => '12 derniers mois',
                ])
                ->default(30)
                ->selectablePlaceholder(false)
                ->native(false),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exporter')
                ->label('Exporter en CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action('exporter'),
        ];
    }

    public function getWidgets(): array
    {
        return [
            ResumeFrequentation::class,
            CourbeVisites::class,
            RepartitionVisites::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 1;
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
        $jours = (int) ($this->filters['periode'] ?? 30);
        $debut = now()->subDays($jours)->startOfDay();
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
}
