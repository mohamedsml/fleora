<?php

namespace App\Console\Commands;

use App\Models\Creation;
use App\Models\Occasion;
use Illuminate\Console\Command;

/**
 * Génère public/sitemap.xml dans les deux langues.
 *
 * Fichier statique plutôt que route dynamique : sur un hébergement mutualisé,
 * un sitemap calculé à chaque appel est une requête de plus sur la base pour un
 * contenu qui change quelques fois par mois. Apache le sert directement.
 *
 * Regénéré à chaque déploiement (voir deploy/deploy.sh).
 */
class GenererSitemap extends Command
{
    protected $signature = 'fleora:sitemap';

    protected $description = 'Génère public/sitemap.xml avec les deux langues';

    public function handle(): int
    {
        $urls = [
            ...$this->pagesStatiques(),
            ...$this->pagesDeContenu(),
        ];

        file_put_contents(public_path('sitemap.xml'), $this->xml($urls));

        $this->info(count($urls).' URL écrites dans public/sitemap.xml');

        return self::SUCCESS;
    }

    /**
     * Une entrée par page, chacune déclarant ses équivalents dans les autres
     * langues.
     *
     * @return array<int, array{routes: array<string,string>, priorite: string, frequence: string}>
     */
    private function pagesStatiques(): array
    {
        // La page de remerciement est exclue : elle porte `noindex` et n'a de
        // sens qu'après un envoi.
        $pages = [
            'accueil' => ['1.0', 'weekly'],
            'creations' => ['0.9', 'weekly'],
            'occasions' => ['0.9', 'monthly'],
            'demande' => ['0.8', 'monthly'],
            'a-propos' => ['0.6', 'yearly'],
            'contact' => ['0.6', 'yearly'],
            'faq' => ['0.6', 'monthly'],
            'confidentialite' => ['0.3', 'yearly'],
        ];

        $entrees = [];

        foreach ($pages as $nom => [$priorite, $frequence]) {
            $routes = [];

            foreach (config('app.locales') as $langue) {
                $routes[$langue] = route_langue($nom, [], $langue);
            }

            $entrees[] = compact('routes', 'priorite', 'frequence');
        }

        return $entrees;
    }

    /**
     * Créations et occasions publiées, avec leurs slugs propres à chaque langue.
     */
    private function pagesDeContenu(): array
    {
        $entrees = [];

        foreach (Occasion::publie()->get() as $occasion) {
            $routes = [];

            foreach (config('app.locales') as $langue) {
                $routes[$langue] = route_langue('occasions.show', $occasion->slugPour($langue), $langue);
            }

            // Priorité haute : ce sont les pages à forte intention d'achat.
            $entrees[] = ['routes' => $routes, 'priorite' => '0.8', 'frequence' => 'monthly'];
        }

        foreach (Creation::publie()->get() as $creation) {
            $routes = [];

            foreach (config('app.locales') as $langue) {
                $routes[$langue] = route_langue('creations.show', $creation->slugPour($langue), $langue);
            }

            $entrees[] = ['routes' => $routes, 'priorite' => '0.7', 'frequence' => 'monthly'];
        }

        return $entrees;
    }

    /**
     * Chaque URL déclare toutes ses versions linguistiques via `xhtml:link`.
     *
     * Les déclarations doivent être réciproques — une page listant ses
     * alternatives sans qu'elles la listent en retour voit tout le bloc ignoré
     * par Google. Générer les deux langues dans la même boucle le garantit.
     */
    private function xml(array $entrees): string
    {
        $defaut = config('app.locales')[0];

        $lignes = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"',
            '        xmlns:xhtml="http://www.w3.org/1999/xhtml">',
        ];

        foreach ($entrees as $entree) {
            foreach ($entree['routes'] as $langue => $url) {
                $lignes[] = '  <url>';
                $lignes[] = '    <loc>'.e($url).'</loc>';

                foreach ($entree['routes'] as $autreLangue => $autreUrl) {
                    $code = $autreLangue === 'fr' ? 'fr-CA' : 'en-CA';
                    $lignes[] = '    <xhtml:link rel="alternate" hreflang="'.$code.'" href="'.e($autreUrl).'"/>';
                }

                // x-default vers le français : marché principal.
                $lignes[] = '    <xhtml:link rel="alternate" hreflang="x-default" href="'.e($entree['routes'][$defaut]).'"/>';

                $lignes[] = '    <changefreq>'.$entree['frequence'].'</changefreq>';
                $lignes[] = '    <priority>'.$entree['priorite'].'</priority>';
                $lignes[] = '  </url>';
            }
        }

        $lignes[] = '</urlset>';

        return implode("\n", $lignes)."\n";
    }
}
