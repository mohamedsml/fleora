<?php

/*
|--------------------------------------------------------------------------
| Montage des routes localisées
|--------------------------------------------------------------------------
|
| routes/site.php est chargé une fois par langue :
|
|   français (langue par défaut) → pas de préfixe, noms nus
|       /creations                  route('creations')
|
|   anglais                       → préfixe /en, noms préfixés
|       /en/creations               route('en.creations')
|
| Le français occupe la racine parce que c'est le marché principal et que la
| racine porte l'autorité de domaine.
|
| Les segments d'URL sont lus depuis lang/{langue}/routes.php et passés à
| site.php par variable — jamais via __(), qui ne fonctionne pas ici : au
| moment de l'enregistrement des routes, la locale de la requête n'existe pas
| encore. Cette approche reste compatible avec `route:cache`, indispensable sur
| un hébergement mutualisé.
|
*/

use Illuminate\Support\Facades\Route;

$locales = config('app.locales', ['fr']);
$parDefaut = $locales[0];

foreach ($locales as $langue) {
    $segments = require lang_path("{$langue}/routes.php");

    Route::prefix($langue === $parDefaut ? '' : $langue)
        ->name($langue === $parDefaut ? '' : "{$langue}.")
        ->group(function () use ($segments, $langue) {
            require __DIR__.'/site.php';
        });
}
