<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

/**
 * Image attachée à une création, une occasion ou un item de production.
 *
 * `variantes` contient les WebP générés à l'upload, indexés par largeur.
 * On ne redimensionne jamais à la volée : c'est le poste de coût n°1 en CPU
 * sur un hébergement mutualisé.
 */
#[Table('media')]
class Media extends Model
{
    use HasTranslations;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'variantes' => 'array',
            'taille' => 'integer',
            'largeur' => 'integer',
            'hauteur' => 'integer',
            'ordre' => 'integer',
        ];
    }

    /** @return MorphTo<Model, $this> */
    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * URL publique de l'original.
     */
    public function url(): string
    {
        return Storage::disk($this->disque)->url($this->chemin);
    }

    /**
     * URL d'une variante par largeur, avec repli sur l'original si elle manque.
     */
    public function urlVariante(int $largeur): string
    {
        $chemin = $this->variantes[(string) $largeur] ?? null;

        return $chemin
            ? Storage::disk($this->disque)->url($chemin)
            : $this->url();
    }

    /**
     * Attribut `srcset` complet à partir des variantes générées.
     * Chaîne vide si aucune variante — l'appelant retombe alors sur `src` seul.
     */
    public function srcset(): string
    {
        $variantes = $this->variantes ?? [];

        if ($variantes === []) {
            return '';
        }

        $sources = [];

        foreach ($variantes as $largeur => $chemin) {
            $sources[] = Storage::disk($this->disque)->url($chemin)." {$largeur}w";
        }

        return implode(', ', $sources);
    }
}
