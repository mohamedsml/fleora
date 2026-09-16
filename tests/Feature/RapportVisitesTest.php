<?php

namespace Tests\Feature;

use App\Enums\StatutDemande;
use App\Filament\Pages\RapportVisites;
use App\Filament\Widgets\CourbeVisites;
use App\Filament\Widgets\DemandesEnCours;
use App\Filament\Widgets\RepartitionVisites;
use App\Filament\Widgets\ResumeFrequentation;
use App\Models\CustomRequest;
use App\Models\User;
use App\Models\Visite;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Rapport de fréquentation détaillé.
 *
 * Tout passe par des widgets Filament : le panneau sert un CSS précompilé qui
 * ne contient que les classes de ses propres composants, et une version
 * antérieure écrivait ses classes Tailwind à la main — elles n'étaient jamais
 * générées, et la page s'affichait en texte brut.
 */
class RapportVisitesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['actif' => true]));
        Filament::setCurrentPanel('admin');
    }

    private function visite(array $attributs = []): Visite
    {
        return Visite::create($attributs + [
            'chemin' => '/',
            'langue' => 'fr',
            'appareil' => 'ordinateur',
            'empreinte' => str_repeat('a', 64),
        ]);
    }

    // ── Page ────────────────────────────────────────────────────────────

    #[Test]
    public function la_page_est_accessible(): void
    {
        $this->get('/admin/rapport-visites')->assertOk();
    }

    #[Test]
    public function la_page_porte_le_filtre_de_periode(): void
    {
        $this->get('/admin/rapport-visites')
            ->assertOk()
            ->assertSee('Période analysée')
            ->assertSee('Exporter en CSV');
    }

    // ── Widgets ─────────────────────────────────────────────────────────

    #[Test]
    public function les_chiffres_cles_s_affichent(): void
    {
        $this->visite();

        Livewire::test(ResumeFrequentation::class, ['pageFilters' => ['periode' => 30]])
            ->assertSee('Visites')
            ->assertSee('Pages par visiteur')
            ->assertSee('Conversion');
    }

    #[Test]
    public function la_courbe_se_rend(): void
    {
        $this->visite();

        Livewire::test(CourbeVisites::class, ['pageFilters' => ['periode' => 30]])
            ->assertSee('Évolution des visites');
    }

    #[Test]
    public function les_repartitions_se_rendent(): void
    {
        $this->visite(['chemin' => '/creations', 'source' => 'instagram.com']);

        Livewire::test(RepartitionVisites::class, ['pageFilters' => ['periode' => 30]])
            ->assertSee('Pages d’entrée')
            ->assertSee('Sources')
            ->assertSee('instagram.com');
    }

    #[Test]
    public function le_filtre_de_periode_atteint_les_widgets(): void
    {
        // Sans transmission du filtre, la page afficherait toujours trente
        // jours quelle que soit la sélection.
        $this->visite(['chemin' => '/recente']);
        $this->visite([
            'chemin' => '/ancienne',
            'empreinte' => str_repeat('b', 64),
            'created_at' => now()->subDays(40),
        ]);

        Livewire::test(RepartitionVisites::class, ['pageFilters' => ['periode' => 7]])
            ->assertSee('/recente')
            ->assertDontSee('/ancienne');

        Livewire::test(RepartitionVisites::class, ['pageFilters' => ['periode' => 90]])
            ->assertSee('/ancienne');
    }

    // ── Calculs ─────────────────────────────────────────────────────────

    #[Test]
    public function les_pages_d_entree_retiennent_la_premiere_vue(): void
    {
        // Une visiteuse arrive sur une fiche création puis va à l'accueil :
        // c'est la fiche qui est la page d'entrée, pas l'accueil.
        $this->visite(['chemin' => '/creations/eclat-de-roses']);
        $this->visite(['chemin' => '/']);

        $this->assertSame(
            ['/creations/eclat-de-roses' => 1],
            Visite::pagesEntree(now()->subDays(30), now()),
        );
    }

    #[Test]
    public function la_profondeur_signale_les_visiteurs_d_une_seule_page(): void
    {
        $this->visite(['chemin' => '/']);
        $this->visite(['chemin' => '/creations']);
        $this->visite(['chemin' => '/', 'empreinte' => str_repeat('c', 64)]);

        $profondeur = Visite::profondeur(now()->subDays(30), now());

        $this->assertSame(2, $profondeur['visiteurs']);
        $this->assertSame(1.5, $profondeur['pages_par_visiteur']);
        $this->assertSame(50.0, (float) $profondeur['une_seule_page']);
    }

    #[Test]
    public function la_courbe_comble_les_jours_sans_visite(): void
    {
        // Une courbe qui saute les creux ment sur la régularité du trafic.
        $this->visite(['created_at' => now()->subDays(5)]);

        $serie = Visite::parJour(now()->subDays(7)->startOfDay(), now());

        // Le modèle ne renvoie que les jours peuplés ; c'est le widget qui
        // comble — on vérifie ici que la donnée brute est bien datée.
        $this->assertArrayHasKey(now()->subDays(5)->format('Y-m-d'), $serie);
    }

    // ── Export ──────────────────────────────────────────────────────────

    #[Test]
    public function l_export_csv_contient_les_visites(): void
    {
        $this->visite(['chemin' => '/creations', 'source' => 'instagram.com']);

        $page = new RapportVisites;
        $page->filters = ['periode' => 30];

        ob_start();
        $page->exporter()->sendContent();
        $csv = ob_get_clean();

        $this->assertStringContainsString('/creations', $csv);
        $this->assertStringContainsString('instagram.com', $csv);
        $this->assertStringContainsString('Page', $csv, 'L’en-tête doit être présent.');

        // BOM UTF-8 : sans lui, Excel affiche « Ã© » à la place des accents.
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
    }

    // ── Tableau de bord ─────────────────────────────────────────────────

    #[Test]
    public function le_tableau_de_bord_montre_les_demandes(): void
    {
        CustomRequest::create([
            'nom' => 'Sarah', 'courriel' => 'sarah@example.com',
            'statut' => StatutDemande::Nouvelle,
            'consentement' => true, 'consentement_le' => now(),
        ]);

        Livewire::test(DemandesEnCours::class)
            ->assertSee('Nouvelles')
            ->assertSee('Sans réponse depuis 24 h');
    }

    #[Test]
    public function les_widgets_du_rapport_ne_sont_pas_sur_le_tableau_de_bord(): void
    {
        // Ils lisent le filtre de période de leur page : posés ailleurs, ils
        // afficheraient toujours trente jours.
        $widgets = array_map(
            fn ($w) => class_basename($w),
            Filament::getPanel('admin')->getWidgets(),
        );

        $this->assertContains('DemandesEnCours', $widgets);
        $this->assertNotContains('ResumeFrequentation', $widgets);
        $this->assertNotContains('CourbeVisites', $widgets);
        $this->assertNotContains('RepartitionVisites', $widgets);
    }
}
