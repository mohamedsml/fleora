<?php

/*
|--------------------------------------------------------------------------
| Textes présents sur toutes les pages
|--------------------------------------------------------------------------
|
| En-tête, pied de page, boutons récurrents, libellés d'accessibilité.
| Sans eux, même une page traduite paraît à moitié française.
|
*/

return [
    'nav' => [
        'accueil' => 'Accueil',
        'creations' => 'Créations',
        'occasions' => 'Occasions',
        'a_propos' => 'À propos',
        'contact' => 'Contact',
        'faq' => 'Questions fréquentes',
        'principale' => 'Navigation principale',
        'mobile' => 'Navigation mobile',
        'ouvrir_menu' => 'Ouvrir le menu',
        'fermer_menu' => 'Fermer le menu',
    ],

    /*
     * « Créer ma Fleora » est le CTA principal du site.
     *
     * Il remplace « Demander une soumission », qui décrivait une démarche
     * administrative là où la cliente vient chercher une création. La clé
     * garde son nom `soumission` : la renommer casserait les sept vues qui
     * l'appellent, sans rien apporter.
     */
    'cta' => [
        'soumission' => 'Créer ma Fleora',
        'creations' => 'Voir les créations',
        'contact' => 'Nous écrire',
    ],

    'accessibilite' => [
        'aller_contenu' => 'Aller au contenu',
        'accueil' => ':marque — accueil',
    ],

    'langue' => [
        'changer' => 'Changer de langue',
        'fr' => 'Français',
        'en' => 'English',
        'fr_court' => 'FR',
        'en_court' => 'EN',
    ],

    'pied' => [
        'navigation' => 'Navigation',
        'joindre' => 'Nous joindre',
        // Positionnement unifié : l'offre est « fleurs + cadeaux +
        // personnalisation », pas seulement des boîtes. Trois vocabulaires
        // coexistaient auparavant sur le site.
        'slogan' => 'Des créations uniques, imaginées avec soin pour vos moments précieux.',
        'description' => 'Créations florales & cadeaux personnalisés',
        'confidentialite' => 'Politique de confidentialité',
        'droits' => 'Tous droits réservés.',
        'region' => 'Grand Montréal, Laval et Rive-Nord',
    ],

    'preparation' => [
        'texte' => 'Cette page est en cours de préparation. En attendant, nos créations sont déjà visibles — et nous répondons à toute question par courriel.',
        'question' => 'Une question ?',
    ],
];
