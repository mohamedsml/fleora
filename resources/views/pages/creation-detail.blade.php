@php
    $titre = $creation->t('titre');
    $description = $creation->t('description');
    $prix = $creation->fourchettePrix();
    $principale = $creation->imagePrincipale();

@endphp

<x-layout :titre="$titre" :description="$description" :modele="$creation">

    {{-- Product convient même sans vente en ligne : la page présente bien un
         produit identifiable, et Google affiche alors la fourchette de prix. --}}
    {{-- Balisage présent même sans photo : une fiche sans image reste un
         produit identifiable, et l'absence d'image n'est pas une raison de
         renoncer au prix affiché dans les résultats de recherche. --}}
    <x-slot:schema>
        <x-schema :donnees="\App\Support\DonneesStructurees::creation($creation)" />
        <x-schema :donnees="\App\Support\DonneesStructurees::filAriane([
            __('commun.nav.accueil') => route_langue('accueil'),
            __('commun.nav.creations') => route_langue('creations'),
            $titre => route_langue('creations.show', $creation->slugPour()),
        ])" />
    </x-slot:schema>



    <div class="mx-auto max-w-7xl px-6 py-12 lg:py-20">

        <a href="{{ route_langue('creations') }}"
           class="inline-flex items-center gap-2 text-sm text-ink-400 transition hover:text-ink-700">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>
            {{ __('galerie.retour') }}
        </a>

        <div class="mt-10 grid gap-12 lg:grid-cols-2 lg:gap-20">

            {{-- ── Photos ──────────────────────────────────────────────── --}}
            <div x-data="{ active: 0 }">
                @if ($creation->media->isNotEmpty())
                    <div class="overflow-hidden rounded-3xl bg-ivory-200">
                        @foreach ($creation->media as $i => $photo)
                            <img x-show="active === {{ $i }}"
                                 x-cloak="{{ $i > 0 ?: null }}"
                                 src="{{ $photo->urlVariante(1200) }}"
                                 @if ($srcset = $photo->srcset()) srcset="{{ $srcset }}" @endif
                                 sizes="(min-width: 1024px) 50vw, 92vw"
                                 alt="{{ $photo->t('alt') ?? $titre }}"
                                 {{-- La première image est le LCP de cette page. --}}
                                 loading="{{ $i === 0 ? 'eager' : 'lazy' }}"
                                 fetchpriority="{{ $i === 0 ? 'high' : 'auto' }}"
                                 width="{{ $photo->largeur }}"
                                 height="{{ $photo->hauteur }}"
                                 class="aspect-[4/5] w-full object-cover">
                        @endforeach
                    </div>

                    @if ($creation->media->count() > 1)
                        <div class="mt-4 grid grid-cols-5 gap-3">
                            @foreach ($creation->media as $i => $photo)
                                <button type="button" @click="active = {{ $i }}"
                                        :class="active === {{ $i }} ? 'ring-2 ring-terracotta-400' : 'opacity-60 hover:opacity-100'"
                                        class="aspect-square overflow-hidden rounded-lg transition">
                                    <img src="{{ $photo->urlVariante(400) }}"
                                         alt="" loading="lazy"
                                         class="h-full w-full object-cover">
                                </button>
                            @endforeach
                        </div>
                    @endif
                @else
                    <div class="flex aspect-[4/5] items-center justify-center rounded-3xl bg-gradient-to-br from-blush-100 to-sage-100">
                        <span class="font-display text-3xl text-ink-400">{{ config('app.name') }}</span>
                    </div>
                @endif
            </div>

            {{-- ── Informations ────────────────────────────────────────── --}}
            <div class="lg:pt-6">
                <h1 class="font-display text-4xl text-ink-900 sm:text-5xl">{{ $titre }}</h1>

                @if ($prix)
                    <p class="mt-4 text-lg text-ink-600">{{ $prix }}</p>
                @endif

                @if ($description)
                    <p class="mt-8 leading-relaxed text-ink-600">{{ $description }}</p>
                @endif

                <dl class="mt-10 space-y-5 border-t border-sand-200 pt-8">
                    @if ($creation->occasions->isNotEmpty())
                        <div>
                            <dt class="text-xs uppercase tracking-widest text-ink-400">{{ __('galerie.occasions') }}</dt>
                            <dd class="mt-2 flex flex-wrap gap-2">
                                @foreach ($creation->occasions as $o)
                                    {{-- Vers la galerie filtrée : maillage interne
                                         utile au SEO, et parcours naturel pour qui
                                         prépare cet événement précis. --}}
                                    <a href="{{ route_langue('creations', ['occasion' => $o->slugPour()]) }}"
                                       class="rounded-full bg-ivory-100 px-3 py-1 text-sm text-ink-600 transition hover:bg-sand-100">
                                        {{ $o->t('nom') }}
                                    </a>
                                @endforeach
                            </dd>
                        </div>
                    @endif

                    @if ($creation->productType)
                        <div>
                            <dt class="text-xs uppercase tracking-widest text-ink-400">{{ __('galerie.type') }}</dt>
                            <dd class="mt-2 text-ink-600">{{ $creation->productType->t('nom') }}</dd>
                        </div>
                    @endif

                    @if (filled($creation->couleurs))
                        <div>
                            <dt class="text-xs uppercase tracking-widest text-ink-400">{{ __('galerie.couleurs') }}</dt>
                            <dd class="mt-2 text-ink-600">{{ implode(' · ', $creation->couleurs) }}</dd>
                        </div>
                    @endif
                </dl>

                {{-- Le CTA qui transforme une fiche en demande. Le slug part en
                     paramètre : le formulaire s'ouvrira pré-rempli, et on saura
                     quelles créations génèrent des demandes. --}}
                <div class="mt-12">
                    <x-ui.bouton :href="route_langue('demande', ['creation' => $creation->slugPour()])">
                        {{ __('galerie.cta_detail') }}
                    </x-ui.bouton>
                    <p class="mt-3 text-sm text-ink-400">{{ __('galerie.cta_detail_aide') }}</p>
                </div>
            </div>
        </div>

        {{-- ── Créations similaires ────────────────────────────────────── --}}
        @if ($similaires->isNotEmpty())
            <section class="mt-28 border-t border-sand-200 pt-16">
                <h2 class="font-display text-3xl text-ink-900">{{ __('galerie.similaires') }}</h2>

                <div class="mt-10 grid grid-cols-1 gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($similaires as $autre)
                        <x-site.carte-creation :creation="$autre" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-layout>
