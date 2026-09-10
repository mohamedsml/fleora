<?php

namespace App\Console\Commands;

use App\Mail\ConfirmationDemande;
use App\Mail\NouvelleDemande;
use App\Models\CustomRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Vérifie la configuration SMTP en production, avec un envoi réel.
 *
 * `Mail::fake()` prouve qu'un courriel est parti du code, pas qu'il sort du
 * serveur : identifiants SMTP, port, chiffrement et réputation de l'expéditeur
 * ne se testent qu'en envoyant pour de vrai.
 *
 * L'adresse de destination est obligatoire : aucune valeur par défaut, pour
 * qu'un lancement distrait n'écrive jamais à une cliente.
 */
class TesterCourriel extends Command
{
    protected $signature = 'fleora:tester-courriel
                            {destinataire : Adresse qui recevra le test}
                            {--gabarits : Envoie aussi les deux courriels de demande}';

    protected $description = 'Envoie un courriel de test pour valider la configuration SMTP';

    public function handle(): int
    {
        $destinataire = $this->argument('destinataire');

        if (! filter_var($destinataire, FILTER_VALIDATE_EMAIL)) {
            $this->error("« {$destinataire} » n’est pas une adresse valide.");

            return self::FAILURE;
        }

        $this->afficherConfiguration();

        if (config('mail.default') === 'log') {
            $this->warn('MAIL_MAILER=log : rien ne sortira du serveur, le contenu ira dans storage/logs/laravel.log.');
        }

        try {
            $this->envoyerTestSimple($destinataire);
            $this->info("✓ Courriel de test envoyé à {$destinataire}");

            if ($this->option('gabarits')) {
                $this->envoyerGabarits($destinataire);
                $this->info('✓ Gabarits de demande envoyés');
            }
        } catch (Throwable $e) {
            $this->error('✗ Échec de l’envoi : '.$e->getMessage());
            $this->diagnostiquer($e);

            return self::FAILURE;
        }

        $this->newLine();
        $this->line('Vérifiez la boîte de réception <options=bold>et</> les indésirables.');
        $this->line('Sans SPF ni DKIM sur le domaine, le premier courriel part souvent en spam.');

        return self::SUCCESS;
    }

    private function afficherConfiguration(): void
    {
        $this->table(['Réglage', 'Valeur'], [
            ['mailer', config('mail.default')],
            ['host', config('mail.mailers.smtp.host') ?: '—'],
            ['port', config('mail.mailers.smtp.port') ?: '—'],
            ['scheme', config('mail.mailers.smtp.scheme') ?: 'auto'],
            ['username', config('mail.mailers.smtp.username') ?: '—'],
            // Jamais le mot de passe : cette sortie finit dans des captures
            // d'écran et des journaux.
            ['password', config('mail.mailers.smtp.password') ? '••• défini' : '⚠ vide'],
            ['from', config('mail.from.address') ?: '⚠ vide'],
            ['queue', config('queue.default')],
        ]);
    }

    private function envoyerTestSimple(string $destinataire): void
    {
        $corps = implode("\n", [
            'Test de configuration SMTP — '.config('app.name'),
            '',
            'Si vous lisez ceci, l’envoi de courriels fonctionne.',
            '',
            'Environnement : '.app()->environment(),
            'Serveur SMTP  : '.config('mail.mailers.smtp.host').':'.config('mail.mailers.smtp.port'),
            'Expéditeur    : '.config('mail.from.address'),
            'Horodatage    : '.now()->toDateTimeString().' ('.config('app.timezone').')',
        ]);

        // Envoi direct, hors file d'attente : on veut l'erreur SMTP tout de
        // suite, pas dans une tâche échouée une minute plus tard.
        Mail::raw($corps, function ($message) use ($destinataire) {
            $message->to($destinataire)
                ->subject('['.config('app.name').'] Test SMTP — '.now()->format('H:i'));
        });
    }

    /**
     * Envoie les deux vrais gabarits : c'est le seul moyen de voir leur rendu
     * dans un client de messagerie réel.
     */
    private function envoyerGabarits(string $destinataire): void
    {
        $demande = CustomRequest::with(['occasion', 'productType', 'creationReference', 'attachments'])
            ->latest()
            ->first();

        if (! $demande) {
            $this->warn('Aucune demande en base : gabarits ignorés. Envoyez d’abord une demande depuis le site.');

            return;
        }

        $this->line("Gabarits construits depuis la demande {$demande->numero_suivi}.");

        Mail::to($destinataire)->send(new ConfirmationDemande($demande));
        Mail::to($destinataire)->send(new NouvelleDemande($demande));
    }

    /**
     * Traduit les erreurs SMTP fréquentes en action concrète.
     */
    private function diagnostiquer(Throwable $e): void
    {
        $message = strtolower($e->getMessage());

        $pistes = match (true) {
            str_contains($message, 'authenticat') || str_contains($message, '535') => [
                'Identifiants refusés par le serveur SMTP.',
                'MAIL_USERNAME doit être l’adresse complète (contact@…), pas seulement « contact ».',
                'Vérifiez le mot de passe de la boîte dans hPanel → Courriels.',
            ],
            str_contains($message, 'connection') || str_contains($message, 'timed out') => [
                'Connexion impossible au serveur SMTP.',
                'Port 465 avec MAIL_SCHEME=smtps, ou 587 avec MAIL_SCHEME=tls.',
                'Vérifiez MAIL_HOST (smtp.hostinger.com pour une boîte Hostinger).',
            ],
            str_contains($message, 'rfccompliance') || str_contains($message, 'addr-spec') => [
                'Adresse mal formée.',
                'MAIL_FROM_ADDRESS est probablement vide ou mal orthographiée.',
            ],
            str_contains($message, 'certificate') || str_contains($message, 'ssl') => [
                'Échec de la négociation TLS.',
                'Vérifiez la cohérence entre MAIL_PORT et MAIL_SCHEME.',
            ],
            default => [
                'Après toute modification du .env : php artisan config:clear',
                'Le détail complet est dans storage/logs/laravel.log',
            ],
        };

        $this->newLine();
        $this->line('<options=bold>Pistes :</>');

        foreach ($pistes as $piste) {
            $this->line('  • '.$piste);
        }
    }
}
