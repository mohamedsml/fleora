<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Témoignage client.
 *
 * `publie` est à false par défaut : un avis ne paraît qu'après vérification
 * qu'il est réel et que la cliente a consenti à sa diffusion. Publier un faux
 * avis est une pratique commerciale trompeuse.
 */
class Testimonial extends Model
{
    use HasTranslations;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'note' => 'integer',
            'publie' => 'boolean',
            'ordre' => 'integer',
        ];
    }

    /** @return BelongsTo<Occasion, $this> */
    public function occasion(): BelongsTo
    {
        return $this->belongsTo(Occasion::class);
    }

    /** @return BelongsTo<Creation, $this> */
    public function creation(): BelongsTo
    {
        return $this->belongsTo(Creation::class);
    }

    #[Scope]
    protected function publie(Builder $query): void
    {
        $query->where('publie', true)->orderBy('ordre');
    }
}
