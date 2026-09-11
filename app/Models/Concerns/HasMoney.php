<?php

namespace App\Models\Concerns;

use NumberFormatter;

/**
 * Formatage des montants stockés en cents.
 *
 * Toute la base garde l'argent en entiers de cents. La conversion en dollars
 * n'a lieu qu'à l'affichage — jamais au calcul, jamais au stockage.
 */
trait HasMoney
{
    /**
     * Montant en cents formaté en devise canadienne, ex. 12500 → « 125,00 $ ».
     *
     * `$decimales` à false arrondit au dollar : « 70 $ » plutôt que « 70,00 $ ».
     * C'est ce qu'on veut sur une vitrine, où le prix est indicatif et où les
     * centimes alourdissent la lecture. Les décimales restent le défaut : un
     * devis ou une facture ne peut pas arrondir.
     */
    public function argent(?int $cents, ?string $langue = null, bool $decimales = true): ?string
    {
        if ($cents === null) {
            return null;
        }

        $locale = ($langue ?? app()->getLocale()) === 'en' ? 'en_CA' : 'fr_CA';

        $format = new NumberFormatter($locale, NumberFormatter::CURRENCY);

        if (! $decimales) {
            // Les deux attributs sont nécessaires : le maximum seul laisserait
            // le formateur compléter jusqu'au minimum imposé par la devise.
            $format->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, 0);
            $format->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 0);
        }

        return $format->formatCurrency($cents / 100, 'CAD');
    }
}
