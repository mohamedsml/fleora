<?php

namespace Tests\Feature;

use App\Models\Creation;
use App\Models\Faq;
use App\Models\Occasion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Sitemap, robots et données structurées.
 */
class SeoTest extends TestCase
{
    use RefreshDatabase;

    private function contenu(): void
    {
        $occasion = Occasion::create([
            'nom_fr' => 'Mariage', 'slug_fr' => 'mariage',
            'nom_en' => 'Weddings', 'slug_en' => 'wedding',
            'publie' => true,
        ]);

        Creation::create([
            'titre_fr' => 'Coffret', 'slug_fr' => 'coffret',
            'titre_en' => 'Gift box', 'slug_en' => 'gift-box',
            'prix_min' => 4500, 'prix_max' => 9000,
            'publie' => true,
        ])->occasions()->attach($occasion);
    }

    // ── Sitemap ─────────────────────────────────────────────────────────

    #[Test]
    public function le_sitemap_liste_les_deux_langues(): void
    {
        $this->contenu();

        $this->artisan('fleora:sitemap')->assertSuccessful();

        $xml = file_get_contents(public_path('sitemap.xml'));

        $this->assertStringContainsString(url('/creations'), $xml);
        $this->assertStringContainsString(url('/en/creations'), $xml);
        $this->assertStringContainsString(url('/occasions/mariage'), $xml);
        $this->assertStringContainsString(url('/en/occasions/wedding'), $xml);
    }

    #[Test]
    public function le_sitemap_declare_des_hreflang_reciproques(): void
    {
        // Un bloc non réciproque est purement ignoré par Google.
        $this->artisan('fleora:sitemap');

        $xml = file_get_contents(public_path('sitemap.xml'));

        $this->assertStringContainsString('hreflang="fr-CA"', $xml);
        $this->assertStringContainsString('hreflang="en-CA"', $xml);
        $this->assertStringContainsString('hreflang="x-default"', $xml);
    }

    #[Test]
    public function le_sitemap_exclut_la_page_de_remerciement(): void
    {
        // Elle porte noindex et n'a de sens qu'après un envoi.
        $this->artisan('fleora:sitemap');

        $this->assertStringNotContainsString(
            url('/merci'),
            file_get_contents(public_path('sitemap.xml'))
        );
    }

    #[Test]
    public function le_sitemap_est_un_xml_valide(): void
    {
        $this->contenu();
        $this->artisan('fleora:sitemap');

        $xml = simplexml_load_file(public_path('sitemap.xml'));

        $this->assertNotFalse($xml, 'Le sitemap n’est pas un XML valide.');
        $this->assertGreaterThan(0, count($xml->url));
    }

    #[Test]
    public function robots_declare_le_sitemap_et_protege_l_administration(): void
    {
        $robots = file_get_contents(public_path('robots.txt'));

        $this->assertStringContainsString('Sitemap:', $robots);
        $this->assertStringContainsString('Disallow: /admin', $robots);
    }

    // ── Données structurées ─────────────────────────────────────────────

    #[Test]
    public function l_accueil_declare_l_entreprise(): void
    {
        // Le signal SEO local le plus direct.
        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('"@type":"Store"', $html);
        $this->assertStringContainsString('areaServed', $html);
        $this->assertStringContainsString('Montréal', $html);
    }

    #[Test]
    public function les_pages_internes_ont_un_fil_d_ariane(): void
    {
        // Google l'affiche à la place de l'URL brute : un chemin lisible
        // inspire plus confiance qu'une adresse.
        $this->contenu();

        foreach (['/contact', '/faq', '/creations/coffret', '/occasions/mariage'] as $url) {
            $this->assertStringContainsString(
                '"@type":"BreadcrumbList"',
                $this->get($url)->getContent(),
                "Fil d'Ariane absent de {$url}"
            );
        }
    }

    #[Test]
    public function une_creation_declare_sa_fourchette_de_prix(): void
    {
        $this->contenu();

        $html = $this->get('/creations/coffret')->getContent();

        $this->assertStringContainsString('"@type":"Product"', $html);
        $this->assertStringContainsString('AggregateOffer', $html);
        $this->assertStringContainsString('45', $html);
        // MadeToOrder : chaque pièce est faite à la commande, annoncer du
        // stock serait faux.
        $this->assertStringContainsString('MadeToOrder', $html);
    }

    #[Test]
    public function les_donnees_structurees_suivent_la_langue(): void
    {
        $this->contenu();

        $this->assertStringContainsString(
            'Weddings',
            $this->get('/en/occasions/wedding')->getContent()
        );
    }

    #[Test]
    public function le_json_ld_est_valide(): void
    {
        // Un JSON mal formé est ignoré en silence par Google.
        $this->contenu();

        Faq::create([
            'question_fr' => 'Délai ?', 'reponse_fr' => 'Deux semaines.',
            'publie' => true, 'sur_accueil' => true,
        ]);

        foreach (['/', '/contact', '/faq', '/creations/coffret'] as $url) {
            preg_match_all(
                '~<script type="application/ld\+json">(.*?)</script>~s',
                $this->get($url)->getContent(),
                $blocs
            );

            $this->assertNotEmpty($blocs[1], "Aucun JSON-LD sur {$url}");

            foreach ($blocs[1] as $json) {
                $this->assertNotNull(
                    json_decode($json),
                    "JSON-LD invalide sur {$url} : ".json_last_error_msg()
                );
            }
        }
    }
}
