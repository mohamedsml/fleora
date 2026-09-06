<?php

namespace Tests\Feature;

use App\Models\Creation;
use App\Models\Occasion;
use App\Models\ProductType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Vérifie que le back-office se charge réellement — un écran Filament peut
 * casser au rendu sans qu'aucun test de modèle ne s'en aperçoive.
 */
class BackOfficeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin@fleora.test',
            'password' => bcrypt('secret'),
        ]);
    }

    #[Test]
    public function le_back_office_exige_une_authentification(): void
    {
        $this->get('/admin/creations')->assertRedirect('/admin/login');
    }

    #[Test]
    public function un_compte_desactive_perd_l_acces(): void
    {
        $user = $this->admin();
        $user->update(['actif' => false]);

        $this->actingAs($user)->get('/admin/creations')->assertForbidden();
    }

    #[Test]
    public function les_ecrans_de_liste_se_chargent(): void
    {
        $this->actingAs($this->admin());

        $this->get('/admin')->assertOk();
        $this->get('/admin/creations')->assertOk();
        $this->get('/admin/occasions')->assertOk();
        $this->get('/admin/product-types')->assertOk();
    }

    #[Test]
    public function les_formulaires_de_creation_se_chargent(): void
    {
        $this->actingAs($this->admin());

        $this->get('/admin/creations/create')->assertOk();
        $this->get('/admin/occasions/create')->assertOk();
        $this->get('/admin/product-types/create')->assertOk();
    }

    #[Test]
    public function les_formulaires_d_edition_se_chargent(): void
    {
        $this->actingAs($this->admin());

        $type = ProductType::create(['nom_fr' => 'Boîte à fleurs', 'slug_fr' => 'boite-a-fleurs']);
        $occasion = Occasion::create(['nom_fr' => 'Mariage', 'slug_fr' => 'mariage']);
        $creation = Creation::create([
            'titre_fr' => 'Boîte blush',
            'slug_fr' => 'boite-blush',
            'product_type_id' => $type->id,
            'prix_min' => 6500,
        ]);
        $creation->occasions()->attach($occasion->id);

        $this->get("/admin/creations/{$creation->id}/edit")->assertOk();
        $this->get("/admin/occasions/{$occasion->id}/edit")->assertOk();
        $this->get("/admin/product-types/{$type->id}/edit")->assertOk();
    }
}
