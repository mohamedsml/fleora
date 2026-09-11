@php
    $marque = config('app.name');
    $courriel = config('fleora.contact.courriel');
    $mois = config('fleora.conservation.demandes_mois');
    $maj = \Carbon\Carbon::create(2026, 9, 11);

    $lienCourriel = '<a href="mailto:'.e($courriel).'" class="text-ink-900 underline underline-offset-4 hover:text-terracotta-600">'.e($courriel).'</a>';
@endphp

<x-layout :titre="__('confidentialite.meta_titre')"
          :description="__('confidentialite.meta_description')">

    <div class="mx-auto max-w-2xl px-6 py-20 lg:py-28">

        <h1 class="font-display text-4xl text-ink-900 sm:text-5xl">
            {{ __('confidentialite.titre') }}
        </h1>

        <p class="mt-4 text-sm text-ink-400">
            {{ __('confidentialite.maj', ['date' => $maj->locale(app()->getLocale())->translatedFormat('j F Y')]) }}
        </p>

        {{-- La version française fait foi au Québec : le lecteur anglophone
             doit le savoir avant de se fier à cette traduction. --}}
        @if (app()->getLocale() !== 'fr')
            <p class="mt-6 rounded-xl bg-ivory-100 px-5 py-4 text-sm text-ink-500">
                {{ __('confidentialite.prevalence') }}
            </p>
        @endif

        {{-- Styles posés à la main plutôt que le plugin typography de Tailwind :
             une dépendance de plus pour deux pages légales ne se justifie pas. --}}
        <div class="mt-12 space-y-10 leading-relaxed text-ink-600">

            <p>{!! str_replace('**', '', __('confidentialite.intro', ['marque' => $marque])) !!}</p>

            <section>
                <h2 class="font-display text-2xl text-ink-900">{{ __('confidentialite.responsable.titre') }}</h2>
                <p class="mt-4">
                    {{ __('confidentialite.responsable.texte', ['marque' => $marque]) }}
                    {!! $lienCourriel !!}
                </p>
            </section>

            <section>
                <h2 class="font-display text-2xl text-ink-900">{{ __('confidentialite.collecte.titre') }}</h2>
                <p class="mt-4">{{ __('confidentialite.collecte.intro') }}</p>
                <ul class="mt-4 space-y-2 pl-5">
                    @foreach (['item1', 'item2', 'item3', 'item4'] as $item)
                        <li class="list-disc">{{ __("confidentialite.collecte.{$item}") }}</li>
                    @endforeach
                </ul>
                <p class="mt-4">{{ __('confidentialite.collecte.technique') }}</p>
                <p class="mt-4 text-ink-900">{{ __('confidentialite.collecte.paiement') }}</p>
            </section>

            <section>
                <h2 class="font-display text-2xl text-ink-900">{{ __('confidentialite.utilisation.titre') }}</h2>
                <p class="mt-4">{{ __('confidentialite.utilisation.intro') }}</p>
                <ul class="mt-4 space-y-2 pl-5">
                    @foreach (['item1', 'item2', 'item3'] as $item)
                        <li class="list-disc">{{ __("confidentialite.utilisation.{$item}") }}</li>
                    @endforeach
                </ul>
                <p class="mt-4 text-ink-900">{{ __('confidentialite.utilisation.jamais') }}</p>
            </section>

            <section>
                <h2 class="font-display text-2xl text-ink-900">{{ __('confidentialite.tiers.titre') }}</h2>
                <p class="mt-4">{{ __('confidentialite.tiers.texte') }}</p>
                <p class="mt-4">{{ __('confidentialite.tiers.hebergement') }}</p>
            </section>

            <section>
                <h2 class="font-display text-2xl text-ink-900">{{ __('confidentialite.conservation.titre') }}</h2>
                <p class="mt-4">{{ __('confidentialite.conservation.texte', ['mois' => $mois]) }}</p>
                <p class="mt-4">{{ __('confidentialite.conservation.comptable') }}</p>
            </section>

            <section>
                <h2 class="font-display text-2xl text-ink-900">{{ __('confidentialite.temoins.titre') }}</h2>
                <p class="mt-4">{{ __('confidentialite.temoins.texte') }}</p>
            </section>

            <section>
                <h2 class="font-display text-2xl text-ink-900">{{ __('confidentialite.droits.titre') }}</h2>
                <p class="mt-4">{{ __('confidentialite.droits.intro') }}</p>
                <ul class="mt-4 space-y-2 pl-5">
                    @foreach (['item1', 'item2', 'item3', 'item4', 'item5'] as $item)
                        <li class="list-disc">{{ __("confidentialite.droits.{$item}") }}</li>
                    @endforeach
                </ul>
                <p class="mt-4">
                    {!! __('confidentialite.droits.delai', ['courriel' => $lienCourriel]) !!}
                </p>
            </section>

            <section>
                <h2 class="font-display text-2xl text-ink-900">{{ __('confidentialite.securite.titre') }}</h2>
                <p class="mt-4">{{ __('confidentialite.securite.texte') }}</p>
            </section>

            <section>
                <h2 class="font-display text-2xl text-ink-900">{{ __('confidentialite.plainte.titre') }}</h2>
                <p class="mt-4">
                    {{ __('confidentialite.plainte.texte') }}
                    <a href="https://www.cai.gouv.qc.ca" target="_blank" rel="noopener"
                       class="text-ink-900 underline underline-offset-4 hover:text-terracotta-600">{{ __('confidentialite.plainte.lien') }}</a>.
                </p>
            </section>

            <section>
                <h2 class="font-display text-2xl text-ink-900">{{ __('confidentialite.modifications.titre') }}</h2>
                <p class="mt-4">{{ __('confidentialite.modifications.texte') }}</p>
            </section>
        </div>
    </div>
</x-layout>
