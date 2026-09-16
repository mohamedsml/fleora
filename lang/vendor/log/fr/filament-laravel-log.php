<?php

/*
|--------------------------------------------------------------------------
| Lecteur de journaux — français
|--------------------------------------------------------------------------
|
| Le paquet fournit dix langues, mais pas le français. L'application tournant
| en « fr » avec un repli lui aussi en « fr », aucune traduction n'était
| trouvée : l'écran affichait les clés brutes
| (« log::filament-laravel-log.navigation.label ») à la place des libellés.
|
| Ce fichier publié dans lang/vendor/ prévaut sur celui du paquet et survit
| aux mises à jour de composer.
|
*/

return [
    'navigation' => [
        'group' => 'Système',
        'label' => 'Journaux',
    ],

    'page' => [
        'title' => 'Journaux',

        'form' => [
            'placeholder' => 'Choisir ou rechercher un fichier de journal…',
        ],
    ],

    'actions' => [
        'clear' => [
            'label' => 'Vider',

            'modal' => [
                'heading' => 'Vider les journaux ?',
                // L'action est irréversible : le texte doit le dire, pas
                // seulement demander confirmation.
                'description' => 'Le contenu du fichier sera définitivement supprimé. '
                    .'Les erreurs déjà survenues ne seront plus consultables.',

                'actions' => [
                    'confirm' => 'Vider',
                ],
            ],
        ],

        'jumpToStart' => [
            'label' => 'Début du fichier',
        ],

        'jumpToEnd' => [
            'label' => 'Fin du fichier',
        ],

        'refresh' => [
            'label' => 'Actualiser',
        ],
    ],
];
