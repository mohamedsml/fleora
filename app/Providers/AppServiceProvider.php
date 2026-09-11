<?php

namespace App\Providers;

use App\Http\Middleware\SetLocale;
use App\Models\Creation;
use App\Models\Occasion;
use App\Support\Traductions;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Singleton : le layout, le sélecteur de langue et les balises hreflang
        // interrogent tous la même instance au cours d'une requête. Des calculs
        // séparés finiraient par diverger, et des hreflang non réciproques sont
        // ignorés par Google.
        $this->app->singleton(Traductions::class);
    }

    public function boot(): void
    {
        $this->resoudreLesSlugs();
        $this->conserverLaLangueDansLivewire();
    }

    /**
     * Maintient la langue à travers les requêtes Livewire.
     *
     * Livewire poste ses mises à jour sur /livewire/update, une URL sans
     * préfixe de langue : le middleware SetLocale y verrait toujours du
     * français. Sans ce correctif, chaque interaction avec la galerie ou le
     * formulaire sur une page anglaise renverrait des libellés français.
     *
     * `addPersistentMiddleware` rejoue le middleware de la requête d'origine
     * sur les requêtes suivantes du composant — c'est le mécanisme prévu par
     * Livewire pour exactement ce cas.
     */
    private function conserverLaLangueDansLivewire(): void
    {
        Livewire::addPersistentMiddleware([
            SetLocale::class,
        ]);
    }

    /**
     * Résolution des slugs indépendante de la langue.
     *
     * Le scope `parSlug` cherche la colonne de la locale courante puis celle des
     * autres langues : une création sans `slug_en` reste ainsi atteignable sous
     * `/en/creations/...` plutôt que de renvoyer une 404.
     *
     * La redirection vers l'URL canonique est faite dans routes/site.php, pas
     * ici : un binding ne doit pas produire de réponse HTTP.
     */
    private function resoudreLesSlugs(): void
    {
        Route::bind('creation', fn (string $slug) => Creation::publie()
            ->parSlug($slug)
            ->firstOrFail());

        Route::bind('occasion', fn (string $slug) => Occasion::publie()
            ->parSlug($slug)
            ->firstOrFail());
    }
}
