<?php

namespace Tests\Feature;

use App\Models\Creation;
use App\Models\Faq;
use App\Models\Occasion;
use Database\Seeders\TraductionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Traduction du contenu — fichiers de langue et base de données.
 */
class ContenuBilingueTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function les_fichiers_de_langue_sont_a_parite(): void
    {
        // Une clé présente en français mais absente en anglais affiche la clé
        // brute à l'écran (« pages.contact.titre »), pas un texte.
        $fichiers = array_map('basename', glob(lang_path('fr/*.php')));

        foreach ($fichiers as $fichier) {
            $fr = require lang_path("fr/{$fichier}");
            $cheminEn = lang_path("en/{$fichier}");

            $this->assertFileExists($cheminEn, "lang/en/{$fichier} manque.");

            $manquantes = array_diff_key(
                \Arr::dot($fr),
                \Arr::dot(require $cheminEn)
            );

            $this->assertSame(
                [],
                $manquantes,
                "Clés absentes de lang/en/{$fichier} : ".implode(', ', array_keys($manquantes))
            );
        }
    }

    #[Test]
    public function les_pages_affichent_leur_texte_traduit(): void
    {
        // assertStringContainsString plutôt qu'assertSee : ce dernier échappe
        // la chaîne attendue, ce qui ne correspond plus au HTML dès qu'il y a
        // un accent ou une apostrophe typographique.
        $attendus = [
            '/' => 'Des boîtes décorées',
            '/en' => 'Decorated boxes',
            '/contact' => 'Parlons de votre projet',
            '/en/contact' => 'Let us talk about your project',
            '/a-propos' => 'Bienvenue chez',
            '/en/about' => 'Welcome to',
        ];

        foreach ($attendus as $url => $texte) {
            $this->assertStringContainsString(
                $texte,
                $this->get($url)->getContent(),
                "« {$texte} » absent de {$url}"
            );
        }
    }

    #[Test]
    public function le_contenu_de_la_base_suit_la_langue(): void
    {
        Occasion::create([
            'nom_fr' => 'Mariage', 'slug_fr' => 'mariage',
            'nom_en' => 'Weddings', 'slug_en' => 'wedding',
            'intro_fr' => 'Cadeaux pour vos invités.',
            'intro_en' => 'Gifts for your guests.',
            'publie' => true,
        ]);

        $this->assertStringContainsString('Cadeaux pour vos invités', $this->get('/occasions/mariage')->getContent());
        $this->assertStringContainsString('Gifts for your guests', $this->get('/en/occasions/wedding')->getContent());
    }

    #[Test]
    public function un_contenu_non_traduit_retombe_sur_le_francais(): void
    {
        // Un site à moitié traduit doit rester lisible plutôt que d'afficher
        // des trous : c'est la règle du trait HasTranslations.
        Creation::create([
            'titre_fr' => 'Boîte automne',
            'slug_fr' => 'boite-automne',
            'publie' => true,
        ]);

        $this->assertStringContainsString('Boîte automne', $this->get('/en/creations/boite-automne')->getContent());
    }

    #[Test]
    public function les_categories_de_faq_sont_traduites(): void
    {
        // Sans colonne anglaise, /en/faq afficherait « Livraison » au milieu de
        // questions anglaises.
        Faq::create([
            'question_fr' => 'Livrez-vous ?', 'reponse_fr' => 'Oui.',
            'question_en' => 'Do you deliver?', 'reponse_en' => 'Yes.',
            'categorie_fr' => 'Livraison', 'categorie_en' => 'Delivery',
            'publie' => true,
        ]);

        $this->get('/faq')->assertSee('Livraison')->assertDontSee('Delivery');
        $this->get('/en/faq')->assertSee('Delivery')->assertDontSee('Livraison');
    }

    #[Test]
    public function la_politique_anglaise_signale_la_prevalence_du_francais(): void
    {
        // La version française fait foi au Québec : le lecteur anglophone doit
        // le savoir avant de s'y fier.
        $this->get('/en/privacy')->assertSee('French version prevails', false);
        $this->get('/confidentialite')->assertDontSee('prevails', false);
    }

    #[Test]
    public function le_seeder_de_traductions_est_idempotent(): void
    {
        // Il doit pouvoir être rejoué en production sans écraser une correction
        // saisie dans Filament.
        $faq = Faq::create([
            'question_fr' => 'La soumission est-elle payante ?',
            'reponse_fr' => 'Non.',
            'question_en' => 'CORRECTION MANUELLE',
            'publie' => true,
        ]);

        $this->seed(TraductionsSeeder::class);

        $this->assertSame('CORRECTION MANUELLE', $faq->fresh()->question_en);
    }

    #[Test]
    public function le_seeder_remplit_les_champs_vides(): void
    {
        $faq = Faq::create([
            'question_fr' => 'La soumission est-elle payante ?',
            'reponse_fr' => 'Non.',
            'publie' => true,
        ]);

        $this->assertNull($faq->question_en);

        $this->seed(TraductionsSeeder::class);

        $this->assertNotNull($faq->fresh()->question_en);
    }
}
