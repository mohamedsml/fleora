<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Détermine la langue de la requête à partir de l'URL.
 *
 * `/en/creations` → anglais. Tout le reste → français, qui occupe la racine.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * POURQUOI AUCUNE DÉTECTION AUTOMATIQUE
 * ─────────────────────────────────────────────────────────────────────────
 * Il serait tentant de lire l'en-tête `Accept-Language` et de rediriger le
 * visiteur vers sa langue. C'est une erreur documentée par Google, et elle
 * coûterait cher ici :
 *
 *   1. Googlebot explore depuis des adresses américaines, sans `Accept-Language`
 *      ou avec `en`. Une redirection lui servirait l'anglais sur les URL
 *      françaises : les pages FR ne seraient jamais indexées correctement.
 *   2. Une redirection ajoute un aller-retour réseau sur chaque entrée du site,
 *      sur un hébergement mutualisé où le temps de réponse est déjà le maillon
 *      faible.
 *   3. Une visiteuse québécoise arrivant par un lien anglais partagé serait
 *      renvoyée en français contre sa volonté.
 *
 * Le choix de langue est donc explicite : il passe par le sélecteur, et par lui
 * seul.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale(static::depuisLUrl($request));

        return $next($request);
    }

    /**
     * Lit la locale dans le premier segment du chemin.
     *
     * Méthode statique et publique : le composant Livewire s'en sert aussi pour
     * retrouver la locale à partir du référent, ses requêtes AJAX passant par
     * `/livewire/update`, qui n'a pas de préfixe de langue.
     */
    public static function depuisLUrl(Request $request): string
    {
        $premier = $request->segment(1);
        $locales = config('app.locales', ['fr']);

        // Le français occupe la racine : il n'apparaît jamais dans l'URL.
        $prefixes = array_slice($locales, 1);

        // La langue par défaut vient de `app.locales[0]`, PAS de
        // `config('app.locale')` : cette dernière reflète la locale COURANTE,
        // que setLocale() a pu modifier. Une requête française arrivant après
        // une requête anglaise resterait alors en anglais — visible en test,
        // et réel dès que deux requêtes partagent un processus.
        return in_array($premier, $prefixes, true)
            ? $premier
            : $locales[0];
    }
}
