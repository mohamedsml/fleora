<?php

namespace Tests\Feature;

use App\Models\Creation;
use App\Services\ImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Vérifie que les photos téléversées arrivent réellement dans le HTML public.
 * Un upload réussi mais une URL cassée donne une galerie vide sans erreur.
 */
class AffichagePhotosTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function la_galerie_sert_les_variantes_webp(): void
    {
        Storage::fake('public');

        $creation = Creation::create([
            'titre_fr' => 'Coffret Éloïse',
            'slug_fr' => 'coffret-eloise',
            'publie' => true,
        ]);

        ImageService::make()->attacher(
            UploadedFile::fake()->image('photo.jpg', 1600, 2000),
            $creation,
            ['ordre' => 0, 'alt_fr' => 'Boîte à fleurs blush avec prénom doré']
        );

        $reponse = $this->get('/creations/coffret-eloise');

        $reponse->assertOk()
            // Le chemin public passe par le lien storage
            ->assertSee('/storage/media/creations/', false)
            ->assertSee('.webp', false)
            // srcset : sans lui, un mobile téléchargerait l'image pleine taille
            ->assertSee('srcset', false)
            // alt écrit à la main : SEO image et accessibilité
            ->assertSee('Boîte à fleurs blush avec prénom doré', false);
    }

    #[Test]
    public function une_creation_sans_photo_reste_presentable(): void
    {
        Creation::create(['titre_fr' => 'Sans photo', 'slug_fr' => 'sans-photo', 'publie' => true]);

        $this->get('/creations/sans-photo')->assertOk()->assertSee('Sans photo');
    }
}
