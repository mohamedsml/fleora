<?php

namespace App\Models;

use App\Enums\StatutFacture;
use App\Models\Concerns\HasMoney;
use App\Support\Numerotation;
use App\Support\Taxes;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Facture.
 *
 * ⚠️ DOCUMENT FISCAL. Trois règles non négociables :
 *   1. numérotation séquentielle sans trou (voir App\Support\Numerotation)
 *   2. une facture émise ne se modifie plus — elle s'annule par note de crédit
 *   3. jamais de suppression dure, d'où le softDeletes
 *
 * Les numéros de TPS/TVQ sont recopiés à l'émission plutôt que lus dans la
 * config : ils doivent rester ceux en vigueur à la date d'émission.
 */
class Invoice extends Model
{
    use HasMoney, SoftDeletes;

    protected $guarded = ['id'];

    /**
     * Le statut est aussi défini côté modèle, pas seulement en base : une
     * instance neuve doit connaître son statut avant tout aller-retour SQL,
     * sinon la logique métier lit `null` et se trompe.
     */
    protected $attributes = [
        'statut' => StatutFacture::Brouillon->value,
    ];

    protected function casts(): array
    {
        return [
            'sous_total' => 'integer',
            'tps' => 'integer',
            'tvq' => 'integer',
            'total' => 'integer',
            'emise_le' => 'date',
            'echeance_le' => 'date',
            'payee_le' => 'datetime',
            'statut' => StatutFacture::class,
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (self $facture): void {
            $avant = $facture->getOriginal('statut');
            $avant = $avant instanceof StatutFacture ? $avant : StatutFacture::tryFrom((string) $avant);

            if ($avant?->estFigee() !== true) {
                return;
            }

            // Une facture figée n'accepte plus que le passage au paiement ou
            // à l'annulation. Toute autre modification est un incident.
            $autorise = ['statut', 'payee_le', 'mode_paiement', 'notes', 'updated_at', 'deleted_at'];

            if (array_diff(array_keys($facture->getDirty()), $autorise) !== []) {
                throw new RuntimeException(
                    "Facture {$facture->numero} déjà émise : son contenu ne peut plus être modifié. ".
                    'Émettez une note de crédit.'
                );
            }
        });
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return HasMany<InvoiceItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('ordre');
    }

    /**
     * Recalcule les totaux depuis les lignes. Refuse de toucher une facture
     * déjà émise.
     */
    public function recalculer(): void
    {
        if ($this->statut->estFigee()) {
            return;
        }

        $sousTotal = (int) $this->items()->sum('total');

        $this->forceFill(Taxes::ventiler($sousTotal, Taxes::inscrit()))->save();
    }

    /**
     * Émet la facture : fige son contenu, attribue son numéro et recopie les
     * numéros de taxes en vigueur.
     *
     * L'attribution du numéro vit ici, et nulle part ailleurs : c'est le seul
     * moment où la séquence doit avancer. Le tout dans une transaction, pour
     * que le numéro réservé et la facture qui le porte soient écrits ensemble
     * ou pas du tout — sinon un échec entre les deux laisse un trou.
     */
    public function emettre(int $joursEcheance = 30): void
    {
        if ($this->statut !== StatutFacture::Brouillon) {
            throw new RuntimeException("Facture {$this->numero} déjà émise.");
        }

        DB::transaction(function () use ($joursEcheance): void {
            $this->recalculer();

            $this->forceFill([
                'numero' => $this->numero ?? Numerotation::suivant('invoices', 'FAC'),
                'statut' => StatutFacture::Emise,
                'emise_le' => now()->toDateString(),
                'echeance_le' => now()->addDays($joursEcheance)->toDateString(),
                'numero_tps' => Taxes::inscrit() ? config('fleora.taxes.numero_tps') : null,
                'numero_tvq' => Taxes::inscrit() ? config('fleora.taxes.numero_tvq') : null,
            ])->save();
        });
    }

    public function estEnRetard(): bool
    {
        return $this->statut === StatutFacture::Emise
            && $this->echeance_le !== null
            && $this->echeance_le->isPast();
    }

    #[Scope]
    protected function impayees(Builder $query): void
    {
        $query->where('statut', StatutFacture::Emise)->orderBy('echeance_le');
    }
}
