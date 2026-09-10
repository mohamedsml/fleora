<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;

/**
 * Traitement des images téléversées.
 *
 * Les variantes sont générées UNE FOIS à l'upload, jamais à l'affichage :
 * redimensionner à la volée est le poste de coût n°1 en CPU sur un
 * hébergement mutualisé, et Hostinger coupe les processus trop gourmands.
 *
 * Format WebP : ~30 % plus léger que le JPEG à qualité équivalente, et
 * supporté par tous les navigateurs depuis 2020.
 */
class ImageService
{
    /**
     * Largeurs générées, alignées sur les `sizes` des composants Blade.
     * 1600 couvre les écrans Retina en pleine largeur ; au-delà le gain
     * visuel ne justifie plus le poids.
     */
    public const LARGEURS = [400, 800, 1200, 1600];

    private const QUALITE = 82;

    public function __construct(private ImageManager $manager) {}

    public static function make(): self
    {
        // Imagick plutôt que GD : meilleur rendu sur les redimensionnements
        // importants, et gestion correcte des profils couleur des photos.
        return new self(new ImageManager(new ImagickDriver));
    }

    /**
     * Téléverse une image, génère ses variantes, et l'attache au modèle.
     *
     * @param  Model  $mediable  Création, occasion… (relation morphMany `media`)
     */
    public function attacher(UploadedFile $fichier, Model $mediable, array $attributs = []): Media
    {
        $disque = $attributs['disque'] ?? 'public';
        $dossier = $this->dossierPour($mediable);

        // Nom régénéré : on ne fait jamais confiance au nom fourni par
        // l'utilisateur (traversée de chemin, caractères exotiques, collisions).
        $base = Str::uuid()->toString();

        $image = $this->manager->read($fichier->getRealPath());

        // Réencodage systématique de l'original : neutralise toute charge utile
        // dissimulée dans un fichier qui se présente comme une image.
        $cheminOriginal = "{$dossier}/{$base}.webp";
        Storage::disk($disque)->put(
            $cheminOriginal,
            (string) $image->toWebp(self::QUALITE)
        );

        $variantes = $this->genererVariantes($image, $disque, $dossier, $base);

        return $mediable->media()->create([
            ...$attributs,
            'chemin' => $cheminOriginal,
            'disque' => $disque,
            'mime' => 'image/webp',
            'taille' => Storage::disk($disque)->size($cheminOriginal),
            'largeur' => $image->width(),
            'hauteur' => $image->height(),
            'variantes' => $variantes,
        ]);
    }

    /**
     * (Re)génère les variantes d'un média existant.
     * Utile après un changement de la liste des largeurs.
     */
    public function regenerer(Media $media): Media
    {
        $disque = $media->disque;

        if (! Storage::disk($disque)->exists($media->chemin)) {
            return $media;
        }

        $image = $this->manager->read(
            Storage::disk($disque)->path($media->chemin)
        );

        $dossier = dirname($media->chemin);
        $base = pathinfo($media->chemin, PATHINFO_FILENAME);

        $media->update([
            'largeur' => $image->width(),
            'hauteur' => $image->height(),
            'variantes' => $this->genererVariantes($image, $disque, $dossier, $base),
        ]);

        return $media;
    }

    /**
     * Supprime le fichier original et toutes ses variantes.
     */
    public function supprimerFichiers(Media $media): void
    {
        $disque = Storage::disk($media->disque);

        $disque->delete($media->chemin);

        foreach ($media->variantes ?? [] as $chemin) {
            $disque->delete($chemin);
        }
    }

    /**
     * @return array<string, string> largeur => chemin
     */
    private function genererVariantes(
        ImageInterface $image,
        string $disque,
        string $dossier,
        string $base,
    ): array {
        $variantes = [];
        $largeurSource = $image->width();

        foreach (self::LARGEURS as $largeur) {
            // Ne jamais agrandir : une variante 1600 depuis un original de
            // 900 px serait plus lourde sans être plus nette.
            if ($largeurSource < $largeur) {
                continue;
            }

            $chemin = "{$dossier}/{$base}-{$largeur}.webp";

            // scale() modifie l'image EN PLACE (vérifié sur Intervention 3) :
            // sans copie, la première variante réduirait l'original et les
            // suivantes partiraient d'une source déjà dégradée.
            Storage::disk($disque)->put(
                $chemin,
                (string) (clone $image)->scale(width: $largeur)->toWebp(self::QUALITE)
            );

            $variantes[(string) $largeur] = $chemin;
        }

        return $variantes;
    }

    private function dossierPour(Model $mediable): string
    {
        return 'media/'.Str::plural(Str::snake(class_basename($mediable)));
    }
}
