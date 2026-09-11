<?php

namespace App\Support;

use App\Models\Creation;
use App\Models\Occasion;
use App\Models\SiteSetting;

/**
 * Données structurées schema.org.
 *
 * Permet à Google d'afficher les coordonnées, la zone desservie, un fil
 * d'Ariane ou une fourchette de prix directement dans ses résultats.
 *
 * Centralisé plutôt que dispersé dans les vues : le NAP — nom, adresse,
 * téléphone — doit rester identique partout, y compris avec la fiche Google
 * Business. Deux écritures différentes affaiblissent le signal local.
 */
class DonneesStructurees
{
    /**
     * L'entreprise. Le signal SEO local le plus direct.
     *
     * `Store` plutôt que `LocalBusiness` : plus précis, et accepté partout où
     * LocalBusiness l'est.
     */
    public static function entreprise(): array
    {
        $contact = config('fleora.contact');

        $donnees = [
            '@context' => 'https://schema.org',
            '@type' => 'Store',
            'name' => config('app.name'),
            'url' => url('/'),
            'image' => asset('images/og-fleora.jpg'),
            'email' => $contact['courriel'],
            'priceRange' => '$$',
            'currenciesAccepted' => 'CAD',

            // Zone de service plutôt qu'adresse : l'atelier n'accueille pas de
            // public, et publier une adresse personnelle n'apporte rien.
            'areaServed' => array_map(
                fn (string $ville) => ['@type' => 'City', 'name' => $ville],
                ['Montréal', 'Laval', 'Terrebonne', 'Blainville', 'Repentigny', 'Longueuil']
            ),

            'address' => [
                '@type' => 'PostalAddress',
                'addressRegion' => $contact['region'],
                'addressCountry' => 'CA',
                'addressLocality' => $contact['ville'],
            ],
        ];

        if (filled($contact['telephone'])) {
            $donnees['telephone'] = $contact['telephone'];
        }

        // sameAs relie le site aux profils sociaux : Google les rapproche pour
        // constituer le panneau de connaissances.
        $profils = array_filter([
            SiteSetting::lire('instagram'),
            SiteSetting::lire('facebook'),
        ]);

        if ($profils !== []) {
            $donnees['sameAs'] = array_values($profils);
        }

        return $donnees;
    }

    /**
     * Fil d'Ariane.
     *
     * Google l'affiche à la place de l'URL brute dans ses résultats : un
     * chemin lisible inspire plus confiance qu'une adresse.
     *
     * @param  array<string, string>  $niveaux  libellé => URL
     */
    public static function filAriane(array $niveaux): array
    {
        $elements = [];
        $position = 1;

        foreach ($niveaux as $libelle => $url) {
            $elements[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $libelle,
                'item' => $url,
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $elements,
        ];
    }

    /**
     * Une création, avec sa fourchette de prix.
     */
    public static function creation(Creation $creation): array
    {
        $donnees = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $creation->t('titre'),
            'description' => $creation->t('description'),
            'image' => $creation->media->map(fn ($m) => $m->urlVariante(1200))->values()->all(),
            'brand' => ['@type' => 'Brand', 'name' => config('app.name')],
        ];

        if ($creation->prix_min) {
            $offre = [
                '@type' => 'AggregateOffer',
                'priceCurrency' => 'CAD',
                'lowPrice' => $creation->prix_min / 100,
                // MadeToOrder : chaque pièce est fabriquée à la commande,
                // annoncer du stock serait faux.
                'availability' => 'https://schema.org/MadeToOrder',
            ];

            if ($creation->prix_max) {
                $offre['highPrice'] = $creation->prix_max / 100;
            }

            $donnees['offers'] = $offre;
        }

        return $donnees;
    }

    /**
     * Liste de créations — galerie ou page occasion.
     */
    public static function listeCreations(iterable $creations, string $nom): array
    {
        $elements = [];
        $position = 1;

        foreach ($creations as $creation) {
            $elements[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'url' => route_langue('creations.show', $creation->slugPour()),
                'name' => $creation->t('titre'),
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => $nom,
            'numberOfItems' => count($elements),
            'itemListElement' => $elements,
        ];
    }

    /**
     * Questions fréquentes. Google les affiche en accordéon dans ses résultats.
     */
    public static function faq(iterable $faqs): array
    {
        $questions = [];

        foreach ($faqs as $faq) {
            $questions[] = [
                '@type' => 'Question',
                'name' => $faq->t('question'),
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq->t('reponse'),
                ],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $questions,
        ];
    }

    /**
     * Une occasion présentée comme une page de collection.
     */
    public static function occasion(Occasion $occasion, iterable $creations): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $occasion->t('nom'),
            'description' => $occasion->t('intro'),
            'url' => route_langue('occasions.show', $occasion->slugPour()),
            'mainEntity' => static::listeCreations($creations, $occasion->t('nom')),
        ];
    }
}
