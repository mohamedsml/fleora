@props(['compact' => false])

@php
    $traductions = app(\App\Support\Traductions::class);
    $courante = app()->getLocale();
    $locales = config('app.locales', ['fr']);
@endphp

{{--
    Sélecteur de langue.

    De vrais liens `<a href>` plutôt qu'un menu en JavaScript : explorables par
    Google, fonctionnels sans JS, et ouvrables dans un nouvel onglet.

    Chaque lien mène à la PAGE ÉQUIVALENTE, jamais à l'accueil — le service
    Traductions calcule l'URL en tenant compte des slugs traduits et des filtres
    de galerie. Un sélecteur qui ramène à l'accueil est l'erreur la plus
    frustrante de ce type de composant.
--}}
<div {{ $attributes->merge(['class' => 'flex items-center gap-1']) }}
     role="group"
     aria-label="{{ __('commun.langue.changer') }}">

    @foreach ($locales as $index => $langue)
        @if ($index > 0)
            <span aria-hidden="true" class="text-sand-300">·</span>
        @endif

        @if ($langue === $courante)
            {{-- La langue active n'est pas un lien : cliquer dessus ne ferait
                 rien, et un lecteur d'écran annoncerait une destination
                 identique à la page courante. --}}
            <span class="px-1.5 py-1 text-xs font-medium tracking-wide text-ink-900"
                  aria-current="true">
                {{ __("commun.langue.{$langue}_court") }}
            </span>
        @else
            <a href="{{ $traductions->urlPour($langue) }}"
               hreflang="{{ $traductions->codeRegional($langue) }}"
               lang="{{ $langue }}"
               class="px-1.5 py-1 text-xs tracking-wide text-ink-400 transition-colors hover:text-blush-600">
                <span class="sr-only">{{ __("commun.langue.{$langue}") }}</span>
                <span aria-hidden="true">{{ __("commun.langue.{$langue}_court") }}</span>
            </a>
        @endif
    @endforeach
</div>
