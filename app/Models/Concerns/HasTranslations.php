<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Lecture d'un champ bilingue stocké en colonnes `_fr` / `_en`.
 *
 * Le français est la langue de référence : le contenu anglais peut être vide
 * pendant la saisie, et un site à moitié traduit doit rester lisible plutôt que
 * d'afficher des trous. On retombe donc toujours sur le français.
 */
trait HasTranslations
{
    /**
     * Recherche par slug, dans la langue courante puis dans l'autre.
     *
     * Le repli est indispensable : une création sans `slug_en` doit rester
     * accessible sous `/en/creations/...`, sinon le sélecteur de langue mènerait
     * à une 404 sur la moitié du catalogue.
     *
     * ⚠️ Ce repli crée un risque de contenu dupliqué — la même page répondrait
     * sur deux URL différentes sous `/en/`. C'est à l'appelant de rediriger en
     * 301 vers l'URL canonique de la locale ; voir `slugPour()`.
     */
    #[Scope]
    protected function parSlug(Builder $query, string $slug, ?string $langue = null): void
    {
        $langue = $langue ?? app()->getLocale();

        $query->where(function (Builder $q) use ($slug, $langue): void {
            $q->where("slug_{$langue}", $slug);

            // Colonnes des autres langues, en secours.
            foreach (config('app.locales', ['fr']) as $autre) {
                if ($autre !== $langue) {
                    $q->orWhere("slug_{$autre}", $slug);
                }
            }
        });
    }

    /**
     * Slug canonique du modèle dans une langue, avec repli sur le français.
     *
     * Sert à construire les URL — sélecteur de langue, hreflang, sitemap — et à
     * détecter qu'une requête est arrivée sur un slug non canonique.
     */
    public function slugPour(?string $langue = null): string
    {
        $langue = $langue ?? app()->getLocale();

        $slug = $this->getAttribute("slug_{$langue}");

        return filled($slug) ? $slug : $this->getAttribute('slug_fr');
    }

    /**
     * Valeur d'un champ dans la langue demandée, avec repli sur le français.
     *
     * @param  string  $champ  Nom de base sans suffixe, ex. « titre »
     * @param  string|null  $langue  « fr » ou « en » ; locale courante si null
     */
    public function t(string $champ, ?string $langue = null): ?string
    {
        $langue = $langue ?? app()->getLocale();

        if ($langue !== 'fr') {
            $valeur = $this->getAttribute("{$champ}_{$langue}");

            // Une chaîne vide vaut « pas encore traduit », pas « traduit par du vide »
            if (filled($valeur)) {
                return $valeur;
            }
        }

        return $this->getAttribute("{$champ}_fr");
    }
}
