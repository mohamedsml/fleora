<?php

return [
    'meta' => [
        'titre' => 'Créations florales & cadeaux personnalisés',
        'description' => 'Créations florales et cadeaux personnalisés, imaginés avec soin pour mariages, baby showers, baptêmes et anniversaires. :region.',
    ],

    'hero' => [
        'titre_1' => 'Des créations uniques',
        'titre_2' => 'pour vos moments précieux',
        'intro' => 'Fleurs, couleurs, message à inscrire, présentation : chaque création est imaginée avec soin, pensée pour la personne à qui elle est destinée.',
        'reassurance' => 'Réponse sous 24 h · Sans engagement',
        // Signature de marque, reprise en bas de la page À propos.
        'signature' => 'Votre intention. Votre message. Notre création.',
    ],

    // Le titre diffère volontairement du H1 de la page Occasions : deux pages
    // portant le même titre se concurrencent dans les résultats de recherche.
    'occasions' => [
        'titre' => 'Chaque moment mérite une attention',
        'intro' => 'Un mariage, une naissance, un diplôme — découvrez les créations pensées pour chaque événement.',
    ],

    'creations' => [
        'titre' => 'Nos créations',
        'intro' => 'Chaque création est imaginée avec soin et pensée pour être unique. Découvrez nos réalisations pour vous inspirer.',
        'lien' => 'Voir toute la galerie →',
    ],

    'etapes' => [
        'titre' => 'Comment ça fonctionne',
        1 => ['titre' => 'Racontez-nous votre occasion', 'texte' => 'Occasion, date, couleurs, message à inscrire. Le formulaire prend quelques minutes.'],
        2 => ['titre' => 'Imaginez votre création', 'texte' => 'Nous vous proposons une création personnalisée sous 24 h, avec les options et le prix.'],
        3 => ['titre' => 'Nous créons votre attention', 'texte' => 'Une photo vous est envoyée avant la remise. Aucune surprise.'],
    ],

    /*
     * Appel à l'action final.
     *
     * Ces trois textes étaient codés en dur dans la vue : ils s'affichaient
     * en français sur /en, ce qui cassait la version anglaise en silence.
     */
    'appel' => [
        'titre' => 'Une idée en tête ?',
        'texte' => 'Racontez-nous votre occasion, vos envies et les petits détails qui comptent. Nous vous répondons sous 24 h avec une proposition personnalisée.',
    ],

    'temoignages' => ['titre' => 'Ce qu’elles en disent'],

    'faq' => ['titre' => 'Questions fréquentes'],
];
