<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Type de produit : boîte à fleurs, boîte cadeau, coffret prénom, lot d'invités.
 */
class ProductType extends Model
{
    use HasTranslations;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'ordre' => 'integer',
            'publie' => 'boolean',
        ];
    }

    /** @return HasMany<Creation, $this> */
    public function creations(): HasMany
    {
        return $this->hasMany(Creation::class);
    }

    #[Scope]
    protected function publie(Builder $query): void
    {
        $query->where('publie', true)->orderBy('ordre');
    }
}
