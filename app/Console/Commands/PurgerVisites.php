<?php

namespace App\Console\Commands;

use App\Models\Visite;
use Illuminate\Console\Command;

/**
 * Applique la durée de conservation des statistiques.
 *
 * Sur Cloud Startup, l'espace et les inodes sont partagés entre tous les sites
 * du compte. Une table de visites sans purge devient le plus gros objet de la
 * base en une année — pour des données dont personne ne consulte le détail
 * au-delà de quelques mois.
 */
class PurgerVisites extends Command
{
    protected $signature = 'fleora:purger-visites
                            {--essai : Affiche ce qui serait supprimé sans rien supprimer}';

    protected $description = 'Supprime les visites au-delà de la durée de conservation';

    public function handle(): int
    {
        $mois = config('fleora.conservation.visites_mois', 12);
        $limite = now()->subMonths($mois);

        $nombre = Visite::where('created_at', '<', $limite)->count();

        if ($nombre === 0) {
            $this->info("Aucune visite antérieure à {$limite->translatedFormat('j F Y')}.");

            return self::SUCCESS;
        }

        if ($this->option('essai')) {
            $this->info("[Essai] {$nombre} visite(s) seraient supprimées.");

            return self::SUCCESS;
        }

        // Par lots : une suppression de plusieurs centaines de milliers de
        // lignes d'un coup verrouille la table et peut dépasser le temps
        // d'exécution alloué sur mutualisé.
        $supprimees = 0;

        do {
            $lot = Visite::where('created_at', '<', $limite)->limit(5000)->delete();
            $supprimees += $lot;
        } while ($lot > 0);

        $this->info("{$supprimees} visite(s) supprimée(s).");

        return self::SUCCESS;
    }
}
