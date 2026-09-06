<?php

namespace App\Models;

use App\Enums\TypeMouvement;
use App\Models\Concerns\HasMoney;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Matière première : fleur, boîte, ruban, accessoire.
 *
 * `stock_actuel` est un cache dérivé des mouvements, jamais une source de
 * vérité qu'on modifierait à la main : passer par `mouvement()` garantit qu'un
 * écart de stock est toujours explicable par son historique.
 */
class Material extends Model
{
    use HasMoney;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'stock_actuel' => 'decimal:2',
            'seuil_alerte' => 'decimal:2',
            'cout_unitaire' => 'integer',
            'actif' => 'boolean',
        ];
    }

    /** @return HasMany<MaterialMovement, $this> */
    public function movements(): HasMany
    {
        return $this->hasMany(MaterialMovement::class)->latest();
    }

    /**
     * Enregistre un mouvement et met à jour le stock de façon atomique.
     *
     * La transaction et le verrou évitent la perte d'écriture classique : deux
     * sorties simultanées qui liraient le même stock de départ en écriraient
     * chacune une, et une des deux disparaîtrait.
     *
     * @param  float  $quantite  Valeur absolue, sauf ajustement qui se saisit signé
     */
    public function mouvement(
        TypeMouvement $type,
        float $quantite,
        ?string $motif = null,
        ?ProductionItem $item = null,
    ): MaterialMovement {
        return DB::transaction(function () use ($type, $quantite, $motif, $item) {
            $frais = static::query()->lockForUpdate()->find($this->id);

            $delta = $quantite * $type->signe();
            $apres = round((float) $frais->stock_actuel + $delta, 2);

            $frais->forceFill(['stock_actuel' => $apres])->save();
            $this->stock_actuel = $apres;

            return $this->movements()->create([
                'production_item_id' => $item?->id,
                'type' => $type,
                'quantite' => $quantite,
                'stock_apres' => $apres,
                'motif' => $motif,
            ]);
        });
    }

    /**
     * Valeur du stock détenu, en cents.
     */
    public function valeurStock(): int
    {
        return (int) round((float) $this->stock_actuel * $this->cout_unitaire);
    }

    public function sousLeSeuil(): bool
    {
        return (float) $this->stock_actuel <= (float) $this->seuil_alerte;
    }

    #[Scope]
    protected function aRecommander(Builder $query): void
    {
        $query->where('actif', true)
            ->whereColumn('stock_actuel', '<=', 'seuil_alerte');
    }
}
