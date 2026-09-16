<?php

namespace App\Console\Commands;

use App\Models\Visite;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Retire des statistiques les visites qui ne viennent pas de vraies clientes.
 *
 *     php artisan fleora:nettoyer-visites --essai   # montre sans supprimer
 *     php artisan fleora:nettoyer-visites
 *
 * Deux cas sont visés, tous deux constatés en production :
 *
 * 1. Les balayages automatiques. Deux empreintes ont enregistré 141 pages en
 *    90 secondes chacune en se présentant comme un navigateur ordinaire —
 *    282 des 369 visites d'une seule journée. La détection par nom d'agent ne
 *    les voyait pas ; c'est le rythme qui les trahit.
 *
 * 2. Les vérifications faites depuis le poste du propriétaire avant que
 *    l'exclusion par IP n'existe. Elles ne sont plus identifiables par
 *    adresse — l'empreinte est irréversible — mais partagent le même rythme
 *    anormal.
 */
class NettoyerVisites extends Command
{
    protected $signature = 'fleora:nettoyer-visites
                            {--essai : Affiche ce qui serait supprimé, sans rien écrire}
                            {--seuil=30 : Pages par minute au-delà desquelles une empreinte est écartée}';

    protected $description = 'Retire les visites issues de balayages automatiques';

    public function handle(): int
    {
        $seuil = (int) $this->option('seuil');
        $essai = (bool) $this->option('essai');

        $suspectes = $this->empreintesSuspectes($seuil);

        if ($suspectes->isEmpty()) {
            $this->components->info('Aucune empreinte au rythme anormal.');

            return self::SUCCESS;
        }

        $this->table(
            ['Empreinte', 'Visites', 'Durée', 'Pages/minute'],
            $suspectes->map(fn ($e) => [
                substr($e->empreinte, 0, 12).'…',
                $e->visites,
                $e->minutes < 1 ? 'moins d’une minute' : round($e->minutes).' min',
                round($e->cadence),
            ])->all(),
        );

        $total = $suspectes->sum('visites');

        if ($essai) {
            $this->components->warn("{$total} visite(s) seraient supprimées. Relancer sans --essai.");

            return self::SUCCESS;
        }

        if (! $this->option('no-interaction')
            && ! $this->confirm("Supprimer ces {$total} visite(s) ?", false)) {
            $this->line('Annulé.');

            return self::SUCCESS;
        }

        $supprimees = Visite::whereIn('empreinte', $suspectes->pluck('empreinte'))->delete();

        $this->components->info("{$supprimees} visite(s) supprimées.");
        $this->line('  Restant : '.Visite::count().' visite(s)');

        return self::SUCCESS;
    }

    /**
     * Empreintes dont le rythme de consultation dépasse le seuil.
     *
     * On raisonne par empreinte et non par visite isolée : c'est la séquence
     * entière d'un balayage qu'il faut retirer, pas seulement ses pages les
     * plus rapprochées.
     */
    private function empreintesSuspectes(int $seuil): Collection
    {
        return Visite::query()
            ->selectRaw('empreinte, COUNT(*) as visites')
            ->selectRaw('TIMESTAMPDIFF(SECOND, MIN(created_at), MAX(created_at)) / 60 as minutes')
            ->groupBy('empreinte')
            // Sous dix pages, même très rapprochées, c'est une navigation
            // plausible — quelqu'un qui ouvre plusieurs créations d'affilée.
            ->havingRaw('COUNT(*) >= 10')
            ->get()
            ->map(function ($ligne) {
                // Une séquence instantanée donnerait une division par zéro :
                // on plancher à une seconde, ce qui la classe forcément.
                $minutes = max((float) $ligne->minutes, 1 / 60);
                $ligne->cadence = $ligne->visites / $minutes;

                return $ligne;
            })
            ->filter(fn ($ligne) => $ligne->cadence > $seuil)
            ->sortByDesc('visites')
            ->values();
    }
}
