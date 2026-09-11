<?php

namespace Tests\Feature;

use App\Filament\Widgets\CreationsLesPlusConsultees;
use App\Filament\Widgets\PagesLesPlusVues;
use App\Filament\Widgets\SourcesDeTrafic;
use App\Filament\Widgets\StatistiquesVisites;
use App\Models\Creation;
use App\Models\CustomRequest;
use App\Models\User;
use App\Models\Visite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Collecte des visites et tableau de bord.
 */
class StatistiquesTest extends TestCase
{
    use RefreshDatabase;

    /** Agent d'un vrai navigateur : curl est filtré comme robot. */
    private const NAVIGATEUR = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/130.0 Safari/537.36';

    private function visiter(string $url, array $entetes = []): void
    {
        $this->withHeaders(['User-Agent' => self::NAVIGATEUR, ...$entetes])->get($url);
    }

    /**
     * Aucune visite ne doit précéder ces tests.
     *
     * Le middleware écrit dans `terminate()`, donc après l'envoi de la
     * réponse. Un test HTTP sans RefreshDatabase laisse ainsi une ligne
     * définitive en base : c'est exactement ce que faisait le ExampleTest
     * livré par Laravel, dont le trait était commenté. Le symptôme
     * n'apparaissait que sur la suite complète, et ici — loin de sa cause.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame(
            0,
            Visite::count(),
            'Des visites préexistent : un test HTTP tourne sans RefreshDatabase '
            .'et laisse des lignes en base.'
        );
    }

    // ── Collecte ────────────────────────────────────────────────────────

    #[Test]
    public function une_visite_est_enregistree(): void
    {
        $this->visiter('/creations');

        $this->assertSame(1, Visite::count());
        $this->assertSame('/creations', Visite::first()->chemin);
    }

    #[Test]
    public function l_administration_n_est_pas_comptee(): void
    {
        // Compter ses propres visites fausserait tous les chiffres.
        $this->actingAs(User::factory()->create());
        $this->visiter('/admin');

        $this->assertSame(0, Visite::count());
    }

    #[Test]
    public function les_robots_ne_sont_pas_comptes(): void
    {
        $this->withHeaders(['User-Agent' => 'Googlebot/2.1'])->get('/');
        $this->withHeaders(['User-Agent' => 'curl/8.0'])->get('/');

        $this->assertSame(0, Visite::count());
    }

    #[Test]
    public function aucune_donnee_personnelle_n_est_conservee(): void
    {
        // Ni IP, ni témoin, ni identifiant persistant : c'est ce qui dispense
        // de bannière de consentement sous la Loi 25.
        $this->visiter('/');

        $visite = Visite::first();
        $colonnes = array_keys($visite->getAttributes());

        $this->assertNotContains('ip', $colonnes);
        $this->assertNotContains('user_agent', $colonnes);

        // L'empreinte est un hachage, jamais une valeur lisible.
        $this->assertSame(64, strlen($visite->empreinte));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $visite->empreinte);
    }

    #[Test]
    public function la_source_ne_retient_que_le_domaine(): void
    {
        // « instagram.com » suffit à décider où investir ; l'URL exacte
        // n'apprendrait rien de plus.
        $this->visiter('/creations', ['Referer' => 'https://www.instagram.com/p/abc123/']);

        $this->assertSame('instagram.com', Visite::first()->source);
    }

    #[Test]
    public function la_navigation_interne_n_est_pas_une_source(): void
    {
        $this->visiter('/creations', ['Referer' => url('/')]);

        $this->assertNull(Visite::first()->source);
    }

    #[Test]
    public function la_creation_consultee_est_identifiee(): void
    {
        // C'est ce qui dira quelles pièces intéressent réellement.
        $creation = Creation::create([
            'titre_fr' => 'Coffret', 'slug_fr' => 'coffret', 'publie' => true,
        ]);

        $this->visiter('/creations/coffret');

        $visite = Visite::first();
        $this->assertSame('Creation', $visite->entite_type);
        $this->assertSame($creation->id, (int) $visite->entite_id);
    }

    #[Test]
    public function l_appareil_est_distingue(): void
    {
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0) Mobile Safari'])->get('/');

        $this->assertSame('mobile', Visite::first()->appareil);
    }

    #[Test]
    public function la_langue_est_enregistree(): void
    {
        $this->visiter('/en/creations');

        $this->assertSame('en', Visite::first()->langue);
    }

    #[Test]
    public function la_visite_est_horodatee_dans_le_fuseau_de_l_application(): void
    {
        // Défaut trouvé à l'écriture de ces tests : quand la colonne
        // s'horodatait elle-même (`useCurrent()`), MariaDB écrivait en UTC
        // alors que l'application vit en America/Toronto. Les visites
        // atterrissaient quatre heures dans le futur, hors de toutes les
        // bornes `now()` du tableau de bord — qui affichait donc zéro en
        // permanence pendant que la table se remplissait.
        $this->visiter('/');

        $this->assertSame(
            1,
            Visite::entre(now()->subDays(30)->startOfDay(), now())->count(),
            'La visite doit tomber dans la période mesurée par le tableau de bord.'
        );
    }

    // ── Tableau de bord ─────────────────────────────────────────────────

    #[Test]
    public function le_tableau_de_bord_affiche_les_statistiques(): void
    {
        $this->actingAs(User::factory()->create());

        Visite::create([
            'chemin' => '/creations', 'langue' => 'fr',
            'appareil' => 'mobile', 'empreinte' => str_repeat('a', 64),
        ]);

        Livewire::test(StatistiquesVisites::class)
            ->assertSee('Visites')
            ->assertSee('Taux de conversion');
    }

    #[Test]
    public function le_taux_de_conversion_est_calcule(): void
    {
        // Le seul chiffre qui décide vraiment : demandes ÷ visiteurs.
        $this->actingAs(User::factory()->create());

        for ($i = 0; $i < 100; $i++) {
            Visite::create([
                'chemin' => '/', 'langue' => 'fr',
                'empreinte' => hash('sha256', (string) $i),
            ]);
        }

        CustomRequest::create([
            'nom' => 'Sarah', 'courriel' => 'sarah@example.com',
            'consentement' => true, 'consentement_le' => now(),
        ]);

        // 1 demande pour 100 visiteurs = 1 %
        Livewire::test(StatistiquesVisites::class)->assertSee('1 %');
    }

    #[Test]
    public function les_widgets_de_detail_se_chargent(): void
    {
        $this->actingAs(User::factory()->create());

        $creation = Creation::create([
            'titre_fr' => 'Coffret', 'slug_fr' => 'coffret', 'publie' => true,
        ]);

        Visite::create([
            'chemin' => '/creations/coffret', 'langue' => 'fr',
            'source' => 'instagram.com', 'empreinte' => str_repeat('b', 64),
            'entite_type' => 'Creation', 'entite_id' => $creation->id,
        ]);

        Livewire::test(PagesLesPlusVues::class)->assertSee('/creations/coffret');
        Livewire::test(SourcesDeTrafic::class)->assertSee('Instagram');
        Livewire::test(CreationsLesPlusConsultees::class)->assertSee('Coffret');
    }

    // ── Purge ───────────────────────────────────────────────────────────

    #[Test]
    public function la_purge_supprime_les_visites_anciennes(): void
    {
        // Sans purge, la table devient le plus gros objet de la base en un an.
        Visite::create([
            'chemin' => '/', 'langue' => 'fr', 'empreinte' => str_repeat('c', 64),
            'created_at' => now()->subMonths(14),
        ]);

        Visite::create([
            'chemin' => '/', 'langue' => 'fr', 'empreinte' => str_repeat('d', 64),
            'created_at' => now()->subMonths(2),
        ]);

        $this->artisan('fleora:purger-visites')->assertSuccessful();

        $this->assertSame(1, Visite::count());
    }

    #[Test]
    public function le_mode_essai_ne_supprime_rien(): void
    {
        Visite::create([
            'chemin' => '/', 'langue' => 'fr', 'empreinte' => str_repeat('e', 64),
            'created_at' => now()->subMonths(14),
        ]);

        $this->artisan('fleora:purger-visites --essai')->assertSuccessful();

        $this->assertSame(1, Visite::count());
    }
}
