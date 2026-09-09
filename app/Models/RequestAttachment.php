<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Image d'inspiration jointe à une demande.
 *
 * Stockée sur un disque privé : ce sont des fichiers envoyés par une cliente,
 * pas du contenu public. L'accès passe par une URL temporaire signée.
 */
class RequestAttachment extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'taille' => 'integer',
        ];
    }

    /** @return BelongsTo<CustomRequest, $this> */
    public function request(): BelongsTo
    {
        return $this->belongsTo(CustomRequest::class, 'request_id');
    }

    /**
     * URL signée valable 15 minutes — assez pour consulter en back-office,
     * trop court pour être partagée durablement.
     */
    public function urlTemporaire(): string
    {
        return Storage::disk('local')->temporaryUrl($this->chemin, now()->addMinutes(15));
    }
}
