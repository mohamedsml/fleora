<?php

namespace Tests\Feature;

use App\Mail\ConfirmationDemande;
use App\Mail\NouvelleDemande;
use App\Models\CustomRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Délivrabilité des courriels transactionnels.
 *
 * Un rapport mail-tester a montré 6 % de texte pour 16 Ko de HTML : sans
 * version texte explicite, Laravel dérive le text/plain du Markdown en y
 * laissant le balisage. Les filtres lisent ce ratio comme un signal de
 * pourriel — et un courriel de confirmation qui n'arrive pas coûte une
 * cliente.
 */
class CourrielsDelivrabiliteTest extends TestCase
{
    use RefreshDatabase;

    private function demande(): CustomRequest
    {
        return CustomRequest::create([
            'nom' => 'Sarah Tremblay',
            'courriel' => 'sarah@example.com',
            'ville' => 'Laval',
            'texte_a_inscrire' => 'Félicitations',
            'consentement' => true,
            'consentement_le' => now(),
        ]);
    }

    #[Test]
    public function la_confirmation_porte_une_version_texte(): void
    {
        $courriel = new ConfirmationDemande($this->demande());

        $this->assertSame(
            'mail.confirmation-demande-texte',
            $courriel->content()->text,
        );
    }

    #[Test]
    public function la_notification_porte_une_version_texte(): void
    {
        $courriel = new NouvelleDemande($this->demande());

        $this->assertSame(
            'mail.nouvelle-demande-texte',
            $courriel->content()->text,
        );
    }

    #[Test]
    public function la_version_texte_de_la_confirmation_se_rend(): void
    {
        // Un gabarit texte qui lève une exception casserait tout l'envoi, y
        // compris la version HTML.
        $demande = $this->demande();
        $rendu = (new ConfirmationDemande($demande))->render();

        $this->assertNotEmpty($rendu);
    }

    #[Test]
    public function la_version_texte_contient_le_numero_de_suivi(): void
    {
        // Le numéro est ce que la cliente citera en répondant : il doit
        // survivre à un client de messagerie qui n'affiche que le texte.
        $demande = $this->demande();

        $texte = view('mail.confirmation-demande-texte', [
            'demande' => $demande,
            'delai' => 24,
        ])->render();

        $this->assertStringContainsString($demande->numero_suivi, $texte);
        $this->assertStringContainsString('Sarah', $texte);
    }

    #[Test]
    public function la_version_texte_ne_contient_aucun_balisage(): void
    {
        // C'est tout l'objet de la correction : ni HTML, ni Markdown résiduel.
        $texte = view('mail.confirmation-demande-texte', [
            'demande' => $this->demande(),
            'delai' => 24,
        ])->render();

        $this->assertStringNotContainsString('<', $texte);
        $this->assertStringNotContainsString('**', $texte);
        $this->assertStringNotContainsString('x-mail::', $texte);
    }

    #[Test]
    public function la_notification_interne_liste_les_champs_du_projet(): void
    {
        $demande = $this->demande();

        $texte = view('mail.nouvelle-demande-texte', ['demande' => $demande])->render();

        $this->assertStringContainsString('sarah@example.com', $texte);
        $this->assertStringContainsString('Félicitations', $texte);
        // Le tableau Markdown de la version HTML deviendrait illisible aplati.
        $this->assertStringNotContainsString('|---|', $texte);
    }

    #[Test]
    public function la_confirmation_repond_a_l_adresse_de_l_atelier(): void
    {
        // Un Reply-To sur un autre domaine que l'expéditeur déclenche
        // FREEMAIL_FORGED_REPLYTO chez SpamAssassin (-2,5 points).
        $courriel = new ConfirmationDemande($this->demande());

        $adresses = collect($courriel->envelope()->replyTo)
            ->map(fn ($a) => is_string($a) ? $a : $a->address);

        $this->assertSame([config('fleora.contact.courriel')], $adresses->all());
    }
}
