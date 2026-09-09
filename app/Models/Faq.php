<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Question fréquente.
 *
 * Double rôle : lever les objections avant la demande de soumission, et
 * alimenter le schema.org FAQPage pour la longue traîne SEO.
 */
class Faq extends Model
{
    use HasTranslations;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'sur_accueil' => 'boolean',
            'ordre' => 'integer',
            'publie' => 'boolean',
        ];
    }

    #[Scope]
    protected function publie(Builder $query): void
    {
        $query->where('publie', true)->orderBy('ordre');
    }

    #[Scope]
    protected function surAccueil(Builder $query): void
    {
        $query->where('sur_accueil', true);
    }
}
