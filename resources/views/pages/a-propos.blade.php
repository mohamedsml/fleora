@php
    $marque = config('app.name');
@endphp

<x-layout :titre="__('pages.a_propos.meta_titre')"
          :description="__('pages.a_propos.meta_description', ['marque' => $marque])">

    <div class="mx-auto max-w-3xl px-6 py-20 lg:py-28">

        <header>
            <h1 class="font-display text-4xl text-ink-900 sm:text-5xl lg:text-6xl">
                {{ __('pages.a_propos.titre', ['marque' => $marque]) }}
            </h1>
        </header>

        {{-- space-y-10 plutôt que 8 : avec un corps de 18 px et une interligne
             à 1.7, 32 px ne séparent pas assez deux paragraphes — le texte
             paraît compact. --}}
        <div class="mt-14 space-y-10 text-lg leading-relaxed text-ink-600">

            <p class="!mb-14 text-xl leading-relaxed text-ink-700">
                {{ __('pages.a_propos.bienvenue', ['marque' => $marque]) }}
            </p>

            @foreach (['p1', 'p2', 'p3', 'p4', 'p5'] as $paragraphe)
                <p>{{ __("pages.a_propos.{$paragraphe}", ['marque' => $marque]) }}</p>
            @endforeach

            {{-- La signature de marque : mise en valeur plutôt que noyée dans le
                 flux, c'est la phrase que le visiteur doit retenir. --}}
            <p class="!mt-16 border-l-2 border-blush-300 pl-6 font-display text-2xl leading-snug text-ink-900">
                {{ __('pages.a_propos.signature') }}
            </p>

            <div class="!mt-16 border-t border-sand-200 pt-10 text-center">
                <p class="font-display text-2xl text-ink-900">
                    {{ __('pages.a_propos.marque', ['marque' => $marque]) }}
                </p>
                <p class="mt-2 text-ink-500">
                    {{ __('pages.a_propos.slogan') }}
                </p>
            </div>
        </div>

        <div class="mt-20 rounded-3xl bg-ivory-100 px-8 py-12 text-center">
            <h2 class="font-display text-2xl text-ink-900">
                {{ __('pages.a_propos.cta_titre') }}
            </h2>
            <p class="mx-auto mt-3 max-w-lg text-ink-600">
                {{ __('pages.a_propos.cta_texte') }}
            </p>
            <div class="mt-8 flex flex-wrap justify-center gap-4">
                <x-ui.bouton :href="route_langue('demande')">
                    {{ __('commun.cta.soumission') }}
                </x-ui.bouton>
                <x-ui.bouton :href="route_langue('creations')" variante="secondaire">
                    {{ __('commun.cta.creations') }}
                </x-ui.bouton>
            </div>
        </div>
    </div>
</x-layout>
