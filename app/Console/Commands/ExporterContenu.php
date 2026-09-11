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
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * Exporte le contenu éditorial dans une archive autonome.
 *
 * Sert à rapatrier la production en local sans toucher aux données clientes :
 * l'archive contient le catalogue et les photos, jamais les demandes, les
 * devis, les comptes ni les statistiques de visite.
 *
 *     php artisan fleora:exporter-contenu
 *
 * Les photos sont incluses parce qu'une création sans sa photo n'est pas
 * récupérable : le fichier vit sur le disque, pas dans la base, et un simple
 * export SQL laisserait une galerie d'images cassées.
 */
class ExporterContenu extends Command
{
    protected $signature = 'fleora:exporter-contenu
                            {--sortie= : Chemin de l’archive (défaut : storage/app/exports)}
                            {--sans-photos : N’exporter que les données}';

    protected $description = 'Exporte le catalogue et ses photos dans une archive .zip';

    public function handle(): int
    {
        $donnees = $this->collecter();

        $dossier = $this->option('sortie') ?: storage_path('app/exports');
        File::ensureDirectoryExists($dossier);

        $archive = rtrim($dossier, '/').'/contenu-'.now()->format('Y-m-d_His').'.zip';

        $zip = new ZipArchive;

        if ($zip->open($archive, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->components->error("Impossible de créer l’archive : {$archive}");

            return self::FAILURE;
        }

        $zip->addFromString('contenu.json', json_encode(
            $donnees,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        ));

        $photos = $this->option('sans-photos') ? 0 : $this->ajouterPhotos($zip);

        $zip->close();

        // `components->info()` interprète les barres obliques comme des
        // séparateurs de style et affiche un chemin illisible.
        $this->newLine();
        $this->line('  <fg=green>Archive :</> '.$archive);
        $this->table(
            ['Élément', 'Nombre'],
            [
                ['Créations', count($donnees['creations'])],
                ['Occasions', count($donnees['occasions'])],
                ['Types de produit', count($donnees['product_types'])],
                ['FAQ', count($donnees['faqs'])],
                ['Témoignages', count($donnees['testimonials'])],
                ['Médias', count($donnees['media'])],
                ['Fichiers photo', $photos],
                ['Poids', $this->poids($archive)],
            ],
        );

        return self::SUCCESS;
    }

    /**
     * Le contenu éditorial, et lui seul.
     *
     * Ni `users`, ni `requests`, ni `visites` : ces tables contiennent des
     * renseignements personnels de clientes (Loi 25) et des accès. Elles n'ont
     * rien à faire sur un poste de développement.
     *
     * @return array<string, mixed>
     */
    private function collecter(): array
    {
        return [
            'exporte_le' => now()->toIso8601String(),
            'source' => config('app.url'),

            'product_types' => ProductType::orderBy('id')->get()->toArray(),
            'occasions' => Occasion::orderBy('id')->get()->toArray(),

            'creations' => Creation::with('occasions:id')
                ->orderBy('id')
                ->get()
                ->map(function (Creation $creation) {
                    $ligne = $creation->attributesToArray();
                    // Le pivot est exporté en même temps : sans lui, les
                    // filtres par occasion seraient vides après import.
                    $ligne['occasion_ids'] = $creation->occasions->pluck('id')->all();

                    return $ligne;
                })
                ->all(),

            'faqs' => Faq::orderBy('id')->get()->toArray(),
            'testimonials' => Testimonial::orderBy('id')->get()->toArray(),
            'site_settings' => SiteSetting::orderBy('id')->get()->toArray(),
            'media' => Media::orderBy('id')->get()->toArray(),
        ];
    }

    /**
     * Ajoute les fichiers référencés par la table `media`.
     *
     * On suit les chemins enregistrés plutôt que de copier tout le dossier :
     * les fichiers orphelins d'anciens téléversements ne sont pas emportés.
     */
    private function ajouterPhotos(ZipArchive $zip): int
    {
        $ajoutes = 0;
        $manquants = [];

        foreach (Media::all() as $media) {
            $disque = Storage::disk($media->disque);

            // L'original, puis chaque variante de largeur.
            $chemins = array_merge(
                [$media->chemin],
                array_values($media->variantes ?? []),
            );

            foreach (array_unique($chemins) as $chemin) {
                if (blank($chemin)) {
                    continue;
                }

                if (! $disque->exists($chemin)) {
                    $manquants[] = $chemin;

                    continue;
                }

                $zip->addFromString("photos/{$chemin}", $disque->get($chemin));
                $ajoutes++;
            }
        }

        if ($manquants !== []) {
            $this->components->warn(
                count($manquants).' fichier(s) référencés en base mais absents du disque :'
            );
            foreach (array_slice($manquants, 0, 5) as $chemin) {
                $this->line("  {$chemin}");
            }
        }

        return $ajoutes;
    }

    private function poids(string $chemin): string
    {
        $octets = filesize($chemin) ?: 0;

        return $octets > 1048576
            ? round($octets / 1048576, 1).' Mo'
            : round($octets / 1024).' Ko';
    }
}
