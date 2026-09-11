<?php

namespace Tests\Feature;

use App\Models\Creation;
use App\Models\Faq;
use App\Models\Media;
use App\Models\Occasion;
use App\Models\ProductType;
use App\Models\SiteSetting;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ProductionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Contenu initial de la production.
 *
 * Ce seeder tourne sur la vraie base : chacune de ses garanties est vérifiée
 * ici plutôt que constatée après coup sur le site public.
 */
class ProductionSeederTest extends TestCase
{
    use RefreshDatabase;

    private function semer(): void
    {
        $this->seed(ProductionSeeder::class);
    }

    #[Test]
    public function la_structure_du_catalogue_est_publiee(): void
    {
        // Occasions et types font vivre les filtres de la galerie et les pages
        // d'occasion : ce sont les pages à plus forte intention d'achat.
        $this->semer();

        $this->assertSame(6, Occasion::where('publie', true)->count());
        $this->assertSame(4, ProductType::where('publie', true)->count());
        $this->assertGreaterThanOrEqual(12, Faq::where('publie', true)->count());
    }

    #[Test]
    public function les_creations_sans_photo_restent_en_brouillon(): void
    {
        // Publiées, elles annonceraient un catalogue qui n'existe pas.
        $this->semer();

        $this->assertGreaterThan(0, Creation::count(), 'Les gabarits doivent exister.');
        $this->assertSame(
            0,
            Creation::where('publie', true)->count(),
            'Aucune création fictive ne doit être visible du public.'
        );
    }

    #[Test]
    public function la_galerie_publique_ne_montre_aucune_creation_fictive(): void
    {
        $this->semer();

        $reponse = $this->get('/creations');

        $reponse->assertOk();
        $reponse->assertDontSee('Boîte blush et ivoire');
    }

    #[Test]
    public function le_compte_instagram_est_le_vrai(): void
    {
        $this->semer();

        $this->assertSame(
            'https://www.instagram.com/fleora.ca/',
            SiteSetting::lire('instagram')
        );
    }

    #[Test]
    public function une_creation_publiee_a_la_main_survit_a_un_rejeu(): void
    {
        // Le cas qui compte vraiment : le seeder peut être relancé après que
        // le propriétaire a publié ses vraies pièces. Les dépublier
        // silencieusement retirerait le catalogue du site.
        $this->semer();

        $creation = Creation::where('slug_fr', 'boite-blush-ivoire')->firstOrFail();

        // Le propriétaire téléverse une photo et publie depuis /admin.
        Media::create([
            'mediable_type' => Creation::class,
            'mediable_id' => $creation->id,
            'chemin' => 'creations/photo.webp',
            'largeur' => 1200,
            'hauteur' => 1200,
        ]);
        $creation->update(['publie' => true]);

        $this->semer();

        $this->assertTrue(
            $creation->fresh()->publie,
            'Un rejeu ne doit jamais dépublier une création complétée à la main.'
        );
    }

    #[Test]
    public function aucun_utilisateur_de_test_n_est_cree(): void
    {
        // DatabaseSeeder crée « test@example.com » : un compte d'administration
        // sans mot de passe choisi n'a rien à faire en production.
        $this->semer();

        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    #[Test]
    public function le_seeder_par_defaut_refuse_de_tourner_en_production(): void
    {
        // `db:seed` sans --class est la commande qu'on tape par réflexe : elle
        // créerait un accès à l'administration avec un mot de passe de factory.
        app()['env'] = 'production';

        $this->expectException(\RuntimeException::class);

        // Appel direct : passer par `$this->seed()` ferait transiter
        // l'exception par la commande console, qui l'enveloppe.
        (new DatabaseSeeder)->run();
    }

    #[Test]
    public function le_seeder_est_idempotent(): void
    {
        $this->semer();
        $occasions = Occasion::count();
        $faqs = Faq::count();

        $this->semer();

        $this->assertSame($occasions, Occasion::count());
        $this->assertSame($faqs, Faq::count());
    }
}
