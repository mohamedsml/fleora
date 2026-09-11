<?php

namespace App\Support;

use App\Models\Concerns\HasTranslations;
use App\Models\Occasion;
use App\Models\ProductType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

/**
 * Correspondance des URL entre les langues.
 *
 * Point unique de vérité pour trois choses qui doivent rester cohérentes :
 * le sélecteur de langue, les balises `hreflang` et l'URL canonique. Les
 * calculer séparément les ferait diverger — et des `hreflang` non réciproques
 * sont purement et simplement ignorés par Google.
 *
 * Le modèle de la page courante, quand il y en a un, est déclaré par la vue via
 * la prop `:modele` du layout. Sans lui, le service ne pourrait pas deviner que
 * `/occasions/mariage` correspond à `/en/occasions/wedding`.
 */
class Traductions
{
    /** Modèle de la page courante, s'il s'agit d'une page de détail. */
    private ?Model $modele = null;

    /**
     * Déclare l'entité affichée. Appelé par le layout.
     */
    public function pour(?Model $modele): static
    {
        $this->modele = $modele;

        return $this;
    }

    /**
     * Nom de route préfixé pour une langue donnée.
     *
     * Le français n'a pas de préfixe ; les autres langues en ont un
     * (« en.creations »).
     */
    public function nomRoute(string $nom, string $langue): string
    {
        // Retire un préfixe déjà présent avant d'appliquer le bon.
        foreach (config('app.locales', ['fr']) as $locale) {
            if (str_starts_with($nom, "{$locale}.")) {
                $nom = substr($nom, strlen($locale) + 1);
                break;
            }
        }

        return $langue === $this->langueParDefaut() ? $nom : "{$langue}.{$nom}";
    }

    /**
     * URL de la page courante dans une autre langue.
     *
     * Retombe sur l'accueil de la langue cible uniquement si la route courante
     * est introuvable — jamais par défaut. Un sélecteur qui renvoie à l'accueil
     * est l'erreur la plus frustrante de ce type de composant.
     */
    public function urlPour(string $langue): string
    {
        $route = Route::current();

        if (! $route || ! $route->getName()) {
            return url($langue === $this->langueParDefaut() ? '/' : "/{$langue}");
        }

        $nom = $this->nomRoute($route->getName(), $langue);

        if (! Route::has($nom)) {
            return url($langue === $this->langueParDefaut() ? '/' : "/{$langue}");
        }

        return route($nom, $this->parametres($langue));
    }

    /**
     * Paramètres de route traduits : le slug de l'entité, et les filtres de
     * galerie qui portent eux aussi des slugs.
     */
    private function parametres(string $langue): array
    {
        $route = Route::current();
        $params = $route ? $route->parameters() : [];

        // Un modèle lié remplace le slug brut par celui de la langue cible.
        foreach ($params as $cle => $valeur) {
            if ($valeur instanceof Model && $this->estTraduisible($valeur)) {
                $params[$cle] = $valeur->slugPour($langue);
            }
        }

        // Le modèle déclaré par la vue prime — cas d'une page de détail dont le
        // paramètre est resté une chaîne.
        if ($this->modele && $this->estTraduisible($this->modele)) {
            foreach ($params as $cle => $valeur) {
                if (is_string($valeur)) {
                    $params[$cle] = $this->modele->slugPour($langue);
                    break;
                }
            }
        }

        return array_merge($params, $this->filtresTraduits($langue));
    }

    /**
     * Traduit les filtres de la galerie (`?occasion=mariage` → `?occasion=wedding`).
     *
     * Sans cela, changer de langue depuis une galerie filtrée viderait le
     * résultat : le slug français ne correspondrait à rien côté anglais.
     */
    private function filtresTraduits(string $langue): array
    {
        $traduits = [];

        $sources = [
            'occasion' => Occasion::class,
            'type' => ProductType::class,
        ];

        foreach ($sources as $parametre => $classe) {
            $slug = request()->query($parametre);

            if (blank($slug)) {
                continue;
            }

            $entite = $classe::query()->parSlug($slug)->first();

            $traduits[$parametre] = $entite ? $entite->slugPour($langue) : $slug;
        }

        return $traduits;
    }

    /**
     * URL canonique de la page courante.
     *
     * Volontairement sans les paramètres de requête : une galerie filtrée n'est
     * pas une page distincte à indexer, et laisser les combinaisons de filtres
     * créer des URL multiplierait le contenu dupliqué.
     */
    public function canonique(): string
    {
        return url()->current();
    }

    /**
     * Balises `hreflang` de la page courante, réciproques par construction.
     *
     * @return array<string, string> locale => URL
     */
    public function alternatives(): array
    {
        $alternatives = [];

        foreach (config('app.locales', ['fr']) as $langue) {
            $alternatives[$langue] = $this->urlPour($langue);
        }

        return $alternatives;
    }

    /**
     * Code de langue complet pour `hreflang` et Open Graph.
     */
    public function codeRegional(string $langue): string
    {
        return match ($langue) {
            'fr' => 'fr-CA',
            'en' => 'en-CA',
            default => $langue,
        };
    }

    public function langueParDefaut(): string
    {
        return config('app.locales', ['fr'])[0];
    }

    private function estTraduisible(Model $modele): bool
    {
        return in_array(HasTranslations::class, class_uses_recursive($modele), true);
    }
}
