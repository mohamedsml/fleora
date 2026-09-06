<?php

namespace App\Support;

/**
 * Calcul TPS/TVQ, en cents et en entiers.
 *
 * Les taux vivent dans `config/fleora.php` et non en dur ici : un taux change
 * par décision gouvernementale, et les documents déjà émis doivent conserver
 * les montants calculés à leur date — jamais recalculés à la volée.
 *
 * Au Québec, TPS et TVQ s'appliquent toutes deux sur le sous-total hors taxes
 * (pas de taxe sur taxe depuis 2013).
 */
final class Taxes
{
    /**
     * Ventile un sous-total en cents.
     *
     * `$taxable` vaut par défaut l'état d'inscription de l'entreprise plutôt
     * que `true` : un défaut à `true` ferait facturer des taxes par simple
     * oubli d'argument, ce qui est une infraction si l'entreprise n'est pas
     * inscrite aux fichiers TPS/TVQ.
     *
     * @return array{sous_total: int, tps: int, tvq: int, total: int}
     */
    public static function ventiler(int $sousTotal, ?bool $taxable = null): array
    {
        $taxable ??= self::inscrit();

        if (! $taxable) {
            return ['sous_total' => $sousTotal, 'tps' => 0, 'tvq' => 0, 'total' => $sousTotal];
        }

        // intval(round()) et non intdiv : l'arrondi au cent le plus proche est
        // la règle de Revenu Québec, une troncature créerait un écart au débit
        // du fisc qui s'accumule sur l'année.
        $tps = (int) round($sousTotal * config('fleora.taxes.tps'));
        $tvq = (int) round($sousTotal * config('fleora.taxes.tvq'));

        return [
            'sous_total' => $sousTotal,
            'tps' => $tps,
            'tvq' => $tvq,
            'total' => $sousTotal + $tps + $tvq,
        ];
    }

    /**
     * L'entreprise facture-t-elle les taxes ?
     *
     * Faux tant que le seuil de 30 000 $ sur 12 mois glissants n'est pas franchi
     * et que l'inscription volontaire n'est pas faite.
     */
    public static function inscrit(): bool
    {
        return (bool) config('fleora.taxes.inscrit');
    }
}
