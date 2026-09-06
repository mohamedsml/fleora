<?php

namespace App\Models;

use App\Models\Concerns\HasMoney;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Une création du catalogue visuel.
 *
 * Le prix est une fourchette « à partir de » : chaque pièce étant sur mesure,
 * annoncer un prix ferme serait faux. La fourchette qualifie quand même le
 * visiteur avant qu'il ne remplisse le formulaire.
 */
class Creation extends Model
{
    use HasMoney, HasTranslations;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'couleurs' => 'array',
            'prix_min' => 'integer',
            'prix_max' => 'integer',
            'vedette' => 'boolean',
            'ordre' => 'integer',
            'publie' => 'boolean',
        ];
    }

    /** @return BelongsTo<ProductType, $this> */
    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class);
    }

    /** @return BelongsToMany<Occasion, $this> */
    public function occasions(): BelongsToMany
    {
        return $this->belongsToMany(Occasion::class);
    }

    /** @return MorphMany<Media, $this> */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable')->orderBy('ordre');
    }

    /**
     * Image principale : la première dans l'ordre défini en back-office.
     */
    public function imagePrincipale(): ?Media
    {
        return $this->media->first();
    }

    /**
     * Fourchette de prix formatée, ex. « À partir de 45,00 $ » ou « 45,00 $ – 90,00 $ ».
     * Retourne null si aucun prix n'est saisi — mieux vaut ne rien afficher.
     */
    public function fourchettePrix(?string $langue = null): ?string
    {
        if ($this->prix_min === null) {
            return null;
        }

        $min = $this->argent($this->prix_min, $langue);

        if ($this->prix_max === null || $this->prix_max <= $this->prix_min) {
            return __('fleora.prix.a_partir_de', ['montant' => $min], $langue);
        }

        return "{$min} – {$this->argent($this->prix_max, $langue)}";
    }

    #[Scope]
    protected function publie(Builder $query): void
    {
        $query->where('publie', true)->orderBy('ordre');
    }

    #[Scope]
    protected function vedette(Builder $query): void
    {
        $query->where('vedette', true);
    }
}
