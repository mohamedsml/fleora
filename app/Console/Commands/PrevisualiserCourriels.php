<?php

namespace App\Console\Commands;

use App\Mail\ConfirmationDemande;
use App\Mail\NouvelleDemande;
use App\Models\CustomRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Envoie les deux courriels de demande avec la dernière demande reçue, ou des
 * données d'exemple.
 *
 * `Mail::fake()` vérifie qu'un courriel est parti, pas qu'il est lisible. Un
 * gabarit peut passer tous les tests et rester illisible dans une vraie boîte
 * — d'où cette commande, à lancer avant toute mise en ligne.
 *
 * Réservée au développement : elle enverrait de vrais courriels en production.
 */
class PrevisualiserCourriels extends Command
{
    protected $signature = 'fleora:previsualiser-courriels
                            {--a= : Adresse de destination (défaut : celle du .env)}';

    protected $description = 'Envoie les courriels de demande pour en vérifier le rendu';

    public function handle(): int
    {
        if (! app()->environment('local')) {
            $this->error('Commande réservée à l’environnement local : elle envoie de vrais courriels.');

            return self::FAILURE;
        }

        $destinataire = $this->option('a') ?? config('fleora.contact.courriel');

        $demande = CustomRequest::with(['occasion', 'productType', 'creationReference', 'attachments'])
            ->latest()
            ->first();

        if (! $demande) {
            $this->warn('Aucune demande en base — création d’un exemple non enregistré.');
            $demande = $this->exemple();
        } else {
            $this->line("Demande utilisée : {$demande->numero_suivi}");
        }

        Mail::to($destinataire)->send(new ConfirmationDemande($demande));
        Mail::to($destinataire)->send(new NouvelleDemande($demande));

        $this->info("Deux courriels envoyés à {$destinataire}.");

        if (config('mail.default') === 'log') {
            $this->line('MAIL_MAILER=log : le contenu est dans storage/logs/laravel.log');
        }

        return self::SUCCESS;
    }

    /**
     * Demande d'exemple complète, jamais enregistrée : `make()` sans `save()`.
     */
    private function exemple(): CustomRequest
    {
        $demande = new CustomRequest([
            'numero_suivi' => 'FL-'.now()->format('ym').'-DEMO',
            'nom' => 'Sarah Tremblay',
            'courriel' => 'sarah.tremblay@example.com',
            'telephone' => '(514) 555-1234',
            'ville' => 'Laval',
            'moyen_prefere' => 'texto',
            'source' => 'instagram',
            'occasion_autre' => 'Baby shower',
            'date_evenement' => now()->addDays(18),
            'quantite' => '6-15',
            'texte_a_inscrire' => 'Éloïse',
            'couleurs' => ['blush', 'ivoire', 'or'],
            'theme' => 'boheme',
            'fleurs' => 'artificielles',
            'budget' => '150-300',
            'commentaires' => "Le prénom en doré si possible.\nLivraison à Laval le matin de l'événement.",
            'consentement' => true,
            'consentement_le' => now(),
            'langue' => 'fr',
        ]);

        // Les gabarits parcourent ces relations : sans elles, le rendu échoue.
        $demande->setRelation('attachments', collect());
        $demande->setRelation('occasion', null);
        $demande->setRelation('productType', null);
        $demande->setRelation('creationReference', null);

        return $demande;
    }
}
