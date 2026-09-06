<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatutDevis: string implements HasColor, HasLabel
{
    case Brouillon = 'brouillon';
    case Envoye = 'envoye';
    case Accepte = 'accepte';
    case Refuse = 'refuse';
    case Expire = 'expire';

    public function getLabel(): string
    {
        return match ($this) {
            self::Brouillon => 'Brouillon',
            self::Envoye => 'Envoyé',
            self::Accepte => 'Accepté',
            self::Refuse => 'Refusé',
            self::Expire => 'Expiré',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Brouillon => 'gray',
            self::Envoye => 'info',
            self::Accepte => 'success',
            self::Refuse => 'danger',
            self::Expire => 'warning',
        };
    }
}
