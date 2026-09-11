@php
    $courriel = config('fleora.contact.courriel');
    $whatsapp = config('fleora.contact.whatsapp');
    $delai = config('fleora.contact.delai_reponse_h');

    // Carte centrée sur le Grand Montréal — pas sur une adresse. L'atelier
    // n'accueille pas de public, et publier une adresse personnelle n'apporte
    // rien tout en exposant le domicile. Le cadrage montre la zone desservie,
    // ce qui est l'information réellement utile à la visiteuse.
    $carte = 'https://www.openstreetmap.org/export/embed.html'
        .'?bbox=-74.35%2C45.30%2C-73.20%2C45.85&layer=mapnik';
@endphp

<x-layout :titre="__('pages.contact.meta_titre')"
          :description="__('pages.contact.meta_description')">

    <x-slot:schema>
        <x-schema :donnees="\App\Support\DonneesStructurees::entreprise()" />
        <x-schema :donnees="\App\Support\DonneesStructurees::filAriane([
            __('commun.nav.accueil') => route_langue('accueil'),
            __('pages.contact.meta_titre') => route_langue('contact'),
        ])" />
    </x-slot:schema>

    <div class="mx-auto max-w-5xl px-6 py-20 lg:py-28">

        <header class="max-w-2xl">
            <h1 class="font-display text-4xl text-ink-900 sm:text-5xl lg:text-6xl">
                {{ __('pages.contact.titre') }}
            </h1>
            <p class="mt-6 text-lg leading-relaxed text-ink-600">
                {{ __('pages.contact.intro', ['delai' => $delai]) }}
            </p>
        </header>

        <div class="mt-16 grid gap-12 lg:grid-cols-2 lg:gap-16">

            {{-- ── Moyens de contact ───────────────────────────────────── --}}
            <div class="space-y-8">

                <div class="rounded-3xl border border-sand-200 bg-ivory-100 px-8 py-10">
                    <h2 class="font-display text-2xl text-ink-900">{{ __('pages.contact.projet_titre') }}</h2>
                    <p class="mt-3 leading-relaxed text-ink-600">
                        {{ __('pages.contact.projet_texte') }}
                    </p>
                    <div class="mt-7">
                        <x-ui.bouton :href="route_langue('demande')">
                            {{ __('commun.cta.soumission') }}
                        </x-ui.bouton>
                    </div>
                </div>

                <div>
                    <h2 class="font-display text-2xl text-ink-900">{{ __('pages.contact.courriel_titre') }}</h2>
                    <p class="mt-3 text-ink-600">
                        {{ __('pages.contact.courriel_texte') }}
                    </p>
                    <a href="mailto:{{ $courriel }}"
                       class="mt-3 inline-block text-lg text-ink-900 underline underline-offset-4 transition hover:text-terracotta-600">
                        {{ $courriel }}
                    </a>
                </div>

                {{-- Affiché seulement si un numéro est configuré : un bouton qui
                     mène à un numéro inexistant est pire que pas de bouton. --}}
                @if ($whatsapp)
                    <div>
                        <h2 class="font-display text-2xl text-ink-900">{{ __('pages.contact.whatsapp_titre') }}</h2>
                        <p class="mt-3 text-ink-600">
                            {{ __('pages.contact.whatsapp_texte') }}
                        </p>
                        <a href="https://wa.me/{{ preg_replace('/\D/', '', $whatsapp) }}"
                           target="_blank" rel="noopener"
                           class="mt-3 inline-flex items-center gap-2 text-lg text-ink-900 underline underline-offset-4 transition hover:text-terracotta-600">
                            {{ __('pages.contact.whatsapp_lien') }}
                        </a>
                    </div>
                @endif

                <div class="border-t border-sand-200 pt-8">
                    <h2 class="font-display text-2xl text-ink-900">{{ __('pages.contact.zone_titre') }}</h2>
                    <p class="mt-3 leading-relaxed text-ink-600">
                        {{ __('pages.contact.zone_texte') }}
                    </p>
                    <p class="mt-4 text-sm text-ink-400">
                        {{ __('pages.contact.zone_note') }}
                    </p>
                </div>
            </div>

            {{-- ── Carte ───────────────────────────────────────────────── --}}
            <div>
                <div class="overflow-hidden rounded-3xl border border-sand-200">
                    {{-- OpenStreetMap plutôt que Google Maps : aucun témoin déposé,
                         donc rien à déclarer dans la politique de confidentialité
                         ni à soumettre à un bandeau de consentement. --}}
                    <iframe
                        src="{{ $carte }}"
                        title="{{ __('pages.contact.carte_titre') }}"
                        class="h-[420px] w-full"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>

                <p class="mt-4 text-center text-sm text-ink-400">
                    {{ __('pages.contact.carte_legende') }}
                </p>
            </div>
        </div>

        <div class="mt-24 rounded-3xl bg-ivory-100 px-8 py-12 text-center">
            <h2 class="font-display text-2xl text-ink-900">{{ __('pages.contact.inspiration_titre') }}</h2>
            <p class="mx-auto mt-3 max-w-lg text-ink-600">
                {{ __('pages.contact.inspiration_texte') }}
            </p>
            <div class="mt-8">
                <x-ui.bouton :href="route_langue('creations')" variante="secondaire">
                    {{ __('commun.cta.creations') }}
                </x-ui.bouton>
            </div>
        </div>
    </div>
</x-layout>
