<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Lecteur de journaux.
 *
 * Il vit sur sa propre route, hors du panneau Filament : sans porte, il
 * serait accessible à quiconque connaît l'URL. Les journaux exposent des
 * chemins du serveur, des extraits de code et parfois des données de requête.
 */
class LecteurJournauxTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function la_porte_est_definie(): void
    {
        // Sans elle, le paquet laisse passer tout le monde hors production.
        $this->assertTrue(Gate::has('viewLogViewer'));
    }

    #[Test]
    public function un_administrateur_actif_y_accede(): void
    {
        $this->actingAs(User::factory()->create(['actif' => true]));

        $this->get('/log-viewer')->assertOk();
    }

    #[Test]
    public function un_compte_desactive_est_refuse(): void
    {
        // Un compte désactivé dont la session reste ouverte ne doit pas lire
        // les journaux, au même titre qu'il ne peut plus ouvrir /admin.
        $desactive = User::factory()->create(['actif' => false]);

        $this->assertFalse(Gate::forUser($desactive)->allows('viewLogViewer'));
    }

    #[Test]
    public function un_visiteur_anonyme_est_refuse(): void
    {
        $this->assertFalse(Gate::forUser(null)->allows('viewLogViewer'));
    }

    #[Test]
    public function le_menu_de_l_administration_y_mene(): void
    {
        // Le lecteur étant hors du panneau, il faudrait sinon connaître l'URL
        // par cœur.
        $this->actingAs(User::factory()->create(['actif' => true]));

        $this->get('/admin')
            ->assertOk()
            ->assertSee('log-viewer', false);
    }

    #[Test]
    public function les_journaux_tournent_chaque_jour(): void
    {
        // Un laravel.log unique grossit sans fin et mélange toutes les dates :
        // retrouver une erreur d'avant-hier devient une fouille.
        $this->assertSame(
            ['daily'],
            config('logging.channels.stack.channels'),
        );

        $this->assertSame('daily', config('logging.channels.daily.driver'));
    }

    #[Test]
    public function la_retention_des_journaux_est_bornee(): void
    {
        // Sans limite, les fichiers s'accumulent indéfiniment — l'espace est
        // compté sur un hébergement mutualisé.
        $this->assertGreaterThan(0, config('logging.channels.daily.max_files'));
        $this->assertLessThanOrEqual(30, config('logging.channels.daily.max_files'));
    }
}
