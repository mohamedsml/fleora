<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Gestion des comptes d'administration.
 *
 * Ces comptes ouvrent sur les demandes clientes — donc sur des renseignements
 * personnels (Loi 25). Les garde-fous qui empêchent de fermer l'administration
 * par inadvertance sont vérifiés ici, pas supposés.
 */
class UtilisateursBackOfficeTest extends TestCase
{
    use RefreshDatabase;

    private function connecte(array $attributs = []): User
    {
        $utilisateur = User::factory()->create($attributs + ['actif' => true]);
        $this->actingAs($utilisateur);

        return $utilisateur;
    }

    // ── Création ────────────────────────────────────────────────────────

    #[Test]
    public function un_compte_est_cree_et_peut_acceder_a_l_administration(): void
    {
        $this->connecte();

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Sarah',
                'email' => 'sarah@fleora.ca',
                'password' => 'phrase-de-passe-solide',
                'actif' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $cree = User::where('email', 'sarah@fleora.ca')->firstOrFail();

        $this->assertSame('Sarah', $cree->name);
        // `actif` conditionne canAccessPanel() : sans lui le compte existe
        // mais /admin renvoie 403.
        $this->assertTrue($cree->actif);
    }

    #[Test]
    public function le_mot_de_passe_est_hache_une_seule_fois(): void
    {
        // Le modèle porte `'password' => 'hashed'`. Hacher en plus dans la
        // page produirait un double hachage : la connexion échouerait sans
        // message exploitable.
        $this->connecte();

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Test',
                'email' => 'hash@fleora.ca',
                'password' => 'phrase-de-passe-solide',
                'actif' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $cree = User::where('email', 'hash@fleora.ca')->firstOrFail();

        $this->assertNotSame('phrase-de-passe-solide', $cree->password);
        $this->assertTrue(Hash::check('phrase-de-passe-solide', $cree->password));
    }

