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
          :description="$occasion->t('meta_description') ?: $intro">

    <div class="mx-auto max-w-7xl px-6 py-16 lg:py-24">

        <a href="{{ route('occasions') }}"
           class="inline-flex items-center gap-2 text-sm text-ink-400 transition hover:text-ink-700">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>
            Toutes les occasions
        </a>

        <header class="mt-10 max-w-2xl">
            <h1 class="font-display text-4xl text-ink-900 sm:text-5xl lg:text-6xl">{{ $nom }}</h1>

            @if ($intro)
                <p class="mt-6 text-lg leading-relaxed text-ink-600">{{ $intro }}</p>
            @endif
        </header>

        @if ($creations->isNotEmpty())
            <div class="mt-16 grid grid-cols-1 gap-x-8 gap-y-14 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($creations as $index => $creation)
                    <x-site.carte-creation :creation="$creation" :index="$index" />
                @endforeach
            </div>

            @if ($total > $creations->count())
                <div class="mt-14 text-center">
                    <a href="{{ route('creations', ['occasion' => $occasion->slug_fr]) }}"
                       class="rounded-full border border-sand-300 px-8 py-3 text-sm text-ink-700 transition hover:border-ink-900 hover:bg-ivory-100">
                        Voir les {{ $total }} créations
                    </a>
                </div>
            @endif
        @else
            <p class="mt-16 text-ink-400">
                Les créations pour cette occasion arrivent bientôt.
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
            <h2 class="font-display text-3xl text-ink-900">Une création pour votre {{ mb_strtolower($nom) }}</h2>
            <p class="mx-auto mt-4 max-w-lg text-ink-600">
                Dites-nous vos couleurs, le prénom à inscrire et votre date —
                nous vous répondons sous 24 h.
            </p>
            <div class="mt-8">
                <x-ui.bouton :href="route('demande', ['occasion' => $occasion->slug_fr])">
                    Demander une soumission
                </x-ui.bouton>
            </div>
        </div>
    </div>
</x-layout>
