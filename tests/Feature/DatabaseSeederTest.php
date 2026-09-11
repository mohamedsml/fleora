<?php

namespace Tests\Feature;

use App\Models\Occasion;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Base de développement recréée par `make fresh`.
 *
 * Ce seeder existe pour qu'un `migrate:fresh` local rende le site
 * immédiatement utilisable — compte d'administration compris, qu'il fallait
 * sinon recréer à la main après chaque remise à zéro.
 */
class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    private function configurer(?string $courriel, ?string $motDePasse): void
    {
        config([
            'fleora.admin.nom' => 'Mohamed',
            'fleora.admin.courriel' => $courriel,
            'fleora.admin.mot_de_passe' => $motDePasse,
        ]);
    }

    #[Test]
    public function le_compte_du_env_est_cree_et_peut_ouvrir_l_administration(): void
    {
        $this->configurer('moi@example.ca', 'phrase-de-passe-de-dev');

        $this->seed(DatabaseSeeder::class);

        $utilisateur = User::where('email', 'moi@example.ca')->firstOrFail();

        $this->assertSame('Mohamed', $utilisateur->name);
        // `actif` conditionne canAccessPanel() : sans lui, /admin renvoie 403.
        $this->assertTrue($utilisateur->actif);
        $this->assertTrue(Hash::check('phrase-de-passe-de-dev', $utilisateur->password));
    }

    #[Test]
    public function le_contenu_de_demonstration_est_charge(): void
    {
        // Sans lui, une base fraîche donne un site vide où rien ne se vérifie
        // visuellement.
        $this->configurer('moi@example.ca', 'phrase-de-passe-de-dev');

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(6, Occasion::count());
        // Les traductions suivent dans la foulée.
        $this->assertNotNull(Occasion::where('slug_fr', 'mariage')->first()?->nom_en);
    }

    #[Test]
    public function un_env_incomplet_ne_cree_aucun_compte(): void
    {
        // Mieux vaut aucun compte qu'un compte au mot de passe deviné : un
        // défaut du type « password » finirait par suivre un .env recopié.
        $this->configurer('moi@example.ca', null);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(0, User::count());
    }

    #[Test]
    public function rejouer_le_seed_ne_cree_pas_de_doublon(): void
    {
        $this->configurer('moi@example.ca', 'phrase-de-passe-de-dev');

        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, User::where('email', 'moi@example.ca')->count());
    }

    #[Test]
    public function rejouer_le_seed_remet_le_mot_de_passe_du_env(): void
    {
        // Le cas réel : mot de passe oublié en local, on relance `make fresh`.
        $this->configurer('moi@example.ca', 'phrase-de-passe-de-dev');
        $this->seed(DatabaseSeeder::class);

        User::where('email', 'moi@example.ca')->update(['password' => Hash::make('autre-chose')]);

        $this->seed(DatabaseSeeder::class);

        $utilisateur = User::where('email', 'moi@example.ca')->firstOrFail();
        $this->assertTrue(Hash::check('phrase-de-passe-de-dev', $utilisateur->password));
    }

    #[Test]
    public function le_seeder_refuse_de_tourner_en_production(): void
    {
        // `db:seed` sans --class exécute ce seeder : il créerait un accès à
        // l'administration avec un mot de passe venu d'un .env de dev.
        $this->configurer('moi@example.ca', 'phrase-de-passe-de-dev');
        app()['env'] = 'production';

        $this->expectException(\RuntimeException::class);

        (new DatabaseSeeder)->run();
    }
}
