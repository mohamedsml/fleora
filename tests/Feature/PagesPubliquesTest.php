<?php

namespace Tests\Feature;

use App\Models\Creation;
use App\Models\Occasion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PagesPubliquesTest extends TestCase
{
    use RefreshDatabase;

    /** Toutes les pages atteignables depuis la navigation. */
    public static function pages(): array
    {
        return [
            'accueil' => ['/'],
            'créations' => ['/creations'],
            'occasions' => ['/occasions'],
            'demande' => ['/demande'],
            'merci' => ['/merci'],
            'à propos' => ['/a-propos'],
            'contact' => ['/contact'],
            'faq' => ['/faq'],
            'confidentialité' => ['/confidentialite'],
        ];
    }

    #[Test]
    #[DataProvider('pages')]
    public function chaque_page_repond(string $url): void
    {
        $this->get($url)->assertOk();
    }

    #[Test]
    public function aucun_lien_de_navigation_ne_mene_a_une_404(): void
    {
        // Le vrai risque : un lien ajouté dans l'en-tête ou le pied vers une
        // page pas encore créée. Le visiteur tombe sur une 404 et repart.
        //
        // On vérifie les routes NOMMÉES référencées par la navigation plutôt
        // que d'analyser le HTML : les assets (favicons, manifeste, build)
        // pollueraient l'extraction sans rien apprendre sur les pages.
        $routes = [
            'accueil', 'creations', 'occasions', 'demande', 'merci',
            'a-propos', 'contact', 'faq', 'confidentialite',
        ];

        foreach ($routes as $nom) {
            $this->assertTrue(
                Route::has($nom),
                "La route nommée « {$nom} » n'existe pas : tout lien vers elle lèvera une exception."
            );

            $this->get(route($nom))->assertOk("Page en échec : {$nom}");
        }
    }

    #[Test]
    public function l_accueil_ne_pointe_pas_vers_des_pages_inexistantes(): void
    {
        // L'accueil lie les créations vedettes et les occasions : ces liens
        // sont construits à partir de données, donc plus fragiles qu'un lien
        // statique.
        $occasion = Occasion::create(['nom_fr' => 'Mariage', 'slug_fr' => 'mariage', 'publie' => true]);
        $creation = Creation::create([
            'titre_fr' => 'Coffret', 'slug_fr' => 'coffret', 'publie' => true, 'vedette' => true,
        ]);
        $creation->occasions()->attach($occasion);

        $this->get('/')->assertOk();

        $this->get('/creations/coffret')->assertOk();
        $this->get('/occasions/mariage')->assertOk();
    }

    #[Test]
    public function les_pages_vides_ne_sont_pas_indexees(): void
    {
        // Une page sans contenu indexée par Google dégrade la qualité perçue
        // du domaine entier. La directive doit partir avec le contenu réel.
        foreach (['/a-propos', '/contact', '/faq', '/merci'] as $url) {
            $this->get($url)->assertSee('name="robots" content="noindex', false);
        }
    }

    #[Test]
    public function les_pages_avec_contenu_sont_indexables(): void
    {
        $this->get('/')->assertDontSee('noindex', false);
        $this->get('/creations')->assertDontSee('noindex', false);
        $this->get('/confidentialite')->assertDontSee('noindex', false);
    }

    #[Test]
    public function le_hub_occasions_liste_les_occasions_publiees(): void
    {
        Occasion::create(['nom_fr' => 'Mariage', 'slug_fr' => 'mariage', 'publie' => true]);
        Occasion::create(['nom_fr' => 'Brouillon', 'slug_fr' => 'brouillon', 'publie' => false]);

        $this->get('/occasions')
            ->assertOk()
            ->assertSee('Mariage')
            ->assertDontSee('Brouillon');
    }

    #[Test]
    public function le_hub_occasions_pointe_vers_la_galerie_filtree(): void
    {
        // Le lien est utile tout de suite, avant même que les pages de détail
        // par occasion existent.
        Occasion::create(['nom_fr' => 'Mariage', 'slug_fr' => 'mariage', 'publie' => true]);

        $this->get('/occasions')
            ->assertSee(route('creations', ['occasion' => 'mariage']), false);
    }

    #[Test]
    public function la_politique_annonce_la_duree_de_conservation_appliquee(): void
    {
        // La page doit refléter ce que la commande de purge fait réellement :
        // une promesse non tenue est pire qu'une absence de politique.
        $this->get('/confidentialite')
            ->assertSee(config('fleora.conservation.demandes_mois').' mois', false);
    }
}
