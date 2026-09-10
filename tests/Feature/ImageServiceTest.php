<?php

namespace Tests\Feature;

use App\Models\Creation;
use App\Services\ImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImageServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function creation(): Creation
    {
        return Creation::create([
            'titre_fr' => 'Coffret Éloïse',
            'slug_fr' => 'coffret-eloise',
            'publie' => true,
        ]);
    }

    /**
     * Une image de largeur donnée, en WebP.
     */
    private function image(int $largeur, int $hauteur): UploadedFile
    {
        return UploadedFile::fake()->image('photo.jpg', $largeur, $hauteur);
    }

    #[Test]
    public function il_genere_toutes_les_variantes_utiles(): void
    {
        $media = ImageService::make()->attacher(
            $this->image(2000, 2500),
            $this->creation()
        );

        // 400/800/1200/1600 : toutes tiennent dans un original de 2000 px.
        // Les clés numériques sont normalisées en entiers par PHP.
        $this->assertSame([400, 800, 1200, 1600], array_keys($media->variantes));

        foreach ($media->variantes as $chemin) {
            Storage::disk('public')->assertExists($chemin);
        }
    }

    #[Test]
    public function chaque_variante_a_la_bonne_largeur(): void
    {
        // Le vrai piège : scale() modifie l'image EN PLACE dans Intervention 3.
        // Sans clone, la variante 400 réduirait l'original et les suivantes
        // seraient générées depuis une source déjà dégradée — ou sautées.
        $media = ImageService::make()->attacher(
            $this->image(2000, 2500),
            $this->creation()
        );

        foreach ([400, 800, 1200, 1600] as $attendue) {
            $chemin = Storage::disk('public')->path($media->variantes[$attendue]);
            [$largeur] = getimagesize($chemin);

            $this->assertSame(
                $attendue,
                $largeur,
                "La variante {$attendue} mesure {$largeur} px — l'original a probablement été modifié en place."
            );
        }
    }

    #[Test]
    public function il_n_agrandit_jamais_une_petite_image(): void
    {
        // Une variante 1600 depuis un original de 900 px serait plus lourde
        // sans être plus nette.
        $media = ImageService::make()->attacher(
            $this->image(900, 1125),
            $this->creation()
        );

        $this->assertSame([400, 800], array_keys($media->variantes));
    }

    #[Test]
    public function il_reencode_en_webp(): void
    {
        // Réencodage systématique : neutralise une charge utile dissimulée
        // dans un fichier qui se présente comme une image.
        $media = ImageService::make()->attacher(
            $this->image(1200, 1500),
            $this->creation()
        );

        $this->assertSame('image/webp', $media->mime);
        $this->assertStringEndsWith('.webp', $media->chemin);
    }

    #[Test]
    public function il_ne_reprend_pas_le_nom_de_fichier_fourni(): void
    {
        // Un nom fourni par l'utilisateur peut contenir une traversée de
        // chemin ou des caractères qui cassent le stockage.
        $media = ImageService::make()->attacher(
            UploadedFile::fake()->image('../../etc/passwd.jpg', 1200, 1500),
            $this->creation()
        );

        $this->assertStringNotContainsString('passwd', $media->chemin);
        $this->assertStringNotContainsString('..', $media->chemin);
    }

    #[Test]
    public function il_construit_un_srcset_exploitable(): void
    {
        $media = ImageService::make()->attacher(
            $this->image(2000, 2500),
            $this->creation()
        );

        $srcset = $media->srcset();

        $this->assertStringContainsString('400w', $srcset);
        $this->assertStringContainsString('1600w', $srcset);
    }

    #[Test]
    public function il_supprime_original_et_variantes(): void
    {
        $service = ImageService::make();
        $media = $service->attacher($this->image(2000, 2500), $this->creation());

        $chemins = [$media->chemin, ...array_values($media->variantes)];

        $service->supprimerFichiers($media);

        foreach ($chemins as $chemin) {
            Storage::disk('public')->assertMissing($chemin);
        }
    }
}
