<x-layout titre="À propos"
          :description="config('app.name').' crée des compositions florales et des coffrets cadeaux personnalisés pour célébrer les personnes et les moments qui comptent.'">

    <div class="mx-auto max-w-3xl px-6 py-20 lg:py-28">

        <header>
            <h1 class="font-display text-4xl text-ink-900 sm:text-5xl lg:text-6xl">
                À propos de {{ config('app.name') }}
            </h1>
        </header>

        {{-- space-y-10 plutôt que 8 : avec un corps de 18 px et une interligne
             à 1.7, 32 px ne séparent pas assez deux paragraphes — le texte
             paraît compact. --}}
        <div class="mt-14 space-y-10 text-lg leading-relaxed text-ink-600">

            <p class="!mb-14 text-xl leading-relaxed text-ink-700">
                Bienvenue chez {{ config('app.name') }} 🌸
            </p>

            <p>
                {{ config('app.name') }} est née d'une envie simple : transformer l'art
                d'offrir en une expérience unique et mémorable.
            </p>

            <p>
                Nous créons des compositions florales et des coffrets cadeaux
                personnalisés, pensés avec soin pour célébrer les personnes et les
                moments qui comptent.
            </p>

            <p>
                Chaque création est imaginée avec une attention particulière portée
                aux détails : les fleurs, les couleurs, les petites attentions,
                l'emballage et surtout le message que vous souhaitez transmettre.
            </p>

            <p>
                Chez {{ config('app.name') }}, nous croyons qu'un cadeau n'a pas besoin
                d'être grand pour être inoubliable. Il doit simplement être choisi
                avec intention.
            </p>

            <p>
                Que ce soit pour un anniversaire, une naissance, une célébration, une
                déclaration d'amour, un remerciement ou simplement pour faire plaisir
                sans raison particulière, nous sommes là pour donner vie à votre idée.
            </p>

            {{-- La signature de marque : mise en valeur plutôt que noyée dans le
                 flux, c'est la phrase que le visiteur doit retenir. --}}
            <p class="!mt-16 border-l-2 border-blush-300 pl-6 font-display text-2xl leading-snug text-ink-900">
                Votre intention. Votre message. Notre création. 🌷
            </p>

            <div class="!mt-16 border-t border-sand-200 pt-10 text-center">
                <p class="font-display text-2xl text-ink-900">
                    {{ config('app.name') }} — Floral Gifts &amp; Boxes
                </p>
                <p class="mt-2 text-ink-500">
                    Offrir autrement. Créer des souvenirs.
                </p>
            </div>
        </div>

        <div class="mt-20 rounded-3xl bg-ivory-100 px-8 py-12 text-center">
            <h2 class="font-display text-2xl text-ink-900">Un projet en tête ?</h2>
            <p class="mx-auto mt-3 max-w-lg text-ink-600">
                Racontez-nous votre événement. Nous vous répondons sous 24 h avec
                une proposition personnalisée.
            </p>
            <div class="mt-8 flex flex-wrap justify-center gap-4">
                <x-ui.bouton :href="route_langue('demande')">Demander une soumission</x-ui.bouton>
                <x-ui.bouton :href="route_langue('creations')" variante="secondaire">
                    Voir les créations
                </x-ui.bouton>
            </div>
        </div>
    </div>
</x-layout>
