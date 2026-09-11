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
     * Prix de départ formaté, ex. « À partir de 70 $ ».
     *
     * Toujours un prix de départ, jamais une fourchette : chaque création est
     * personnalisée, donc le prix final dépend de la demande. Annoncer
     * « 70 $ – 150 $ » laisserait croire à un catalogue figé, et le haut de
     * la fourchette décourage avant même la conversation.
     *
     * `prix_max` reste en base : il sert aux données structurées
     * (`lowPrice`/`highPrice`), que Google attend sous forme d'intervalle.
     *
     * Retourne null si aucun prix n'est saisi — mieux vaut ne rien afficher
     * qu'un prix inventé.
     */
    public function fourchettePrix(?string $langue = null): ?string
    {
        if ($this->prix_min === null) {
            return null;
        }

        // Sans décimales : sur une vitrine, « 70 $ » se lit mieux que
        // « 70,00 $ », et les centimes n'ont pas de sens sur un prix indicatif.
        $min = $this->argent($this->prix_min, $langue, decimales: false);

        return __('fleora.prix.a_partir_de', ['montant' => $min], $langue);
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
