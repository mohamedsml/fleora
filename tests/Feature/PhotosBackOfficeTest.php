<?php

namespace Tests\Feature;

use App\Filament\Resources\Creations\Pages\CreateCreation;
use App\Filament\Resources\Creations\Pages\EditCreation;
use App\Models\Creation;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PhotosBackOfficeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->actingAs(User::factory()->create());
    }

    private function photo(int $largeur = 1600, int $hauteur = 2000): UploadedFile
    {
        return UploadedFile::fake()->image('creation.jpg', $largeur, $hauteur);
    }

    #[Test]
    public function il_cree_une_creation_avec_ses_photos(): void
    {
        Livewire::test(CreateCreation::class)
            ->fillForm([
                'titre_fr' => 'Coffret Éloïse',
                'slug_fr' => 'coffret-eloise',
                'photos' => [$this->photo(), $this->photo()],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $creation = Creation::firstWhere('slug_fr', 'coffret-eloise');

        $this->assertCount(2, $creation->media);

        // Les variantes doivent exister : c'est elles que le site sert.
        foreach ($creation->media as $media) {
            $this->assertNotEmpty($media->variantes);
            Storage::disk('public')->assertExists($media->chemin);
        }
    }

    #[Test]
    public function la_premiere_photo_devient_l_image_principale(): void
    {
        // C'est elle qui apparaît dans la galerie et dans les partages.
        Livewire::test(CreateCreation::class)
            ->fillForm([
                'titre_fr' => 'Coffret',
                'slug_fr' => 'coffret',
                'photos' => [$this->photo(), $this->photo()],
            ])
            ->call('create');

        $creation = Creation::firstWhere('slug_fr', 'coffret');

        $this->assertSame(0, $creation->media->first()->ordre);
        $this->assertSame(
            $creation->media->sortBy('ordre')->first()->id,
            $creation->imagePrincipale()->id
        );
    }

    #[Test]
    public function il_ajoute_une_photo_sans_perdre_les_existantes(): void
    {
        $creation = Creation::create(['titre_fr' => 'Coffret', 'slug_fr' => 'coffret']);
        $existante = ImageService::make()->attacher($this->photo(), $creation, ['ordre' => 0]);

        Livewire::test(EditCreation::class, ['record' => $creation->getKey()])
            ->fillForm([
                // Filament renvoie les photos déjà en base sous forme de chemin,
                // et les nouvelles comme des fichiers.
                'photos' => [$existante->chemin, $this->photo()],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertCount(2, $creation->fresh()->media);
        Storage::disk('public')->assertExists($existante->chemin);
    }

    #[Test]
    public function retirer_une_photo_supprime_aussi_ses_fichiers(): void
    {
        // Sans cela, les cinq fichiers de chaque photo retirée resteraient sur
        // le disque. L'espace et les inodes sont comptés sur mutualisé.
        $creation = Creation::create(['titre_fr' => 'Coffret', 'slug_fr' => 'coffret']);
        $service = ImageService::make();

        $gardee = $service->attacher($this->photo(), $creation, ['ordre' => 0]);
        $retiree = $service->attacher($this->photo(), $creation, ['ordre' => 1]);

        $fichiers = [$retiree->chemin, ...array_values($retiree->variantes)];

        Livewire::test(EditCreation::class, ['record' => $creation->getKey()])
            ->fillForm(['photos' => [$gardee->chemin]])
            ->call('save');

        $this->assertCount(1, $creation->fresh()->media);

        foreach ($fichiers as $chemin) {
            Storage::disk('public')->assertMissing($chemin);
        }

        Storage::disk('public')->assertExists($gardee->chemin);
    }

    #[Test]
    public function reordonner_les_photos_change_l_image_principale(): void
    {
        $creation = Creation::create(['titre_fr' => 'Coffret', 'slug_fr' => 'coffret']);
        $service = ImageService::make();

        $premiere = $service->attacher($this->photo(), $creation, ['ordre' => 0]);
        $seconde = $service->attacher($this->photo(), $creation, ['ordre' => 1]);

        Livewire::test(EditCreation::class, ['record' => $creation->getKey()])
            ->fillForm(['photos' => [$seconde->chemin, $premiere->chemin]])
            ->call('save');

        $this->assertSame($seconde->id, $creation->fresh()->imagePrincipale()->id);
    }

    #[Test]
    public function le_formulaire_precharge_les_photos_existantes(): void
    {
        // Sans préchargement, ouvrir puis enregistrer une fiche effacerait
        // toutes ses photos.
        $creation = Creation::create(['titre_fr' => 'Coffret', 'slug_fr' => 'coffret']);
        $media = ImageService::make()->attacher($this->photo(), $creation, ['ordre' => 0]);

        Livewire::test(EditCreation::class, ['record' => $creation->getKey()])
            ->assertFormSet(fn (array $state) => in_array($media->chemin, $state['photos'], true));
    }

    #[Test]
    public function une_creation_sans_photo_reste_valide(): void
    {
        // On saisit souvent la fiche avant d'avoir les photos.
        Livewire::test(CreateCreation::class)
            ->fillForm(['titre_fr' => 'Sans photo', 'slug_fr' => 'sans-photo'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertNotNull(Creation::firstWhere('slug_fr', 'sans-photo'));
    }
}
