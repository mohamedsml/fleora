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

    /**
     * Visites par jour sur la période, pour le graphique d'évolution.
     *
     * Les jours sans visite sont absents du résultat : c'est à l'appelant de
     * combler les trous, sinon la courbe relierait deux points distants en
     * masquant le creux.
     *
     * @return array<string, int> date « Y-m-d » => nombre de visites
     */
    public static function parJour($debut, $fin): array
    {
        return static::query()->entre($debut, $fin)
            ->selectRaw('DATE(created_at) as jour, COUNT(*) as total')
            ->groupBy('jour')
            ->orderBy('jour')
            ->pluck('total', 'jour')
            ->all();
    }

    /**
     * Répartition des visites selon une colonne, la plus fréquente d'abord.
     *
     * @return array<string, int>
     */
    public static function repartition(string $colonne, $debut, $fin, int $limite = 20): array
    {
        return static::query()->entre($debut, $fin)
            ->whereNotNull($colonne)
            ->selectRaw("{$colonne} as valeur, COUNT(*) as total")
            ->groupBy('valeur')
            ->orderByDesc('total')
            ->limit($limite)
            ->pluck('total', 'valeur')
            ->all();
    }

    /**
     * Pages par lesquelles les visiteurs entrent sur le site.
     *
     * La page d'entrée est la première vue d'une empreinte donnée. C'est elle
     * qui dit ce que Google et Instagram envoient réellement — rarement
     * l'accueil, souvent une fiche création.
     *
     * @return array<string, int>
     */
    public static function pagesEntree($debut, $fin, int $limite = 10): array
    {
        // La sous-requête retient la première visite de chaque empreinte ;
        // un GROUP BY direct sur le chemin compterait toutes les pages vues.
        $premieres = static::query()->entre($debut, $fin)
            ->selectRaw('MIN(id) as id')
            ->groupBy('empreinte');

        return static::query()->whereIn('id', $premieres)
            ->selectRaw('chemin, COUNT(*) as total')
            ->groupBy('chemin')
            ->orderByDesc('total')
            ->limit($limite)
            ->pluck('total', 'chemin')
            ->all();
    }

    /**
     * Nombre de pages vues par visiteur, et part de ceux qui n'en voient qu'une.
     *
     * Un taux de page unique élevé signale une page d'entrée qui ne donne pas
     * envie d'aller plus loin.
     *
     * @return array{visiteurs: int, pages_par_visiteur: float, une_seule_page: float}
     */
    public static function profondeur($debut, $fin): array
    {
        $parVisiteur = static::query()->entre($debut, $fin)
            ->selectRaw('empreinte, COUNT(*) as pages')
            ->groupBy('empreinte')
            ->pluck('pages');

        $visiteurs = $parVisiteur->count();

        if ($visiteurs === 0) {
            return ['visiteurs' => 0, 'pages_par_visiteur' => 0.0, 'une_seule_page' => 0.0];
        }

        return [
            'visiteurs' => $visiteurs,
            'pages_par_visiteur' => round($parVisiteur->sum() / $visiteurs, 1),
            'une_seule_page' => round($parVisiteur->filter(fn ($n) => $n === 1)->count() / $visiteurs * 100),
        ];
    }
}
