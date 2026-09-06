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
     */
    public function argent(?int $cents, ?string $langue = null): ?string
    {
        if ($cents === null) {
            return null;
        }

        $locale = ($langue ?? app()->getLocale()) === 'en' ? 'en_CA' : 'fr_CA';

        return (new NumberFormatter($locale, NumberFormatter::CURRENCY))
            ->formatCurrency($cents / 100, 'CAD');
    }
}
