<?php

namespace App\Models;

use App\Enums\TypeMouvement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Mouvement de stock — la trace d'audit des matières.
 *
 * Immuable par nature : on ne corrige pas un mouvement, on en enregistre un
 * d'ajustement. C'est ce qui rend l'historique opposable.
 */
class MaterialMovement extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'type' => TypeMouvement::class,
            'quantite' => 'decimal:2',
            'stock_apres' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Material, $this> */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /** @return BelongsTo<ProductionItem, $this> */
    public function productionItem(): BelongsTo
    {
        return $this->belongsTo(ProductionItem::class);
    }
}