    #[Test]
    public function un_mot_de_passe_trop_court_est_refuse(): void
    {
        $this->connecte();

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Test',
                'email' => 'court@fleora.ca',
                'password' => 'court',
                'actif' => true,
            ])
            ->call('create')
            ->assertHasFormErrors(['password']);

        $this->assertDatabaseMissing('users', ['email' => 'court@fleora.ca']);
    }

    #[Test]
    public function un_courriel_deja_pris_est_refuse(): void
    {
        $this->connecte();
        User::factory()->create(['email' => 'existe@fleora.ca']);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Doublon',
                'email' => 'existe@fleora.ca',
                'password' => 'phrase-de-passe-solide',
                'actif' => true,
            ])
            ->call('create')
            ->assertHasFormErrors(['email']);
    }

    // ── Modification ────────────────────────────────────────────────────

    #[Test]
    public function un_champ_mot_de_passe_vide_conserve_l_ancien(): void
    {
        // Sinon toute modification du nom effacerait le mot de passe.
        $this->connecte();

        $cible = User::factory()->create([
            'email' => 'cible@fleora.ca',
            'password' => 'ancienne-phrase-de-passe',
        ]);
        $hachageAvant = $cible->fresh()->password;

        Livewire::test(EditUser::class, ['record' => $cible->getRouteKey()])
            ->fillForm(['name' => 'Nom modifié', 'password' => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        $cible->refresh();

        $this->assertSame('Nom modifié', $cible->name);
        $this->assertSame($hachageAvant, $cible->password);
    }

    #[Test]
    public function le_mot_de_passe_peut_etre_remplace(): void
    {
        $this->connecte();

        $cible = User::factory()->create(['email' => 'cible@fleora.ca']);

        Livewire::test(EditUser::class, ['record' => $cible->getRouteKey()])
            ->fillForm(['password' => 'nouvelle-phrase-de-passe'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue(Hash::check('nouvelle-phrase-de-passe', $cible->fresh()->password));
    }

    // ── Garde-fous ──────────────────────────────────────────────────────

    #[Test]
    public function on_ne_peut_pas_desactiver_son_propre_compte(): void
    {
        // Le compte deviendrait inaccessible dès l'enregistrement, et
        // personne d'autre ne pourrait forcément le rouvrir.
        $moi = $this->connecte();
        User::factory()->create(['actif' => true]); // un autre actif existe

        Livewire::test(EditUser::class, ['record' => $moi->getRouteKey()])
            ->fillForm(['actif' => false])
            ->call('save')
            ->assertHasFormErrors(['actif']);

        $this->assertTrue($moi->fresh()->actif);
    }

    #[Test]
    public function on_ne_peut_pas_desactiver_le_dernier_compte_actif(): void
    {
        // Sans ce garde-fou, un clic ferme l'administration pour de bon et il
        // faut un accès SSH pour la rouvrir.
        $this->connecte();
        $dernier = User::where('actif', true)->firstOrFail();

        // Tous les autres comptes sont déjà désactivés.
        User::factory()->create(['actif' => false]);

        Livewire::test(EditUser::class, ['record' => $dernier->getRouteKey()])
            ->fillForm(['actif' => false])
            ->call('save')
            ->assertHasFormErrors(['actif']);

        $this->assertTrue($dernier->fresh()->actif);
    }

    #[Test]
    public function un_autre_compte_peut_etre_desactive(): void
    {
        // Le garde-fou ne doit pas empêcher l'usage normal : couper l'accès à
        // quelqu'un sans supprimer son historique.
        $this->connecte();

        $autre = User::factory()->create(['actif' => true]);

        Livewire::test(EditUser::class, ['record' => $autre->getRouteKey()])
            ->fillForm(['actif' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse($autre->fresh()->actif);
    }

    #[Test]
    public function un_compte_desactive_ne_peut_plus_ouvrir_l_administration(): void
    {
        $desactive = User::factory()->create(['actif' => false]);

        $this->actingAs($desactive)->get('/admin')->assertForbidden();
    }

    #[Test]
    public function la_liste_est_accessible_et_montre_les_comptes(): void
    {
        $this->connecte(['name' => 'Moi']);
        User::factory()->create(['name' => 'Collègue']);

        Livewire::test(ListUsers::class)
            ->assertSee('Moi')
            ->assertSee('Collègue');
    }

    #[Test]
    public function on_ne_peut_pas_supprimer_son_propre_compte(): void
    {
        // La suppression déconnecte immédiatement et détruit l'accès.
        $moi = $this->connecte();
        User::factory()->create(['actif' => true]);

        Livewire::test(ListUsers::class)
            ->assertTableActionHidden('delete', $moi);
    }

    #[Test]
    public function on_ne_peut_pas_supprimer_le_dernier_compte_actif(): void
    {
        $this->connecte();
        $dernier = User::where('actif', true)->firstOrFail();
        User::factory()->create(['actif' => false]);

        Livewire::test(ListUsers::class)
            ->assertTableActionHidden('delete', $dernier);
    }

    #[Test]
    public function un_autre_compte_peut_etre_supprime(): void
    {
        $this->connecte();
        $autre = User::factory()->create(['actif' => true]);

        Livewire::test(ListUsers::class)
            ->callTableAction('delete', $autre);

        $this->assertModelMissing($autre);
    }

    #[Test]
    public function une_suppression_groupee_epargne_les_comptes_proteges(): void
    {
        // Une sélection groupée contourne les protections individuelles : sans
        // filtrage, cocher « tout » fermerait l'administration.
        $moi = $this->connecte();
        $autre = User::factory()->create(['actif' => true]);

        Livewire::test(ListUsers::class)
            ->callTableBulkAction('delete', [$moi, $autre]);

        $this->assertModelExists($moi);
        $this->assertModelMissing($autre);
    }

    #[Test]
    public function l_ecran_des_utilisateurs_repond(): void
    {
        $this->connecte();

        $this->get('/admin/users')->assertOk();
    }
}
