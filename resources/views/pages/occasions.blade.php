{{--
    Hub des occasions.

    Le visiteur arrive avec un événement précis en tête — un mariage, un baby
    shower. Lui donner sa porte d'entrée réduit le rebond bien plus qu'un
    catalogue générique.

    Les pages de détail par occasion (/occasions/mariage…) arriveront ensuite :
    ce sont elles qui portent le référencement à forte intention.
--}}
<x-layout titre="Occasions"
          description="Mariages, baby showers, anniversaires, baptêmes, graduations : des créations pensées pour chaque événement.">

    <div class="mx-auto max-w-7xl px-6 py-20 lg:py-28">

        <header class="max-w-2xl">
            <h1 class="font-display text-4xl text-ink-900 sm:text-5xl lg:text-6xl">
                Pour chaque occasion
            </h1>
            <p class="mt-6 text-lg leading-relaxed text-ink-600">
                Chaque événement a son langage. Choisissez le vôtre pour voir les
                créations qui lui correspondent.
            </p>
        </header>

        @if ($occasions->isEmpty())
            <p class="mt-16 text-ink-400">
                Les occasions seront bientôt disponibles.
            </p>
        @else
            <div class="mt-14 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($occasions as $occasion)
                    {{-- Vers la galerie pré-filtrée : le lien est utile tout de
                         suite, avant même que les pages de détail existent. --}}
                    <a href="{{ route_langue('creations', ['occasion' => $occasion->slugPour()]) }}"
                       class="group block">
                        <div class="relative aspect-square overflow-hidden rounded-2xl bg-ivory-200">
                            @php $image = $occasion->media->first(); @endphp

                            @if ($image)
                                <img src="{{ $image->urlVariante(800) }}"
                                     @if ($srcset = $image->srcset()) srcset="{{ $srcset }}" @endif
                                     sizes="(min-width: 1024px) 30vw, (min-width: 640px) 45vw, 90vw"
                                     alt="{{ $image->t('alt') ?? $occasion->t('nom') }}"
                                     loading="lazy" decoding="async"
                                     width="{{ $image->largeur }}" height="{{ $image->hauteur }}"
                                     class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105">
                            @else
                                <div class="h-full w-full bg-gradient-to-br from-blush-100 to-sage-100"></div>
                            @endif

                            {{-- Voile dégradé : garantit la lisibilité du titre
                                 quelle que soit la photo posée dessous. --}}
                            <div class="absolute inset-0 bg-gradient-to-t from-ink-900/60 via-transparent to-transparent"></div>

                            <h2 class="absolute bottom-5 left-6 font-display text-2xl text-ivory-50">
                                {{ $occasion->t('nom') }}
                            </h2>
                        </div>

                        @if ($occasion->t('intro'))
                            <p class="mt-4 text-sm leading-relaxed text-ink-600">
                                {{ Str::limit($occasion->t('intro'), 120) }}
                            </p>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif

        <div class="mt-24 rounded-3xl bg-ivory-100 px-8 py-12 text-center">
            <h2 class="font-display text-3xl text-ink-900">Votre occasion n'est pas dans la liste ?</h2>
            <p class="mx-auto mt-4 max-w-lg text-ink-600">
                Racontez-nous votre projet — nous créons sur mesure pour tous les
                moments qui comptent.
            </p>
            <div class="mt-8">
                <x-ui.bouton :href="route_langue('demande')">Demander une soumission</x-ui.bouton>
            </div>
        </div>
    </div>
</x-layout>
