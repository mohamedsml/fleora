<?php

namespace Tests\Feature;

use App\Models\User;
use App\Providers\Filament\AdminPanelProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * La connexion automatique contourne l'authentification de l'administration.
 * Ces tests existent pour qu'une régression sur ses gardes casse la suite
 * plutôt que d'ouvrir le back-office en production.
 *
 * La logique vit dans AdminPanelProvider::boot(). Comme il s'exécute au
 * démarrage de l'application, avant que les tests ne posent leur config, on le
 * rejoue explicitement après avoir réglé l'environnement voulu.
 */
class ConnexionAutomatiqueTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function elle_est_desactivee_par_defaut(): void
    {
        // Absence de configuration = désactivé. Un oubli ne doit jamais
        // produire une administration ouverte.
        // (config() renvoie false, pas la chaîne « false » : phpunit.xml
        // utilise la syntaxe (false) que Laravel convertit en booléen.)
        $this->assertFalse((bool) config('fleora.auto_login'));

        User::factory()->create();

        $this->get('/admin')->assertRedirect();
        $this->assertGuest();
    }

    #[Test]
    public function elle_connecte_l_administrateur_en_local(): void
    {
        $admin = User::factory()->create();

        config(['fleora.auto_login' => true]);
        app()->detectEnvironment(fn () => 'local');

        // boot() a déjà eu lieu : on le rejoue avec la config voulue.
        (new AdminPanelProvider(app()))->boot();

        $this->assertAuthenticatedAs($admin);
    }

    #[Test]
    public function elle_leve_une_exception_hors_local(): void
    {
        // Le scénario redouté : un .env de développement copié sur le serveur.
        // Une erreur bruyante vaut mieux qu'une administration ouverte.
        User::factory()->create();

        config(['fleora.auto_login' => true]);
        app()->detectEnvironment(fn () => 'production');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('hors environnement local');

        (new AdminPanelProvider(app()))->boot();
    }

    #[Test]
    public function elle_refuse_une_adresse_publique(): void
    {
        // Troisième verrou : même avec APP_ENV=local sur un serveur exposé,
        // une requête venue d'Internet ne connecte personne.
        User::factory()->create();

        config(['fleora.auto_login' => true]);
        app()->detectEnvironment(fn () => 'local');

        request()->server->set('REMOTE_ADDR', '203.0.113.42');

        (new AdminPanelProvider(app()))->boot();

        $this->assertGuest();
    }

    #[Test]
    public function elle_accepte_le_reseau_docker(): void
    {
        // L'environnement de développement passe par Docker : les requêtes
        // arrivent d'une IP privée, pas de 127.0.0.1.
        $admin = User::factory()->create();

        config(['fleora.auto_login' => true]);
        app()->detectEnvironment(fn () => 'local');

        request()->server->set('REMOTE_ADDR', '172.30.0.1');

        (new AdminPanelProvider(app()))->boot();

        $this->assertAuthenticatedAs($admin);
    }

    #[Test]
    public function elle_respecte_une_session_deja_ouverte(): void
    {
        // Se connecter comme quelqu'un d'autre doit rester possible.
        User::factory()->create();
        $autre = User::factory()->create();

        $this->actingAs($autre);

        config(['fleora.auto_login' => true]);
        app()->detectEnvironment(fn () => 'local');

        (new AdminPanelProvider(app()))->boot();

        $this->assertAuthenticatedAs($autre);
    }

    #[Test]
    public function elle_ne_plante_pas_sans_utilisateur(): void
    {
        // Base fraîche, aucun compte créé.
        config(['fleora.auto_login' => true]);
        app()->detectEnvironment(fn () => 'local');

        (new AdminPanelProvider(app()))->boot();

        $this->assertGuest();
    }
}
