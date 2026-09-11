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

<x-layout titre="Contact"
          description="Écrivez-nous pour votre projet de boîte personnalisée. Réponse sous 24 h. Livraison dans le Grand Montréal, Laval et la Rive-Nord.">

    <div class="mx-auto max-w-5xl px-6 py-20 lg:py-28">

        <header class="max-w-2xl">
            <h1 class="font-display text-4xl text-ink-900 sm:text-5xl lg:text-6xl">
                Parlons de votre projet
            </h1>
            <p class="mt-6 text-lg leading-relaxed text-ink-600">
                Une question, une idée encore floue, une date qui approche ?
                Écrivez-nous — nous répondons à chaque message sous {{ $delai }} heures.
            </p>
        </header>

        <div class="mt-16 grid gap-12 lg:grid-cols-2 lg:gap-16">

            {{-- ── Moyens de contact ───────────────────────────────────── --}}
            <div class="space-y-8">

                <div class="rounded-3xl border border-sand-200 bg-ivory-100 px-8 py-10">
                    <h2 class="font-display text-2xl text-ink-900">Pour un projet précis</h2>
                    <p class="mt-3 leading-relaxed text-ink-600">
                        Le formulaire prend trois minutes et nous donne tout ce qu'il
                        faut pour vous répondre avec une proposition chiffrée plutôt
                        qu'une question de plus.
                    </p>
                    <div class="mt-7">
                        <x-ui.bouton :href="route('demande')">
                            Demander une soumission
                        </x-ui.bouton>
                    </div>
                </div>

                <div>
                    <h2 class="font-display text-2xl text-ink-900">Par courriel</h2>
                    <p class="mt-3 text-ink-600">
                        Pour toute autre question.
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
                        <h2 class="font-display text-2xl text-ink-900">Par WhatsApp</h2>
                        <p class="mt-3 text-ink-600">
                            Pour un échange rapide, ou pour nous envoyer une photo
                            d'inspiration.
                        </p>
                        <a href="https://wa.me/{{ preg_replace('/\D/', '', $whatsapp) }}"
                           target="_blank" rel="noopener"
                           class="mt-3 inline-flex items-center gap-2 text-lg text-ink-900 underline underline-offset-4 transition hover:text-terracotta-600">
                            Nous écrire sur WhatsApp
                        </a>
                    </div>
                @endif

                <div class="border-t border-sand-200 pt-8">
                    <h2 class="font-display text-2xl text-ink-900">Zone desservie</h2>
                    <p class="mt-3 leading-relaxed text-ink-600">
                        Nous livrons dans tout le Grand Montréal : Montréal, Laval,
                        la Rive-Nord et la Rive-Sud. La cueillette est possible sur
                        rendez-vous.
                    </p>
                    <p class="mt-4 text-sm text-ink-400">
                        Notre atelier n'accueille pas de public — chaque création est
                        préparée sur commande.
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
                        title="Zone desservie : le Grand Montréal"
                        class="h-[420px] w-full"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>

                <p class="mt-4 text-center text-sm text-ink-400">
                    Livraison dans le Grand Montréal
                </p>
            </div>
        </div>

        <div class="mt-24 rounded-3xl bg-ivory-100 px-8 py-12 text-center">
            <h2 class="font-display text-2xl text-ink-900">Vous cherchez l'inspiration ?</h2>
            <p class="mx-auto mt-3 max-w-lg text-ink-600">
                Nos créations donnent une bonne idée de ce qui est possible — et
                chacune peut être adaptée à vos couleurs.
            </p>
            <div class="mt-8">
                <x-ui.bouton :href="route('creations')" variante="secondaire">
                    Voir les créations
                </x-ui.bouton>
            </div>
        </div>
    </div>
</x-layout>
