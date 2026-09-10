<?php

namespace Tests\Feature;

use App\Enums\StatutDemande;
use App\Filament\Resources\CustomRequests\CustomRequestResource;
use App\Filament\Resources\CustomRequests\Pages\EditCustomRequest;
use App\Filament\Resources\CustomRequests\Pages\ListCustomRequests;
use App\Models\CustomRequest;
use App\Models\Occasion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DemandesBackOfficeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    private function demande(array $attributs = []): CustomRequest
    {
        return CustomRequest::create([
            'nom' => 'Sarah Tremblay',
            'courriel' => 'sarah@example.com',
            'consentement' => true,
            'consentement_le' => now(),
            ...$attributs,
        ]);
    }

    #[Test]
    public function la_liste_se_charge(): void
    {
        $this->demande();

        $this->get('/admin/custom-requests')
            ->assertOk()
            ->assertSee('Sarah Tremblay');
    }

    #[Test]
    public function la_fiche_affiche_la_demande(): void
    {
        $demande = $this->demande(['telephone' => '(514) 555-1234']);

        $this->get("/admin/custom-requests/{$demande->id}")
            ->assertOk()
            ->assertSee($demande->numero_suivi)
            ->assertSee('sarah@example.com');
    }

    #[Test]
    public function les_demandes_sans_reponse_arrivent_en_premier(): void
    {
        // Ouvrir le back-office doit répondre à « qu'est-ce qui m'attend ? »
        // sans avoir à composer un filtre.
        $this->demande(['nom' => 'Deja traitee', 'repondu_le' => now()]);
        $this->demande(['nom' => 'Sans reponse']);

        Livewire::test(ListCustomRequests::class)
            ->assertSeeInOrder(['Sans reponse', 'Deja traitee']);
    }

    #[Test]
    public function changer_le_statut_horodate_la_reponse(): void
    {
        // Sans automatisme, ce champ resterait vide : personne ne pense à
        // renseigner une date en traitant une demande. Or c'est lui qui
        // alimente l'onglet « À traiter » et le compteur de navigation.
        $demande = $this->demande();

        $this->assertNull($demande->repondu_le);

        Livewire::test(EditCustomRequest::class, ['record' => $demande->id])
            ->fillForm(['statut' => StatutDemande::EnCours->value])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNotNull($demande->fresh()->repondu_le);
    }

    #[Test]
    public function rester_en_nouvelle_n_horodate_pas(): void
    {
        $demande = $this->demande();

        Livewire::test(EditCustomRequest::class, ['record' => $demande->id])
            ->fillForm(['statut' => StatutDemande::Nouvelle->value])
            ->call('save');

        $this->assertNull($demande->fresh()->repondu_le);
    }

    #[Test]
    public function les_donnees_de_la_cliente_ne_sont_pas_modifiables(): void
    {
        // Ce que la cliente a écrit est une déclaration horodatée, pas un
        // brouillon interne : le formulaire n'expose que le suivi commercial.
        $demande = $this->demande(['budget' => '150-300']);

        Livewire::test(EditCustomRequest::class, ['record' => $demande->id])
            ->assertFormFieldDoesNotExist('budget')
            ->assertFormFieldDoesNotExist('courriel')
            ->assertFormFieldExists('statut')
            ->assertFormFieldExists('notes_internes');
    }

    #[Test]
    public function le_compteur_de_navigation_suit_les_demandes_sans_reponse(): void
    {
        $this->demande();
        $this->demande(['nom' => 'Autre']);
        $this->demande(['nom' => 'Traitee', 'repondu_le' => now()]);

        $this->assertSame(
            '2',
            CustomRequestResource::getNavigationBadge()
        );
    }

    #[Test]
    public function le_compteur_passe_au_rouge_si_une_demande_est_urgente(): void
    {
        $this->demande(['date_evenement' => now()->addDays(5)]);

        $this->assertSame(
            'danger',
            CustomRequestResource::getNavigationBadgeColor()
        );
    }

    #[Test]
    public function une_demande_supprimee_reste_consultable(): void
    {
        // softDeletes sur le modèle ne sert à rien si l'interface les cache
        // définitivement.
        $demande = $this->demande();
        $demande->delete();

        $this->get("/admin/custom-requests/{$demande->id}")->assertOk();
    }

    #[Test]
    public function on_ne_peut_pas_creer_une_demande_a_la_main(): void
    {
        // Une demande vient toujours du formulaire public : en saisir une
        // produirait un consentement Loi 25 non horodaté.
        $this->get('/admin/custom-requests/create')->assertNotFound();
    }

    #[Test]
    public function la_liste_affiche_l_occasion_saisie_en_texte_libre(): void
    {
        // Le cas « Autre » : l'occasion n'est pas liée, elle est en clair.
        $this->demande(['occasion_autre' => 'Départ à la retraite']);

        Livewire::test(ListCustomRequests::class)
            ->assertSee('Départ à la retraite');
    }
}
