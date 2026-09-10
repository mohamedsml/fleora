<?php

use App\Models\Creation;
use App\Models\Faq;
use App\Models\Occasion;
use App\Models\Testimonial;
use Illuminate\Support\Facades\Route;

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

Route::view('/creations', 'pages.creations')->name('creations');

Route::get('/creations/{slug}', function (string $slug) {
    $creation = Creation::publie()
        ->with(['media', 'occasions', 'productType'])
        ->where('slug_fr', $slug)
        ->firstOrFail();

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
| Conversion
|--------------------------------------------------------------------------
*/

Route::view('/demande', 'pages.demande')->name('demande');

// Page de confirmation distincte, et non une modale : c'est le seul repère
// mesurable proprement dans un outil d'analytique.
Route::view('/merci', 'pages.merci')->name('merci');
