<?php

namespace Tests\Feature;

use App\Enums\StatutDemande;
use App\Filament\Pages\RapportVisites;
use App\Filament\Widgets\DemandesEnCours;
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
 * Le tableau de bord garde la synthèse ; cette page répond aux questions qui
 * demandent de creuser — par où les visiteuses arrivent, ce qu'elles
 * consultent, où elles s'arrêtent.
 */
class RapportVisitesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['actif' => true]));
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

    #[Test]
    public function la_page_est_accessible(): void
    {
        $this->get('/admin/rapport-visites')->assertOk();
    }

    #[Test]
    public function la_page_affiche_les_chiffres_cles(): void
    {
        $this->visite();

        Livewire::test(RapportVisites::class)
            ->assertSee('Visites')
            ->assertSee('Pages par visiteur')
            ->assertSee('Conversion');
    }

    #[Test]
    public function la_periode_filtre_les_donnees(): void
    {
        $this->visite(['chemin' => '/recent']);
        $this->visite([
            'chemin' => '/ancien',
            'empreinte' => str_repeat('b', 64),
            'created_at' => now()->subDays(60),
        ]);

        Livewire::test(RapportVisites::class)
            ->set('periode', 7)
            ->assertSee('/recent')
            ->assertDontSee('/ancien');
    }

    #[Test]
    public function les_pages_d_entree_retiennent_la_premiere_vue(): void
    {
        // Une visiteuse arrive sur une fiche création puis va à l'accueil :
        // c'est la fiche qui est la page d'entrée, pas l'accueil.
        $this->visite(['chemin' => '/creations/eclat-de-roses']);
        $this->visite(['chemin' => '/']);

        $entrees = Visite::pagesEntree(now()->subDays(30), now());

        $this->assertSame(['/creations/eclat-de-roses' => 1], $entrees);
    }

    #[Test]
    public function la_courbe_comble_les_jours_sans_visite(): void
    {
        // Une courbe qui saute les creux ment sur la régularité du trafic.
        $this->visite(['created_at' => now()->subDays(5)]);

        $serie = Livewire::test(RapportVisites::class)
            ->set('periode', 7)
            ->instance()
            ->evolutionQuotidienne();

        $this->assertGreaterThanOrEqual(7, count($serie));
        $this->assertContains(0, $serie, 'Les jours vides doivent valoir zéro.');
    }

    #[Test]
    public function la_profondeur_signale_les_visiteurs_d_une_seule_page(): void
    {
        // Deux pages pour la première empreinte, une seule pour la seconde.
        $this->visite(['chemin' => '/']);
        $this->visite(['chemin' => '/creations']);
        $this->visite(['chemin' => '/', 'empreinte' => str_repeat('c', 64)]);

        $profondeur = Visite::profondeur(now()->subDays(30), now());

        $this->assertSame(2, $profondeur['visiteurs']);
        $this->assertSame(1.5, $profondeur['pages_par_visiteur']);
        $this->assertSame(50.0, (float) $profondeur['une_seule_page']);
    }

    #[Test]
    public function l_export_csv_contient_les_visites(): void
    {
        $this->visite(['chemin' => '/creations', 'source' => 'instagram.com']);

        $reponse = (new RapportVisites)->exporter();

        // Le contenu est produit en flux : on le capture pour le vérifier.
        ob_start();
        $reponse->sendContent();
        $csv = ob_get_clean();

        $this->assertStringContainsString('/creations', $csv);
        $this->assertStringContainsString('instagram.com', $csv);
        $this->assertStringContainsString('Page', $csv, 'L’en-tête doit être présent.');

        // BOM UTF-8 : sans lui, Excel affiche « Ã© » à la place des accents.
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
    }

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
    public function le_tableau_de_bord_ne_porte_plus_le_detail_des_visites(): void
    {
        // Le détail vit sur la page dédiée, où il se filtre par période.
        $widgets = array_map(
            fn ($w) => class_basename($w),
            Filament::getPanel('admin')->getWidgets(),
        );

        $this->assertContains('DemandesEnCours', $widgets);
        $this->assertNotContains('PagesLesPlusVues', $widgets);
        $this->assertNotContains('SourcesDeTrafic', $widgets);
    }
}
