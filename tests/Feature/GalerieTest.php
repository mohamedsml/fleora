<?php

namespace Tests\Feature;

use App\Livewire\Galerie;
use App\Models\Creation;
use App\Models\Occasion;
use App\Models\ProductType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GalerieTest extends TestCase
{
    use RefreshDatabase;

    private function occasion(string $nom, string $slug): Occasion
    {
        return Occasion::create(['nom_fr' => $nom, 'slug_fr' => $slug, 'publie' => true]);
    }

    private function creation(string $titre, string $slug, array $attributs = []): Creation
    {
        return Creation::create([
            'titre_fr' => $titre,
            'slug_fr' => $slug,
            'publie' => true,
            ...$attributs,
        ]);
    }

    #[Test]
    public function la_galerie_se_charge_meme_sans_contenu(): void
    {
        // Un site fraîchement installé doit rester présentable.
        $this->get('/creations')->assertOk();
    }

    #[Test]
    public function elle_masque_les_creations_non_publiees(): void
    {
        $this->creation('Visible', 'visible');
        $this->creation('Brouillon', 'brouillon', ['publie' => false]);

        Livewire::test(Galerie::class)
            ->assertSee('Visible')
            ->assertDontSee('Brouillon');
    }

    #[Test]
    public function elle_filtre_par_occasion(): void
    {
        $mariage = $this->occasion('Mariage', 'mariage');
        $bapteme = $this->occasion('Baptême', 'bapteme');

        $this->creation('Lot invités', 'lot-invites')->occasions()->attach($mariage);
        $this->creation('Coffret bébé', 'coffret-bebe')->occasions()->attach($bapteme);

        Livewire::test(Galerie::class)
            ->call('filtrerOccasion', 'mariage')
            ->assertSee('Lot invités')
            ->assertDontSee('Coffret bébé');
    }

    #[Test]
    public function elle_combine_occasion_et_type(): void
    {
        $mariage = $this->occasion('Mariage', 'mariage');
        $boite = ProductType::create(['nom_fr' => 'Boîte à fleurs', 'slug_fr' => 'boite-fleurs', 'publie' => true]);
        $lot = ProductType::create(['nom_fr' => 'Lot invités', 'slug_fr' => 'lot-invites', 'publie' => true]);

        $this->creation('Boîte mariage', 'boite-mariage', ['product_type_id' => $boite->id])
            ->occasions()->attach($mariage);
        $this->creation('Lot mariage', 'lot-mariage', ['product_type_id' => $lot->id])
            ->occasions()->attach($mariage);

        Livewire::test(Galerie::class)
            ->call('filtrerOccasion', 'mariage')
            ->call('filtrerType', 'boite-fleurs')
            ->assertSee('Boîte mariage')
            ->assertDontSee('Lot mariage');
    }

    #[Test]
    public function recliquer_un_filtre_actif_le_retire(): void
    {
        // Évite d'avoir à chercher un bouton « tout afficher ».
        $this->occasion('Mariage', 'mariage');

        Livewire::test(Galerie::class)
            ->call('filtrerOccasion', 'mariage')
            ->assertSet('occasion', 'mariage')
            ->call('filtrerOccasion', 'mariage')
            ->assertSet('occasion', '');
    }

    #[Test]
    public function les_filtres_vivent_dans_l_url(): void
    {
        // Sans cela, un lien filtré ne se partage pas et le bouton retour du
        // navigateur ne fonctionne plus.
        $mariage = $this->occasion('Mariage', 'mariage');
        $this->creation('Lot invités', 'lot-invites')->occasions()->attach($mariage);
        $this->creation('Autre', 'autre');

        Livewire::withQueryParams(['occasion' => 'mariage'])
            ->test(Galerie::class)
            ->assertSee('Lot invités')
            ->assertDontSee('Autre');
    }

    #[Test]
    public function elle_pagine_par_douze(): void
    {
        // Bouton « voir plus » plutôt qu'un défilement infini, qui casse le
        // bouton retour et empêche d'atteindre le pied de page.
        for ($i = 1; $i <= 15; $i++) {
            $this->creation("Création {$i}", "creation-{$i}");
        }

        Livewire::test(Galerie::class)
            ->assertSet('affichees', 12)
            ->assertSee('Création 12')
            ->assertDontSee('Création 15')
            ->call('voirPlus')
            ->assertSee('Création 15');
    }

    #[Test]
    public function changer_de_filtre_remet_la_pagination_a_zero(): void
    {
        $this->occasion('Mariage', 'mariage');

        Livewire::test(Galerie::class)
            ->call('voirPlus')
            ->assertSet('affichees', 24)
            ->call('filtrerOccasion', 'mariage')
            ->assertSet('affichees', 12);
    }

    #[Test]
    public function la_fiche_affiche_la_creation(): void
    {
        $this->creation('Coffret Éloïse', 'coffret-eloise', [
            'description_fr' => 'Roses éternelles et prénom doré.',
        ]);

        $this->get('/creations/coffret-eloise')
            ->assertOk()
            ->assertSee('Coffret Éloïse')
            ->assertSee('Roses éternelles');
    }

    #[Test]
    public function une_fiche_non_publiee_renvoie_404(): void
    {
        $this->creation('Brouillon', 'brouillon', ['publie' => false]);

        $this->get('/creations/brouillon')->assertNotFound();
    }

    #[Test]
    public function la_fiche_pointe_vers_la_demande_avec_sa_reference(): void
    {
        // C'est ce lien qui transforme la galerie en source de demandes, et qui
        // dira quelles créations intéressent réellement.
        $this->creation('Coffret Éloïse', 'coffret-eloise');

        $this->get('/creations/coffret-eloise')
            ->assertSee(route('demande', ['creation' => 'coffret-eloise']), false);
    }

    #[Test]
    public function la_fiche_suggere_des_creations_de_la_meme_occasion(): void
    {
        $mariage = $this->occasion('Mariage', 'mariage');

        $this->creation('Coffret mariés', 'coffret-maries')->occasions()->attach($mariage);
        $this->creation('Lot invités', 'lot-invites')->occasions()->attach($mariage);
        $this->creation('Sans rapport', 'sans-rapport');

        $this->get('/creations/coffret-maries')
            ->assertSee('Lot invités')
            ->assertDontSee('Sans rapport');
    }
}
