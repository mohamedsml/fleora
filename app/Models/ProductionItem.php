<?php

namespace App\Models;

use App\Enums\StatutProduction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pièce à fabriquer pour une commande — une carte du kanban de production.
 */
class ProductionItem extends Model
{
    protected $guarded = ['id'];

    protected $attributes = [
        'statut' => StatutProduction::AFaire->value,
    ];

    protected function casts(): array
    {
        return [
            'quantite' => 'integer',
            'statut' => StatutProduction::class,
            'commence_le' => 'datetime',
            'termine_le' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Les dates de début et de fin sont horodatées au franchissement de
        // l'étape plutôt que saisies : c'est ce qui rend le temps de production
        // réellement mesurable.
        static::saving(function (self $item): void {
            if (! $item->isDirty('statut')) {
                return;
            }

            if ($item->statut !== StatutProduction::AFaire && $item->commence_le === null) {
                $item->commence_le = now();
            }

            if ($item->statut === StatutProduction::Pret && $item->termine_le === null) {
                $item->termine_le = now();
            }
        });
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<Creation, $this> */
    public function creation(): BelongsTo
    {
        return $this->belongsTo(Creation::class);
    }

    /** @return BelongsTo<Media, $this> */
    public function photoValidation(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'photo_validation_id');
    }

    /** @return HasMany<MaterialMovement, $this> */
    public function movements(): HasMany
    {
        return $this->hasMany(MaterialMovement::class);
    }
}
