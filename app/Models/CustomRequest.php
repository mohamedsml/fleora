<?php

namespace App\Models;

use App\Enums\StatutDemande;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Demande de soumission — la conversion du site.
 *
 * Nommé `CustomRequest` et non `Request` : `Illuminate\Http\Request` est importé
 * dans presque tous les contrôleurs, et deux `Request` dans un même fichier
 * produisent des bugs pénibles à diagnostiquer.
 *
 * ⚠️ RENSEIGNEMENTS PERSONNELS (Loi 25) — nom, courriel, téléphone, ville,
 * date d'événement. Consentement horodaté obligatoire, conservation limitée
 * à 24 mois après le dernier contact, droit d'accès et de suppression sous
 * 30 jours. Ne jamais journaliser le contenu de ce modèle en clair.
 */
#[Table('requests')]
class CustomRequest extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $attributes = [
        'statut' => StatutDemande::Nouvelle->value,
    ];

    protected function casts(): array
    {
        return [
            'date_evenement' => 'date',
            'couleurs' => 'array',
            'consentement' => 'boolean',
            'consentement_le' => 'datetime',
            'infolettre' => 'boolean',
            'statut' => StatutDemande::class,
            'repondu_le' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Numéro de suivi attribué avant insertion : il est communiqué à la
        // cliente dans le courriel de confirmation, il doit donc exister dès
        // la création et ne jamais changer ensuite.
        static::creating(function (self $demande): void {
            $demande->numero_suivi ??= static::genererNumeroSuivi();
        });
    }

    /**
     * Format FL-AAMM-XXXX. Le préfixe daté rend le numéro lisible au téléphone
     * et la partie aléatoire empêche de deviner le volume d'affaires.
     */
    public static function genererNumeroSuivi(): string
    {
        do {
            $numero = 'FL-'.now()->format('ym').'-'.Str::upper(Str::random(4));
        } while (static::withTrashed()->where('numero_suivi', $numero)->exists());

        return $numero;
    }

    /** @return BelongsTo<Occasion, $this> */
    public function occasion(): BelongsTo
    {
        return $this->belongsTo(Occasion::class);
    }

    /** @return BelongsTo<ProductType, $this> */
    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class);
    }

    /** @return BelongsTo<Creation, $this> */
    public function creationReference(): BelongsTo
    {
        return $this->belongsTo(Creation::class, 'creation_reference_id');
    }

    /** @return HasMany<RequestAttachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(RequestAttachment::class, 'request_id');
    }

    /** @return HasMany<Quote, $this> */
    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class, 'request_id');
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'request_id');
    }

    /**
     * Libellé de l'occasion, y compris quand la cliente a saisi « autre ».
     */
    public function occasionLibelle(): ?string
    {
        return $this->occasion?->t('nom') ?? $this->occasion_autre;
    }

    /**
     * Une demande dont l'événement approche et qui n'a pas encore de réponse
     * est le premier signal d'alerte du tableau de bord.
     */
    public function estUrgente(): bool
    {
        return $this->repondu_le === null
            && $this->date_evenement !== null
            && $this->date_evenement->isBefore(now()->addDays(14));
    }

    #[Scope]
    protected function actives(Builder $query): void
    {
        $query->whereIn('statut', StatutDemande::actives());
    }

    #[Scope]
    protected function nonRepondues(Builder $query): void
    {
        $query->whereNull('repondu_le');
    }
}
