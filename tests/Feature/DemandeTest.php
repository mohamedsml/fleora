<?php

namespace Tests\Feature;

use App\Livewire\FormulaireDemande;
use App\Mail\ConfirmationDemande;
use App\Mail\NouvelleDemande;
use App\Models\Creation;
use App\Models\CustomRequest;
use App\Models\Occasion;
use App\Models\ProductType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DemandeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Storage::fake('local');
        RateLimiter::clear('demande:127.0.0.1');
    }

    /**
     * Remplit l'étape 3 et envoie. `ouvert_a` est reculé de 10 secondes :
     * le formulaire rejette les envois de moins de 3 s comme robotiques.
     */
    private function envoyer(array $donnees = []): Testable
    {
        return Livewire::test(FormulaireDemande::class)
            ->set('ouvert_a', now()->subSeconds(10)->timestamp)
            ->set('nom', 'Sarah Tremblay')
            ->set('courriel', 'sarah@example.com')
            ->set('consentement', true)
            ->set($donnees)
            ->call('envoyer');
    }

    #[Test]
    public function la_page_se_charge(): void
    {
        $this->get('/demande')->assertOk()->assertSee(__('demande.titre'));
    }

    #[Test]
    public function il_enregistre_la_demande_et_envoie_deux_courriels(): void
    {
        $this->envoyer()->assertRedirect(route('merci'));

        $demande = CustomRequest::firstWhere('courriel', 'sarah@example.com');

        $this->assertNotNull($demande);
        $this->assertSame('Sarah Tremblay', $demande->nom);

        // Confirmation à la cliente : sans réponse immédiate, elle doute que
        // sa demande soit passée.
        Mail::assertQueued(ConfirmationDemande::class, fn ($mail) => $mail->hasTo('sarah@example.com'));

        // Notification à l'atelier.
        Mail::assertQueued(NouvelleDemande::class);
    }

    #[Test]
    public function il_attribue_un_numero_de_suivi(): void
    {
        // Communiqué à la cliente : il doit exister dès la création.
        $this->envoyer();

        $demande = CustomRequest::firstWhere('courriel', 'sarah@example.com');

        $this->assertMatchesRegularExpression('/^FL-\d{4}-[A-Z0-9]{4}$/', $demande->numero_suivi);
    }

    #[Test]
    public function il_horodate_le_consentement(): void
    {
        // Loi 25 : c'est l'horodatage qui est opposable, pas le booléen.
        $this->envoyer();

        $demande = CustomRequest::firstWhere('courriel', 'sarah@example.com');

        $this->assertTrue($demande->consentement);
        $this->assertNotNull($demande->consentement_le);
    }

    #[Test]
    public function il_refuse_sans_consentement(): void
    {
        Livewire::test(FormulaireDemande::class)
            ->set('ouvert_a', now()->subSeconds(10)->timestamp)
            ->set('nom', 'Sarah')
            ->set('courriel', 'sarah@example.com')
            ->set('consentement', false)
            ->call('envoyer')
            ->assertHasErrors('consentement');

        $this->assertSame(0, CustomRequest::count());
    }

    #[Test]
    public function il_refuse_un_courriel_invalide(): void
    {
        $this->envoyer(['courriel' => 'pas-un-courriel'])
            ->assertHasErrors('courriel');

        $this->assertSame(0, CustomRequest::count());
    }

    #[Test]
    public function l_infolettre_n_est_jamais_pre_cochee(): void
    {
        // Exigence légale, pas une préférence d'interface.
        Livewire::test(FormulaireDemande::class)->assertSet('infolettre', false);
    }

    #[Test]
    public function il_valide_etape_par_etape(): void
    {
        // Valider l'étape 3 alors que la visiteuse est à l'étape 1 afficherait
        // des erreurs sur des champs qu'elle n'a pas encore vus.
        Livewire::test(FormulaireDemande::class)
            ->assertSet('etape', 1)
            ->call('suivant')
            ->assertHasNoErrors()
            ->assertSet('etape', 2)
            ->call('suivant')
            ->assertSet('etape', 3);
    }

    #[Test]
    public function il_refuse_une_date_passee(): void
    {
        Livewire::test(FormulaireDemande::class)
            ->set('date_evenement', now()->subWeek()->toDateString())
            ->call('suivant')
            ->assertHasErrors('date_evenement')
            ->assertSet('etape', 1);
    }

    #[Test]
    public function il_signale_un_delai_serre(): void
    {
        // Mieux vaut annoncer le délai serré tout de suite que décevoir après.
        $composant = Livewire::test(FormulaireDemande::class)
            ->set('date_evenement', now()->addDays(5)->toDateString());

        $this->assertTrue($composant->instance()->delaiServe);

        $composant->set('date_evenement', now()->addMonths(3)->toDateString());
        $this->assertFalse($composant->instance()->delaiServe);
    }

    #[Test]
    public function il_pre_remplit_depuis_une_creation(): void
    {
        // Un clic sur « je veux quelque chose comme ça » supprime une friction.
        $occasion = Occasion::create(['nom_fr' => 'Mariage', 'slug_fr' => 'mariage', 'publie' => true]);
        $type = ProductType::create(['nom_fr' => 'Lot invités', 'slug_fr' => 'lot', 'publie' => true]);

        $creation = Creation::create([
            'titre_fr' => 'Coffret Éloïse',
            'slug_fr' => 'coffret-eloise',
            'publie' => true,
            'product_type_id' => $type->id,
            'couleurs' => ['blush', 'or'],
        ]);
        $creation->occasions()->attach($occasion);

        Livewire::test(FormulaireDemande::class, ['creation' => 'coffret-eloise'])
            ->assertSet('product_type_id', $type->id)
            ->assertSet('occasion_id', $occasion->id)
            ->assertSet('couleurs', ['blush', 'or']);
    }

    #[Test]
    public function il_lie_la_creation_de_reference(): void
    {
        // C'est ce lien qui dira quelles créations génèrent des demandes.
        $creation = Creation::create([
            'titre_fr' => 'Coffret', 'slug_fr' => 'coffret', 'publie' => true,
        ]);

        Livewire::test(FormulaireDemande::class, ['creation' => 'coffret'])
            ->set('ouvert_a', now()->subSeconds(10)->timestamp)
            ->set('nom', 'Sarah')
            ->set('courriel', 'sarah@example.com')
            ->set('consentement', true)
            ->call('envoyer');

        $this->assertSame(
            $creation->id,
            CustomRequest::first()->creation_reference_id
        );
    }

    #[Test]
    public function il_rejette_le_champ_leurre(): void
    {
        // Un humain ne remplit jamais un champ masqué en CSS.
        $this->envoyer(['site_web' => 'http://spam.example'])
            ->assertRedirect(route('merci'));

        // Redirigé comme un humain — on ne prévient pas le bot — mais rien
        // n'est enregistré et aucun courriel ne part.
        $this->assertSame(0, CustomRequest::count());
        Mail::assertNothingQueued();
    }

    #[Test]
    public function il_rejette_un_envoi_trop_rapide(): void
    {
        // Moins de 3 secondes entre l'ouverture et l'envoi : c'est un script.
        Livewire::test(FormulaireDemande::class)
            ->set('nom', 'Bot')
            ->set('courriel', 'bot@example.com')
            ->set('consentement', true)
            ->call('envoyer');

        $this->assertSame(0, CustomRequest::count());
        Mail::assertNothingQueued();
    }

    #[Test]
    public function il_limite_le_nombre_de_demandes_par_heure(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $this->envoyer(['courriel' => "cliente{$i}@example.com"]);
        }

        $this->assertSame(3, CustomRequest::count());

        // La quatrième est bloquée.
        $this->envoyer(['courriel' => 'quatrieme@example.com'])
            ->assertHasErrors('courriel');

        $this->assertSame(3, CustomRequest::count());
    }

    #[Test]
    public function il_joint_les_images_d_inspiration(): void
    {
        $this->envoyer([
            'inspirations' => [
                UploadedFile::fake()->image('inspiration.jpg', 800, 1000),
            ],
        ]);

        $demande = CustomRequest::first();

        $this->assertCount(1, $demande->attachments);
        // Stockage sur le disque `local`, hors de la racine web : ce sont des
        // fichiers envoyés par des tiers.
        Storage::disk('local')->assertExists($demande->attachments->first()->chemin);
    }

    #[Test]
    public function la_page_merci_affiche_le_numero(): void
    {
        $this->envoyer();

        $numero = CustomRequest::first()->numero_suivi;

        $this->withSession(['demande_numero' => $numero])
            ->get('/merci')
            ->assertOk()
            ->assertSee($numero);
    }

    #[Test]
    public function la_page_merci_reste_valide_sans_session(): void
    {
        // Arriver sur /merci sans avoir envoyé de demande ne doit rien inventer.
        $this->get('/merci')->assertOk();
    }

    #[Test]
    public function il_enregistre_l_ip_et_la_langue(): void
    {
        // Utiles pour l'anti-spam et pour répondre dans la bonne langue.
        $this->envoyer();

        $demande = CustomRequest::first();

        $this->assertNotNull($demande->ip);
        $this->assertSame('fr', $demande->langue);
    }
}
