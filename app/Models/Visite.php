<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Une visite de page.
 *
 * Ne contient aucune donnée personnelle : voir la migration pour le détail de
 * l'empreinte quotidienne, qui permet de compter des visiteurs sans permettre
 * d'en identifier un.
 */
class Visite extends Model
{
    /**
     * Pas de `updated_at` : une visite ne se modifie jamais.
     *
     * `created_at` reste en revanche géré par Laravel — et surtout pas par le
     * `useCurrent()` de la colonne. MariaDB horodate en UTC quand
     * l'application vit en America/Toronto : les visites seraient écrites
     * quatre heures dans le futur, et toutes les bornes `now()` du tableau de
     * bord les excluraient. Le symptôme serait un tableau de bord
     * définitivement à zéro alors que la table se remplit.
     */
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    #[Scope]
    protected function depuis(Builder $query, int $jours): void
    {
        $query->where('created_at', '>=', now()->subDays($jours)->startOfDay());
    }

    #[Scope]
    protected function entre(Builder $query, $debut, $fin): void
    {
        $query->whereBetween('created_at', [$debut, $fin]);
    }

    /**
     * Visiteurs distincts sur la période, au sens de l'empreinte du jour.
     *
     * Une même personne revenant le lendemain compte deux fois : c'est le prix
     * de l'absence de suivi persistant, et c'est assumé.
     */
    #[Scope]
    protected function visiteurs(Builder $query): void
    {
        $query->distinct('empreinte');
    }
}
