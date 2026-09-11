@props([
    'titre',
    'intro' => null,
])

{{--
    Page annoncée dans la navigation mais dont le contenu reste à écrire.

    Mieux qu'une 404 : le visiteur sait que la page existe et qu'elle arrive,
    et il repart avec une action possible plutôt qu'une impasse. Les liens de
    l'en-tête et du pied de page restent donc valides pendant la rédaction.

    ⚠️ `noindex` : une page vide indexée par Google est un signal de qualité
    négatif pour tout le domaine. Retirer la directive en même temps que le
    contenu réel.
--}}
<x-layout :titre="$titre" :description="$intro" noindex>

    <div class="mx-auto max-w-2xl px-6 py-24 text-center lg:py-32">

        <h1 class="font-display text-4xl text-ink-900 sm:text-5xl">{{ $titre }}</h1>

        @if ($intro)
            <p class="mx-auto mt-6 max-w-lg text-lg leading-relaxed text-ink-600">
                {{ $intro }}
            </p>
        @endif

        <div class="mt-12 rounded-3xl border border-sand-200 bg-ivory-100 px-8 py-10">
            <p class="text-ink-600">
                Cette page est en cours de préparation. En attendant, nos créations
                sont déjà visibles — et nous répondons à toute question par courriel.
            </p>

            <div class="mt-8 flex flex-wrap justify-center gap-4">
                <x-ui.bouton :href="route('creations')">Voir les créations</x-ui.bouton>
                <x-ui.bouton :href="route('demande')" variante="secondaire">
                    Demander une soumission
                </x-ui.bouton>
            </div>
        </div>

        <p class="mt-10 text-sm text-ink-400">
            Une question ?
            <a href="mailto:{{ config('fleora.contact.courriel') }}"
               class="text-ink-700 underline underline-offset-4 hover:text-terracotta-600">
                {{ config('fleora.contact.courriel') }}
            </a>
        </p>
    </div>
</x-layout>
