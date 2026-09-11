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

        // Les statistiques n'ont pas de valeur au-delà d'une comparaison
        // annuelle, et l'espace est partagé sur mutualisé.
        'visites_mois' => 12,
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
    | Coordonnées
    |--------------------------------------------------------------------------
    |
    | Le NAP (nom, adresse, téléphone) doit rester identique au caractère près
    | à celui du Google Business Profile : une adresse écrite différemment sur
    | deux sources affaiblit le signal de référencement local.
    |
    */

    'contact' => [
        'courriel' => env('FLEORA_COURRIEL', 'contact@fleora.ca'),
        'telephone' => env('FLEORA_TELEPHONE', ''),
        'whatsapp' => env('FLEORA_WHATSAPP', ''),
        'ville' => env('FLEORA_VILLE', 'Laval'),
        'region' => 'QC',
        'delai_reponse_h' => 24,
    ],

    /*
    |--------------------------------------------------------------------------
    | Palettes proposées dans le formulaire
    |--------------------------------------------------------------------------
    |
    | Pastilles cliquables plutôt qu'un champ libre : la saisie est plus rapide
    | sur mobile, et les réponses restent exploitables pour la production.
    | `hex` sert uniquement à l'aperçu visuel.
    |
    */

    'palettes' => [
        'blush' => ['libelle' => 'Blush', 'hex' => '#E8C4C0'],
        'ivoire' => ['libelle' => 'Ivoire', 'hex' => '#F7F4EF'],
        'sauge' => ['libelle' => 'Sauge', 'hex' => '#A8B5A0'],
        'terracotta' => ['libelle' => 'Terracotta', 'hex' => '#C08A6E'],
        'or' => ['libelle' => 'Or', 'hex' => '#C9A961'],
        'bleu_poudre' => ['libelle' => 'Bleu poudré', 'hex' => '#B8C7D4'],
        'lavande' => ['libelle' => 'Lavande', 'hex' => '#C9BFD6'],
        'blanc' => ['libelle' => 'Blanc', 'hex' => '#FDFCFA'],
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

    /*
    |--------------------------------------------------------------------------
    | Compte d'administration recréé par les seeds (développement)
    |--------------------------------------------------------------------------
    |
    | Utilisé par DatabaseSeeder, donc par `make fresh`. Les trois valeurs
    | vivent dans le .env et non dans le dépôt : elles dépendent de la machine,
    | et un mot de passe versionné finirait par se retrouver ailleurs.
    |
    | Aucun défaut pour le mot de passe : sans lui, le seeder n'écrit rien et le
    | dit. Un défaut comme « password » serait tôt ou tard emporté en
    | production par un .env recopié.
    |
    */

    'admin' => [
        'nom' => env('FLEORA_ADMIN_NOM', 'Administration'),
        'courriel' => env('FLEORA_ADMIN_COURRIEL'),
        'mot_de_passe' => env('FLEORA_ADMIN_MOT_DE_PASSE'),
    ],

];
