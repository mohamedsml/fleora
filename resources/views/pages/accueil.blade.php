@php
    use App\Models\SiteSetting;

    $region = SiteSetting::lire('region', 'Montréal, Laval et Rive-Nord');
@endphp

<x-layout
    :titre="__('accueil.meta.titre')"
    :description="__('accueil.meta.description', ['region' => $region])">

    {{-- ─── Héros ──────────────────────────────────────────────────────
         Une promesse claire, une preuve de localisation, un seul appel à
         l'action dominant. Pas de carrousel : il disperse l'attention et
         personne ne voit la deuxième diapositive. --}}
    <section class="relative overflow-hidden">
        <div class="mx-auto max-w-7xl px-6 pb-20 pt-16 lg:px-8 lg:pb-28 lg:pt-24">
            <div class="grid items-center gap-14 lg:grid-cols-2 lg:gap-20">
                <div data-reveal>
                    <p class="text-xs uppercase tracking-[0.25em] text-blush-600">
                        {{ $region }}
                    </p>

                    <h1 class="mt-6 font-display text-5xl leading-[1.05] text-ink-900 sm:text-6xl lg:text-7xl">
                        {{ __('accueil.hero.titre_1') }}<br>
                        <em class="not-italic text-blush-600">{{ __('accueil.hero.titre_2') }}</em>
                    </h1>

                    <p class="mt-7 max-w-lg text-lg leading-relaxed text-ink-600">
                        {{ __('accueil.hero.intro') }}
                    </p>

                    <div class="mt-10 flex flex-wrap gap-4">
                        <x-ui.bouton href="{{ route_langue('demande') }}">
                            {{ __('commun.cta.soumission') }}
                        </x-ui.bouton>
                        <x-ui.bouton href="{{ route_langue('creations') }}" variante="secondaire">
                            {{ __('commun.cta.creations') }}
                        </x-ui.bouton>
                    </div>

                    <p class="mt-6 text-sm text-ink-400">
                        {{ __('accueil.hero.reassurance') }}
                    </p>
                </div>

                {{-- Composition de trois images : donne un aperçu du catalogue
                     dès le premier écran, plus convaincant qu'une seule photo. --}}
                <div class="relative" data-reveal>
                    @if ($vedettes->isNotEmpty())
                        <div class="grid grid-cols-2 gap-4">
                            @foreach ($vedettes->take(3) as $index => $creation)
                                @php $image = $creation->imagePrincipale(); @endphp
                                <div @class([
                                    'overflow-hidden rounded-2xl bg-ivory-200',
                                    'col-span-2 aspect-[16/10]' => $index === 0,
                                    'aspect-square' => $index !== 0,
                                ])>
                                    @if ($image)
                                        <img src="{{ $image->urlVariante(800) }}"
                                             alt="{{ $image->t('alt') ?? $creation->t('titre') }}"
                                             {{-- La première image est au-dessus de la ligne de
                                                  flottaison : chargement prioritaire, jamais lazy. --}}
                                             @if ($index === 0) fetchpriority="high" @else loading="lazy" @endif
                                             class="h-full w-full object-cover">
                                    @else
                                        <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-blush-100 to-sage-100">
                                            <span class="font-display text-xl text-ink-400">{{ $creation->t('titre') }}</span>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="aspect-[4/3] rounded-2xl bg-gradient-to-br from-blush-100 via-ivory-200 to-sage-100"></div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- ─── Occasions ──────────────────────────────────────────────────
         Placées haut : la visiteuse arrive avec un événement précis en tête
         et cherche d'abord à savoir « est-ce que ça me concerne ». --}}
    @if ($occasions->isNotEmpty())
        <section class="border-y border-ink-800/5 bg-ivory-100 py-20 lg:py-24" data-reveal>
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="max-w-2xl">
                    <h2 class="font-display text-4xl text-ink-900 lg:text-5xl">{{ __('accueil.occasions.titre') }}</h2>
                    <p class="mt-4 text-ink-600">
                        {{ __('accueil.occasions.intro') }}
                    </p>
                </div>

                <div class="mt-12 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                    @foreach ($occasions as $occasion)
                        <a href="{{ route_langue('occasions.show', $occasion->slugPour()) }}"
                           class="group rounded-2xl border border-ink-800/10 bg-ivory-50 p-5 text-center transition duration-300 hover:-translate-y-1 hover:border-blush-300 hover:shadow-lg hover:shadow-blush-500/10">
                            <span class="font-display text-lg text-ink-800 group-hover:text-blush-700">
                                {{ $occasion->t('nom') }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ─── Créations vedettes ─────────────────────────────────────── --}}
    @if ($vedettes->isNotEmpty())
        <section class="py-20 lg:py-28" data-reveal>
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="flex flex-wrap items-end justify-between gap-6">
                    <div class="max-w-2xl">
                        <h2 class="font-display text-4xl text-ink-900 lg:text-5xl">{{ __('accueil.creations.titre') }}</h2>
                        <p class="mt-4 text-ink-600">
                            {{ __('accueil.creations.intro') }}
                        </p>
                    </div>
                    <a href="{{ route_langue('creations') }}" class="text-sm text-blush-600 underline-offset-4 hover:underline">
                        {{ __('accueil.creations.lien') }}
                    </a>
                </div>

                <div class="mt-14 grid gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($vedettes as $creation)
                        <x-site.carte-creation :creation="$creation" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ─── Fonctionnement ─────────────────────────────────────────────
         Lève l'inquiétude principale du sur-mesure : « combien de temps,
         et est-ce que je vais voir le résultat avant ? » --}}
    <section class="border-y border-ink-800/5 bg-ivory-100 py-20 lg:py-24" data-reveal>
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="max-w-2xl">
                <h2 class="font-display text-4xl text-ink-900 lg:text-5xl">{{ __('accueil.etapes.titre') }}</h2>
            </div>

            <ol class="mt-14 grid gap-10 md:grid-cols-3">
                @foreach ([1, 2, 3] as $numero)
                    <li>
                        <span class="font-display text-5xl text-blush-300">0{{ $numero }}</span>
                        <h3 class="mt-4 font-display text-2xl text-ink-900">
                            {{ __("accueil.etapes.{$numero}.titre") }}
                        </h3>
                        <p class="mt-3 leading-relaxed text-ink-600">
                            {{ __("accueil.etapes.{$numero}.texte") }}
                        </p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- ─── Témoignages ────────────────────────────────────────────── --}}
    @if ($temoignages->isNotEmpty())
        <section class="py-20 lg:py-28" data-reveal>
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <h2 class="font-display text-4xl text-ink-900 lg:text-5xl">{{ __('accueil.temoignages.titre') }}</h2>

                <div class="mt-14 grid gap-8 md:grid-cols-3">
                    @foreach ($temoignages as $temoignage)
                        <figure class="rounded-2xl border border-ink-800/10 bg-ivory-100 p-8">
                            @if ($temoignage->note)
                                <div class="flex gap-0.5" aria-label="{{ $temoignage->note }} sur 5">
                                    @for ($i = 0; $i < $temoignage->note; $i++)
                                        <svg class="h-4 w-4 fill-gold-400" viewBox="0 0 20 20" aria-hidden="true">
                                            <path d="M10 1.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8-5.2-2.7-5.2 2.7 1-5.8L1.5 7.7l5.9-.9z"/>
                                        </svg>
                                    @endfor
                                </div>
                            @endif

                            <blockquote class="mt-5 leading-relaxed text-ink-700">
                                « {{ $temoignage->t('texte') }} »
                            </blockquote>

                            <figcaption class="mt-6 text-sm text-ink-400">
                                {{ $temoignage->auteur }}@if ($temoignage->ville), {{ $temoignage->ville }}@endif
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ─── FAQ ────────────────────────────────────────────────────────
         <details> natif : accessible au clavier et fonctionnel sans
         JavaScript, là où un accordéon maison demande beaucoup de soin. --}}
    @if ($faqs->isNotEmpty())
        <section class="border-t border-ink-800/5 bg-ivory-100 py-20 lg:py-24" data-reveal>
            <div class="mx-auto max-w-3xl px-6 lg:px-8">
                <h2 class="font-display text-4xl text-ink-900 lg:text-5xl">{{ __('accueil.faq.titre') }}</h2>

                <div class="mt-12 divide-y divide-ink-800/10">
                    @foreach ($faqs as $faq)
                        <details class="group py-6">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-6 text-left">
                                <span class="font-medium text-ink-800">{{ $faq->t('question') }}</span>
                                <svg class="h-5 w-5 shrink-0 text-blush-500 transition-transform duration-300 group-open:rotate-45"
                                     fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                            </summary>
                            <p class="mt-4 leading-relaxed text-ink-600">{{ $faq->t('reponse') }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ─── Appel à l'action final ─────────────────────────────────── --}}
    <section class="py-24 lg:py-32" data-reveal>
        <div class="mx-auto max-w-3xl px-6 text-center lg:px-8">
            <h2 class="font-display text-4xl text-ink-900 lg:text-5xl">
                Parlons de votre projet
            </h2>
            <p class="mx-auto mt-5 max-w-xl text-lg leading-relaxed text-ink-600">
                Décrivez-nous votre événement : nous vous répondons sous 24 heures,
                sans engagement de votre part.
            </p>
            <div class="mt-10">
                <x-ui.bouton href="{{ route_langue('demande') }}">
                    Demander une soumission
                </x-ui.bouton>
            </div>
        </div>
    </section>
</x-layout>
