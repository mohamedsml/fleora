<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatutFacture: string implements HasColor, HasLabel
{
    case Brouillon = 'brouillon';
    case Emise = 'emise';
    case Payee = 'payee';
    case Annulee = 'annulee';

    public function getLabel(): string
    {
        return match ($this) {
            self::Brouillon => 'Brouillon',
            self::Emise => 'Émise',
            self::Payee => 'Payée',
            self::Annulee => 'Annulée',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Brouillon => 'gray',
            self::Emise => 'warning',
            self::Payee => 'success',
            self::Annulee => 'danger',
        };
    }

    /**
     * Une facture émise ou payée est un document fiscal : son contenu ne se
     * modifie plus, il s'annule par une note de crédit.
     */
    public function estFigee(): bool
    {
        return in_array($this, [self::Emise, self::Payee, self::Annulee], true);
    }
}
