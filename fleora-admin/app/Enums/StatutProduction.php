<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Colonnes du kanban de production.
 */
enum StatutProduction: string implements HasColor, HasLabel
{
    case AFaire = 'a_faire';
    case EnCours = 'en_cours';
    case Assemble = 'assemble';
    case Photographie = 'photographie';
    case Valide = 'valide';
    case Pret = 'pret';

    public function getLabel(): string
    {
        return match ($this) {
            self::AFaire => 'À faire',
            self::EnCours => 'En cours',
            self::Assemble => 'Assemblé',
            self::Photographie => 'Photographié',
            self::Valide => 'Validé',
            self::Pret => 'Prêt',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::AFaire => 'gray',
            self::EnCours => 'warning',
            self::Assemble => 'info',
            self::Photographie => 'primary',
            self::Valide, self::Pret => 'success',
        };
    }
}
