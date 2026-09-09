<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatutCommande: string implements HasColor, HasLabel
{
    case Confirmee = 'confirmee';
    case EnProduction = 'en_production';
    case Prete = 'prete';
    case Livree = 'livree';
    case Annulee = 'annulee';

    public function getLabel(): string
    {
        return match ($this) {
            self::Confirmee => 'Confirmée',
            self::EnProduction => 'En production',
            self::Prete => 'Prête',
            self::Livree => 'Livrée',
            self::Annulee => 'Annulée',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Confirmee => 'info',
            self::EnProduction => 'warning',
            self::Prete => 'primary',
            self::Livree => 'success',
            self::Annulee => 'danger',
        };
    }
}
