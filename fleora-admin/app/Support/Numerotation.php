<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Attribution de numéros séquentiels aux devis, commandes et factures.
 *
 * ⚠️ Les factures exigent une séquence SANS TROU : c'est une obligation
 * fiscale, un numéro manquant se justifie lors d'une vérification.
 *
 * D'où le verrou : deux enregistrements simultanés qui liraient tous deux le
 * dernier numéro produiraient un doublon (rejeté par l'index unique) ou un
 * saut. `lockForUpdate` sérialise les lecteurs jusqu'à la fin de la
 * transaction — d'où l'exigence d'appeler cette méthode dans une transaction
 * qui couvre aussi l'insertion.
 */
final class Numerotation
{
    /**
     * Numéro suivant au format PREFIXE-AAAA-NNNN, ex. « FAC-2026-0001 ».
     *
     * La séquence repart à 1 chaque année civile : c'est la convention
     * comptable usuelle et elle garde les numéros courts.
     *
     * @param  string  $table  Table portant la colonne `numero`
     * @param  string  $prefixe  DEV, CMD ou FAC
     */
    public static function suivant(string $table, string $prefixe): string
    {
        $annee = now()->year;
        $motif = "{$prefixe}-{$annee}-";

        return DB::transaction(function () use ($table, $motif) {
            $dernier = DB::table($table)
                ->where('numero', 'like', $motif.'%')
                ->lockForUpdate()
                ->orderByDesc('numero')
                ->value('numero');

            // Le rang est la partie après le dernier tiret. On repart de 0 si
            // aucun numéro n'existe encore pour l'année.
            $rang = $dernier
                ? (int) substr($dernier, strrpos($dernier, '-') + 1)
                : 0;

            return $motif.str_pad((string) ($rang + 1), 4, '0', STR_PAD_LEFT);
        });
    }
}
