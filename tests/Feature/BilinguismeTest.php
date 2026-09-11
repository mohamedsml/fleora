<?php

namespace Tests\Feature;

use App\Models\Creation;
use App\Models\Occasion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Fondations du bilinguisme.
 *
 * Tout le reste du site en dépend : un routage localisé cassé ne se voit pas
 * immédiatement — les pages françaises continuent de fonctionner — mais la
 * moitié anglaise du site disparaît en silence.
 */
class BilinguismeTest extends TestCase
{
    use RefreshDatabase;

    private function occasion(): Occasion
    {
        return Occasion::create([
            'nom_fr' => 'Mariage', 'slug_fr' => 'mariage',
            'nom_en' => 'Wedding', 'slug_en' => 'wedding',
            'publie' => true,
        ]);
    }

    #[Test]
    public function le_francais_occupe_la_racine(): void
    {
        // La racine porte l'autorité de domaine : elle revient au marché
        // principal, qui est francophone.
        $this->get('/')->assertOk();
        $this->get('/creations')->assertOk();
    }

    #[Test]
    public function l_anglais_est_prefixe(): void
    {
        $this->get('/en')->assertOk();
        $this->get('/en/creations')->assertOk();
    }

    #[Test]
    public function les_segments_d_url_sont_traduits(): void
    {
        // Le mot-clé dans l'URL compte pour le référencement anglophone.
        $this->get('/en/request')->assertOk();
        $this->get('/en/privacy')->assertOk();
        $this->get('/en/about')->assertOk();
        $this->get('/en/thank-you')->assertOk();

        // Les segments français ne répondent pas sous /en.
        $this->get('/en/demande')->assertNotFound();
        $this->get('/en/confidentialite')->assertNotFound();
    }

    #[Test]
    public function la_locale_suit_l_url(): void
    {
        $this->get('/creations')->assertSee('<html lang="fr"', false);
        $this->get('/en/creations')->assertSee('<html lang="en"', false);
    }

    #[Test]
    public function les_slugs_de_contenu_sont_traduits(): void
    {
        $this->occasion();

        $this->get('/occasions/mariage')->assertOk();
        $this->get('/en/occasions/wedding')->assertOk();
    }

    #[Test]
    public function un_slug_non_canonique_redirige_en_301(): void
    {
        // Sans cette redirection, la même page répondrait sur deux URL sous
        // /en/ — et Google indexerait les deux comme du contenu dupliqué.
        $this->occasion();

        $this->get('/en/occasions/mariage')
            ->assertRedirect('/en/occasions/wedding')
            ->assertStatus(301);
    }

    #[Test]
    public function un_contenu_non_traduit_reste_atteignable_en_anglais(): void
    {
        // Une création sans slug_en ne doit pas produire une 404 sous /en :
        // le sélecteur de langue mènerait dans le vide sur la moitié du
        // catalogue.
        Creation::create([
            'titre_fr' => 'Boîte automne',
            'slug_fr' => 'boite-automne',
            'publie' => true,
        ]);

        $this->get('/en/creations/boite-automne')->assertOk();
    }

    #[Test]
    public function les_routes_existent_dans_les_deux_langues(): void
    {
        foreach (['accueil', 'creations', 'occasions', 'demande', 'merci',
            'confidentialite', 'a-propos', 'contact', 'faq'] as $nom) {
            $this->assertTrue(Route::has($nom), "Route française « {$nom} » absente.");
            $this->assertTrue(Route::has("en.{$nom}"), "Route anglaise « en.{$nom} » absente.");
        }
    }

    #[Test]
    public function le_helper_construit_les_url_de_chaque_langue(): void
    {
        $this->assertSame(url('/creations'), route_langue('creations', [], 'fr'));
        $this->assertSame(url('/en/creations'), route_langue('creations', [], 'en'));
    }

    #[Test]
    public function la_langue_par_defaut_du_code_est_le_francais(): void
    {
        // Avec `config:cache` en production et un .env incomplet, un défaut
        // « en » basculerait tout le site en anglais sans erreur visible.
        $defaut = require config_path('app.php');

        $this->assertSame('fr', $defaut['locale']);
        $this->assertSame('fr', $defaut['fallback_locale']);
    }

    #[Test]
    public function une_page_inexistante_renvoie_404_dans_les_deux_langues(): void
    {
        $this->get('/nimporte-quoi')->assertNotFound();
        $this->get('/en/nonsense')->assertNotFound();
    }
}
