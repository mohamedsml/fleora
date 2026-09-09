<?php

namespace Tests\Feature;

use App\Models\Creation;
use App\Models\Faq;
use App\Models\Occasion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SitePublicTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function l_accueil_se_charge_meme_sans_contenu(): void
    {
        // Un site fraîchement installé n'a aucune création : la page doit
        // rester présentable plutôt que planter sur une collection vide.
        $this->get('/')->assertOk()->assertSee('Fleora');
    }

    #[Test]
    public function l_accueil_affiche_les_creations_vedettes(): void
    {
        $occasion = Occasion::create(['nom_fr' => 'Mariage', 'slug_fr' => 'mariage', 'publie' => true]);

        $vedette = Creation::create([
            'titre_fr' => 'Boîte blush', 'slug_fr' => 'boite-blush',
            'publie' => true, 'vedette' => true, 'prix_min' => 6500,
        ]);
        $vedette->occasions()->attach($occasion->id);

        $this->get('/')
            ->assertOk()
            ->assertSee('Boîte blush')
            ->assertSee('Mariage');
    }

    #[Test]
    public function l_accueil_masque_ce_qui_n_est_pas_publie(): void
    {
        Creation::create([
            'titre_fr' => 'Brouillon secret', 'slug_fr' => 'brouillon',
            'publie' => false, 'vedette' => true,
        ]);

        Occasion::create(['nom_fr' => 'Occasion cachée', 'slug_fr' => 'cachee', 'publie' => false]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Brouillon secret')
            ->assertDontSee('Occasion cachée');
    }

    #[Test]
    public function l_accueil_n_affiche_que_les_faq_marquees_accueil(): void
    {
        Faq::create(['question_fr' => 'Question vedette', 'reponse_fr' => 'R', 'sur_accueil' => true, 'publie' => true]);
        Faq::create(['question_fr' => 'Question secondaire', 'reponse_fr' => 'R', 'sur_accueil' => false, 'publie' => true]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Question vedette')
            ->assertDontSee('Question secondaire');
    }
}
