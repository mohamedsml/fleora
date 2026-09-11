{{--
    ⚠️ TEXTE PROVISOIRE — à remplacer par la véritable histoire.

    Ce qui est écrit ici est volontairement générique : personne ne peut
    inventer votre parcours à votre place, et c'est précisément ce qui fait
    vendre l'artisanat. Les faits vérifiables (« depuis 2024 », « plus de
    200 créations ») ont été évités : une affirmation fausse sur une page
    « À propos » se remarque et coûte plus cher qu'une page sobre.

    À remplacer par : pourquoi vous avez commencé, ce qui vous tient à cœur
    dans le travail, et ce qui vous distingue concrètement.
--}}
<x-layout titre="À propos"
          description="Des boîtes décoratives assemblées à la main au Québec, pensées pour les moments qui comptent.">

    <div class="mx-auto max-w-3xl px-6 py-20 lg:py-28">

        <header>
            <h1 class="font-display text-4xl text-ink-900 sm:text-5xl lg:text-6xl">
                Le geste avant l'objet
            </h1>
        </header>

        {{-- space-y-10 plutôt que 8 : avec un corps de 18 px et une interligne
             à 1.7, 32 px ne séparent pas assez deux paragraphes — le texte
             paraît compact. Les titres reçoivent une marge supérieure
             nettement plus grande pour ouvrir visuellement chaque section. --}}
        <div class="mt-14 space-y-10 text-lg leading-relaxed text-ink-600">

            <p class="!mb-14 text-xl leading-relaxed text-ink-700">
                Une boîte n'est jamais qu'une boîte. C'est un prénom qu'on écrit,
                une couleur qu'on choisit parce qu'elle rappelle quelque chose, un
                soin qu'on prend pour quelqu'un.
            </p>

            <p>
                {{ config('app.name') }} est né d'une conviction simple : les objets
                qu'on offre méritent autant d'attention que les mots qu'on prononce.
                Chaque création est assemblée à la main, pièce par pièce, dans notre
                atelier au Québec.
            </p>

            <p>
                Nous ne produisons pas en série. Chaque commande part d'une
                conversation — votre événement, vos couleurs, le prénom à inscrire —
                et devient une pièce qui n'existera qu'une fois.
            </p>

            <h2 class="!mt-20 font-display text-3xl text-ink-900">Comment nous travaillons</h2>

            <p>
                Nous choisissons des matières qui durent : fleurs éternelles
                soigneusement sélectionnées, boîtes rigides, finitions posées à la
                main. Une création doit pouvoir rester en place longtemps après
                l'événement.
            </p>

            <p>
                Quand une idée nous semble irréalisable dans le délai ou le budget
                annoncé, nous le disons franchement plutôt que de livrer quelque
                chose d'approximatif.
            </p>

            <h2 class="!mt-20 font-display text-3xl text-ink-900">Où nous trouver</h2>

            <p>
                Nous livrons dans tout le Grand Montréal — Montréal, Laval, la
                Rive-Nord et la Rive-Sud. La cueillette est possible sur rendez-vous.
            </p>
        </div>

        <div class="mt-20 rounded-3xl bg-ivory-100 px-8 py-12 text-center">
            <h2 class="font-display text-2xl text-ink-900">Un projet en tête ?</h2>
            <p class="mx-auto mt-3 max-w-lg text-ink-600">
                Racontez-nous votre événement. Nous vous répondons sous 24 h avec
                une proposition personnalisée.
            </p>
            <div class="mt-8 flex flex-wrap justify-center gap-4">
                <x-ui.bouton :href="route('demande')">Demander une soumission</x-ui.bouton>
                <x-ui.bouton :href="route('creations')" variante="secondaire">
                    Voir les créations
                </x-ui.bouton>
            </div>
        </div>
    </div>
</x-layout>
