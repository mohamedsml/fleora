<?php

namespace Database\Seeders;

use App\Models\Creation;
use App\Models\Faq;
use App\Models\Occasion;
use App\Models\ProductType;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

/**
 * Données de démonstration pour explorer le back-office.
 *
 * Les occasions et types de produit correspondent au vrai catalogue visé ; les
 * créations sont fictives et servent à voir l'interface peuplée. À remplacer
 * par le contenu réel avant la mise en ligne.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $types = collect([
            ['nom_fr' => 'Boîte à fleurs', 'nom_en' => 'Flower box', 'slug_fr' => 'boite-a-fleurs', 'slug_en' => 'flower-box'],
            ['nom_fr' => 'Coffret prénom', 'nom_en' => 'Name box', 'slug_fr' => 'coffret-prenom', 'slug_en' => 'name-box'],
            ['nom_fr' => 'Boîte cadeau', 'nom_en' => 'Gift box', 'slug_fr' => 'boite-cadeau', 'slug_en' => 'gift-box'],
            ['nom_fr' => 'Lot pour invités', 'nom_en' => 'Guest favours', 'slug_fr' => 'lot-invites', 'slug_en' => 'guest-favours'],
        ])->map(fn ($t, $i) => ProductType::updateOrCreate(
            ['slug_fr' => $t['slug_fr']],
            $t + ['ordre' => $i * 10, 'publie' => true],
        ));

        $occasions = collect([
            ['nom_fr' => 'Mariage', 'nom_en' => 'Wedding', 'slug_fr' => 'mariage', 'slug_en' => 'wedding'],
            ['nom_fr' => 'Baby shower', 'nom_en' => 'Baby shower', 'slug_fr' => 'baby-shower', 'slug_en' => 'baby-shower'],
            ['nom_fr' => 'Baptême', 'nom_en' => 'Baptism', 'slug_fr' => 'bapteme', 'slug_en' => 'baptism'],
            ['nom_fr' => 'Anniversaire', 'nom_en' => 'Birthday', 'slug_fr' => 'anniversaire', 'slug_en' => 'birthday'],
            ['nom_fr' => 'Graduation', 'nom_en' => 'Graduation', 'slug_fr' => 'graduation', 'slug_en' => 'graduation'],
            ['nom_fr' => 'Cadeau personnalisé', 'nom_en' => 'Personalised gift', 'slug_fr' => 'cadeau-personnalise', 'slug_en' => 'personalised-gift'],
        ])->map(fn ($o, $i) => Occasion::updateOrCreate(
            ['slug_fr' => $o['slug_fr']],
            $o + [
                'ordre' => $i * 10,
                'publie' => true,
                'intro_fr' => "Des créations sur mesure pour votre {$o['nom_fr']}, décorées à la main dans la région de Montréal.",
            ],
        ));

        $creations = [
            ['titre_fr' => 'Boîte blush et ivoire', 'titre_en' => 'Blush and ivory box', 'slug_fr' => 'boite-blush-ivoire',
                'couleurs' => ['blush', 'ivoire', 'or'], 'prix_min' => 6500, 'prix_max' => 12000,
                'type' => 'boite-a-fleurs', 'occasions' => ['mariage', 'anniversaire'], 'vedette' => true],
            ['titre_fr' => 'Coffret prénom bleu ciel', 'titre_en' => 'Sky blue name box', 'slug_fr' => 'coffret-prenom-bleu',
                'couleurs' => ['bleu ciel', 'blanc'], 'prix_min' => 4500, 'prix_max' => 7500,
                'type' => 'coffret-prenom', 'occasions' => ['baby-shower', 'bapteme'], 'vedette' => true],
            ['titre_fr' => 'Boîte ronde pivoines', 'titre_en' => 'Round peony box', 'slug_fr' => 'boite-ronde-pivoines',
                'couleurs' => ['rose', 'crème'], 'prix_min' => 8000, 'prix_max' => 15000,
                'type' => 'boite-a-fleurs', 'occasions' => ['mariage'], 'vedette' => true],
            ['titre_fr' => 'Lot d’invités mariage', 'titre_en' => 'Wedding guest favours', 'slug_fr' => 'lot-invites-mariage',
                'couleurs' => ['ivoire', 'or'], 'prix_min' => 1200,
                'type' => 'lot-invites', 'occasions' => ['mariage'], 'vedette' => false],
            ['titre_fr' => 'Coffret graduation', 'slug_fr' => 'coffret-graduation',
                'couleurs' => ['noir', 'or'], 'prix_min' => 5500, 'prix_max' => 9000,
                'type' => 'boite-cadeau', 'occasions' => ['graduation'], 'vedette' => false],
            // Volontairement non publiée : montre le badge « à publier »
            ['titre_fr' => 'Boîte automne (brouillon)', 'slug_fr' => 'boite-automne',
                'couleurs' => ['terracotta', 'moutarde'], 'prix_min' => 7000,
                'type' => 'boite-a-fleurs', 'occasions' => ['cadeau-personnalise'], 'vedette' => false, 'publie' => false],
        ];

        foreach ($creations as $i => $c) {
            $creation = Creation::updateOrCreate(
                ['slug_fr' => $c['slug_fr']],
                [
                    'product_type_id' => $types->firstWhere('slug_fr', $c['type'])?->id,
                    'titre_fr' => $c['titre_fr'],
                    'titre_en' => $c['titre_en'] ?? null,
                    'description_fr' => 'Création décorée à la main, personnalisable selon vos couleurs et votre thème.',
                    'couleurs' => $c['couleurs'],
                    'prix_min' => $c['prix_min'],
                    'prix_max' => $c['prix_max'] ?? null,
                    'vedette' => $c['vedette'],
                    'ordre' => $i * 10,
                    'publie' => $c['publie'] ?? true,
                ],
            );

            $creation->occasions()->sync(
                $occasions->whereIn('slug_fr', $c['occasions'])->pluck('id')->all(),
            );
        }

        $faqs = [
            ['q' => 'Combien de temps à l’avance dois-je commander ?', 'r' => 'Idéalement 2 à 3 semaines avant votre événement. Pour les grandes quantités, comptez 4 semaines.', 'accueil' => true],
            ['q' => 'Utilisez-vous des fleurs naturelles ou artificielles ?', 'r' => 'Les deux. Les fleurs artificielles haut de gamme se conservent indéfiniment ; les naturelles apportent un parfum et une fraîcheur incomparables mais durent quelques jours.', 'accueil' => true],
            ['q' => 'Livrez-vous à Laval et sur la Rive-Nord ?', 'r' => 'Oui. La cueillette est gratuite, la livraison est facturée selon la distance. Nous desservons Montréal, Laval et la Rive-Nord.', 'accueil' => true],
            ['q' => 'Puis-je faire inscrire un prénom sur la boîte ?', 'r' => 'Absolument, c’est notre spécialité. Prénom, date, message court : vous choisissez le texte, la police et la couleur.', 'accueil' => true],
            ['q' => 'Quel est le montant minimum de commande ?', 'r' => 'Aucun minimum pour une pièce unique. Pour les lots d’invités, la commande démarre à 10 unités.', 'accueil' => false],
        ];

        foreach ($faqs as $i => $f) {
            Faq::updateOrCreate(
                ['question_fr' => $f['q']],
                ['reponse_fr' => $f['r'], 'sur_accueil' => $f['accueil'], 'ordre' => $i * 10, 'publie' => true],
            );
        }

        SiteSetting::ecrire('nom_entreprise', 'Fleora');
        SiteSetting::ecrire('courriel', 'info@fleora.ca');
        SiteSetting::ecrire('telephone', '450-000-0000');
        SiteSetting::ecrire('region', 'Montréal, Laval et Rive-Nord');
        SiteSetting::ecrire('instagram', 'https://instagram.com/fleora');
    }
}
