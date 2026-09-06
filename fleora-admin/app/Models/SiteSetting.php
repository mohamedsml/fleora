<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Réglages globaux en paires clé/valeur JSON : NAP, réseaux sociaux, horaires,
 * bandeau d'annonce.
 *
 * Lu à chaque rendu de page, donc mis en cache. Toute écriture vide le cache —
 * sans quoi une modification en back-office resterait invisible sur le site.
 */
class SiteSetting extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'valeur' => 'array',
        ];
    }

    protected const CACHE_CLE = 'fleora.settings';

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_CLE));
        static::deleted(fn () => Cache::forget(self::CACHE_CLE));
    }

    /**
     * Tous les réglages sous forme de tableau clé => valeur.
     *
     * @return array<string, mixed>
     */
    public static function tous(): array
    {
        return Cache::rememberForever(
            self::CACHE_CLE,
            fn () => static::query()->pluck('valeur', 'cle')->all(),
        );
    }

    /**
     * Un réglage, avec valeur par défaut si la clé n'existe pas encore.
     */
    public static function lire(string $cle, mixed $defaut = null): mixed
    {
        return data_get(static::tous(), $cle, $defaut);
    }

    /**
     * Écrit un réglage et invalide le cache via l'événement `saved`.
     */
    public static function ecrire(string $cle, mixed $valeur): void
    {
        static::updateOrCreate(['cle' => $cle], ['valeur' => $valeur]);
    }
}
