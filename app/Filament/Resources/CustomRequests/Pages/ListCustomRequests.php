<?php

namespace App\Filament\Resources\CustomRequests\Pages;

use App\Filament\Resources\CustomRequests\CustomRequestResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListCustomRequests extends ListRecords
{
    protected static string $resource = CustomRequestResource::class;

    /**
     * Les demandes sans réponse d'abord, puis les plus récentes.
     *
     * Ouvrir le back-office doit répondre à « qu'est-ce qui m'attend ? » sans
     * avoir à composer un filtre. Les filtres du tableau — dont « Urgentes » —
     * restent disponibles pour affiner.
     */
    protected function getTableQuery(): ?Builder
    {
        return parent::getTableQuery()
            ?->orderByRaw('repondu_le IS NOT NULL')
            ->orderByDesc('created_at');
    }
}
