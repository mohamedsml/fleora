<?php

namespace Tests\Feature;

use App\Models\Occasion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Libellé d'appel à l'action propre à chaque occasion.
 *
 * « Créer pour mon mariage » confirme à la visiteuse qu'elle est au bon
 * endroit, là où un bouton générique la laisse douter.
 */
class TextesOccasionsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function la_page_affiche_le_bouton_propre_a_l_occasion(): void
    {
        Occasion::create([
            'nom_fr' => 'Mariage', 'slug_fr' => 'mariage',
            'cta_fr' => 'Créer pour mon mariage', 'publie' => true,
        ]);

        $this->get('/occasions/mariage')
            ->assertOk()
            ->assertSee('Créer pour mon mariage');
    }

    #[Test]
    public function sans_libelle_la_page_retombe_sur_le_bouton_generique(): void
    {
        // Une occasion ajoutée dans /admin sans libellé ne doit pas afficher
        // un bouton vide.
        Occasion::create(['nom_fr' => 'Nouvelle', 'slug_fr' => 'nouvelle', 'publie' => true]);

        $this->get('/occasions/nouvelle')
            ->assertOk()
            ->assertSee(__('commun.cta.soumission'));
    }

    #[Test]
    public function le_bouton_est_traduit(): void
    {
        Occasion::create([
            'nom_fr' => 'Mariage', 'slug_fr' => 'mariage', 'slug_en' => 'wedding',
            'cta_fr' => 'Créer pour mon mariage', 'cta_en' => 'Create for my wedding',
            'publie' => true,
        ]);

        $this->get('/en/occasions/wedding')
            ->assertOk()
            ->assertSee('Create for my wedding')
            ->assertDontSee('Créer pour mon mariage');
    }

    #[Test]
    public function le_seeder_pose_les_libelles_sans_ecraser_les_introductions(): void
    {
        $occasion = Occasion::create([
            'nom_fr' => 'Mariage', 'slug_fr' => 'mariage',
            'intro_fr' => 'Texte saisi à la main', 'publie' => true,
        ]);

        $this->artisan('fleora:textes-occasions')->assertSuccessful();

        $occasion->refresh();

        $this->assertSame('Créer pour mon mariage', $occasion->cta_fr);
        // Une saisie faite dans /admin ne doit jamais être écrasée.
        $this->assertSame('Texte saisi à la main', $occasion->intro_fr);
    }

    #[Test]
    public function l_option_remplacer_ecrase_les_introductions(): void
    {
        $occasion = Occasion::create([
            'nom_fr' => 'Mariage', 'slug_fr' => 'mariage',
            'intro_fr' => 'Ancien texte générique', 'publie' => true,
        ]);

        $this->artisan('fleora:textes-occasions --remplacer --no-interaction')
            ->assertSuccessful();

        $this->assertStringContainsString(
            'créations florales',
            $occasion->fresh()->intro_fr,
        );
    }
}
