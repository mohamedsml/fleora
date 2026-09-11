<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * En-têtes HTTP de sécurité.
 *
 * Aucun n'était posé jusqu'ici : ni HSTS, ni protection contre l'encadrement,
 * ni interdiction du reniflage de type MIME.
 *
 * Posés dans PHP plutôt que dans `.htaccess` : la configuration reste dans le
 * dépôt, versionnée et testable, et elle survit à une réinitialisation du
 * fichier par le panneau d'hébergement.
 */
class EnTetesSecurite
{
    public function handle(Request $request, Closure $next): Response
    {
        $reponse = $next($request);

        // Ne touche pas aux téléchargements de fichiers ni aux flux : ces
        // réponses n'ont pas d'en-têtes de document à protéger.
        if (! $this->estUnDocument($reponse)) {
            return $reponse;
        }

        $entetes = [
            // Empêche le navigateur de deviner un type MIME : un fichier
            // téléversé qui se présente comme une image ne pourra pas être
            // interprété comme du script.
            'X-Content-Type-Options' => 'nosniff',

            // Interdit l'encadrement du site par un tiers — parade au
            // détournement de clic sur le formulaire et l'administration.
            'X-Frame-Options' => 'SAMEORIGIN',

            // Le référent complet n'est transmis qu'au sein du site ; les
            // liens sortants ne reçoivent que le domaine.
            'Referrer-Policy' => 'strict-origin-when-cross-origin',

            // Le site n'a besoin d'aucune de ces capacités : les refuser
            // explicitement empêche un script injecté d'y accéder.
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=(), usb=()',
        ];

        // HSTS uniquement en HTTPS : posé en HTTP, il n'a aucun effet, et sur
        // un domaine de développement il rendrait le site inaccessible hors TLS.
        if ($request->secure()) {
            $entetes['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        $entetes['Content-Security-Policy'] = $this->politiqueContenu();

        foreach ($entetes as $nom => $valeur) {
            $reponse->headers->set($nom, $valeur);
        }

        return $reponse;
    }

    /**
     * Politique de sécurité du contenu.
     *
     * ⚠️ Trois dépendances externes doivent rester autorisées, sinon le site
     * casse en silence — le navigateur bloque sans rien afficher :
     *
     *   - Cloudflare Turnstile : script et cadre du captcha
     *   - OpenStreetMap : cadre de la carte sur la page Contact
     *   - Livewire : styles injectés à l'exécution
     *
     * `unsafe-inline` sur les scripts est nécessaire à Alpine et Livewire, qui
     * évaluent des expressions dans les attributs HTML. Un passage aux nonces
     * supposerait de réécrire tous les `x-data` et `wire:click` du site : le
     * gain ne justifierait pas le risque de régression.
     */
    private function politiqueContenu(): string
    {
        $directives = [
            "default-src 'self'",

            // 'unsafe-eval' : Alpine compile ses expressions à l'exécution.
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://challenges.cloudflare.com",

            "style-src 'self' 'unsafe-inline'",

            // data: pour les images en ligne ; blob: pour les aperçus de
            // fichiers avant envoi dans le formulaire.
            "img-src 'self' data: blob: https://*.tile.openstreetmap.org",

            "font-src 'self' data:",

            "connect-src 'self'",

            // Turnstile et la carte de contact s'affichent dans des cadres.
            'frame-src https://challenges.cloudflare.com https://www.openstreetmap.org',

            // Personne ne doit pouvoir encadrer ce site — doublon moderne de
            // X-Frame-Options, que certains navigateurs privilégient.
            "frame-ancestors 'self'",

            // Le formulaire ne poste que vers le site lui-même.
            "form-action 'self'",

            "base-uri 'self'",

            // Aucun greffon : Flash et consorts n'ont plus lieu d'être.
            "object-src 'none'",
        ];

        return implode('; ', $directives);
    }

    /**
     * Vrai pour une page HTML : les fichiers servis et les flux n'ont pas à
     * porter ces en-têtes.
     */
    private function estUnDocument(Response $reponse): bool
    {
        $type = $reponse->headers->get('Content-Type', '');

        return $type === '' || str_contains($type, 'text/html');
    }
}
