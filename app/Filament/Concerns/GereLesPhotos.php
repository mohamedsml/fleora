<?php

namespace App\Filament\Concerns;

use App\Models\Media;
use App\Services\ImageService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Téléversement des photos depuis Filament.
 *
 * Le champ `photos` du formulaire n'existe pas en base : les images vivent
 * dans la table `media` (relation polymorphe). On l'extrait donc avant la
 * sauvegarde du modèle, puis on traite les fichiers une fois l'enregistrement
 * créé — un média a besoin de l'id de son parent.
 *
 * Pourquoi ne pas utiliser un RelationManager, plus « standard » chez Filament :
 * il n'apparaît qu'après la première sauvegarde. Ajouter une création et ses
 * photos deviendrait une opération en deux temps, alors que c'est le même
 * geste dans la tête de la personne qui saisit.
 */
trait GereLesPhotos
{
    /** Fichiers extraits du formulaire, en attente de traitement. */
    protected array $photosATraiter = [];

    /**
     * Retire `photos` des données du modèle et le met de côté.
     */
    protected function extrairePhotos(array $data): array
    {
        $photos = $data['photos'] ?? [];

        // Filament renvoie un tableau associatif dont l'ordre des clés reflète
        // l'ordre voulu par l'utilisateur, mais dont l'itération ne le suit pas
        // (les nouveaux fichiers arrivent avec des clés hors séquence).
        // On trie explicitement sur la clé.
        ksort($photos, SORT_NATURAL);

        $this->photosATraiter = array_values($photos);
        unset($data['photos']);

        return $data;
    }

    /**
     * Traite les fichiers téléversés et synchronise l'ordre d'affichage.
     */
    protected function traiterPhotos(Model $enregistrement): void
    {
        $service = ImageService::make();
        $ordre = 0;

        // Chemins qui doivent survivre : les photos conservées ET celles qu'on
        // vient de créer. Sans les secondes, le nettoyage supprimerait
        // immédiatement les photos tout juste téléversées.
        $conserves = [];

        foreach ($this->photosATraiter as $entree) {
            // Une entrée déjà en base = photo existante réordonnée.
            // Filament renvoie alors son chemin, pas un fichier.
            if (is_string($entree)) {
                Media::where('mediable_type', $enregistrement->getMorphClass())
                    ->where('mediable_id', $enregistrement->getKey())
                    ->where('chemin', $entree)
                    ->update(['ordre' => $ordre++]);

                $conserves[] = $entree;

                continue;
            }

            if ($entree instanceof UploadedFile) {
                $media = $service->attacher($entree, $enregistrement, ['ordre' => $ordre++]);
                $conserves[] = $media->chemin;
            }
        }

        $this->supprimerPhotosRetirees($enregistrement, $conserves);
    }

    /**
     * Supprime les médias absents du formulaire — et leurs fichiers.
     *
     * Sans cela, retirer une photo de la liste la ferait disparaître du site
     * tout en laissant ses cinq fichiers sur le disque. Sur un hébergement
     * mutualisé, l'espace et les inodes sont comptés.
     */
    protected function supprimerPhotosRetirees(Model $enregistrement, array $conserves): void
    {
        $aSupprimer = Media::where('mediable_type', $enregistrement->getMorphClass())
            ->where('mediable_id', $enregistrement->getKey())
            ->when($conserves !== [], fn ($q) => $q->whereNotIn('chemin', $conserves))
            ->get();

        $service = ImageService::make();

        foreach ($aSupprimer as $media) {
            $service->supprimerFichiers($media);
            $media->delete();
        }
    }

    /**
     * Chemins des photos déjà en base, dans l'ordre d'affichage.
     * Sert à pré-remplir le champ à l'ouverture du formulaire.
     */
    protected function photosExistantes(Model $enregistrement): array
    {
        return $enregistrement->media()
            ->orderBy('ordre')
            ->pluck('chemin')
            ->filter(fn (string $chemin) => Storage::disk('public')->exists($chemin))
            ->values()
            ->all();
    }
}
