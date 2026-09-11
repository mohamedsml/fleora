<?php

namespace Tests\Feature;

use App\Models\Creation;
use App\Models\CustomRequest;
use App\Models\Media;
use App\Models\Occasion;
use App\Models\User;
use App\Models\Visite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use ZipArchive;

/**
 * Rapatriement du catalogue de production vers un poste de développement.
 *
 * L'aller-retour doit être intègre : une création sans ses occasions ni ses
 * photos n'est pas récupérée, elle est seulement recopiée à moitié.
 */
class ExportContenuTest extends TestCase
{
    use RefreshDatabase;

    private string $dossier;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->dossier = storage_path('framework/testing/exports');
        File::ensureDirectoryExists($this->dossier);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dossier);

        parent::tearDown();
    }

    private function exporter(array $options = []): string
    {
        $this->artisan('fleora:exporter-contenu', ['--sortie' => $this->dossier, ...$options])
            ->assertSuccessful();

        return collect(File::files($this->dossier))->sortByDesc->getMTime()->first()->getPathname();
    }

    private function creationAvecPhoto(): Creation
    {
        $occasion = Occasion::create([
            'nom_fr' => 'Mariage', 'slug_fr' => 'mariage', 'publie' => true,
        ]);

        $creation = Creation::create([
            'titre_fr' => 'Éclat de roses', 'slug_fr' => 'eclat-de-roses',
            'prix_min' => 6500, 'publie' => true,
        ]);

        $creation->occasions()->attach($occasion);

        Storage::disk('public')->put('creations/photo.jpg', 'contenu-original');
        Storage::disk('public')->put('creations/photo-400.webp', 'contenu-variante');

        Media::create([
            'mediable_type' => Creation::class,
            'mediable_id' => $creation->id,
            'chemin' => 'creations/photo.jpg',
            'disque' => 'public',
            'largeur' => 1200, 'hauteur' => 1200,
            'alt_fr' => 'Boîte rose',
            'variantes' => ['400' => 'creations/photo-400.webp'],
        ]);

        return $creation;
    }

    // ── Export ──────────────────────────────────────────────────────────

    #[Test]
    public function l_archive_contient_les_donnees_et_les_photos(): void
    {
        $this->creationAvecPhoto();

        $zip = new ZipArchive;
        $zip->open($this->exporter());

        $this->assertNotFalse($zip->getFromName('contenu.json'));
        // L'original ET la variante : une galerie sans variantes affiche des
        // images cassées sur mobile.
        $this->assertNotFalse($zip->getFromName('photos/creations/photo.jpg'));
        $this->assertNotFalse($zip->getFromName('photos/creations/photo-400.webp'));

        $zip->close();
    }

    #[Test]
    public function aucune_donnee_personnelle_n_est_exportee(): void
    {
        // Le point le plus important : cette archive descend sur un poste de
        // développement. Les demandes clientes (Loi 25), les comptes et les
        // statistiques n'ont rien à y faire.
        $this->creationAvecPhoto();

        User::factory()->create(['email' => 'admin@fleora.ca']);
        CustomRequest::create([
            'nom' => 'Sarah', 'courriel' => 'sarah@example.com',
            'consentement' => true, 'consentement_le' => now(),
        ]);
        Visite::create(['chemin' => '/', 'langue' => 'fr', 'empreinte' => str_repeat('a', 64)]);

        $zip = new ZipArchive;
        $zip->open($this->exporter());
        $json = $zip->getFromName('contenu.json');
        $zip->close();

        $this->assertStringNotContainsString('sarah@example.com', $json);
        $this->assertStringNotContainsString('admin@fleora.ca', $json);

        $donnees = json_decode($json, true);
        $this->assertArrayNotHasKey('users', $donnees);
        $this->assertArrayNotHasKey('requests', $donnees);
        $this->assertArrayNotHasKey('visites', $donnees);
    }

    #[Test]
    public function l_option_sans_photos_allege_l_archive(): void
    {
        $this->creationAvecPhoto();

        $zip = new ZipArchive;
        $zip->open($this->exporter(['--sans-photos' => true]));

        $this->assertNotFalse($zip->getFromName('contenu.json'));
        $this->assertFalse($zip->getFromName('photos/creations/photo.jpg'));

        $zip->close();
    }

    // ── Aller-retour ────────────────────────────────────────────────────

    #[Test]
    public function le_catalogue_est_restaure_a_l_identique(): void
    {
        $origine = $this->creationAvecPhoto();
        $archive = $this->exporter();

        $this->viderLeCatalogue();
        Storage::disk('public')->delete(['creations/photo.jpg', 'creations/photo-400.webp']);

        $this->artisan('fleora:importer-contenu', ['archive' => $archive, '--force' => true])
            ->assertSuccessful();

        $restauree = Creation::where('slug_fr', 'eclat-de-roses')->firstOrFail();

        // L'identifiant est conservé : les médias et le pivot le référencent.
        $this->assertSame($origine->id, $restauree->id);
        $this->assertSame(6500, $restauree->prix_min);
        $this->assertSame(['mariage'], $restauree->occasions->pluck('slug_fr')->all());
    }

    #[Test]
    public function les_fichiers_photo_sont_restaures_avec_leur_contenu(): void
    {
        // Restaurer la ligne `media` sans le fichier laisserait une galerie
        // d'images cassées — le pire résultat : l'erreur ne se voit qu'à
        // l'affichage.
        $this->creationAvecPhoto();
        $archive = $this->exporter();

        $this->viderLeCatalogue();
        Storage::disk('public')->delete(['creations/photo.jpg', 'creations/photo-400.webp']);

        $this->artisan('fleora:importer-contenu', ['archive' => $archive, '--force' => true])
            ->assertSuccessful();

        $this->assertSame('contenu-original', Storage::disk('public')->get('creations/photo.jpg'));
        $this->assertSame('contenu-variante', Storage::disk('public')->get('creations/photo-400.webp'));

        $media = Media::firstOrFail();
        $this->assertSame('Boîte rose', $media->alt_fr);
        $this->assertSame(['400' => 'creations/photo-400.webp'], $media->variantes);
    }

    #[Test]
    public function un_import_n_ajoute_pas_de_doublon(): void
    {
        $this->creationAvecPhoto();
        $archive = $this->exporter();

        $this->artisan('fleora:importer-contenu', ['archive' => $archive, '--force' => true])
            ->assertSuccessful();

        $this->assertSame(1, Creation::count());
        $this->assertSame(1, Occasion::count());
    }

    #[Test]
    public function les_demandes_locales_survivent_a_un_import(): void
    {
        // L'import remplace le catalogue, pas la base entière.
        $this->creationAvecPhoto();
        $archive = $this->exporter();

        CustomRequest::create([
            'nom' => 'Test', 'courriel' => 'test@example.com',
            'consentement' => true, 'consentement_le' => now(),
        ]);

        $this->artisan('fleora:importer-contenu', ['archive' => $archive, '--force' => true])
            ->assertSuccessful();

        $this->assertSame(1, CustomRequest::count());
    }

    #[Test]
    public function une_archive_introuvable_echoue_proprement(): void
    {
        $this->artisan('fleora:importer-contenu', [
            'archive' => $this->dossier.'/absente.zip', '--force' => true,
        ])->assertFailed();
    }

    #[Test]
    public function l_import_refuse_de_tourner_en_production(): void
    {
        // Elle remplacerait le catalogue réel par une copie datée.
        $this->creationAvecPhoto();
        $archive = $this->exporter();

        app()['env'] = 'production';

        $this->artisan('fleora:importer-contenu', ['archive' => $archive, '--force' => true])
            ->assertFailed();
    }

    private function viderLeCatalogue(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Media::query()->delete();
        Creation::query()->delete();
        Occasion::query()->delete();
        DB::table('creation_occasion')->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
