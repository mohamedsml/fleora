<?php

namespace App\Models;

use App\Models\Concerns\HasMoney;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ligne de devis.
 *
 * La description est libre : chaque création étant unique, rattacher la ligne
 * à une entrée du catalogue est facultatif et purement indicatif.
 */
class QuoteItem extends Model
{
    use HasMoney;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'quantite' => 'integer',
            'prix_unitaire' => 'integer',
            'total' => 'integer',
            'ordre' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Le total de ligne est dérivé, jamais saisi : le laisser modifiable
        // permettrait un devis dont les lignes ne somment pas au total.
        static::saving(function (self $ligne): void {
            $ligne->total = $ligne->quantite * $ligne->prix_unitaire;
        });

        static::saved(fn (self $ligne) => $ligne->quote?->recalculer());
        static::deleted(fn (self $ligne) => $ligne->quote?->recalculer());
    }

    /** @return BelongsTo<Quote, $this> */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /** @return BelongsTo<Creation, $this> */
    public function creation(): BelongsTo
    {
        return $this->belongsTo(Creation::class);
    }
}
