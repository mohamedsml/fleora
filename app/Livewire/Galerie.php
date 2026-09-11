<?php

namespace App\Livewire;

use App\Models\Creation;
use App\Models\Occasion;
use App\Models\ProductType;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Galerie filtrable des créations.
 *
 * Deux axes de filtrage seulement — occasion et type de produit. Au-delà, le
 * visiteur ne filtre plus : il quitte.
 *
 * Les filtres vivent dans l'URL (#[Url]) pour trois raisons concrètes :
 * un lien filtré se partage, le bouton retour du navigateur fonctionne, et
 * les pages Occasions peuvent pointer directement vers une galerie pré-filtrée.
 */
class Galerie extends Component
{
    /** Slug d'occasion, ex. « mariage ». */
    #[Url(as: 'occasion', except: '')]
    public string $occasion = '';

    /** Slug de type de produit, ex. « boite-a-fleurs ». */
    #[Url(as: 'type', except: '')]
    public string $type = '';

    /**
     * Nombre de créations affichées. Pas de défilement infini : il casse le
     * bouton retour, empêche d'atteindre le pied de page, et nuit au SEO.
     */
    public int $affichees = 12;

    private const PAR_PAGE = 12;

    public function filtrerOccasion(string $slug): void
    {
        // Recliquer sur un filtre actif le retire : évite d'avoir à chercher
        // un bouton « tout afficher ».
        $this->occasion = $this->occasion === $slug ? '' : $slug;
        $this->affichees = self::PAR_PAGE;
    }

    public function filtrerType(string $slug): void
    {
        $this->type = $this->type === $slug ? '' : $slug;
        $this->affichees = self::PAR_PAGE;
    }

    public function reinitialiser(): void
    {
        $this->occasion = '';
        $this->type = '';
        $this->affichees = self::PAR_PAGE;
    }

    public function voirPlus(): void
    {
        $this->affichees += self::PAR_PAGE;
    }

    public function render(): View
    {
        $requete = Creation::publie()
            // Sans `with`, chaque carte déclencherait ses propres requêtes
            // pour ses images et ses occasions.
            ->with(['media', 'occasions'])
            // parSlug plutôt que slug_fr : le filtre doit fonctionner avec le
            // slug de la langue courante, sinon changer de langue sur une
            // galerie filtrée viderait le résultat.
            ->when($this->occasion, fn ($q) => $q->whereHas(
                'occasions',
                fn ($o) => $o->parSlug($this->occasion)
            ))
            ->when($this->type, fn ($q) => $q->whereHas(
                'productType',
                fn ($t) => $t->parSlug($this->type)
            ));

        $total = $requete->clone()->count();

        return view('livewire.galerie', [
            'creations' => $requete->take($this->affichees)->get(),
            'total' => $total,
            'resteAAfficher' => max(0, $total - $this->affichees),
            'occasions' => Occasion::publie()->get(),
            'types' => ProductType::publie()->get(),
        ]);
    }
}
