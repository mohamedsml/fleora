<?php

namespace App\Models;

use App\Models\Concerns\HasMoney;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ligne de facture.
 *
 * Description libre et non rattachée au catalogue : le libellé imprimé sur une
 * facture ne doit pas changer si une création est renommée plus tard.
 */
class InvoiceItem extends Model
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
        static::saving(function (self $ligne): void {
            $ligne->total = $ligne->quantite * $ligne->prix_unitaire;
        });

        static::saved(fn (self $ligne) => $ligne->invoice?->recalculer());
        static::deleted(fn (self $ligne) => $ligne->invoice?->recalculer());
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
