<?php

namespace App\Http\Middleware;

use App\Models\Visite;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Enregistre les visites du site public.
 *
 * Écrit APRÈS l'envoi de la réponse (`terminate`) : le visiteur n'attend jamais
 * l'écriture en base. Sur un hébergement mutualisé où chaque milliseconde
 * compte, c'est la différence entre une mesure utile et une mesure qui dégrade
 * ce qu'elle mesure.
 *
 * Aucun témoin, aucune adresse IP conservée : voir la migration pour l'empreinte
 * quotidienne. C'est ce qui dispense de bannière de consentement.
 */
class EnregistrerVisite
{
    /** Chemins jamais comptés. */
    private const EXCLUS = [
        'admin', 'livewire', 'build', 'storage', 'images',
        'sitemap.xml', 'robots.txt', 'favicon.ico', 'up',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    /**
     * Appelé une fois la réponse envoyée au navigateur.
     */
    public function terminate(Request $request, Response $reponse): void
    {
        if (! $this->doitCompter($request, $reponse)) {
            return;
        }

        try {
            Visite::create([
                'chemin' => '/'.ltrim($request->path(), '/'),
                'langue' => app()->getLocale(),
                'source' => $this->source($request),
                'appareil' => $this->appareil($request),
                'empreinte' => $this->empreinte($request),
                ...$this->entite($request),
            ]);
        } catch (Throwable) {
            // Une statistique n'a jamais justifié de casser une page. Si la
            // table manque ou que la base refuse l'écriture, on renonce en
            // silence.
        }
    }

    private function doitCompter(Request $request, Response $reponse): bool
    {
        // Seules les pages HTML consultées comptent : ni les envois de
        // formulaire, ni les fichiers, ni les erreurs.
        if (! $request->isMethod('GET') || $reponse->getStatusCode() !== 200) {
            return false;
        }

        if (! str_contains((string) $reponse->headers->get('Content-Type'), 'text/html')) {
            return false;
        }

        $premier = $request->segment(1) ?? '';

        // `/en` est une langue, pas un dossier exclu : on regarde le segment
        // suivant dans ce cas.
        if (in_array($premier, array_slice(config('app.locales', ['fr']), 1), true)) {
            $premier = $request->segment(2) ?? '';
        }

        if (in_array($premier, self::EXCLUS, true)) {
            return false;
        }

        return ! $this->estUnRobot($request);
    }

    /**
     * Robots connus — ils fausseraient les chiffres sans rien apprendre.
     *
     * Liste volontairement courte : les robots sérieux s'annoncent, et
     * poursuivre les autres relèverait d'une course perdue d'avance.
     */
    private function estUnRobot(Request $request): bool
    {
        $agent = strtolower((string) $request->userAgent());

        if ($agent === '') {
            return true;
        }

        foreach (['bot', 'crawl', 'spider', 'slurp', 'curl', 'wget',
            'headless', 'lighthouse', 'preview', 'monitor'] as $marqueur) {
            if (str_contains($agent, $marqueur)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Domaine du référent, jamais l'URL complète.
     *
     * « instagram.com » suffit à savoir où investir ; l'URL exacte d'un profil
     * ou d'une publication n'apprendrait rien de plus et alourdirait la table.
     */
    private function source(Request $request): ?string
    {
        $referent = $request->headers->get('referer');

        if (blank($referent)) {
            return null;
        }

        $hote = parse_url($referent, PHP_URL_HOST);

        if (! $hote || $hote === $request->getHost()) {
            // Navigation interne : ce n'est pas une source d'acquisition.
            return null;
        }

        return substr(preg_replace('/^www\./', '', $hote), 0, 120);
    }

    private function appareil(Request $request): string
    {
        $agent = strtolower((string) $request->userAgent());

        return match (true) {
            str_contains($agent, 'ipad') || str_contains($agent, 'tablet') => 'tablette',
            str_contains($agent, 'mobi') || str_contains($agent, 'android') => 'mobile',
            default => 'ordinateur',
        };
    }

    /**
     * Empreinte du jour, non réversible et non persistante.
     *
     * Le sel change chaque jour : la même personne produit une empreinte
     * différente demain. On peut donc compter les visiteurs d'une journée sans
     * suivre quiconque dans le temps — et sans pouvoir remonter à une identité.
     */
    private function empreinte(Request $request): string
    {
        return hash('sha256', implode('|', [
            $request->ip(),
            $request->userAgent(),
            config('app.key'),
            now()->toDateString(),
        ]));
    }

    /**
     * Entité consultée sur une fiche création ou une page occasion.
     *
     * C'est ce qui répondra à « quelles créations intéressent vraiment ? » —
     * l'information la plus actionnable du tableau de bord.
     *
     * @return array{entite_type?: string, entite_id?: int}
     */
    private function entite(Request $request): array
    {
        $route = $request->route();

        if (! $route) {
            return [];
        }

        foreach ($route->parameters() as $valeur) {
            if ($valeur instanceof Model) {
                return [
                    'entite_type' => class_basename($valeur),
                    'entite_id' => $valeur->getKey(),
                ];
            }
        }

        return [];
    }
}
