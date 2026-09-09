<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Occasion : mariage, baby shower, anniversaire…
 *
 * Ce sont les pages piliers SEO du site — chacune vise une requête locale
 * (« boîte à fleurs mariage Laval ») et porte son propre contenu long.
 */
class Occasion extends Model
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

    /** @return BelongsToMany<Creation, $this> */
    public function creations(): BelongsToMany
    {
        return $this->belongsToMany(Creation::class);
    }

    /** @return HasMany<Testimonial, $this> */
    public function testimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class);
    }

    /** @return MorphMany<Media, $this> */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable')->orderBy('ordre');
    }

    #[Scope]
    protected function publie(Builder $query): void
    {
        $query->where('publie', true)->orderBy('ordre');
    }
}
