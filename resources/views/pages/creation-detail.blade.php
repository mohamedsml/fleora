@php
    $titre = $creation->t('titre');
    $description = $creation->t('description');
    $prix = $creation->fourchettePrix();
    $principale = $creation->imagePrincipale();

    // Construit hors du template : Blade ne parse pas correctement des
    // tableaux imbriqués passés directement à @json.
    $donneesStructurees = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $titre,
        'description' => $description,
        'image' => $creation->media->map(fn ($m) => $m->urlVariante(1200))->values()->all(),
        'brand' => ['@type' => 'Brand', 'name' => config('app.name')],
    ];

    if ($creation->prix_min) {
        $offre = [
            '@type' => 'AggregateOffer',
            'priceCurrency' => 'CAD',
            'lowPrice' => $creation->prix_min / 100,
            // MadeToOrder plutôt que InStock : chaque pièce est fabriquée
            // à la commande, annoncer du stock serait faux.
            'availability' => 'https://schema.org/MadeToOrder',
        ];

        if ($creation->prix_max) {
            $offre['highPrice'] = $creation->prix_max / 100;
        }

        $donneesStructurees['offers'] = $offre;
    }
@endphp

<x-layout :titre="$titre" :description="$description">

    {{-- Données structurées : permet à Google d'afficher la fourchette de prix
         et l'image dans les résultats. Product convient même sans vente en
         ligne — la page présente bien un produit identifiable. --}}
    @if ($principale)
        <script type="application/ld+json">
            {!! json_encode($donneesStructurees, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
        </script>
    @endif

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
