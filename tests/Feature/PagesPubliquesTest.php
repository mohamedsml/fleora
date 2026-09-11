<?php

namespace Tests\Feature;

use App\Models\Creation;
use App\Models\Faq;
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
        //
        // /merci reste noindex par nature : une page de confirmation n'a rien
        // à faire dans les résultats de recherche.
        $this->get('/merci')->assertSee('name="robots" content="noindex', false);
    }

    #[Test]
    public function les_pages_avec_contenu_sont_indexables(): void
    {
        foreach (['/', '/creations', '/occasions', '/confidentialite',
            '/a-propos', '/contact', '/faq'] as $url) {
            $this->get($url)->assertDontSee('noindex', false);
        }
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
    public function la_faq_groupe_les_questions_par_categorie(): void
    {
        // Une liste à plat se parcourt mal : on cherche dans un thème précis.
        Faq::create([
            'question_fr' => 'Livrez-vous à Laval ?',
            'reponse_fr' => 'Oui.',
            'categorie' => 'Livraison',
            'publie' => true,
        ]);

        $this->get('/faq')
            ->assertOk()
            ->assertSee('Livraison')
            ->assertSee('Livrez-vous à Laval ?');
    }

    #[Test]
    public function la_faq_expose_ses_donnees_structurees(): void
    {
        // schema.org FAQPage : Google affiche alors les questions en accordéon
        // directement dans les résultats de recherche.
        Faq::create([
            'question_fr' => 'Quel est le délai ?',
            'reponse_fr' => 'Deux à trois semaines.',
            'publie' => true,
        ]);

        $this->get('/faq')
            ->assertSee('FAQPage', false)
            ->assertSee('acceptedAnswer', false);
    }

    #[Test]
    public function la_faq_masque_les_questions_non_publiees(): void
    {
        Faq::create([
            'question_fr' => 'Brouillon interne',
            'reponse_fr' => 'Pas prêt.',
            'publie' => false,
        ]);

        $this->get('/faq')->assertDontSee('Brouillon interne');
    }

    #[Test]
    public function le_contact_ne_publie_ni_telephone_ni_adresse(): void
    {
        // Décision explicite : l'atelier n'accueille pas de public, et publier
        // une adresse personnelle expose le domicile sans rien apporter.
        $reponse = $this->get('/contact')->assertOk();

        $reponse->assertDontSee('tel:', false);
        // La carte cadre le Grand Montréal, pas un point d'adresse.
        $reponse->assertSee('openstreetmap', false);
    }

    #[Test]
    public function le_bouton_whatsapp_n_apparait_que_si_un_numero_est_configure(): void
    {
        // Un bouton menant à un numéro inexistant est pire que pas de bouton.
        config(['fleora.contact.whatsapp' => '']);
        $this->get('/contact')->assertDontSee('wa.me', false);

        config(['fleora.contact.whatsapp' => '+1 514 555 1234']);
        $this->get('/contact')->assertSee('wa.me/15145551234', false);
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
