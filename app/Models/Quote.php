<?php

namespace App\Models;

use App\Enums\StatutDevis;
use App\Models\Concerns\HasMoney;
use App\Support\Numerotation;
use App\Support\Taxes;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Devis envoyé à une cliente.
 *
 * Les totaux sont stockés en colonnes plutôt que recalculés à la lecture :
 * un devis accepté doit refléter le montant sur lequel la cliente s'est
 * engagée, même si un prix change ensuite au catalogue.
 */
class Quote extends Model
{
    use HasMoney, SoftDeletes;

    protected $guarded = ['id'];

    protected $attributes = [
        'statut' => StatutDevis::Brouillon->value,
    ];

    protected function casts(): array
    {
        return [
            'sous_total' => 'integer',
            'tps' => 'integer',
            'tvq' => 'integer',
            'total' => 'integer',
            'valide_jusqu_au' => 'date',
            'statut' => StatutDevis::class,
            'envoye_le' => 'datetime',
            'repondu_le' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $devis): void {
            $devis->numero ??= Numerotation::suivant('quotes', 'DEV');
        });
    }

    /** @return BelongsTo<CustomRequest, $this> */
    public function request(): BelongsTo
    {
        return $this->belongsTo(CustomRequest::class, 'request_id');
    }

    /** @return HasMany<QuoteItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class)->orderBy('ordre');
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Recalcule les totaux à partir des lignes et les persiste.
     *
     * Appelé après toute modification de ligne. Les taxes ne sont ventilées que
     * si l'entreprise est inscrite aux fichiers TPS/TVQ.
     */
    public function recalculer(): void
    {
        $sousTotal = (int) $this->items()->sum('total');

        $this->forceFill(Taxes::ventiler($sousTotal, Taxes::inscrit()))->save();
    }

    /**
     * Un devis dont la date de validité est passée ne devrait plus être accepté.
     */
    public function estExpire(): bool
    {
        return $this->valide_jusqu_au !== null
            && $this->valide_jusqu_au->isPast()
            && $this->statut === StatutDevis::Envoye;
    }

    #[Scope]
    protected function enAttente(Builder $query): void
    {
        $query->where('statut', StatutDevis::Envoye);
    }
}
