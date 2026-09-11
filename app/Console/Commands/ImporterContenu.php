<?php

namespace App\Console\Commands;

use App\Models\Creation;
use App\Models\Faq;
use App\Models\Media;
use App\Models\Occasion;
use App\Models\ProductType;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

/**
 * Importe une archive produite par `fleora:exporter-contenu`.
 *
 *     php artisan fleora:importer-contenu storage/app/exports/contenu-….zip
 *
 * Les identifiants d'origine sont conservés : les liens entre créations,
 * occasions et photos reposent dessus, et les renuméroter obligerait à
 * reconstruire toutes les références.
 *
 * ⚠️ Destiné au développement. La commande refuse de s'exécuter en production,
 * où elle écraserait le catalogue réel par une copie datée.
 */
class ImporterContenu extends Command
{
    protected $signature = 'fleora:importer-contenu
                            {archive : Chemin de l’archive .zip}
                            {--force : Ne pas demander confirmation}';

    protected $description = 'Importe le catalogue et ses photos depuis une archive';

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->components->error(
                'Refusé en production : cette commande remplacerait le catalogue '
                .'réel par une copie datée.'
            );

            return self::FAILURE;
        }

        $chemin = $this->argument('archive');

        if (! is_file($chemin)) {
            $this->components->error("Archive introuvable : {$chemin}");

            return self::FAILURE;
        }

        $zip = new ZipArchive;

        if ($zip->open($chemin) !== true) {
            $this->components->error('Archive illisible.');

            return self::FAILURE;
        }

        $json = $zip->getFromName('contenu.json');

        if ($json === false) {
            $this->components->error('Archive invalide : contenu.json absent.');
            $zip->close();

            return self::FAILURE;
        }

        $donnees = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        $this->components->info('Archive du '.($donnees['exporte_le'] ?? '?')
            .' — source : '.($donnees['source'] ?? '?'));

        if (! $this->option('force') && ! $this->confirm(
            'Le catalogue local (créations, occasions, FAQ, photos) sera remplacé. Continuer ?',
            false,
        )) {
            $zip->close();
            $this->line('Annulé.');

            return self::SUCCESS;
        }

        try {
            DB::transaction(fn () => $this->remplacer($donnees));
        } catch (\Throwable $e) {
            $zip->close();
            $this->components->error('Import annulé, rien n’a été modifié : '.$e->getMessage());

            return self::FAILURE;
        }

        $photos = $this->restaurerPhotos($zip, $donnees['media'] ?? []);
        $zip->close();

        $this->components->info('Import terminé.');
        $this->table(
            ['Élément', 'Nombre'],
            [
                ['Créations', Creation::count()],
                ['Occasions', Occasion::count()],
                ['Types de produit', ProductType::count()],
                ['FAQ', Faq::count()],
                ['Médias', Media::count()],
                ['Fichiers photo restaurés', $photos],
            ],
        );

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $donnees
     */
    private function remplacer(array $donnees): void
    {
        // Les clés étrangères refuseraient un vidage dans cet ordre ; on les
        // suspend le temps de la transaction plutôt que de trier les tables.
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach ([Media::class, Testimonial::class, Creation::class,
                Occasion::class, ProductType::class, Faq::class] as $modele) {
                /** @var class-string<Model> $modele */
                $modele::query()->delete();
            }

            DB::table('creation_occasion')->delete();

            $this->inserer(ProductType::class, $donnees['product_types'] ?? []);
            $this->inserer(Occasion::class, $donnees['occasions'] ?? []);
            $this->inserer(Faq::class, $donnees['faqs'] ?? []);

            foreach ($donnees['creations'] ?? [] as $ligne) {
                $occasions = $ligne['occasion_ids'] ?? [];
                unset($ligne['occasion_ids']);

                $creation = new Creation;
                $creation->forceFill($ligne)->save();

                if ($occasions !== []) {
                    $creation->occasions()->sync($occasions);
                }
            }

            $this->inserer(Testimonial::class, $donnees['testimonials'] ?? []);
            $this->inserer(Media::class, $donnees['media'] ?? []);

            // Les réglages sont fusionnés et non remplacés : le .env local
            // peut légitimement différer de la production.
            foreach ($donnees['site_settings'] ?? [] as $reglage) {
                SiteSetting::updateOrCreate(
                    ['cle' => $reglage['cle']],
                    ['valeur' => $reglage['valeur'] ?? null],
                );
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * @param  class-string<Model>  $modele
     * @param  array<int, array<string, mixed>>  $lignes
     */
    private function inserer(string $modele, array $lignes): void
    {
        foreach ($lignes as $ligne) {
            // forceFill contourne la protection d'assignation de masse : on
            // restaure des lignes complètes, `id` et horodatages compris.
            (new $modele)->forceFill($ligne)->save();
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $media
     */
    private function restaurerPhotos(ZipArchive $zip, array $media): int
    {
        $restaures = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $nom = $zip->getNameIndex($i);

            if ($nom === false || ! str_starts_with($nom, 'photos/')) {
                continue;
            }

            $relatif = substr($nom, strlen('photos/'));

            if ($relatif === '' || str_ends_with($relatif, '/')) {
                continue;
            }

            // Une archive peut contenir des chemins remontants : on refuse
            // d'écrire hors du disque de destination.
            if (str_contains($relatif, '..')) {
                throw new RuntimeException("Chemin d’archive refusé : {$relatif}");
            }

            $contenu = $zip->getFromIndex($i);

            if ($contenu === false) {
                continue;
            }

            Storage::disk('public')->put($relatif, $contenu);
            $restaures++;
        }

        return $restaures;
    }
}
