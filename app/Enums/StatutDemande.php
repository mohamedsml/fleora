<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Cycle de vie commercial d'une demande.
 *
 * Un enum plutôt que des chaînes libres : une faute de frappe sur un statut
 * ferait disparaître une demande des filtres du back-office sans erreur visible.
 */
enum StatutDemande: string implements HasColor, HasLabel
{
    case Nouvelle = 'nouvelle';
    case EnCours = 'en_cours';
    case DevisEnvoye = 'devis_envoye';
    case Confirmee = 'confirmee';
    case Livree = 'livree';
    case Perdue = 'perdue';

    public function getLabel(): string
    {
        return match ($this) {
            self::Nouvelle => 'Nouvelle',
            self::EnCours => 'En cours',
            self::DevisEnvoye => 'Devis envoyé',
            self::Confirmee => 'Confirmée',
            self::Livree => 'Livrée',
            self::Perdue => 'Perdue',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Nouvelle => 'info',
            self::EnCours => 'warning',
            self::DevisEnvoye => 'primary',
            self::Confirmee, self::Livree => 'success',
            self::Perdue => 'danger',
        };
    }

    /**
     * Une demande active attend encore une action de notre part.
     *
     * @return array<int, self>
     */
    public static function actives(): array
    {
        return [self::Nouvelle, self::EnCours, self::DevisEnvoye, self::Confirmee];
    }
}
