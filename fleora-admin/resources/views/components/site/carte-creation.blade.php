@props(['creation'])

@php
    $image = $creation->imagePrincipale();
    $prix = $creation->fourchettePrix();
@endphp

<article class="group">
    <div class="relative aspect-[4/5] overflow-hidden rounded-2xl bg-ivory-200">
        @if ($image)
            <img src="{{ $image->urlVariante(800) }}"
                 @if ($srcset = $image->srcset()) srcset="{{ $srcset }}" @endif
                 sizes="(min-width: 1024px) 30vw, (min-width: 640px) 45vw, 90vw"
                 alt="{{ $image->t('alt') ?? $creation->t('titre') }}"
                 {{-- lazy + dimensions : évite que la page saute pendant le
                      chargement des images, ce que Google pénalise (CLS). --}}
                 loading="lazy"
                 decoding="async"
                 width="{{ $image->largeur }}"
                 height="{{ $image->hauteur }}"
                 class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105">
        @else
            {{-- Aucune photo : un aplat discret plutôt qu'une icône de fichier
                 cassé, pour que la galerie reste présentable avant que les
                 vraies photos soient téléversées. --}}
            <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-blush-100 to-sage-100">
                <span class="font-display text-2xl text-ink-400">{{ config('app.name') }}</span>
            </div>
        @endif
    </div>

    <div class="mt-5">
        <h3 class="font-display text-2xl text-ink-900">{{ $creation->t('titre') }}</h3>

        @if ($creation->occasions->isNotEmpty())
            <p class="mt-1.5 text-xs uppercase tracking-widest text-ink-400">
                {{ $creation->occasions->map(fn ($o) => $o->t('nom'))->join(' · ') }}
            </p>
        @endif

        @if ($prix)
            <p class="mt-3 text-sm text-ink-600">{{ $prix }}</p>
        @endif
    </div>
</article>
