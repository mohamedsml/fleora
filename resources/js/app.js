import collapse from '@alpinejs/collapse';

/*
 * Alpine vient de Livewire, on ne l'importe PAS séparément.
 *
 * Livewire embarque sa propre instance d'Alpine et la démarre lui-même. En
 * important puis en lançant une seconde instance ici, les deux entrent en
 * conflit : Livewire ne peut plus se brancher, et tous les `wire:click`
 * deviennent inertes — sans la moindre erreur en console.
 *
 * Le hook `livewire:init` s'exécute avant le démarrage d'Alpine par Livewire :
 * c'est le bon endroit pour enregistrer une extension.
 */
document.addEventListener('livewire:init', () => {
    window.Alpine.plugin(collapse);
});

/**
 * Apparition progressive des sections au défilement.
 *
 * Le CSS laisse le contenu visible par défaut ; c'est ce script qui masque
 * puis révèle. Ainsi, si le JavaScript ne s'exécute pas — erreur réseau,
 * navigateur ancien, robot d'indexation — la page reste entièrement lisible
 * plutôt que blanche.
 */
document.addEventListener('DOMContentLoaded', () => {
    const cibles = document.querySelectorAll('[data-reveal]');

    if (cibles.length === 0) {
        return;
    }

    // Une personne ayant désactivé les animations dans son système ne doit
    // subir aucun mouvement : on affiche tout immédiatement.
    const animationsReduites = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (animationsReduites || ! ('IntersectionObserver' in window)) {
        cibles.forEach((el) => el.classList.add('is-visible'));

        return;
    }

    cibles.forEach((el) => el.classList.add('reveal'));

    const observateur = new IntersectionObserver(
        (entrees) => {
            entrees.forEach((entree) => {
                if (! entree.isIntersecting) {
                    return;
                }

                entree.target.classList.add('is-visible');
                // Une section révélée le reste : on cesse de l'observer pour
                // ne pas garder des callbacks actifs sur toute la page.
                observateur.unobserve(entree.target);
            });
        },
        { rootMargin: '0px 0px -10% 0px', threshold: 0.1 },
    );

    cibles.forEach((el) => observateur.observe(el));

    /*
     * Filet de sécurité : tout ce qui n'a pas été révélé au bout de 3 secondes
     * est affiché sans condition.
     *
     * L'IntersectionObserver ne se déclenche pas dans plusieurs situations
     * réelles — onglet ouvert en arrière-plan, page imprimée, outil de capture
     * automatisé, navigateur qui restaure une position de défilement. Sans ce
     * filet, la visiteuse voit une page blanche et repart.
     */
    window.setTimeout(() => {
        cibles.forEach((el) => el.classList.add('is-visible'));
    }, 3000);
});
