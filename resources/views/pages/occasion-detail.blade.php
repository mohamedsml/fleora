@php
    $nom = $occasion->t('nom');
    $intro = $occasion->t('intro');
@endphp

{{--
    Page d'une occasion.

    C'est le levier SEO le plus rentable du site : quelqu'un qui cherche
    « boîte baby shower personnalisée Laval » a une intention d'achat forte et
    trouve peu de concurrence. La page doit pouvoir convertir seule, sans que
    le visiteur passe par l'accueil.
--}}
<x-layout :titre="$occasion->t('meta_title') ?: $nom"
          :description="$occasion->t('meta_description') ?: $intro"
          :modele="$occasion">

    <x-slot:schema>
        <x-schema :donnees="\App\Support\DonneesStructurees::occasion($occasion, $creations)" />
        <x-schema :donnees="\App\Support\DonneesStructurees::filAriane([
            __('commun.nav.accueil') => route_langue('accueil'),
            __('commun.nav.occasions') => route_langue('occasions'),
            $nom => route_langue('occasions.show', $occasion->slugPour()),
        ])" />
    </x-slot:schema>

    <div class="mx-auto max-w-7xl px-6 py-16 lg:py-24">

        <a href="{{ route_langue('occasions') }}"
           class="inline-flex items-center gap-2 text-sm text-ink-400 transition hover:text-ink-700">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>
            {{ __('pages.occasion.retour') }}
        </a>

        <header class="mt-10 max-w-2xl">
            <h1 class="font-display text-4xl text-ink-900 sm:text-5xl lg:text-6xl">{{ $nom }}</h1>

            @if ($intro)
                <p class="mt-6 text-lg leading-relaxed text-ink-600">{{ $intro }}</p>
            @endif

            {{-- Appel à l'action dès l'en-tête, en plus de celui du bas.
                 Sur la page mariage, huit créations séparaient l'arrivée du
                 seul bouton : une visiteuse déjà décidée devait faire défiler
                 toute la page pour agir, alors que c'est ici que l'intention
                 d'achat est la plus forte. --}}
            <div class="mt-8 flex flex-wrap items-center gap-4">
                <x-ui.bouton :href="route_langue('demande', ['occasion' => $occasion->slugPour()])">
                    {{ $occasion->t('cta') ?: __('commun.cta.soumission') }}
                </x-ui.bouton>
                <span class="text-sm text-ink-400">{{ __('pages.occasion.reassurance') }}</span>
            </div>
        </header>

        @if ($creations->isNotEmpty())
            <div class="mt-16 grid grid-cols-1 gap-x-8 gap-y-14 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($creations as $index => $creation)
                    <x-site.carte-creation :creation="$creation" :index="$index" />
                @endforeach
            </div>

            @if ($total > $creations->count())
                <div class="mt-14 text-center">
                    <a href="{{ route_langue('creations', ['occasion' => $occasion->slugPour()]) }}"
                       class="rounded-full border border-sand-300 px-8 py-3 text-sm text-ink-700 transition hover:border-ink-900 hover:bg-ivory-100">
                        {{ __('pages.occasion.voir_toutes', ['total' => $total]) }}
                    </a>
                </div>
            @endif
        @else
            <p class="mt-16 text-ink-400">
                {{ __('pages.occasion.vide') }}
            </p>
        @endif

        {{-- Contenu rédactionnel : c'est lui qui porte le référencement. Sans
             texte, la page n'a rien à indexer au-delà de son titre. --}}
        @if ($occasion->t('contenu_seo'))
            <div class="mx-auto mt-24 max-w-2xl leading-relaxed text-ink-600">
                {!! nl2br(e($occasion->t('contenu_seo'))) !!}
            </div>
        @endif

        <div class="mt-24 rounded-3xl bg-ivory-100 px-8 py-12 text-center">
            <h2 class="font-display text-3xl text-ink-900">{{ __('pages.occasion.cta_titre', ['occasion' => mb_strtolower($nom)]) }}</h2>
            <p class="mx-auto mt-4 max-w-lg text-ink-600">
                {{ __('pages.occasion.cta_texte') }}
            </p>
            <div class="mt-8">
                {{-- Libellé propre à l'occasion quand il est renseigné :
                     « Créer pour mon mariage » confirme à la visiteuse
                     qu'elle est au bon endroit, là où un bouton générique la
                     laisse douter. Repli sur le CTA commun sinon. --}}
                <x-ui.bouton :href="route_langue('demande', ['occasion' => $occasion->slugPour()])">
                    {{ $occasion->t('cta') ?: __('commun.cta.soumission') }}
                </x-ui.bouton>
            </div>
        </div>
    </div>
</x-layout>
