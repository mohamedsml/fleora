<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Sens d'un mouvement de stock.
 *
 * `signe()` porte la règle unique de calcul : une entrée ajoute, tout le reste
 * retire. Centralisée ici pour qu'aucun appelant ne l'invente à sa façon.
 */
enum TypeMouvement: string implements HasColor, HasLabel
{
    case Entree = 'entree';
    case Sortie = 'sortie';
    case Ajustement = 'ajustement';
    case Perte = 'perte';

    public function getLabel(): string
    {
        return match ($this) {
            self::Entree => 'Entrée',
            self::Sortie => 'Sortie',
            self::Ajustement => 'Ajustement',
            self::Perte => 'Perte',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Entree => 'success',
            self::Sortie => 'info',
            self::Ajustement => 'warning',
            self::Perte => 'danger',
        };
    }

    /**
     * Multiplicateur appliqué à la quantité saisie.
     *
     * L'ajustement vaut +1 : il est saisi signé (une correction négative se
     * saisit avec une quantité négative), alors que sortie et perte sont
     * saisies en valeur absolue.
     */
    public function signe(): int
    {
        return match ($this) {
            self::Entree, self::Ajustement => 1,
            self::Sortie, self::Perte => -1,
        };
    }
}
