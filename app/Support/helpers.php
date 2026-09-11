<?php

use App\Support\Traductions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

if (! function_exists('route_langue')) {
    /**
     * URL d'une route nommée dans une langue donnée.
     *
     * `route_langue('creations', [], 'en')` → /en/creations
     *
     * Sans le paramètre de langue, retombe sur `route()` : les vues existantes
     * continuent donc de fonctionner sans modification, et le préfixe suit
     * automatiquement la locale courante.
     */
    function route_langue(string $nom, mixed $params = [], ?string $langue = null): string
    {
        $langue = $langue ?? app()->getLocale();

        $nomComplet = app(Traductions::class)->nomRoute($nom, $langue);

        // `mixed` plutôt que `array` : route() accepte aussi un scalaire pour
        // un paramètre unique (`route('creations.show', $slug)`), et l'usage
        // est répandu dans les vues.
        return route(Route::has($nomComplet) ? $nomComplet : $nom, $params);
    }
}

if (! function_exists('redirection_canonique')) {
    /**
     * Redirige en 301 si l'URL utilise un slug non canonique pour la langue.
     *
     * Le scope `parSlug` accepte volontairement le slug de n'importe quelle
     * langue, pour qu'un contenu non traduit reste atteignable. Sans cette
     * redirection, la même page répondrait sur deux URL distinctes sous `/en/`
     * — et Google indexerait les deux comme du contenu dupliqué.
     *
     * Renvoie null quand l'URL est déjà canonique : l'appelant poursuit alors
     * son traitement normal.
     */
    function redirection_canonique(Model $modele, string $nomRoute): ?RedirectResponse
    {
        $canonique = $modele->slugPour();

        // Dernier segment de l'URL : c'est le slug tel qu'il a été demandé.
        $segments = request()->segments();
        $utilise = end($segments);

        if ($utilise === $canonique) {
            return null;
        }

        return redirect()->to(route_langue($nomRoute, [$canonique]), 301);
    }
}
