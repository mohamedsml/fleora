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
