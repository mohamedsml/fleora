<?php

namespace App\Mail;

use App\Models\CustomRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notification interne : une demande vient d'arriver.
 *
 * Contient toutes les données de qualification pour décider de la suite sans
 * ouvrir le back-office — occasion, date, budget, quantité. L'objet signale
 * les demandes urgentes, celles dont l'événement est dans moins de 14 jours.
 */
class NouvelleDemande extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CustomRequest $demande) {}

    public function envelope(): Envelope
    {
        $prefixe = $this->demande->estUrgente()
            ? __('courriels.nouvelle.urgent').' '
            : '';

        return new Envelope(
            subject: $prefixe.__('courriels.nouvelle.sujet', [
                'occasion' => $this->demande->occasionLibelle() ?? __('courriels.nouvelle.sans_occasion'),
                'nom' => $this->demande->nom,
            ]),
            // Répondre au courriel répond directement à la cliente.
            // Address explicite : la forme [courriel => nom] est interprétée
            // par Symfony Mailer comme une liste d'adresses, donc « nom »
            // devient une adresse — et lève une RfcComplianceException.
            replyTo: [new Address($this->demande->courriel, $this->demande->nom)],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.nouvelle-demande',
            with: ['demande' => $this->demande],
        );
    }
}
