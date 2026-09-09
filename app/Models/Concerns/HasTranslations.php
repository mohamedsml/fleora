<?php

namespace App\Models\Concerns;

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
