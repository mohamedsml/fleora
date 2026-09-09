<?php

namespace App\Models;

use App\Enums\StatutCommande;
use App\Enums\StatutProduction;
use App\Models\Concerns\HasMoney;
use App\Support\Numerotation;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Commande confirmée.
 *
 * Les coordonnées cliente sont recopiées depuis le devis plutôt que jointes :
 * une commande est un engagement daté, elle doit rester lisible telle qu'elle
 * a été acceptée même si la fiche source est modifiée ou purgée.
 */
class Order extends Model
{
    use HasMoney, SoftDeletes;

    protected $guarded = ['id'];

    protected $attributes = [
        'statut' => StatutCommande::Confirmee->value,
    ];

    protected function casts(): array
    {
        return [
            'sous_total' => 'integer',
            'tps' => 'integer',
            'tvq' => 'integer',
            'total' => 'integer',
            'depot_montant' => 'integer',
            'depot_paye_le' => 'datetime',
            'solde_paye_le' => 'datetime',
            'date_evenement' => 'date',
            'date_livraison_prevue' => 'date',
            'statut' => StatutCommande::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $commande): void {
            $commande->numero ??= Numerotation::suivant('orders', 'CMD');
        });
    }

    /** @return BelongsTo<Quote, $this> */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /** @return BelongsTo<CustomRequest, $this> */
    public function request(): BelongsTo
    {
        return $this->belongsTo(CustomRequest::class, 'request_id');
    }

    /** @return HasMany<ProductionItem, $this> */
    public function productionItems(): HasMany
    {
        return $this->hasMany(ProductionItem::class);
    }

    /** @return HasOne<Invoice, $this> */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    /**
     * Reste à payer, en cents. Zéro quand tout est réglé.
     */
    public function soldeDu(): int
    {
        if ($this->solde_paye_le !== null) {
            return 0;
        }

        return max(0, $this->total - ($this->depot_paye_le !== null ? $this->depot_montant : 0));
    }

    /**
     * Avancement de la production, de 0 à 100.
     *
     * Retourne null si la commande n'a aucun item : « aucun avancement » et
     * « rien à produire » sont deux situations distinctes à l'affichage.
     */
    public function avancement(): ?int
    {
        $total = $this->productionItems()->count();

        if ($total === 0) {
            return null;
        }

        $prets = $this->productionItems()
            ->whereIn('statut', [StatutProduction::Valide, StatutProduction::Pret])
            ->count();

        return (int) round($prets / $total * 100);
    }

    #[Scope]
    protected function aLivrer(Builder $query): void
    {
        $query->whereIn('statut', [
            StatutCommande::Confirmee,
            StatutCommande::EnProduction,
            StatutCommande::Prete,
        ])->orderBy('date_livraison_prevue');
    }
}
