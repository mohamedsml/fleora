<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Taxes (Québec)
    |--------------------------------------------------------------------------
    |
    | Taux en vigueur en 2026 : TPS 5 %, TVQ 9,975 %, appliquées toutes deux
    | sur le sous-total hors taxes.
    |
    | `inscrit` reste à false tant que l'entreprise n'est pas inscrite aux
    | fichiers TPS/TVQ. L'inscription devient obligatoire au-delà de 30 000 $
    | de revenus taxables sur 12 mois glissants ; en deçà elle est volontaire.
    | Facturer des taxes sans être inscrit est une infraction — d'où le défaut.
    |
    */

    'taxes' => [
        'inscrit' => env('FLEORA_TAXES_INSCRIT', false),
        'tps' => 0.05,
        'tvq' => 0.09975,
        'numero_tps' => env('FLEORA_NUMERO_TPS'),
        'numero_tvq' => env('FLEORA_NUMERO_TVQ'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Conservation des renseignements personnels (Loi 25)
    |--------------------------------------------------------------------------
    |
    | Une demande est purgée ce nombre de mois après le dernier contact. La Loi
    | 25 impose de ne pas conserver un renseignement personnel au-delà de la
    | finalité qui a justifié sa collecte.
    |
    */

    'conservation' => [
        'demandes_mois' => 24,
    ],

    /*
    |--------------------------------------------------------------------------
    | Images
    |--------------------------------------------------------------------------
    |
    | Largeurs WebP générées à l'upload. Aucun redimensionnement à la volée :
    | c'est le premier poste de consommation CPU sur un hébergement mutualisé.
    |
    */

    'images' => [
        'largeurs' => [400, 800, 1200, 1600],
        'qualite' => 82,
        'max_upload_mo' => 8,
    ],

    /*
    |--------------------------------------------------------------------------
    | Formulaire de demande
    |--------------------------------------------------------------------------
    */

    'demandes' => [
        'max_pieces_jointes' => 5,
        'max_piece_jointe_mo' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Connexion automatique (développement uniquement)
    |--------------------------------------------------------------------------
    |
    | Connecte le premier administrateur sans saisie de mot de passe, pour
    | éviter de se reconnecter à chaque redémarrage local.
    |
    | ⚠️ Ne JAMAIS activer ailleurs qu'en local : l'administration — donc les
    | demandes clientes, les devis et les factures — serait accessible sans
    | authentification. Le middleware refuse de fonctionner si APP_ENV n'est
    | pas « local », mais la première protection reste de ne pas mettre cette
    | variable dans le .env du serveur.
    |
    */

    'auto_login' => env('FLEORA_AUTO_LOGIN', false),

];
