<?php

namespace App\Console\Commands;

use App\Models\CustomRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Loi 25 — applique la politique de conservation des renseignements personnels.
 *
 * La politique de confidentialité annonce une conservation limitée. Sans cette
 * commande, ce serait une promesse non tenue : les demandes s'accumuleraient
 * indéfiniment, avec les noms, courriels et téléphones des clientes.
 *
 * Suppression définitive, pièces jointes comprises — un `softDelete` ne
 * satisfait pas une obligation d'effacement.
 */
class PurgerDemandes extends Command
{
    protected $signature = 'fleora:purger-demandes
                            {--essai : Affiche ce qui serait supprimé sans rien supprimer}';

    protected $description = 'Supprime les demandes dont le délai de conservation est écoulé (Loi 25)';

    public function handle(): int
    {
        $mois = config('fleora.conservation.demandes_mois');
        $limite = now()->subMonths($mois);
        $essai = $this->option('essai');

        // On se base sur `updated_at` : c'est la date du dernier contact,
        // pas celle de la demande initiale. Une cliente suivie pendant un an
        // ne doit pas voir ses données purgées en cours de projet.
        $demandes = CustomRequest::withTrashed()
            ->with('attachments')
            ->where('updated_at', '<', $limite)
            ->get();

        if ($demandes->isEmpty()) {
            $this->info("Aucune demande antérieure à {$limite->translatedFormat('j F Y')}.");

            return self::SUCCESS;
        }

        $this->info(
            ($essai ? '[Essai] ' : '')
            ."{$demandes->count()} demande(s) antérieure(s) à {$limite->translatedFormat('j F Y')}."
        );

        if ($essai) {
            foreach ($demandes as $demande) {
                $this->line("  {$demande->numero_suivi} — dernier contact {$demande->updated_at->translatedFormat('j M Y')}");
            }

            return self::SUCCESS;
        }

        $fichiers = 0;

        foreach ($demandes as $demande) {
            foreach ($demande->attachments as $piece) {
                if (Storage::disk('local')->delete($piece->chemin)) {
                    $fichiers++;
                }
            }

            // forceDelete : l'effacement doit être réel.
            $demande->forceDelete();
        }

        $this->info("Supprimé : {$demandes->count()} demande(s), {$fichiers} fichier(s).");

        return self::SUCCESS;
    }
}
