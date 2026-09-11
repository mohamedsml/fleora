<?php

/*
|--------------------------------------------------------------------------
| Routes du site public
|--------------------------------------------------------------------------
|
| Ce fichier est chargé UNE FOIS PAR LANGUE depuis routes/web.php. La variable
| $segments contient les segments d'URL de la langue en cours de montage ;
| $langue, son code.
|
| ⚠️ Ne jamais utiliser __() ici : au moment où les routes sont enregistrées,
| la locale de la requête n'est pas encore résolue — elle vaut celle de la
| configuration. Les segments sont donc lus depuis un tableau déjà chargé.
|
| ⚠️ Aucune requête à la base non plus : `route:cache` sérialise ce fichier, et
| une requête s'exécuterait au moment du cache, pas de la requête HTTP.
|
*/

use App\Models\Creation;
use App\Models\Faq;
use App\Models\Occasion;
use App\Models\Testimonial;
use Illuminate\Support\Facades\Route;

/** @var array<string, string> $segments */
/** @var string $langue */
Route::get('/', function () {
    return view('pages.accueil', [
        // `with` sur les relations affichées : sans cela, chaque carte
        // déclencherait ses propres requêtes pour ses images et occasions.
        'vedettes' => Creation::publie()->vedette()
            ->with(['media', 'occasions'])
            ->take(6)
            ->get(),

        'occasions' => Occasion::publie()->take(6)->get(),

        'temoignages' => Testimonial::publie()->take(3)->get(),

        'faqs' => Faq::publie()->surAccueil()->take(4)->get(),
    ]);
})->name('accueil');

/*
|--------------------------------------------------------------------------
| Créations
|--------------------------------------------------------------------------
| La galerie est un composant Livewire : les filtres vivent dans l'URL, donc
| un lien filtré reste partageable et indexable.
*/

Route::view('/'.$segments['creations'], 'pages.creations')->name('creations');

Route::get('/'.$segments['creations'].'/{creation}', function (Creation $creation) {
    // Le binding a résolu le slug dans l'une ou l'autre langue. Si ce n'est pas
    // le slug canonique de la langue courante, on redirige en 301 : sinon la
    // même page répondrait sur deux URL et Google indexerait les deux.
    if ($redirection = redirection_canonique($creation, 'creations.show')) {
        return $redirection;
    }

    $creation->load(['media', 'occasions', 'productType']);

    return view('pages.creation-detail', [
        'creation' => $creation,
        // Même occasion : la suggestion la plus pertinente pour quelqu'un qui
        // prépare un événement précis.
        'similaires' => Creation::publie()
            ->with(['media', 'occasions'])
            ->whereKeyNot($creation->id)
            ->when(
                $creation->occasions->isNotEmpty(),
                fn ($q) => $q->whereHas(
                    'occasions',
                    fn ($o) => $o->whereIn('occasions.id', $creation->occasions->pluck('id'))
                )
            )
            ->take(3)
            ->get(),
    ]);
})->name('creations.show');

/*
|--------------------------------------------------------------------------
| Occasions
|--------------------------------------------------------------------------
| Les pages de détail portent le référencement à forte intention : quelqu'un
| qui cherche « boîte baby shower personnalisée » est très proche de l'achat.
*/

Route::get('/'.$segments['occasions'], function () {
    return view('pages.occasions', [
        // `with` sur media : sans lui, chaque vignette déclencherait sa propre
        // requête.
        'occasions' => Occasion::publie()->with('media')->get(),
    ]);
})->name('occasions');

Route::get('/'.$segments['occasions'].'/{occasion}', function (Occasion $occasion) {
    if ($redirection = redirection_canonique($occasion, 'occasions.show')) {
        return $redirection;
    }

    $requete = Creation::publie()
        ->with(['media', 'occasions'])
        ->whereHas('occasions', fn ($q) => $q->whereKey($occasion->id));

    return view('pages.occasion-detail', [
        'occasion' => $occasion,
        'creations' => $requete->clone()->take(6)->get(),
        'total' => $requete->count(),
    ]);
})->name('occasions.show');

/*
|--------------------------------------------------------------------------
| Conversion
|--------------------------------------------------------------------------
*/

Route::view('/'.$segments['demande'], 'pages.demande')->name('demande');

// Page de confirmation distincte, et non une modale : c'est le seul repère
// mesurable proprement dans un outil d'analytique.
Route::view('/'.$segments['merci'], 'pages.merci')->name('merci');

/*
|--------------------------------------------------------------------------
| Pages légales
|--------------------------------------------------------------------------
*/

// Obligation Loi 25, et le formulaire de demande y renvoie.
Route::view('/'.$segments['confidentialite'], 'pages.confidentialite')
    ->name('confidentialite');

/*
|--------------------------------------------------------------------------
| Pages éditoriales
|--------------------------------------------------------------------------
*/

Route::view('/'.$segments['a-propos'], 'pages.a-propos')->name('a-propos');
Route::view('/'.$segments['contact'], 'pages.contact')->name('contact');

Route::get('/'.$segments['faq'], function () {
    return view('pages.faq', [
        'faqs' => Faq::publie()->get(),
    ]);
})->name('faq');
