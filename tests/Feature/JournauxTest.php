<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Lecteur de logs de l'administration.
 *
 * Les logs Laravel contiennent des traces d'exception : chemins du serveur,
 * extraits de code, parfois des données de requête. Cet écran est donc un
 * accès privilégié, et ces tests vérifient qu'il le reste.
 */
class JournauxTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_visiteur_non_connecte_est_refuse(): void
    {
        $this->get('/admin/logs')->assertRedirect();
    }

    #[Test]
    public function un_administrateur_actif_y_accede(): void
    {
        $this->actingAs(User::factory()->create(['actif' => true]))
            ->get('/admin/logs')
            ->assertSuccessful();
    }

    #[Test]
    public function un_compte_desactive_est_refuse(): void
    {
        // Un compte désactivé dont la session serait encore ouverte ne doit
        // pas pouvoir lire les logs.
        $this->actingAs(User::factory()->create(['actif' => false]))
            ->get('/admin/logs')
            ->assertForbidden();
    }
}
