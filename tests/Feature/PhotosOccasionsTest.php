<?php

namespace Tests\Feature;

use App\Filament\Resources\Occasions\Pages\CreateOccasion;
use App\Filament\Resources\Occasions\Pages\EditOccasion;
use App\Models\Occasion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Photo des occasions.
 *
 * Le formulaire n'avait aucun champ d'image : les cartes s'affichaient en
 * aplat dégradé, sans qu'aucune interface permette d'y remédier.
 */
class PhotosOccasionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->actingAs(User::factory()->create(['actif' => true]));
    }

    private function image(): UploadedFile
    {
        return UploadedFile::fake()->image('occasion.jpg', 1200, 1200);
    }

    #[Test]
    public function une_photo_peut_etre_televersee_a_la_creation(): void
    {
        Livewire::test(CreateOccasion::class)
            ->fillForm([
                'nom_fr' => 'Mariage',
                'slug_fr' => 'mariage',
                'photos' => [$this->image()],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $occasion = Occasion::where('slug_fr', 'mariage')->firstOrFail();

        $this->assertCount(1, $occasion->media);
    }

    #[Test]
    public function la_photo_est_ajoutee_depuis_l_edition(): void
    {
        $occasion = Occasion::create(['nom_fr' => 'Mariage', 'slug_fr' => 'mariage']);

        Livewire::test(EditOccasion::class, ['record' => $occasion->getRouteKey()])
            ->fillForm(['photos' => [$this->image()]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertCount(1, $occasion->fresh()->media);
    }

    #[Test]
    public function les_variantes_web_sont_generees(): void
    {
        // Sans variantes, la carte servirait l'original à toutes les tailles :
        // sur mobile, plusieurs mégaoctets pour une vignette.
        $occasion = Occasion::create(['nom_fr' => 'Mariage', 'slug_fr' => 'mariage']);

        Livewire::test(EditOccasion::class, ['record' => $occasion->getRouteKey()])
            ->fillForm(['photos' => [$this->image()]])
            ->call('save')
            ->assertHasNoFormErrors();

        $media = $occasion->fresh()->media->first();

        $this->assertNotEmpty($media->variantes);
        $this->assertArrayHasKey('400', $media->variantes);
    }

    #[Test]
    public function la_photo_existante_reste_apres_une_modification_de_texte(): void
    {
        // Le piège : un champ FileUpload vide à la réouverture ferait
        // supprimer la photo au premier enregistrement.
        $occasion = Occasion::create(['nom_fr' => 'Mariage', 'slug_fr' => 'mariage']);

        Livewire::test(EditOccasion::class, ['record' => $occasion->getRouteKey()])
            ->fillForm(['photos' => [$this->image()]])
            ->call('save');

        $this->assertCount(1, $occasion->fresh()->media);

        Livewire::test(EditOccasion::class, ['record' => $occasion->getRouteKey()])
            ->fillForm(['intro_fr' => 'Texte modifié'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertCount(1, $occasion->fresh()->media, 'La photo ne doit pas disparaître.');
    }

    #[Test]
    public function la_carte_affiche_la_photo_sur_le_site(): void
    {
        $occasion = Occasion::create([
            'nom_fr' => 'Mariage', 'slug_fr' => 'mariage', 'publie' => true,
        ]);

        Livewire::test(EditOccasion::class, ['record' => $occasion->getRouteKey()])
            ->fillForm(['photos' => [$this->image()]])
            ->call('save');

        $chemin = $occasion->fresh()->media->first()->chemin;

        $this->get('/occasions')
            ->assertOk()
            ->assertSee(pathinfo($chemin, PATHINFO_FILENAME), false);
    }
}
