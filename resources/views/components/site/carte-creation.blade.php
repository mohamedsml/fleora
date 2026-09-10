@props([
    'creation',
    'index' => 0,
    // Active l'ouverture en lightbox (desktop). Le composant reste utilisable
    // sans, par exemple sur l'accueil où un clic mène directement à la fiche.
    'lightbox' => false,
])

@php
    $image = $creation->imagePrincipale();
    $prix = $creation->fourchettePrix();
    $lien = route('creations.show', $creation->slug_fr);

    // Toutes les photos de la création, pour la navigation dans la lightbox.
    $photos = $creation->media->map(fn ($m) => [
        'image' => $m->urlVariante(1200),
        'titre' => $creation->t('titre'),
        'soustitre' => $creation->occasions->map(fn ($o) => $o->t('nom'))->join(' · '),
        'lien' => route('demande', ['creation' => $creation->slug_fr]),
    ])->values();

    // Les 3 premières images portent le LCP : pas de lazy-loading dessus.
    $prioritaire = $index < 3;
@endphp

<article class="group">
    {{--
        Un vrai <a> même quand la lightbox est active : le clic droit, le
        ctrl-clic et les robots d'indexation ont besoin d'une URL réelle.
        Alpine intercepte le clic simple sur desktop uniquement — si ouvrir()
        renvoie false (mobile, ou aucune photo), la navigation suit son cours.
    --}}
    <a href="{{ $lien }}"
       @if ($lightbox && $photos->isNotEmpty())
           {{-- ouvrir() renvoie false sous 1024 px : on laisse alors le
                navigateur suivre le lien vers la fiche. --}}
           @click.prevent="ouvrir({{ Js::from($photos) }}) || window.location.assign($el.href)"
       @endif
       class="block focus:outline-none focus-visible:ring-2 focus-visible:ring-terracotta-400 focus-visible:ring-offset-4">

        <div class="relative aspect-[4/5] overflow-hidden rounded-2xl bg-ivory-200">
            @if ($image)
                <img src="{{ $image->urlVariante(800) }}"
                     @if ($srcset = $image->srcset()) srcset="{{ $srcset }}" @endif
                     sizes="(min-width: 1024px) 30vw, (min-width: 640px) 45vw, 90vw"
                     alt="{{ $image->t('alt') ?? $creation->t('titre') }}"
                     {{-- lazy + dimensions : évite que la page saute pendant le
                          chargement des images, ce que Google pénalise (CLS). --}}
                     loading="{{ $prioritaire ? 'eager' : 'lazy' }}"
                     fetchpriority="{{ $prioritaire ? 'high' : 'auto' }}"
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

            @if ($creation->media->count() > 1)
                <span class="absolute bottom-3 right-3 rounded-full bg-ink-900/60 px-2.5 py-1 text-xs text-ivory-50 backdrop-blur-sm">
                    {{ $creation->media->count() }}
                </span>
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
    </a>
</article>
