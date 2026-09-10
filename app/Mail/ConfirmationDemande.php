<?php

namespace App\Mail;

use App\Models\CustomRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Accusé de réception envoyé à la cliente.
 *
 * Son rôle n'est pas décoratif : sans réponse immédiate, une visiteuse doute
 * que sa demande soit passée et écrit une seconde fois — ou va voir ailleurs.
 * Le courriel confirme la réception, récapitule ce qu'elle a demandé et annonce
 * un délai précis.
 */
class ConfirmationDemande extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CustomRequest $demande) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('courriels.confirmation.sujet', [
                'numero' => $this->demande->numero_suivi,
            ]),
            // Les réponses doivent arriver dans la boîte de l'atelier, pas à
            // l'adresse technique d'envoi.
            replyTo: [config('fleora.contact.courriel')],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.confirmation-demande',
            with: [
                'demande' => $this->demande,
                'delai' => config('fleora.contact.delai_reponse_h'),
            ],
        );
    }
}
